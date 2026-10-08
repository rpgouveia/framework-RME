<?php

namespace App\Actions;

use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\Evidence;
use App\Models\Link;
use App\Models\Reassessment;
use App\Models\StatusHistory;
use App\Models\TaxonomyTerm;
use App\Support\MonitoringProtocol;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
        'verification_status',
        'last_verification_date',
        'last_verified_by',
        'verification_changes',
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
        'replaces_link_id',
        'replaced_by_link_id',
        'reassessments',
        'reverting_adverse_events',
    ];

    /**
     * The header of the adverse events CSV, one row per event (0020), in the
     * same order as adverseEventRows().
     *
     * @var list<string>
     */
    public const ADVERSE_EVENT_CSV_HEADER = [
        'protocol_version',
        'system_id',
        'system_name',
        'event_id',
        'nature',
        'risk_subdomains',
        'occurrence_date',
        'detected_at',
        'intercepting_link_id',
        'intercepting_link',
        'reverted_link_ids',
        'reverted_links',
        'unmapped_risk_subdomains',
    ];

    /**
     * Every adverse event of the system as a CSV row, oldest first, whether
     * it reverted links or not. The unmapped subdomains are those of the
     * event in which the system has no risk registered today (0019, item 4).
     *
     * @return array<int, list<string|int|null>>
     */
    public function adverseEventRows(AiSystem $aiSystem): array
    {
        $protocol = app(MonitoringProtocol::class);
        $label = fn (Link $link): string => "{$link->risk->name} -> {$link->mitigation->name}";

        return $aiSystem->adverseEvents()
            ->with(['riskSubdomains', 'interceptingLink.risk', 'interceptingLink.mitigation', 'reversals.link.risk', 'reversals.link.mitigation'])
            ->orderBy('occurrence_date')
            ->orderBy('id')
            ->get()
            ->map(fn (AdverseEvent $event): array => array_map($this->csvCell(...), [
                $protocol->version()['version'],
                $aiSystem->id,
                $aiSystem->name,
                $event->id,
                $event->nature->value,
                $event->riskSubdomains->map(fn (TaxonomyTerm $term): string => "{$term->code} {$term->name}")->implode(' | '),
                $event->occurrence_date->toDateString(),
                $event->detected_at?->toDateString(),
                $event->intercepting_link_id,
                $event->interceptingLink === null ? null : $label($event->interceptingLink),
                $event->reversals->pluck('link_id')->implode(' | '),
                $event->reversals->map(fn (StatusHistory $reversal): string => $label($reversal->link))->implode(' | '),
                implode(' | ', $protocol->unmappedSubdomainCodes($event)),
            ]))
            ->values()
            ->all();
    }

    /**
     * Build the nested report for the given system.
     *
     * @return array<string, mixed>
     */
    public function handle(AiSystem $aiSystem): array
    {
        $links = Link::query()
            ->whereHas('risk', fn (Builder $query) => $query->whereBelongsTo($aiSystem))
            ->with([
                'risk.riskSubdomain.parent',
                'mitigation.saeriSubcategory.parent',
                'owner',
                'lastVerification.owner',
                'verificationChanges.owner',
                'observedCostEvidence',
                'evidence' => fn ($query) => $query->orderBy('registration_date')->orderBy('id'),
                'reassessments.owner',
                'reassessments.reversal',
                'replacedBy',
            ])
            ->orderBy('id')
            ->get();

        $adverseEvents = $aiSystem->adverseEvents()
            ->with(['riskSubdomains', 'reversals'])
            ->orderBy('occurrence_date')
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
            'links' => $links->map(fn (Link $link): array => $this->link($link, $adverseEvents))->values()->all(),
            // What happened on the system (0020, item 10): each event and
            // the links it reverted.
            'adverse_events' => $adverseEvents->map(fn (AdverseEvent $event): array => $this->adverseEvent($event))->values()->all(),
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
            $link['verification']['status'],
            $link['verification']['last_verified_on'],
            $link['verification']['verified_by'],
            implode(' | ', array_map(
                fn (array $change): string => "{$change['date']}: {$change['from']} -> {$change['to']} ({$change['origin']}".($change['recorded_by'] === null ? '' : ", {$change['recorded_by']}").')',
                $link['verification']['changes'],
            )),
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
            $link['replaces_link_id'],
            $link['replaced_by_link_id'],
            implode(' | ', array_map(
                fn (array $reassessment): string => "{$reassessment['date']}: {$reassessment['outcome']} by {$reassessment['owner']}"
                    ." (reversal: {$reassessment['reversal_origin']}; cause: {$reassessment['cause_status']}"
                    .($reassessment['cause_phase'] === null ? '' : ", {$reassessment['cause_phase']}")
                    .($reassessment['cause'] === null ? '' : ", {$reassessment['cause']}")
                    .") {$reassessment['justification']}",
                $link['reassessments'],
            )),
            implode(' | ', array_map(
                fn (array $event): string => "#{$event['id']} {$event['nature']} [".implode(', ', $event['risk_subdomains'])."] occurred {$event['occurrence_date']}"
                    .($event['detected_at'] === null ? '' : ", detected {$event['detected_at']}"),
                $link['reverting_adverse_events'],
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
     * @param  Collection<int, AdverseEvent>  $adverseEvents  The system's events.
     * @return array<string, mixed>
     */
    protected function link(Link $link, Collection $adverseEvents): array
    {
        $revertedBy = $link->verificationChanges->pluck('adverse_event_id')->filter()->all();

        return [
            'id' => $link->id,
            'status' => $link->status->value,
            // The second dimension (0013): whether evidence proves the link.
            'verification' => [
                'status' => $link->verification_status->value,
                'last_verified_on' => $link->lastVerification?->change_date->toDateString(),
                // A role, never a person (0018).
                'verified_by' => $link->lastVerification?->owner?->organizational_role,
                // Every verification, renewal and reversal, with what
                // triggered it (0018, 0019): no author for an automatic one.
                'changes' => $link->verificationChanges->map(fn (StatusHistory $change): array => [
                    'date' => $change->change_date->toDateString(),
                    'from' => $change->previous_verification?->value,
                    'to' => $change->new_verification?->value,
                    'origin' => $change->origin->value,
                    'recorded_by' => $change->owner?->organizational_role,
                    'reason' => $change->trigger_reason,
                    'adverse_event_id' => $change->adverse_event_id,
                ])->values()->all(),
            ],
            'lifecycle_phase' => $link->lifecycle_phase->value,
            'estimated_cost' => $link->estimated_cost->value,
            // From the latest evidence that reported it (0018).
            'observed_cost' => $link->observedCostEvidence?->observed_cost?->value,
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
                'observed_cost' => $evidence->observed_cost?->value,
            ])->values()->all(),
            // The link it replaced and the one that replaced it (0020).
            'replaces_link_id' => $link->replaces_link_id,
            'replaced_by_link_id' => $link->replacedBy?->id,
            'reassessments' => $link->reassessments->map(fn (Reassessment $reassessment): array => [
                'id' => $reassessment->id,
                'date' => $reassessment->reassessment_date->toDateString(),
                'outcome' => $reassessment->outcome->value,
                'owner' => $reassessment->owner->organizational_role,
                'justification' => $reassessment->justification,
                'cause_status' => $reassessment->cause_status->value,
                'cause' => $reassessment->cause,
                'cause_phase' => $reassessment->cause_phase?->value,
                'reversal_origin' => $reassessment->reversal->origin->value,
                'reversal_date' => $reassessment->reversal->change_date->toDateString(),
                'verification_id' => $reassessment->verification_id,
            ])->values()->all(),
            // The events that reverted this link, also listed in full in the
            // system's adverse_events.
            'reverting_adverse_events' => $adverseEvents
                ->filter(fn (AdverseEvent $event): bool => in_array($event->id, $revertedBy, true))
                ->map(fn (AdverseEvent $event): array => $this->adverseEvent($event))
                ->values()
                ->all(),
        ];
    }

    /**
     * Shape an adverse event of the system (0019, 0020).
     *
     * @return array{id: int, nature: string, risk_subdomains: list<string>, occurrence_date: string, detected_at: string|null, intercepting_link_id: int|null, reverted_link_ids: list<int>}
     */
    protected function adverseEvent(AdverseEvent $event): array
    {
        return [
            'id' => $event->id,
            'nature' => $event->nature->value,
            'risk_subdomains' => array_values($event->riskSubdomains->map(fn (TaxonomyTerm $term): string => $term->code)->all()),
            'occurrence_date' => $event->occurrence_date->toDateString(),
            'detected_at' => $event->detected_at?->toDateString(),
            'intercepting_link_id' => $event->intercepting_link_id,
            'reverted_link_ids' => array_values($event->reversals->map(fn (StatusHistory $reversal): int => $reversal->link_id)->all()),
        ];
    }
}
