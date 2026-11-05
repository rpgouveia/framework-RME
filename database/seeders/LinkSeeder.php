<?php

namespace Database\Seeders;

use App\Actions\CreateLink;
use App\Actions\RecordEvidence;
use App\Actions\RecordStatusChange;
use App\Actions\RevertOverdueLinks;
use App\Enums\ChangeOrigin;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\LinkStatus;
use App\Models\AdverseEvent;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use App\Support\MonitoringProtocol;
use Carbon\CarbonImmutable;
use Closure;
use Database\Factories\StatusHistoryFactory;
use Database\Seeders\Concerns\DescribesEvidence;
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
 * 0018, in turn, so all of them show up. A system in the unacceptable tier
 * may have planned links and never operates (glossary, section 1): its links
 * stay planned and declared, with no evidence.
 *
 * The portfolio has to read as a real one: each risk is linked only to the
 * mitigations recommended for it, the progress agrees with the verification
 * story, the evidence names the mitigation it proves, and the phases are
 * those a system that neither learns continuously nor is being retired goes
 * through.
 */
class LinkSeeder extends Seeder
{
    use DescribesEvidence;

    /** The verification stories, told in turn. */
    protected const STORIES = [
        // Declared, with no evidence yet.
        'declared',
        // Declared with evidence: ready to verify.
        'ready',
        // Verified recently: the review is ahead.
        'verified',
        // Verified one interval ago: the review is due today (0019).
        'due_today',
        // Verified long ago and reverted the day after its review date, as
        // the daily trigger does (review due, 0019).
        'overdue',
        // Verified, then renewed on new evidence before it fell due (0019).
        'renewed',
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
        protected RevertOverdueLinks $revertOverdueLinks,
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

        $mitigations = Mitigation::query()->with('targetRiskSubdomains')->get();

        if ($mitigations->isEmpty()) {
            $mitigations = Mitigation::factory(5)->create()->load('targetRiskSubdomains');
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
                 * Only among the mitigations recommended for the risk, those
                 * whose target subdomains include the risk's, as the link
                 * creation screen recommends them (0012). A risk with none
                 * stays unlinked: it is the catalogue gap of PENDENTES.md,
                 * and no other mitigation stands in for it.
                 */
                $recommended = $mitigations->filter(
                    fn (Mitigation $mitigation): bool => $mitigation->targetRiskSubdomains->contains('id', $risk->risk_subdomain_id),
                );

                if ($recommended->isEmpty()) {
                    continue;
                }

                // Distinct mitigations: a risk and a mitigation may be linked
                // only once (R-6).
                $drawn = $recommended->random(min(fake()->numberBetween(1, 2), $recommended->count()));

                foreach ($drawn as $mitigation) {
                    $story = $risk->aiSystem->category->isOperable()
                        ? self::STORIES[$turn++ % count(self::STORIES)]
                        : 'declared';

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
            'due_today' => $today->subDays($interval),
            'overdue' => $today->subDays($interval + fake()->numberBetween(5, 40)),
            'renewed' => $today->subDays(fake()->numberBetween(40, 80)),
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
                'lifecycle_phase' => RiskSeeder::lifecyclePhase(),
                'estimated_cost' => fake()->randomElement(CostLevel::cases()),
            ]);
        });

        if ($story === 'declared') {
            // No evidence: the progress goes anywhere up to today, except in
            // a system that never operates, where the link stays planned.
            $path = $risk->aiSystem->category->isOperable() ? $this->declaredPath() : [];
            $this->progress($path, $createdOn, $today, $owners, $adverseEvents);
        } else {
            /*
             * The link is implemented, maybe through in progress, before its
             * first evidence, and its progress does not move after that: a
             * verified link is not suspended (a post-MVP gap, 0028).
             */
            $proofBy = $verifiedOn ?? $today;
            $implementedOn = $createdOn->addDays(fake()->numberBetween(1, max(1, intdiv((int) $createdOn->diffInDays($proofBy), 2))));
            $path = fake()->boolean() ? [LinkStatus::InProgress, LinkStatus::Implemented] : [LinkStatus::Implemented];

            $this->progress($path, $createdOn, $implementedOn, $owners, $adverseEvents);

            // Evidence of distinct types, so no two read the same, after the
            // implementation and before the verification (or, when ready,
            // before today).
            foreach (fake()->randomElements(EvidenceType::cases(), fake()->numberBetween(1, 2)) as $type) {
                $this->at($this->between($implementedOn, $proofBy), fn () => $this->recordEvidence->handle($this->link(), $this->evidence($mitigation, $type)));
            }
        }

        if ($verifiedOn !== null) {
            $this->at($verifiedOn, fn () => $this->recordStatusChange->verify($this->link(), $owner));
        }

        if ($story === 'overdue' && $verifiedOn !== null) {
            // The first daily run after the last valid day.
            $this->at($verifiedOn->addDays($interval + 1), fn () => $this->revertOverdueLinks->revert($this->link()));
        }

        if ($story === 'renewed' && $verifiedOn !== null) {
            $renewedOn = $today->subDays(fake()->numberBetween(1, 10));
            $this->at($this->between($verifiedOn, $renewedOn), fn () => $this->recordEvidence->handle($this->link(), $this->evidence($mitigation, after: self::AFTER_VERIFICATION)));
            $this->at($renewedOn, fn () => $this->recordStatusChange->verify($this->link(), $owners->random()));
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
            $this->at($this->between($revertedOn ?? $today, $today), fn () => $this->recordEvidence->handle($this->link(), $this->evidence($mitigation, after: self::AFTER_REVERSAL)));
        }

        $this->play();
    }

    /**
     * Plan the progress moves of the path on days between two others, the
     * first one excluded, in order. Moves on the same day keep the order of
     * the path.
     *
     * @param  list<LinkStatus>  $path
     * @param  Collection<int, Owner>  $owners
     * @param  Collection<int, AdverseEvent>  $adverseEvents
     */
    protected function progress(array $path, CarbonImmutable $from, CarbonImmutable $to, Collection $owners, Collection $adverseEvents): void
    {
        $span = max(1, (int) $from->diffInDays($to));
        $offsets = array_map(fn (): int => fake()->numberBetween(1, $span), $path);
        sort($offsets);

        foreach ($path as $index => $status) {
            $this->at($from->addDays($offsets[$index]), function () use ($status, $owners, $adverseEvents): void {
                $link = $this->link();

                $this->recordStatusChange->handle($link, $status, [
                    'change_date' => today(),
                    'owner_id' => $owners->random()->id,
                    // The reason is optional, and explains the move it justifies.
                    'trigger_reason' => fake()->boolean() ? StatusHistoryFactory::reasonFor($status) : null,
                    // Some changes are forced by something that went wrong.
                    'adverse_event_id' => $this->eventBehind($link, $adverseEvents)?->id,
                ]);
            });
        }
    }

    /**
     * The progress a declared link with no evidence ends in, and the way it
     * got there: planned, in progress, implemented or suspended, and only
     * suspended after it was in progress or implemented.
     *
     * @return list<LinkStatus>
     */
    protected function declaredPath(): array
    {
        return fake()->randomElement(fake()->randomElement([
            [[]],
            [[LinkStatus::InProgress]],
            [[LinkStatus::Implemented], [LinkStatus::InProgress, LinkStatus::Implemented]],
            [
                [LinkStatus::InProgress, LinkStatus::Suspended],
                [LinkStatus::Implemented, LinkStatus::Suspended],
                [LinkStatus::InProgress, LinkStatus::Implemented, LinkStatus::Suspended],
            ],
        ]));
    }

    /**
     * Now and then, an adverse event of the system behind a progress change:
     * only one known (detected or, with no detection date, occurred) between
     * the link's creation and today, the day of the change. With none, the
     * change quotes no event.
     *
     * @param  Collection<int, AdverseEvent>  $adverseEvents
     */
    protected function eventBehind(Link $link, Collection $adverseEvents): ?AdverseEvent
    {
        $events = $adverseEvents
            ->where('ai_system_id', $link->risk->ai_system_id)
            ->filter(function (AdverseEvent $event) use ($link): bool {
                $known = ($event->detected_at ?? $event->occurrence_date)->toDateString();

                return $known >= $link->creation_date->toDateString() && $known <= today()->toDateString();
            });

        return $events->isNotEmpty() && fake()->boolean(33) ? $events->random() : null;
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
     * The fields of a piece of evidence of the mitigation, of the type given
     * or of any; about half report the cost observed so far (RF07). Evidence
     * stored after a verification or a reversal says so at the end, so it
     * never reads the same as the evidence before it.
     *
     * @return array<string, mixed>
     */
    protected function evidence(Mitigation $mitigation, ?EvidenceType $type = null, string $after = ''): array
    {
        $type ??= fake()->randomElement(EvidenceType::cases());

        return [
            'type' => $type,
            'description' => $this->describeEvidence($mitigation, $type, $after),
            'observed_cost' => fake()->boolean() ? fake()->randomElement(CostLevel::cases()) : null,
        ];
    }
}
