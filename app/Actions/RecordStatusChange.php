<?php

namespace App\Actions;

use App\Enums\ChangeOrigin;
use App\Enums\LinkStatus;
use App\Enums\VerificationStatus;
use App\Models\AdverseEvent;
use App\Models\Link;
use App\Models\Owner;
use App\Models\StatusHistory;
use App\Support\MonitoringProtocol;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * The only way a link's status trail is written (UC007, RF09, 0007).
 *
 * The trail is append only: entries are never edited or deleted, which is
 * what lets links be permanent. Every entry is written here, so the previous
 * value always comes from the link itself and never from a request. A link
 * has two dimensions (0013), progress and verification, and each entry
 * changes exactly one of them. Each change locks the link and writes the
 * entry and the link in one transaction.
 */
class RecordStatusChange
{
    public function __construct(
        protected MonitoringProtocol $protocol,
    ) {}

    /**
     * Open the trail of a link that was just created: the entry records the
     * status the link was born with and has no previous status.
     */
    public function open(Link $link): StatusHistory
    {
        return $link->statusHistories()->create([
            'previous_status' => null,
            'new_status' => $link->status,
            'origin' => ChangeOrigin::Manual,
            'change_date' => $link->creation_date,
            'owner_id' => $link->owner_id,
        ]);
    }

    /**
     * Move a link to a new progress status and record the change. The
     * verification is left as it is: cancelling a verified link only takes it
     * out of monitoring (0018).
     *
     * @param  array<string, mixed>  $attributes  The change date, owner, and optional
     *                                            reason and adverse event.
     *
     * @throws InvalidArgumentException When the link cannot move to that status.
     * @throws ValidationException When no owner records the change.
     */
    public function handle(Link $link, LinkStatus $newStatus, array $attributes): StatusHistory
    {
        return $this->locked($link, function (Link $locked) use ($newStatus, $attributes): StatusHistory {
            if (! $locked->status->canTransitionTo($newStatus)) {
                throw new InvalidArgumentException(
                    "A {$locked->status->value} link cannot move to {$newStatus->value}.",
                );
            }

            if (($attributes['owner_id'] ?? null) === null) {
                throw ValidationException::withMessages(['owner_id' => __('A manual change needs an owner.')]);
            }

            $entry = $locked->statusHistories()->create([
                ...Arr::only($attributes, ['change_date', 'owner_id', 'trigger_reason', 'adverse_event_id']),
                'previous_status' => $locked->status,
                'new_status' => $newStatus,
                'origin' => ChangeOrigin::Manual,
            ]);

            $locked->update(['status' => $newStatus]);

            return $entry;
        });
    }

    /**
     * Verify a link on its evidence (RF03, 0018). A declared link becomes
     * verified; a verified one is renewed before its review falls due
     * (0019, item 2), with evidence stored since its last verification.
     * Either way the review clock starts again today, with the interval of
     * the system's current tier.
     *
     * @throws ValidationException When the link cannot be verified now, or
     *                             the verifier is not an active owner.
     */
    public function verify(Link $link, Owner $verifier): StatusHistory
    {
        return $this->locked($link, function (Link $locked) use ($verifier): StatusHistory {
            $problem = $this->verificationProblem($locked);

            if ($problem !== null) {
                throw ValidationException::withMessages(['verification' => $problem]);
            }

            if (! $verifier->isActive()) {
                throw ValidationException::withMessages(['owner_id' => __('Choose an active owner.')]);
            }

            $today = today();

            $entry = $locked->statusHistories()->create([
                'previous_verification' => $locked->verification_status,
                'new_verification' => VerificationStatus::Verified,
                'origin' => ChangeOrigin::Manual,
                'change_date' => $today,
                'owner_id' => $verifier->id,
            ]);

            $locked->update([
                'verification_status' => VerificationStatus::Verified,
                'next_review_date' => $this->protocol->nextReviewDate($locked->risk->aiSystem, $today),
            ]);

            return $entry;
        });
    }

    /**
     * Take a verified link back to declared: it awaits reassessment, and
     * only evidence recorded from now on can verify it again (0013, 0018).
     * A manual reversal needs an active owner and a reason; an automatic one
     * (review due, adverse event, system reclassification) has no author.
     *
     * @throws ValidationException When the link is not verified, or a manual
     *                             reversal lacks its owner or reason.
     */
    public function revert(
        Link $link,
        ChangeOrigin $origin,
        ?Owner $owner = null,
        ?string $reason = null,
        ?AdverseEvent $adverseEvent = null,
    ): StatusHistory {
        return $this->locked($link, function (Link $locked) use ($origin, $owner, $reason, $adverseEvent): StatusHistory {
            if ($locked->verification_status !== VerificationStatus::Verified) {
                throw ValidationException::withMessages(['verification' => __('Only a verified link can be reverted.')]);
            }

            if ($origin === ChangeOrigin::Manual) {
                $problems = array_filter([
                    'owner_id' => $owner?->isActive() ? null : __('Choose an active owner.'),
                    'trigger_reason' => trim((string) $reason) === '' ? __('Say why the verification is being reverted.') : null,
                ]);

                if ($problems !== []) {
                    throw ValidationException::withMessages($problems);
                }
            }

            $entry = $locked->statusHistories()->create([
                'previous_verification' => VerificationStatus::Verified,
                'new_verification' => VerificationStatus::Declared,
                'origin' => $origin,
                'trigger_reason' => $reason,
                'change_date' => today(),
                'owner_id' => $owner?->id,
                'adverse_event_id' => $adverseEvent?->id,
            ]);

            $locked->update([
                'verification_status' => VerificationStatus::Declared,
                'next_review_date' => null,
            ]);

            return $entry;
        });
    }

    /**
     * The first reason the link cannot be verified (or renewed) now, or null
     * when it can. The verifier is chosen apart, so it is not checked here.
     *
     * Only evidence stored after the last change of verification counts: a
     * reversal asks for new proof (0013), and so does a renewal (0019, item
     * 2). With no change yet, any evidence does. Evidence counts by the exact
     * moment it was stored, not by its registration date, which has only the
     * day (0018).
     */
    public function verificationProblem(Link $link): ?string
    {
        if ($link->status === LinkStatus::Cancelled) {
            return __('A cancelled link cannot be verified.');
        }

        if (! $link->risk->aiSystem->category->isOperable()) {
            return __('A link of a system in the unacceptable tier cannot be verified: the system never operates.');
        }

        $lastChange = StatusHistory::lastVerificationChangeOf($link);

        if ($lastChange === null) {
            if ($link->evidence()->doesntExist()) {
                return __('Register evidence before verifying the link.');
            }

            return null;
        }

        if ($link->evidence()->where('created_at', '>', $lastChange->created_at)->exists()) {
            return null;
        }

        $date = $lastChange->change_date->format('d/m/Y');

        if ($lastChange->new_verification === VerificationStatus::Verified) {
            return __('Register new evidence to renew: the last verification was on :date.', ['date' => $date]);
        }

        return __('Register new evidence: the last reversal was on :date.', ['date' => $date]);
    }

    /**
     * Run a change on the link locked for update, in one transaction, and
     * leave the caller's model with the result.
     *
     * @param  callable(Link): StatusHistory  $change
     */
    protected function locked(Link $link, callable $change): StatusHistory
    {
        $entry = DB::transaction(function () use ($link, $change): StatusHistory {
            $locked = Link::query()->with('risk.aiSystem')->lockForUpdate()->findOrFail($link->id);

            return $change($locked);
        });

        $link->refresh();

        return $entry;
    }
}
