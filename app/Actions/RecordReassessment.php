<?php

namespace App\Actions;

use App\Enums\AiSystemCategory;
use App\Enums\CauseStatus;
use App\Enums\ChangeOrigin;
use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\ReassessmentOutcome;
use App\Enums\VerificationStatus;
use App\Models\Link;
use App\Models\Owner;
use App\Models\Reassessment;
use App\Models\StatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Record the reassessment of a link (0020): the single path that concludes a
 * reversal. In one transaction, with the link locked, it writes the
 * reassessment and applies its outcome, every status and verification
 * change going through RecordStatusChange (0007):
 *
 * - maintain: verifies the link in the same act, on evidence stored after
 *   the reversal;
 * - adjust: changes the owner, estimated cost, lifecycle phase and progress
 *   status, records before and after, and may verify in the same act;
 * - replace and close: cancel the link, with the justification as reason.
 *
 * A system in the unacceptable tier only allows adjusting (to plan the
 * discontinuation) and closing (0020, item 8).
 */
class RecordReassessment
{
    /** The link fields an adjustment may change, besides the progress status. */
    public const ADJUSTABLE = ['owner_id', 'estimated_cost', 'lifecycle_phase'];

    public function __construct(
        protected RecordStatusChange $recordStatusChange,
    ) {}

    /**
     * @param  array<string, mixed>  $data  The outcome, owner_id and justification;
     *                                      the cause_status, cause and cause_phase
     *                                      when the cause applies; for an
     *                                      adjustment, the changes and whether to
     *                                      verify. Values may be text or enum cases.
     *
     * @throws ValidationException When a precondition fails, with a readable
     *                             message on the field at fault.
     */
    public function handle(Link $link, array $data): Reassessment
    {
        $reassessment = DB::transaction(function () use ($link, $data): Reassessment {
            $locked = Link::query()->with(['risk.aiSystem', 'lastReversal.reassessment'])->lockForUpdate()->findOrFail($link->id);

            $reversal = $this->pendingReversal($locked);
            $outcome = ReassessmentOutcome::from($this->raw($data['outcome'] ?? ''));
            $owner = Owner::query()->find((int) $this->raw($data['owner_id'] ?? 0));
            $justification = trim($this->raw($data['justification'] ?? ''));

            $this->fail(array_filter([
                'owner_id' => $owner?->isActive() ? null : $this->message('Choose an active owner.'),
                'justification' => $justification === '' ? $this->message('Say why the reassessment concluded this.') : null,
                'outcome' => $this->outcomeProblems($locked)[$outcome->value],
            ]));

            /** @var Owner $owner */
            $reassessment = $locked->reassessments()->create([
                'reversal_id' => $reversal->id,
                'outcome' => $outcome,
                'owner_id' => $owner->id,
                'justification' => $justification,
                ...$this->cause($reversal, $data),
                'reassessment_date' => today(),
            ]);

            match ($outcome) {
                ReassessmentOutcome::Maintain => $this->verify($reassessment, $locked, $owner),
                ReassessmentOutcome::Adjust => $this->adjust($reassessment, $locked, $owner, $data),
                ReassessmentOutcome::Replace, ReassessmentOutcome::Close => $this->recordStatusChange->handle($locked, LinkStatus::Cancelled, [
                    'change_date' => today(),
                    'owner_id' => $owner->id,
                    // The status trail keeps a short reason; the reassessment
                    // keeps the whole justification.
                    'trigger_reason' => Str::limit($justification, 250),
                ]),
            };

            return $reassessment;
        });

        $link->refresh();

        return $reassessment->refresh();
    }

    /**
     * The reversal awaiting reassessment, the preconditions of the link
     * itself: still in the chain, declared, with its last reversal not yet
     * reassessed (0020, item 4).
     *
     * @throws ValidationException
     */
    public function pendingReversal(Link $link): StatusHistory
    {
        $problem = match (true) {
            $link->status === LinkStatus::Cancelled => $this->message('A cancelled link cannot be reassessed.'),
            $link->verification_status !== VerificationStatus::Declared => $this->message('A verified link has nothing to reassess.'),
            $link->lastReversal === null => $this->message('This link was never reverted, so there is nothing to reassess.'),
            $link->lastReversal->reassessment !== null => $this->message('The last reversal of this link was already reassessed.'),
            default => null,
        };

        $this->fail(array_filter(['reassessment' => $problem]));

        /** @var StatusHistory */
        return $link->lastReversal;
    }

    /**
     * Why each outcome is not available for the link now; null when it is.
     * The screen shows the reason next to a disabled outcome.
     *
     * @return array<string, string|null>
     */
    public function outcomeProblems(Link $link): array
    {
        $unacceptable = $link->risk->aiSystem->category === AiSystemCategory::Unacceptable;
        $onlyDiscontinuation = $this->message('A system in the unacceptable tier only allows adjusting, to plan the discontinuation, or closing.');

        return [
            ReassessmentOutcome::Maintain->value => $unacceptable
                ? $onlyDiscontinuation
                : $this->recordStatusChange->verificationProblem($link),
            ReassessmentOutcome::Adjust->value => null,
            ReassessmentOutcome::Replace->value => $unacceptable ? $onlyDiscontinuation : null,
            ReassessmentOutcome::Close->value => null,
        ];
    }

