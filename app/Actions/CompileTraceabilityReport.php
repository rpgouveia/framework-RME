<?php

namespace App\Actions;

use App\Models\AiSystem;
use App\Models\Evidence;
use App\Models\Link;
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
        'link_id',
        'status',
        'lifecycle_phase',
        'estimated_cost',
        'observed_cost',
        'creation_date',
        'next_review_date',
        'risk_id',
        'risk_description',
        'risk_category',
        'risk_lifecycle_phase',
        'risk_uncertainty_level',
        'mitigation_id',
        'mitigation_description',
        'mitigation_saeri_category',
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
            ->with(['risk', 'mitigation', 'owner', 'evidence' => fn ($query) => $query->orderBy('registration_date')])
            ->orderBy('id')
            ->get();

        return [
            'system' => [
                'id' => $aiSystem->id,
                'name' => $aiSystem->name,
                'source_type' => $aiSystem->source_type->value,
                'category' => $aiSystem->category->value,
                'registration_date' => $aiSystem->registration_date->toDateString(),
            ],
            'generated_at' => now()->toIso8601String(),
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
        return array_map(fn (array $link): array => array_map($this->csvCell(...), [
            $link['id'],
            $link['status'],
            $link['lifecycle_phase'],
            $link['estimated_cost'],
            $link['observed_cost'],
            $link['creation_date'],
            $link['next_review_date'],
            $link['risk']['id'],
            $link['risk']['description'],
            $link['risk']['category'],
            $link['risk']['lifecycle_phase'],
            $link['risk']['uncertainty_level'],
            $link['mitigation']['id'],
            $link['mitigation']['description'],
            $link['mitigation']['saeri_category'],
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
     * Decimals use a comma, as spreadsheets in Portuguese expect. Text that
     * a spreadsheet would run as a formula is prefixed with a quote so it is
     * shown as plain text.
     */
    protected function csvCell(string|int|float|null $value): string|int|null
    {
        if (is_float($value)) {
            return number_format($value, 2, ',', '');
        }

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
            'estimated_cost' => $link->estimated_cost,
            'observed_cost' => $link->observed_cost,
            'creation_date' => $link->creation_date->toDateString(),
            'next_review_date' => $link->next_review_date->toDateString(),
            'risk' => [
                'id' => $link->risk->id,
                'description' => $link->risk->description,
                'category' => $link->risk->category->value,
                'lifecycle_phase' => $link->risk->lifecycle_phase->value,
                'uncertainty_level' => $link->risk->uncertainty_level->value,
            ],
            'mitigation' => [
                'id' => $link->mitigation->id,
                'description' => $link->mitigation->description,
                'saeri_category' => $link->mitigation->saeri_category->value,
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
