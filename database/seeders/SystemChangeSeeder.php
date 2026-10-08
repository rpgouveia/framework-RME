<?php

namespace Database\Seeders;

use App\Actions\CreateLink;
use App\Actions\RecordEvidence;
use App\Actions\RecordStatusChange;
use App\Actions\RecordSystemChange;
use App\Enums\AiSystemCategory;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\LifecyclePhase;
use App\Enums\SystemChangeType;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use App\Models\TaxonomyTerm;
use App\Support\AiRiskDomains;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * The system changes of 0021, through the actions and with the clock moved
 * back, on a system of their own so they undo no other story:
 *
 * - a change in the data naming one subdomain, which reverts only the
 *   link of that risk, and another subdomain where the system has no risk
 *   (a risk not yet mapped, from a system change);
 * - a new model version naming no subdomain, which reverts every verified
 *   link left;
 * - a change in a system in the unacceptable tier, recorded with no effect.
 */
class SystemChangeSeeder extends Seeder
{
    protected CarbonImmutable $today;

    public function __construct(
        protected CreateLink $createLink,
        protected RecordEvidence $recordEvidence,
        protected RecordStatusChange $recordStatusChange,
        protected RecordSystemChange $recordSystemChange,
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

        try {
            $aiSystem = AiSystem::factory()->highRisk()->create([
                'name' => 'Triagem automática de chamados',
                'application_domain' => 'Suporte técnico',
            ]);

            [$first, $second] = $this->riskDomains->subdomains()->random(2)->values();
            $unmapped = $this->riskDomains->subdomains()->whereNotIn('id', [$first->id, $second->id])->random();

            foreach ([$first, $second] as $index => $subdomain) {
                $this->verifiedLink($aiSystem, $subdomain, $mitigations->get($index), $owners->random());
            }

            // A change in the data, analysed: it names the subdomains it
            // reaches, so only that risk's link is reverted.
            $this->at(daysAgo: 15);
            $this->recordSystemChange->handle($aiSystem, [
                'type' => SystemChangeType::DataChange,
                'description' => 'Inclusão dos chamados de um novo canal de atendimento na base de treinamento.',
                'change_date' => $this->today->subDays(16),
                'risk_subdomains' => [$first->code, $unmapped->code],
            ]);

            // A new model version, not analysed: every verified link left is
            // reverted.
            $this->at(daysAgo: 7);
            $this->recordSystemChange->handle($aiSystem, [
                'type' => SystemChangeType::ModelVersion,
                'description' => 'Troca do modelo de classificação pela versão 2.0 do fornecedor.',
                'change_date' => $this->today->subDays(7),
                'risk_subdomains' => [],
            ]);

            // A system that may not operate: recorded, reverting nothing.
            $prohibited = AiSystem::query()->where('category', AiSystemCategory::Unacceptable)->latest('id')->first();

            if ($prohibited !== null) {
                $this->at(daysAgo: 2);
                $this->recordSystemChange->handle($prohibited, [
                    'type' => SystemChangeType::ModelVersion,
                    'description' => 'Atualização do modelo durante o planejamento da descontinuação.',
                    'change_date' => $this->today->subDays(2),
                    'risk_subdomains' => [],
                ]);
            }
        } finally {
            Date::setTestNow();
        }
    }

    /**
     * A new risk of the system in the subdomain, linked and verified on
     * evidence weeks before today.
     */
    protected function verifiedLink(AiSystem $aiSystem, TaxonomyTerm $subdomain, Mitigation $mitigation, Owner $owner): Link
    {
        $risk = Risk::factory()->for($aiSystem)->create(['risk_subdomain_id' => $subdomain->id]);

        $this->at(daysAgo: 60);
        $link = $this->createLink->handle([
            'risk_id' => $risk->id,
            'mitigation_id' => $mitigation->id,
            'owner_id' => $owner->id,
            'lifecycle_phase' => LifecyclePhase::Deployment,
            'estimated_cost' => CostLevel::Medium,
        ]);

        $this->at(daysAgo: 50);
        $this->recordEvidence->handle($link, [
            'type' => EvidenceType::TestResult,
            'description' => 'Teste da mitigação em produção, com resultado aprovado.',
        ]);

        $this->at(daysAgo: 45);
        $this->recordStatusChange->verify($link, $owner);

        return $link;
    }

    /**
     * Move the clock to a moment of a day before today, a minute later each
     * time, so the stored moments follow the story.
     */
    protected function at(int $daysAgo): void
    {
        static $minute = 0;

        Date::setTestNow($this->today->subDays($daysAgo)->setTime(11, 0)->addMinutes($minute++));
    }
}
