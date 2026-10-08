<?php

namespace Database\Seeders;

use App\Actions\CreateLink;
use App\Actions\RecordAdverseEvent;
use App\Actions\RecordEvidence;
use App\Actions\RecordStatusChange;
use App\Actions\UpdateAiSystem;
use App\Enums\AdverseEventNature;
use App\Enums\AiSystemCategory;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\LifecyclePhase;
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
 * The reassessment triggers of 0019, told after the links' stories, through
 * the actions: an incident and a near miss intercepted by a link, both
 * reverting the verified links of their subdomain; an event in a subdomain
 * where the system has no risk (a risk not yet mapped); and a system
 * reclassified into the unacceptable tier after its links were verified.
 * The review-due and manual reversals and the renewal are in the links'
 * stories (LinkSeeder).
 *
 * Each trigger works on links of its own, created and verified here with the
 * clock moved back: a new risk in a subdomain the system had none in, or a
 * system of its own for the reclassification. So no trigger undoes the
 * stories the LinkSeeder told. The events are registered today, a few days
 * after they happened.
 */
class ReassessmentSeeder extends Seeder
{
    protected CarbonImmutable $today;

    public function __construct(
        protected CreateLink $createLink,
        protected RecordEvidence $recordEvidence,
        protected RecordStatusChange $recordStatusChange,
        protected RecordAdverseEvent $recordAdverseEvent,
        protected UpdateAiSystem $updateAiSystem,
        protected AiRiskDomains $riskDomains,
    ) {}

    public function run(): void
    {
        $this->today = CarbonImmutable::today();
        $owners = Owner::query()->active()->get();
        $mitigations = Mitigation::all();

        if ($owners->isEmpty() || $mitigations->count() < 2) {
            return;
        }

        $operable = AiSystem::query()
            ->whereNot('category', AiSystemCategory::Unacceptable)
            ->orderBy('id')
            ->get();

        try {
            if ($operable->isNotEmpty()) {
                $this->incident($operable->first(), $mitigations, $owners);
                $this->interceptedNearMiss($operable->last(), $mitigations, $owners);
                $this->unmappedRisk($operable->get(intdiv($operable->count(), 2)) ?? $operable->first());
            }

            $this->reclassification($mitigations, $owners);
        } finally {
            Date::setTestNow();
        }
    }

    /**
     * An incident in the subdomain of a verified link: the link goes back to
     * declared.
     *
     * @param  Collection<int, Mitigation>  $mitigations
     * @param  Collection<int, Owner>  $owners
     */
    protected function incident(AiSystem $aiSystem, Collection $mitigations, Collection $owners): void
    {
        $subdomain = $this->newSubdomainFor($aiSystem);
        [$link] = $this->verifiedLinks($aiSystem, $subdomain, $mitigations->random(1), $owners);

        $this->at(12, 0);
        $this->recordAdverseEvent->handle([
            'ai_system_id' => $aiSystem->id,
            'nature' => AdverseEventNature::Incident,
            'description' => 'O sistema expôs a um grupo de usuários respostas que a mitigação deveria ter barrado.',
            'occurrence_date' => $this->today->subDays(6),
            'detected_at' => $this->today->subDays(3),
            'risk_subdomains' => [$link->risk->riskSubdomain->code],
        ]);
    }

    /**
     * A near miss intercepted by one of two verified links of a subdomain:
     * the interceptor stays verified, the other is reverted.
     *
     * @param  Collection<int, Mitigation>  $mitigations
     * @param  Collection<int, Owner>  $owners
     */
    protected function interceptedNearMiss(AiSystem $aiSystem, Collection $mitigations, Collection $owners): void
    {
        $subdomain = $this->newSubdomainFor($aiSystem);
        [$interceptor] = $this->verifiedLinks($aiSystem, $subdomain, $mitigations->random(2), $owners);

        $this->at(12, 10);
        $this->recordAdverseEvent->handle([
            'ai_system_id' => $aiSystem->id,
            'nature' => AdverseEventNature::NearMiss,
            'description' => 'Uma tentativa de uso indevido foi barrada pela mitigação antes de chegar ao usuário.',
            'occurrence_date' => $this->today->subDays(2),
            'detected_at' => $this->today->subDays(2),
            'risk_subdomains' => [$subdomain->code],
            'intercepting_link_id' => $interceptor->id,
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
     * unacceptable tier: they all go back to declared.
     *
     * @param  Collection<int, Mitigation>  $mitigations
     * @param  Collection<int, Owner>  $owners
     */
    protected function reclassification(Collection $mitigations, Collection $owners): void
    {
        $aiSystem = AiSystem::factory()->highRisk()->create([
            'name' => 'Pontuação de comportamento de cidadãos',
            'application_domain' => 'Concessão de benefícios sociais',
        ]);

        $this->verifiedLinks($aiSystem, $this->newSubdomainFor($aiSystem), $mitigations->random(2), $owners);

        $this->at(12, 30);
        $this->updateAiSystem->handle($aiSystem, ['category' => AiSystemCategory::Unacceptable]);
    }

    /**
     * A new risk of the system in the subdomain, linked to each mitigation and
     * verified on evidence, weeks before today.
     *
     * @param  Collection<int, Mitigation>  $mitigations
     * @param  Collection<int, Owner>  $owners
     * @return list<Link>
     */
    protected function verifiedLinks(AiSystem $aiSystem, TaxonomyTerm $subdomain, Collection $mitigations, Collection $owners): array
    {
        $risk = Risk::factory()->for($aiSystem)->create(['risk_subdomain_id' => $subdomain->id]);
        $links = [];

        foreach ($mitigations->values() as $index => $mitigation) {
            $owner = $owners->random();

            $this->at(9, $index, daysAgo: 60);
            $link = $this->createLink->handle([
                'risk_id' => $risk->id,
                'mitigation_id' => $mitigation->id,
                'owner_id' => $owner->id,
                'lifecycle_phase' => LifecyclePhase::Deployment,
                'estimated_cost' => fake()->randomElement(CostLevel::cases()),
            ]);

            $this->at(9, $index, daysAgo: 50);
            $this->recordEvidence->handle($link, [
                'type' => EvidenceType::TestResult,
                'description' => 'Teste da mitigação em produção, com resultado aprovado.',
            ]);

            $this->at(9, $index, daysAgo: 45);
            $this->recordStatusChange->verify($link, $owner);

            $links[] = $link->load('risk.riskSubdomain');
        }

        return $links;
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
