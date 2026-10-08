<?php

namespace App\Actions;

use App\Models\AiSystem;
use App\Models\Evidence;
use App\Models\Link;
use App\Support\MonitoringProtocol;
use Illuminate\Database\Eloquent\Builder;

/**
 * Compile the traceability report of an AI system: the risk, mitigation,
 * owner, evidence and status of each of its links.
 */
class CompileTraceabilityReport
{
    /**
     * The CSV header, in the same order as the values returned by rows().
     *
     * @var list<string>
     */
    public const CSV_HEADER = [
        'protocol_version',
        'system_id',
        'system_name',
        'system_application_domain',
        'link_id',
        'status',
        'lifecycle_phase',
        'estimated_cost',
        'observed_cost',
        'creation_date',
        'next_review_date',
        'risk_id',
        'risk_name',
        'risk_description',
        'risk_domain_code',
        'risk_domain_name',
        'risk_subdomain_code',
        'risk_subdomain_name',
        'risk_lifecycle_phase',
        'risk_uncertainty_level',
        'mitigation_id',
        'mitigation_name',
        'mitigation_description',
        'mitigation_saeri_category_code',
        'mitigation_saeri_category_name',
        'mitigation_saeri_subcategory_code',
        'mitigation_saeri_subcategory_name',
        'mitigation_source_reference',
        'mitigation_source_document',
        'mitigation_estimate_source',
        'owner_id',
        'owner_organizational_role',
        'owner_area',
        'evidence_count',
        'evidence',
    ];

    /**
     * Build the nested report for the given system.
     *
     * @return array<string, mixed>
     */
    public function handle(AiSystem $aiSystem): array
    {
        $links = Link::query()
            ->whereHas('risk', fn (Builder $query) => $query->whereBelongsTo($aiSystem))
            ->with(['risk.riskSubdomain.parent', 'mitigation.saeriSubcategory.parent', 'owner', 'evidence' => fn ($query) => $query->orderBy('registration_date')])
            ->orderBy('id')
            ->get();

        return [
            'system' => [
                'id' => $aiSystem->id,
                'name' => $aiSystem->name,
                'application_domain' => $aiSystem->application_domain,
                'source_type' => $aiSystem->source_type->value,
                'category' => $aiSystem->category->value,
                'registration_date' => $aiSystem->registration_date->toDateString(),
            ],
            'generated_at' => now()->toIso8601String(),
            // The C3 protocol in force when the report was exported.
            'protocol' => app(MonitoringProtocol::class)->version(),
            'links' => $links->map(fn (Link $link): array => $this->link($link))->values()->all(),
        ];
    }

    /**
     * Flatten the report into one CSV row per link, joining its evidence into
     * a single cell.
     *
     * @param  array<string, mixed>  $report
     * @return array<int, list<string|int|null>>
     */
    public function rows(array $report): array
    {
        // The system is repeated on every row, so each row stands on its own.
        return array_map(fn (array $link): array => array_map($this->csvCell(...), [
            $report['protocol']['version'],
            $report['system']['id'],
            $report['system']['name'],
            $report['system']['application_domain'],
            $link['id'],
            $link['status'],
            $link['lifecycle_phase'],
            $link['estimated_cost'],
            $link['observed_cost'],
            $link['creation_date'],
            $link['next_review_date'],
            $link['risk']['id'],
            $link['risk']['name'],
            $link['risk']['description'],
            $link['risk']['domain']['code'],
            $link['risk']['domain']['name'],
            $link['risk']['subdomain']['code'],
            $link['risk']['subdomain']['name'],
            $link['risk']['lifecycle_phase'],
            $link['risk']['uncertainty_level'],
            $link['mitigation']['id'],
            $link['mitigation']['name'],
            $link['mitigation']['description'],
            $link['mitigation']['saeri_category']['code'],
            $link['mitigation']['saeri_category']['name'],
            $link['mitigation']['saeri_subcategory']['code'],
            $link['mitigation']['saeri_subcategory']['name'],
            $link['mitigation']['source_reference'],
            $link['mitigation']['source_document'],
            $link['mitigation']['estimate_source'],
            $link['owner']['id'],
            $link['owner']['organizational_role'],
            $link['owner']['area'],
            count($link['evidence']),
            implode(' | ', array_map(
                fn (array $evidence): string => "{$evidence['type']}: {$evidence['description']} ({$evidence['registration_date']})",
                $link['evidence'],
            )),
        ]), $report['links']);
    }

    /**
     * Prepare a value for a spreadsheet cell.
     *
     * Text that a spreadsheet would run as a formula is prefixed with a quote
     * so it is shown as plain text.
     */
    protected function csvCell(string|int|null $value): string|int|null
    {
        if (is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Shape a single link and its chain.
     *
     * @return array<string, mixed>
     */
    protected function link(Link $link): array
    {
        return [
            'id' => $link->id,
            'status' => $link->status->value,
            'lifecycle_phase' => $link->lifecycle_phase->value,
            'estimated_cost' => $link->estimated_cost->value,
            'observed_cost' => $link->observed_cost?->value,
            'creation_date' => $link->creation_date->toDateString(),
            // Null when the system's tier has no periodic review.
            'next_review_date' => $link->next_review_date?->toDateString(),
            'risk' => [
                'id' => $link->risk->id,
                'name' => $link->risk->name,
                'description' => $link->risk->description,
                // MIT AI Risk Repository: the domain is the subdomain's parent.
                'domain' => $link->risk->riskSubdomain->parent?->only(['code', 'name']),
                'subdomain' => $link->risk->riskSubdomain->only(['code', 'name']),
                'lifecycle_phase' => $link->risk->lifecycle_phase->value,
                'uncertainty_level' => $link->risk->uncertainty_level->value,
            ],
            'mitigation' => [
                'id' => $link->mitigation->id,
                'name' => $link->mitigation->name,
                'description' => $link->mitigation->description,
                // Traceable to Saeri et al.: the category is the parent of
                // the subcategory the entry is classified under.
                'saeri_category' => $link->mitigation->saeriSubcategory->parent?->only(['code', 'name']),
                'saeri_subcategory' => $link->mitigation->saeriSubcategory->only(['code', 'name']),
                'source_reference' => $link->mitigation->source_reference,
                'source_document' => $link->mitigation->source_document,
                'estimate_source' => $link->mitigation->estimate_source,
            ],
            'owner' => [
                'id' => $link->owner->id,
                'organizational_role' => $link->owner->organizational_role,
                'area' => $link->owner->area,
            ],
            'evidence' => $link->evidence->map(fn (Evidence $evidence): array => [
                'type' => $evidence->type->value,
                'description' => $evidence->description,
                'registration_date' => $evidence->registration_date->toDateString(),
            ])->values()->all(),
        ];
    }
}
