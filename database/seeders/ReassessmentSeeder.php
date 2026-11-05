<?php

namespace Database\Seeders;

use App\Actions\CreateLink;
use App\Actions\RecordAdverseEvent;
use App\Actions\RecordEvidence;
use App\Actions\RecordReassessment;
use App\Actions\RecordStatusChange;
use App\Actions\UpdateAiSystem;
use App\Enums\AdverseEventNature;
use App\Enums\AiSystemCategory;
use App\Enums\CauseStatus;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\ReassessmentOutcome;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use App\Models\TaxonomyTerm;
use App\Support\AiRiskDomains;
use Carbon\CarbonImmutable;
use Database\Factories\AdverseEventFactory;
use Database\Factories\RiskFactory;
use Database\Factories\StatusHistoryFactory;
use Database\Seeders\Concerns\DescribesEvidence;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * The reassessment triggers of 0019 and the reassessments of 0020, told
 * after the links' stories, through the actions:
 *
 * - an incident reverting four verified links, three of them reassessed
 *   (maintained, adjusted without a verification, replaced by a new link)
 *   and one still awaiting;
 * - a near miss intercepted by a link, reverting the other link of its
 *   subdomain, which is then closed;
 * - an event in a subdomain where the system has no risk (a risk not yet
 *   mapped);
 * - a system reclassified into the unacceptable tier after its links were
 *   verified: one link adjusted to plan the discontinuation, one awaiting.
 *
 * The review-due and manual reversals and the renewal are in the links'
 * stories (LinkSeeder), and some of them still await reassessment, for
 * different lengths of time.
 *
 * Each trigger works on links of its own, created and verified here with the
 * clock moved back: a new risk in a subdomain the system had none in, or a
 * system of its own for the reclassification. So no trigger undoes the
 * stories the LinkSeeder told. As there, each link takes a mitigation
 * recommended for its risk and is implemented before its evidence.
 */
class ReassessmentSeeder extends Seeder
{
    use DescribesEvidence;

    protected CarbonImmutable $today;

    /** @var Collection<int, Owner> */
    protected Collection $owners;

    /** @var Collection<int, Mitigation> */
    protected Collection $mitigations;

    public function __construct(
        protected CreateLink $createLink,
        protected RecordEvidence $recordEvidence,
        protected RecordStatusChange $recordStatusChange,
        protected RecordAdverseEvent $recordAdverseEvent,
        protected RecordReassessment $recordReassessment,
        protected UpdateAiSystem $updateAiSystem,
        protected AiRiskDomains $riskDomains,
    ) {}

    public function run(): void
    {
        $this->today = CarbonImmutable::today();
        $this->owners = Owner::query()->active()->get();
        $this->mitigations = Mitigation::query()->with('targetRiskSubdomains')->get();

        if ($this->owners->isEmpty() || $this->mitigations->count() < 5) {
            return;
        }

        $operable = AiSystem::query()
            ->whereNot('category', AiSystemCategory::Unacceptable)
            ->orderBy('id')
            ->get();

        try {
            if ($operable->isNotEmpty()) {
                $this->incident($operable->first());
                $this->interceptedNearMiss($operable->last());
                $this->unmappedRisk($operable->get(intdiv($operable->count(), 2)) ?? $operable->first());
            }

            $this->reclassification();
        } finally {
            Date::setTestNow();
        }
    }