    /**
     * Whether the reversal calls for a cause analysis: an adverse event or a
     * manual reversal does; a review due, a reclassification or a system
     * change (0021, item 5) does not.
     */
    public function causeApplies(StatusHistory $reversal): bool
    {
        return in_array($reversal->origin, [ChangeOrigin::AdverseEvent, ChangeOrigin::Manual], true);
    }

    /**
     * The cause analysis (0020, item 6): not applicable for a review due, a
     * reclassification or a system change, whatever was sent; otherwise identified, with the
     * cause and its phase, or explicitly not identified.
     *
     * @param  array<string, mixed>  $data
     * @return array{cause_status: CauseStatus, cause: string|null, cause_phase: LifecyclePhase|null}
     *
     * @throws ValidationException
     */
    protected function cause(StatusHistory $reversal, array $data): array
    {
        if (! $this->causeApplies($reversal)) {
            return ['cause_status' => CauseStatus::NotApplicable, 'cause' => null, 'cause_phase' => null];
        }

        $status = CauseStatus::tryFrom($this->raw($data['cause_status'] ?? ''));

        if ($status === null || $status === CauseStatus::NotApplicable) {
            $this->fail(['cause_status' => $this->message('Say whether the cause was identified.')]);
        }

        if ($status === CauseStatus::NotIdentified) {
            return ['cause_status' => $status, 'cause' => null, 'cause_phase' => null];
        }

        $cause = trim($this->raw($data['cause'] ?? ''));
        $phase = LifecyclePhase::tryFrom($this->raw($data['cause_phase'] ?? ''));

        $this->fail(array_filter([
            'cause' => $cause === '' ? $this->message('Describe the cause identified.') : null,
            'cause_phase' => $phase === null ? $this->message('Choose the lifecycle phase the cause came from.') : null,
        ]));

        return ['cause_status' => CauseStatus::Identified, 'cause' => $cause, 'cause_phase' => $phase];
    }

    /**
     * Maintain, or adjust and verify: the verification goes through
     * RecordStatusChange, and the reassessment points at it.
     */
    protected function verify(Reassessment $reassessment, Link $link, Owner $owner): void
    {
        $entry = $this->recordStatusChange->verify($link, $owner);

        $reassessment->update(['verification_id' => $entry->id]);
    }

    /**
     * Apply an adjustment and record what changed.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    protected function adjust(Reassessment $reassessment, Link $link, Owner $reassessor, array $data): void
    {
        /** @var array<string, mixed> $wanted */
        $wanted = $data['changes'] ?? [];
        $changes = [];
        $updates = [];

        foreach (self::ADJUSTABLE as $field) {
            $value = $wanted[$field] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $before = $this->scalar($link->getAttribute($field));
            $after = $this->scalar($this->cast($field, $value));

            if ($before !== $after) {
                $updates[$field] = $after;
                $changes[] = ['field' => $field, 'before' => $before, 'after' => $after];
            }
        }

        if (isset($updates['owner_id']) && ! Owner::query()->whereKey($updates['owner_id'])->active()->exists()) {
            $this->fail(['changes.owner_id' => $this->message('Choose an active owner.')]);
        }

        if ($updates !== []) {
            $link->update($updates);
        }

        $status = LinkStatus::tryFrom($this->raw($wanted['status'] ?? ''));

        if ($status !== null && $status !== $link->status) {
            // Cancelling is what closing and replacing are for.
            if ($status === LinkStatus::Cancelled) {
                $this->fail(['changes.status' => $this->message('To cancel the link, choose to close or replace it.')]);
            }

            if (! $link->status->canTransitionTo($status)) {
                $this->fail(['changes.status' => $this->message('A :from link cannot move to :to.', [
                    'from' => $link->status->label(),
                    'to' => $status->label(),
                ])]);
            }

            $changes[] = ['field' => 'status', 'before' => $link->status->value, 'after' => $status->value];

            $this->recordStatusChange->handle($link, $status, [
                'change_date' => today(),
                'owner_id' => $reassessor->id,
                'trigger_reason' => Str::limit($reassessment->justification, 250),
            ]);
        }

        $reassessment->update(['changes' => $changes]);

        if (($data['verify'] ?? false) === true) {
            $this->verify($reassessment, $link, $reassessor);
        }
    }

    protected function cast(string $field, mixed $value): mixed
    {
        $raw = $this->raw($value);

        return match ($field) {
            'owner_id' => (int) $raw,
            'estimated_cost' => CostLevel::from($raw),
            'lifecycle_phase' => LifecyclePhase::from($raw),
            default => $raw,
        };
    }

    /**
     * A field value as the form sends it, whether it came as text or as an
     * enum case.
     */
    protected function raw(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }

    protected function scalar(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof \BackedEnum => (string) $value->value,
            default => (string) $value,
        };
    }

    /**
     * A translated message, always a string.
     *
     * @param  array<string, string>  $replace
     */
    protected function message(string $key, array $replace = []): string
    {
        $message = __($key, $replace);

        return is_string($message) ? $message : $key;
    }

    /**
     * @param  array<string, string>  $problems
     *
     * @throws ValidationException
     */
    protected function fail(array $problems): void
    {
        if ($problems !== []) {
            throw ValidationException::withMessages($problems);
        }
    }
}
