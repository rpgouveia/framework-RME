<?php

namespace Database\Seeders;

use App\Actions\RecordAdverseEvent;
use App\Actions\UpdateAiSystem;
use App\Enums\AdverseEventNature;
use App\Enums\AiSystemCategory;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\Risk;
use App\Support\AiRiskDomains;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * The reassessment triggers of 0019, told after the links' stories, through
 * the actions: an incident and a near miss intercepted by a link, both
 * reverting the verified links of their subdomains; an event in a subdomain
 * where the system has no risk (a risk not yet mapped); and a system
 * reclassified into the unacceptable tier after its links were verified.
 * The review-due and manual reversals and the renewal are in the links'
 * stories (LinkSeeder).
 *
 * Every step is registered today, after the links' stories, so it acts on
 * the links as they stand; the events themselves happened a few days
 * before, and were registered late.
 */
class ReassessmentSeeder extends Seeder
{
    /** @var list<int> Systems already used, so each trigger shows apart. */
    protected array $used = [];

    public function __construct(
        protected RecordAdverseEvent $recordAdverseEvent,
        protected UpdateAiSystem $updateAiSystem,
        protected AiRiskDomains $riskDomains,
    ) {}

    public function run(): void
    {
        $today = CarbonImmutable::today();

        try {
            Date::setTestNow($today->setTime(12, 0));
            $this->incident($today);

            Date::setTestNow($today->setTime(12, 10));
            $this->interceptedNearMiss($today);

            Date::setTestNow($today->setTime(12, 20));
            $this->unmappedRisk($today);

            Date::setTestNow($today->setTime(12, 30));
            $this->reclassification();
        } finally {
            Date::setTestNow();
        }
    }

    /**
     * An incident in the subdomain of a verified link: the link (and any other
     * verified one of that subdomain in the system) goes back to declared.
     */
    protected function incident(CarbonImmutable $today): void
    {
        $link = $this->verifiedLink();

        if ($link === null) {
            return;
        }

        $occurred = $today->subDays(6);

        $this->recordAdverseEvent->handle([
            'ai_system_id' => $link->risk->ai_system_id,
            'nature' => AdverseEventNature::Incident,
            'description' => 'O sistema expôs a um grupo de usuários respostas que a mitigação deveria ter barrado.',
            'occurrence_date' => $occurred,
            'detected_at' => $occurred->addDays(3),
            'risk_subdomains' => [$link->risk->riskSubdomain->code],
        ]);
    }

    /**
     * A near miss intercepted by a verified link: that link stays verified,
     * the others of its subdomain in the system are reverted.
     */
    protected function interceptedNearMiss(CarbonImmutable $today): void
    {
        $link = $this->verifiedLink();

        if ($link === null) {
            return;
        }

        $this->recordAdverseEvent->handle([
            'ai_system_id' => $link->risk->ai_system_id,
            'nature' => AdverseEventNature::NearMiss,
            'description' => 'Uma tentativa de uso indevido foi barrada pela mitigação antes de chegar ao usuário.',
            'occurrence_date' => $today->subDays(2),
            'detected_at' => $today->subDays(2),
            'risk_subdomains' => [$link->risk->riskSubdomain->code],
            'intercepting_link_id' => $link->id,
        ]);
    }

    /**
     * An incident in a subdomain where the system has no risk: a risk not yet
     * mapped, shown on the dashboard until it is registered.
     */
    protected function unmappedRisk(CarbonImmutable $today): void
    {
        $aiSystem = AiSystem::query()
            ->whereNot('category', AiSystemCategory::Unacceptable)
            ->whereNotIn('id', $this->used)
            ->inRandomOrder()
            ->first() ?? AiSystem::query()->first();

        if ($aiSystem === null) {
            return;
        }

        $mapped = Risk::query()->where('ai_system_id', $aiSystem->id)->pluck('risk_subdomain_id')->all();
        $subdomain = $this->riskDomains->subdomains()->whereNotIn('id', $mapped)->random();

        $this->recordAdverseEvent->handle([
            'ai_system_id' => $aiSystem->id,
            'nature' => AdverseEventNature::Incident,
            'description' => 'Ocorrência sem risco correspondente no cadastro do sistema.',
            'occurrence_date' => $today->subDays(4),
            'detected_at' => null,
            'risk_subdomains' => [$subdomain->code],
        ]);
    }

    /**
     * A system reclassified into the unacceptable tier after its links were
     * verified: they all go back to declared.
     */
    protected function reclassification(): void
    {
        $link = $this->verifiedLink();

        if ($link === null) {
            return;
        }

        $this->updateAiSystem->handle($link->risk->aiSystem, ['category' => AiSystemCategory::Unacceptable]);
    }

    /**
     * A verified link of an operable system not used yet by another trigger.
     */
    protected function verifiedLink(): ?Link
    {
        $link = Link::query()
            ->verified()
            ->whereHas('risk.aiSystem', fn ($query) => $query
                ->whereNot('category', AiSystemCategory::Unacceptable)
                ->whereNotIn('id', $this->used))
            ->with('risk.aiSystem', 'risk.riskSubdomain')
            ->inRandomOrder()
            ->first();

        if ($link !== null) {
            $this->used[] = $link->risk->ai_system_id;
        }

        return $link;
    }
}
