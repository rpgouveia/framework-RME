<?php

namespace Database\Seeders;

use App\Actions\CreateLink;
use App\Actions\RecordEvidence;
use App\Actions\RecordStatusChange;
use App\Enums\ChangeOrigin;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Models\AdverseEvent;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use App\Support\MonitoringProtocol;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use LogicException;

/**
 * Seed the links with their whole story, the way the app writes it: created
 * through CreateLink, moved through RecordStatusChange, proven through
 * RecordEvidence. Nothing is put in the database directly, and the clock is
 * moved back so every date and every stored moment is coherent.
 *
 * Each link of an operable system tells one of the verification stories of
 * 0018, in turn, so all of them show up. A link of an unacceptable system is
 * never verified: it only gets declared stories.
 */
class LinkSeeder extends Seeder
{
    /** The verification stories, told in turn. */
    protected const STORIES = [
        // Declared, with no evidence yet.
        'declared',
        // Declared with evidence: ready to verify.
        'ready',
        // Verified recently: the review is ahead.
        'verified',
        // Verified long ago: the review is overdue.
        'overdue',
        // Reverted by hand, with evidence only from before the reversal: a
        // verification is refused until new evidence comes in.
        'reverted_stale',
        // Reverted by hand, with new evidence since: ready to verify again.
        'reverted_ready',
    ];

    /** @var list<array{at: CarbonImmutable, run: Closure(): mixed}> */
    protected array $timeline = [];

    /** The link whose story is being played. */
    protected ?Link $link = null;

    /** Today, read before the clock is moved back. */
    protected CarbonImmutable $today;

    public function __construct(
        protected CreateLink $createLink,
        protected RecordStatusChange $recordStatusChange,
        protected RecordEvidence $recordEvidence,
        protected MonitoringProtocol $protocol,
    ) {}

    /**
     * Run the database seeds.
     *
     * Reuses the existing mitigations and owners instead of creating a new one
     * per link, so the seeded data looks like a real portfolio.
     */
    public function run(): void
    {
        $risks = Risk::query()->with('aiSystem')->get();

        if ($risks->isEmpty()) {
            $risks = Risk::factory(3)->create()->load('aiSystem');
        }

        $mitigations = Mitigation::all();

        if ($mitigations->isEmpty()) {
            $mitigations = Mitigation::factory(5)->create();
        }

        $owners = Owner::query()->active()->get();

        if ($owners->isEmpty()) {
            $owners = Owner::factory(3)->create();
        }

        $adverseEvents = AdverseEvent::all();
        $turn = 0;
        $this->today = CarbonImmutable::today();

        try {
            foreach ($risks as $risk) {
                /*
                 * Draw distinct mitigations for each risk: a risk and a
                 * mitigation may be linked only once (R-6).
                 */
                $drawn = $mitigations->random(min(fake()->numberBetween(1, 2), $mitigations->count()));

                foreach ($drawn as $mitigation) {
                    $story = $risk->aiSystem->category->isOperable()
                        ? self::STORIES[$turn++ % count(self::STORIES)]
                        : fake()->randomElement(['declared', 'ready']);

                    $this->tell($story, $risk, $mitigation, $owners, $adverseEvents);
                }
            }
        } finally {
            Date::setTestNow();
        }
    }