    /**
     * An incident in the subdomain of four verified links, all reverted; the
     * reassessments then go their separate ways (0020, item 7). Five
     * mitigations recommended for the subdomain: four for the links, one for
     * the replacement.
     */
    protected function incident(AiSystem $aiSystem): void
    {
        $subdomain = $this->newSubdomainFor($aiSystem, mitigations: 5);
        $mitigations = $this->recommendedFor($subdomain, 5);
        [$maintained, $adjusted, $replaced] = $this->verifiedLinks($aiSystem, $subdomain, $mitigations->take(4));

        $this->at(12, 0, daysAgo: 20);
        $this->recordAdverseEvent->handle([
            'ai_system_id' => $aiSystem->id,
            'nature' => AdverseEventNature::Incident,
            'description' => AdverseEventFactory::describe($subdomain->code, AdverseEventNature::Incident),
            'occurrence_date' => $this->today->subDays(24),
            'detected_at' => $this->today->subDays(21),
            'risk_subdomains' => [$subdomain->code],
        ]);

        // Maintained: new evidence, then verified in the same act.
        $this->at(10, 0, daysAgo: 14);
        $this->evidence($maintained, self::AFTER_REVERSAL);
        $this->at(10, 0, daysAgo: 12);
        $this->reassess($maintained, ReassessmentOutcome::Maintain, 'A mitigação estava ativa; o incidente veio de um caso fora do escopo dela, já tratado por outro vínculo.', [
            'cause_status' => CauseStatus::Identified,
            'cause' => 'Entrada fora do domínio previsto, que o filtro não cobria.',
            'cause_phase' => LifecyclePhase::DesignAndDevelopment,
        ]);

        // Adjusted, still to be proven: the mitigation is redone after the
        // incident, so it goes back in progress, awaiting verification.
        $this->at(10, 0, daysAgo: 11);
        $this->reassess($adjusted, ReassessmentOutcome::Adjust, 'A mitigação precisa ser reforçada e reimplantada; a prova virá depois da nova implantação.', [
            'cause_status' => CauseStatus::NotIdentified,
            'changes' => [
                'estimated_cost' => CostLevel::High,
                'lifecycle_phase' => LifecyclePhase::Deployment,
                'owner_id' => $this->owners->random()->id,
                'status' => LinkStatus::InProgress,
            ],
        ]);

        // Replaced: cancelled, and a new link with another mitigation for
        // the same risk takes over.
        $this->at(10, 0, daysAgo: 10);
        $this->reassess($replaced, ReassessmentOutcome::Replace, 'A mitigação não é adequada a este risco; outra do catálogo o trata melhor.', [
            'cause_status' => CauseStatus::Identified,
            'cause' => 'A mitigação escolhida não cobre o vetor de ataque observado.',
            'cause_phase' => LifecyclePhase::Inception,
        ]);
        $this->at(10, 30, daysAgo: 10);
        $this->createLink->handle([
            'risk_id' => $replaced->risk_id,
            'mitigation_id' => $mitigations->last()->id,
            'owner_id' => $replaced->owner_id,
            'lifecycle_phase' => LifecyclePhase::Deployment,
            'estimated_cost' => CostLevel::Medium,
            'replaces_link_id' => $replaced->id,
        ]);

        // The fourth link still awaits its reassessment, 20 days on.
    }

    /**
     * A near miss intercepted by one of two verified links: the interceptor
     * stays verified, the other is reverted and then closed.
     */
    protected function interceptedNearMiss(AiSystem $aiSystem): void
    {
        $subdomain = $this->newSubdomainFor($aiSystem, mitigations: 2);
        [$interceptor, $other] = $this->verifiedLinks($aiSystem, $subdomain, $this->recommendedFor($subdomain, 2));

        $this->at(12, 10, daysAgo: 8);
        $this->recordAdverseEvent->handle([
            'ai_system_id' => $aiSystem->id,
            'nature' => AdverseEventNature::NearMiss,
            'description' => AdverseEventFactory::describe($subdomain->code, AdverseEventNature::NearMiss),
            'occurrence_date' => $this->today->subDays(9),
            'detected_at' => $this->today->subDays(9),
            'risk_subdomains' => [$subdomain->code],
            'intercepting_link_id' => $interceptor->id,
        ]);

        $this->at(10, 0, daysAgo: 5);
        $this->reassess($other, ReassessmentOutcome::Close, 'A mitigação interceptadora já cobre o risco; manter esta duplica o esforço.', [
            'cause_status' => CauseStatus::Identified,
            'cause' => 'Duas mitigações sobrepostas para o mesmo vetor.',
            'cause_phase' => LifecyclePhase::DesignAndDevelopment,
        ]);
    }

    /**
     * An incident in a subdomain where the system has no risk: a risk not yet
     * mapped, shown on the dashboard until it is registered.
     */
    protected function unmappedRisk(AiSystem $aiSystem): void
    {
        $subdomain = $this->newSubdomainFor($aiSystem);

        $this->at(12, 20);
        $this->recordAdverseEvent->handle([
            'ai_system_id' => $aiSystem->id,
            'nature' => AdverseEventNature::Incident,
            'description' => AdverseEventFactory::describe($subdomain->code, AdverseEventNature::Incident),
            'occurrence_date' => $this->today->subDays(4),
            'detected_at' => null,
            'risk_subdomains' => [$subdomain->code],
        ]);
    }

    /**
     * A system of its own, with verified links, reclassified into the
     * unacceptable tier: both go back to declared; one is adjusted to plan
     * the discontinuation, the other still awaits.
     */
    protected function reclassification(): void
    {
        $aiSystem = AiSystem::factory()->highRisk()->create([
            'name' => 'Pontuação de comportamento de cidadãos',
            'application_domain' => 'Concessão de benefícios sociais',
        ]);

        $subdomain = $this->newSubdomainFor($aiSystem, mitigations: 2);
        [$planned] = $this->verifiedLinks($aiSystem, $subdomain, $this->recommendedFor($subdomain, 2));

        $this->at(12, 30, daysAgo: 6);
        $this->updateAiSystem->handle($aiSystem, ['category' => AiSystemCategory::Unacceptable]);

        $this->at(10, 0, daysAgo: 3);
        $this->reassess($planned, ReassessmentOutcome::Adjust, 'Plano de descontinuação: a mitigação segue ativa até o desligamento do sistema, previsto para o próximo trimestre.', [
            'changes' => ['lifecycle_phase' => LifecyclePhase::Retirement],
        ]);
    }

