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
 * stories the LinkSeeder told.
 */
class ReassessmentSeeder extends Seeder
{
    protected CarbonImmutable $today;

    /** @var Collection<int, Owner> */
    protected Collection $owners;

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
        $mitigations = Mitigation::all();

        if ($this->owners->isEmpty() || $mitigations->count() < 5) {
            return;
        }

        $operable = AiSystem::query()
            ->whereNot('category', AiSystemCategory::Unacceptable)
            ->orderBy('id')
            ->get();

        try {
            if ($operable->isNotEmpty()) {
                $this->incident($operable->first(), $mitigations->random(5)->values());
                $this->interceptedNearMiss($operable->last(), $mitigations->random(2)->values());
                $this->unmappedRisk($operable->get(intdiv($operable->count(), 2)) ?? $operable->first());
            }

            $this->reclassification($mitigations->random(2)->values());
        } finally {
            Date::setTestNow();
        }
    }

    /**
     * An incident in the subdomain of four verified links, all reverted; the
     * reassessments then go their separate ways (0020, item 7).
     *
     * @param  Collection<int, Mitigation>  $mitigations  Four for the links, one
     *                                                    for the replacement.
     */
    protected function incident(AiSystem $aiSystem, Collection $mitigations): void
    {
        $subdomain = $this->newSubdomainFor($aiSystem);
        [$maintained, $adjusted, $replaced] = $this->verifiedLinks($aiSystem, $subdomain, $mitigations->take(4));

        $this->at(12, 0, daysAgo: 20);
        $this->recordAdverseEvent->handle([
            'ai_system_id' => $aiSystem->id,
            'nature' => AdverseEventNature::Incident,
            'description' => 'O sistema expôs a um grupo de usuários respostas que as mitigações deveriam ter barrado.',
            'occurrence_date' => $this->today->subDays(24),
            'detected_at' => $this->today->subDays(21),
            'risk_subdomains' => [$subdomain->code],
        ]);

        // Maintained: new evidence, then verified in the same act.
        $this->at(10, 0, daysAgo: 14);
        $this->evidence($maintained, 'Novo teste após o incidente, com a mitigação funcionando como esperado.');
        $this->at(10, 0, daysAgo: 12);
        $this->reassess($maintained, ReassessmentOutcome::Maintain, 'A mitigação estava ativa; o incidente veio de um caso fora do escopo dela, já tratado por outro vínculo.', [
            'cause_status' => CauseStatus::Identified,
            'cause' => 'Entrada fora do domínio previsto, que o filtro não cobria.',
            'cause_phase' => LifecyclePhase::Design,
        ]);

        // Adjusted, still to be proven: it goes on awaiting verification.
        $this->at(10, 0, daysAgo: 11);
        $this->reassess($adjusted, ReassessmentOutcome::Adjust, 'A mitigação precisa ser reforçada e reimplantada; a prova virá depois da nova implantação.', [
            'cause_status' => CauseStatus::NotIdentified,
            'changes' => [
                'estimated_cost' => CostLevel::High,
                'lifecycle_phase' => LifecyclePhase::Deployment,
                'owner_id' => $this->owners->random()->id,
                'status' => $this->nextStatus($adjusted),
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
     *
     * @param  Collection<int, Mitigation>  $mitigations
     */
    protected function interceptedNearMiss(AiSystem $aiSystem, Collection $mitigations): void
    {
        $subdomain = $this->newSubdomainFor($aiSystem);
        [$interceptor, $other] = $this->verifiedLinks($aiSystem, $subdomain, $mitigations);

        $this->at(12, 10, daysAgo: 8);
        $this->recordAdverseEvent->handle([
            'ai_system_id' => $aiSystem->id,
            'nature' => AdverseEventNature::NearMiss,
            'description' => 'Uma tentativa de uso indevido foi barrada pela mitigação antes de chegar ao usuário.',
            'occurrence_date' => $this->today->subDays(9),
            'detected_at' => $this->today->subDays(9),
            'risk_subdomains' => [$subdomain->code],
            'intercepting_link_id' => $interceptor->id,
        ]);

        $this->at(10, 0, daysAgo: 5);
        $this->reassess($other, ReassessmentOutcome::Close, 'A mitigação interceptadora já cobre o risco; manter esta duplica o esforço.', [
            'cause_status' => CauseStatus::Identified,
            'cause' => 'Duas mitigações sobrepostas para o mesmo vetor.',
            'cause_phase' => LifecyclePhase::Design,
        ]);
    }

    /**
     * An incident in a subdomain where the system has no risk: a risk not yet
     * mapped, shown on the dashboard until it is registered.
     */
    protected function unmappedRisk(AiSystem $aiSystem): void
    {
        $this->at(12, 20);
        $this->recordAdverseEvent->handle([
            'ai_system_id' => $aiSystem->id,
            'nature' => AdverseEventNature::Incident,
            'description' => 'Ocorrência sem risco correspondente no cadastro do sistema.',
            'occurrence_date' => $this->today->subDays(4),
            'detected_at' => null,
            'risk_subdomains' => [$this->newSubdomainFor($aiSystem)->code],
        ]);
    }

    /**
     * A system of its own, with verified links, reclassified into the
     * unacceptable tier: both go back to declared; one is adjusted to plan
     * the discontinuation, the other still awaits.
     *
     * @param  Collection<int, Mitigation>  $mitigations
     */
    protected function reclassification(Collection $mitigations): void
    {
        $aiSystem = AiSystem::factory()->highRisk()->create([
            'name' => 'Pontuação de comportamento de cidadãos',
            'application_domain' => 'Concessão de benefícios sociais',
        ]);

        [$planned] = $this->verifiedLinks($aiSystem, $this->newSubdomainFor($aiSystem), $mitigations);

        $this->at(12, 30, daysAgo: 6);
        $this->updateAiSystem->handle($aiSystem, ['category' => AiSystemCategory::Unacceptable]);

        $this->at(10, 0, daysAgo: 3);
        $this->reassess($planned, ReassessmentOutcome::Adjust, 'Plano de descontinuação: a mitigação segue ativa até o desligamento do sistema, previsto para o próximo trimestre.', [
            'changes' => ['lifecycle_phase' => LifecyclePhase::Decommissioning],
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
     * A progress move the link may make, other than cancelling.
     */
    protected function nextStatus(Link $link): LinkStatus
    {
        return collect(LinkStatus::cases())
            ->first(fn (LinkStatus $status): bool => $status !== LinkStatus::Cancelled && $link->status->canTransitionTo($status))
            ?? $link->status;
    }

    /**
     * A new risk of the system in the subdomain, linked to each mitigation and
     * verified on evidence, weeks before today.
     *
     * @param  Collection<int, Mitigation>  $mitigations
     * @return list<Link>
     */
    protected function verifiedLinks(AiSystem $aiSystem, TaxonomyTerm $subdomain, Collection $mitigations): array
    {
        $risk = Risk::factory()->for($aiSystem)->create(['risk_subdomain_id' => $subdomain->id]);
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

            $this->at(9, $index, daysAgo: 50);
            $this->evidence($link, 'Teste da mitigação em produção, com resultado aprovado.');

            $this->at(9, $index, daysAgo: 45);
            $this->recordStatusChange->verify($link, $owner);

            $links[] = $link->load(['risk.riskSubdomain', 'owner']);
        }

        return $links;
    }

    protected function evidence(Link $link, string $description): void
    {
        $this->recordEvidence->handle($link, [
            'type' => EvidenceType::TestResult,
            'description' => $description,
        ]);
    }

    /**
     * A subdomain in which the system has no risk yet.
     */
    protected function newSubdomainFor(AiSystem $aiSystem): TaxonomyTerm
    {
        $taken = Risk::query()->where('ai_system_id', $aiSystem->id)->pluck('risk_subdomain_id')->all();

        return $this->riskDomains->subdomains()->whereNotIn('id', $taken)->random();
    }

    /**
     * Move the clock to a time of a day before today.
     */
    protected function at(int $hour, int $minute, int $daysAgo = 0): void
    {
        Date::setTestNow($this->today->subDays($daysAgo)->setTime($hour, $minute));
    }
}