    /**
     * Plan one link's story on a timeline, then play it in order.
     *
     * @param  Collection<int, Owner>  $owners
     * @param  Collection<int, AdverseEvent>  $adverseEvents
     */
    protected function tell(string $story, Risk $risk, Mitigation $mitigation, Collection $owners, Collection $adverseEvents): void
    {
        $today = $this->today;
        $interval = $this->protocol->reviewIntervalDays($risk->aiSystem->category) ?? 180;
        $owner = $owners->random();

        // When the link is verified, if it ever is, and reverted.
        $verifiedOn = match ($story) {
            'verified' => $today->subDays(fake()->numberBetween(1, 30)),
            'overdue' => $today->subDays($interval + fake()->numberBetween(5, 40)),
            'reverted_stale', 'reverted_ready' => $today->subDays(fake()->numberBetween(60, 120)),
            default => null,
        };
        $revertedOn = in_array($story, ['reverted_stale', 'reverted_ready'], true)
            ? $today->subDays(fake()->numberBetween(10, 40))
            : null;
        $createdOn = ($verifiedOn ?? $today)->subDays(fake()->numberBetween(20, 90));

        $this->timeline = [];

        $this->at($createdOn, function () use ($risk, $mitigation, $owner): void {
            $this->link = $this->createLink->handle([
                'risk_id' => $risk->id,
                'mitigation_id' => $mitigation->id,
                'owner_id' => $owner->id,
                'lifecycle_phase' => fake()->randomElement(LifecyclePhase::cases()),
                'estimated_cost' => fake()->randomElement(CostLevel::cases()),
            ]);
        });

        // Progress moves on its own, between the creation and today.
        $changes = fake()->numberBetween(0, 2);

        for ($change = 1; $change <= $changes; $change++) {
            $this->at($this->between($createdOn, $today), function () use ($owners, $adverseEvents, $risk): void {
                $link = $this->link();
                $options = array_filter(
                    $link->status->transitionOptions(),
                    fn (array $option): bool => $option['value'] !== LinkStatus::Cancelled->value,
                );
                $newStatus = LinkStatus::from(fake()->randomElement($options)['value']);
                $events = $adverseEvents->where('ai_system_id', $risk->ai_system_id)
                    ->filter(fn (AdverseEvent $event): bool => $event->occurrence_date->lte(today()));

                $this->recordStatusChange->handle($link, $newStatus, [
                    'change_date' => today(),
                    'owner_id' => $owners->random()->id,
                    'trigger_reason' => fake()->boolean() ? fake()->sentence() : null,
                    // Some changes are forced by something that went wrong.
                    'adverse_event_id' => $events->isNotEmpty() && fake()->boolean(33) ? $events->random()->id : null,
                ]);
            });
        }

        // Evidence before the verification (or, when ready, before today).
        if ($story !== 'declared') {
            $proofBy = $verifiedOn ?? $today;

            foreach (range(1, fake()->numberBetween(1, 2)) as $ignored) {
                $this->at($this->between($createdOn, $proofBy), fn () => $this->recordEvidence->handle($this->link(), $this->evidence()));
            }
        }

        if ($verifiedOn !== null) {
            $this->at($verifiedOn, fn () => $this->recordStatusChange->verify($this->link(), $owner));
        }

        if ($revertedOn !== null) {
            $this->at($revertedOn, fn () => $this->recordStatusChange->revert(
                $this->link(),
                ChangeOrigin::Manual,
                $owners->random(),
                fake()->randomElement([
                    'A auditoria encontrou o controle desativado em produção.',
                    'A evidência apresentada não cobre a versão atual do modelo.',
                    'Mudança no processo exige nova comprovação.',
                ]),
            ));
        }

        if ($story === 'reverted_ready') {
            $this->at($this->between($revertedOn ?? $today, $today), fn () => $this->recordEvidence->handle($this->link(), $this->evidence()));
        }

        $this->play();
    }

    /**
     * The link of the story being played, once created.
     */
    protected function link(): Link
    {
        return $this->link ?? throw new LogicException('The link is created first.');
    }

    /**
     * Plan a step of the story on a day.
     *
     * @param  Closure(): mixed  $step
     */
    protected function at(CarbonImmutable $day, Closure $step): void
    {
        $this->timeline[] = ['at' => $day->startOfDay(), 'run' => $step];
    }

    /**
     * Play the story in order. Steps on the same day keep the order they
     * were planned in, a few minutes apart, so the stored moments agree with
     * the story (a reversal before the evidence that follows it).
     */
    protected function play(): void
    {
        $steps = $this->timeline;
        uasort($steps, fn (array $a, array $b): int => $a['at'] <=> $b['at']);

        $minutes = 0;

        foreach ($steps as $step) {
            Date::setTestNow($step['at']->setTime(9, 0)->addMinutes($minutes++));
            ($step['run'])();
        }
    }

    /**
     * A day between two others, the first one excluded.
     */
    protected function between(CarbonImmutable $from, CarbonImmutable $to): CarbonImmutable
    {
        $days = max(1, (int) $from->diffInDays($to));

        return $from->addDays(fake()->numberBetween(1, $days));
    }

    /**
     * The fields of a piece of evidence; about half report the cost observed
     * so far (RF07).
     *
     * @return array<string, mixed>
     */
    protected function evidence(): array
    {
        return [
            'type' => fake()->randomElement(EvidenceType::cases()),
            'description' => fake()->sentence(),
            'observed_cost' => fake()->boolean() ? fake()->randomElement(CostLevel::cases()) : null,
        ];
    }
}