    /**
     * Reassess a link through RecordReassessment, by an active owner.
     *
     * @param  array<string, mixed>  $data
     */
    protected function reassess(Link $link, ReassessmentOutcome $outcome, string $justification, array $data = []): void
    {
        $this->recordReassessment->handle($link, [
            'outcome' => $outcome,
            'owner_id' => $link->owner->isActive() ? $link->owner_id : $this->owners->random()->id,
            'justification' => $justification,
            ...$data,
        ]);
    }

    /**
     * A new risk of the system in the subdomain, linked to each mitigation,
     * implemented and verified on evidence, weeks before today.
     *
     * @param  Collection<int, Mitigation>  $mitigations
     * @return list<Link>
     */
    protected function verifiedLinks(AiSystem $aiSystem, TaxonomyTerm $subdomain, Collection $mitigations): array
    {
        $risk = Risk::factory()->for($aiSystem)->create([
            'risk_subdomain_id' => $subdomain->id,
            'lifecycle_phase' => RiskSeeder::lifecyclePhase(),
        ]);
        $links = [];

        foreach ($mitigations->values() as $index => $mitigation) {
            $owner = $this->owners->random();

            $this->at(9, $index, daysAgo: 60);
            $link = $this->createLink->handle([
                'risk_id' => $risk->id,
                'mitigation_id' => $mitigation->id,
                'owner_id' => $owner->id,
                'lifecycle_phase' => LifecyclePhase::Deployment,
                'estimated_cost' => fake()->randomElement(CostLevel::cases()),
            ]);

            // Implemented before its evidence.
            foreach ([56 => LinkStatus::InProgress, 52 => LinkStatus::Implemented] as $daysAgo => $status) {
                $this->at(9, $index, daysAgo: $daysAgo);
                $this->recordStatusChange->handle($link, $status, [
                    'change_date' => today(),
                    'owner_id' => $owner->id,
                    'trigger_reason' => fake()->boolean() ? StatusHistoryFactory::reasonFor($status) : null,
                ]);
            }

            $this->at(9, $index, daysAgo: 50);
            $this->evidence($link);

            $this->at(9, $index, daysAgo: 45);
            $this->recordStatusChange->verify($link, $owner);

            $links[] = $link->load(['risk.riskSubdomain', 'owner']);
        }

        return $links;
    }

    /**
     * A test result of the link's mitigation, ending with what it came after,
     * if anything.
     */
    protected function evidence(Link $link, string $after = ''): void
    {
        $this->recordEvidence->handle($link, [
            'type' => EvidenceType::TestResult,
            'description' => $this->describeEvidence($link->mitigation, EvidenceType::TestResult, $after),
        ]);
    }

    /**
     * A subdomain in which the system has no risk yet, with at least so many
     * mitigations recommended for it.
     */
    protected function newSubdomainFor(AiSystem $aiSystem, int $mitigations = 0): TaxonomyTerm
    {
        $taken = Risk::query()->where('ai_system_id', $aiSystem->id)->pluck('risk_subdomain_id')->all();

        // Among the subdomains with a risk and an occurrence of their own, so
        // the risk registered here and the event told read like the real ones.
        return $this->riskDomains->subdomains()
            ->whereIn('code', RiskFactory::subdomainCodes())
            ->whereNotIn('id', $taken)
            ->filter(fn (TaxonomyTerm $subdomain): bool => $this->recommendedFor($subdomain)->count() >= $mitigations)
            ->random();
    }

    /**
     * The mitigations recommended for a risk of the subdomain, those whose
     * target subdomains include it, as the link creation screen recommends
     * them (0012): all of them, or so many drawn among them.
     *
     * @return Collection<int, Mitigation>
     */
    protected function recommendedFor(TaxonomyTerm $subdomain, ?int $count = null): Collection
    {
        $recommended = $this->mitigations
            ->filter(fn (Mitigation $mitigation): bool => $mitigation->targetRiskSubdomains->contains('id', $subdomain->id))
            ->values();

        return $count === null ? $recommended : $recommended->random($count)->values();
    }

    /**
     * Move the clock to a time of a day before today.
     */
    protected function at(int $hour, int $minute, int $daysAgo = 0): void
    {
        Date::setTestNow($this->today->subDays($daysAgo)->setTime($hour, $minute));
    }
}
