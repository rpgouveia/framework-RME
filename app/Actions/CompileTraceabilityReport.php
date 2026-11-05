<?php

namespace App\Actions;

use App\Enums\AdverseEventNature;
use App\Enums\AiSystemCategory;
use App\Enums\CauseStatus;
use App\Enums\ChangeOrigin;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\ReassessmentOutcome;
use App\Enums\SystemChangeType;
use App\Enums\SystemSourceType;
use App\Enums\UncertaintyLevel;
use App\Enums\VerificationStatus;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\Evidence;
use App\Models\Link;
use App\Models\Reassessment;
use App\Models\StatusHistory;
use App\Models\SystemChange;
use App\Models\TaxonomyTerm;
use App\Support\MonitoringProtocol;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Compile the traceability report of an AI system: the risk, mitigation,
 * owner, evidence and status of each of its links.
 *
 * The JSON report speaks in stable English codes; the CSV files are read by
 * people in a spreadsheet, so their headers, enum values and the phrases of
 * their composite cells are in the interface language, with the labels of
 * the screens (0022, addendum of 2026-11-05). Registered content goes out as
 * it was registered, and dates stay in ISO 8601 so they sort.
 */
class CompileTraceabilityReport
{
    /**
     * The prefix of the translation key of a CSV column, which keeps the
     * column identifiers apart from the other keys of `lang/pt_BR.json`.
     */
    public const CSV_COLUMN_KEY_PREFIX = 'Report Column: ';

    /**
     * The columns of the CSV, in the same order as the values returned by
     * rows(). They are identifiers: the file shows them translated, through
     * csvHeader().
     *
     * @var list<string>
     */
    public const CSV_HEADER = [
        'protocol_version',
        'system_id',
        'system_name',
        'system_application_domain',
        'system_category',
        'system_source_type',
        'system_registration_date',
        'link_id',
        'status',
        'status_changes',
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
     * The columns of the adverse events CSV, one row per event (0020), in
     * the same order as adverseEventRows().
     *
     * @var list<string>
     */
    public const ADVERSE_EVENT_CSV_HEADER = [
        'protocol_version',
        'system_id',
        'system_name',
        'event_id',
        'nature',
        'description',
        'materialized_risk_subdomains',
        'occurrence_date',
        'detected_at',
        'intercepting_link_id',
        'intercepting_link',
        'reverted_link_ids',
        'reverted_links',
        'unmapped_subdomains_at_export',
    ];

    /**
     * The columns of the system changes CSV, one row per change (0021), in
     * the same order as systemChangeRows().
     *
     * @var list<string>
     */
    public const SYSTEM_CHANGE_CSV_HEADER = [
        'protocol_version',
        'system_id',
        'system_name',
        'change_id',
        'type',
        'description',
        'change_date',
        'affected_risk_subdomains',
        'reverted_link_ids',
        'reverted_links',
        'unmapped_subdomains_at_export',
    ];

    /**
     * The header of a CSV: each column translated, through its key.
     *
     * @param  list<string>  $columns  One of the column lists of this class.
     * @return list<string>
     */
    public static function csvHeader(array $columns): array
    {
        return array_map(fn (string $column): string => self::text(self::CSV_COLUMN_KEY_PREFIX.$column), $columns);
    }

    /**
     * Every change of the system as a CSV row, oldest first, whether it
     * reverted links or not. With no subdomain given, a change reverted every
     * verified link (0021, item 2).
     *
     * @return array<int, list<string|int|null>>
     */
    public function systemChangeRows(AiSystem $aiSystem): array
    {
        $protocol = app(MonitoringProtocol::class);

        return $aiSystem->systemChanges()
            ->with(['riskSubdomains', 'reversals.link.risk', 'reversals.link.mitigation'])
            ->orderBy('change_date')
            ->orderBy('id')
            ->get()
            ->map(fn (SystemChange $change): array => array_map($this->csvCell(...), [
                $protocol->version()['version'],
                $aiSystem->id,
                $aiSystem->name,
                $change->id,
                $change->type->label(),
                $change->description,
                $change->change_date->toDateString(),
                $change->riskSubdomains->map(fn (TaxonomyTerm $term): string => "{$term->code} {$term->name}")->implode(' | '),
                $change->reversals->pluck('link_id')->implode(' | '),
                $change->reversals->map(fn (StatusHistory $reversal): string => "{$reversal->link->risk->name} -> {$reversal->link->mitigation->name}")->implode(' | '),
                implode(' | ', $protocol->unmappedSubdomainCodes($change)),
            ]))
            ->values()
            ->all();
    }

    /**
     * Every adverse event of the system as a CSV row, oldest first, whether
     * it reverted links or not. The unmapped subdomains are those of the
     * event in which the system has no risk registered on the day of the
     * export, not on the day of the event (0019, item 4); the column name
     * says so.
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
                $event->nature->label(),
                $event->description,
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
                'statusChanges.owner',
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

        $systemChanges = $aiSystem->systemChanges()
            ->with(['riskSubdomains', 'reversals'])
            ->orderBy('change_date')
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
            // The changes of the system and the links they reverted (0021).
            'system_changes' => $systemChanges->map(fn (SystemChange $change): array => [
                'id' => $change->id,
                'type' => $change->type->value,
                'description' => $change->description,
                'change_date' => $change->change_date->toDateString(),
                'risk_subdomains' => array_values($change->riskSubdomains->map(fn (TaxonomyTerm $term): string => $term->code)->all()),
                'reverted_link_ids' => array_values($change->reversals->map(fn (StatusHistory $reversal): int => $reversal->link_id)->all()),
            ])->values()->all(),
        ];
    }

    /**
     * Flatten the report into one CSV row per link, joining its evidence into
     * a single cell. The report comes in codes; the enum values go out by
     * their interface label.
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
            $this->label(AiSystemCategory::class, $report['system']['category']),
            $this->label(SystemSourceType::class, $report['system']['source_type']),
            $report['system']['registration_date'],
            $link['id'],
            $this->label(LinkStatus::class, $link['status']),
            implode(' | ', array_map($this->statusChangeCell(...), $link['status_changes'])),
            $this->label(VerificationStatus::class, $link['verification']['status']),
            $link['verification']['last_verified_on'],
            $link['verification']['verified_by'],
            implode(' | ', array_map($this->verificationChangeCell(...), $link['verification']['changes'])),
            $this->label(LifecyclePhase::class, $link['lifecycle_phase']),
            $this->label(CostLevel::class, $link['estimated_cost']),
            $this->label(CostLevel::class, $link['observed_cost']),
            $link['creation_date'],
            $link['next_review_date'],
            $link['risk']['id'],
            $link['risk']['name'],
            $link['risk']['description'],
            $link['risk']['domain']['code'],
            $link['risk']['domain']['name'],
            $link['risk']['subdomain']['code'],
            $link['risk']['subdomain']['name'],
            $this->label(LifecyclePhase::class, $link['risk']['lifecycle_phase']),
            $this->label(UncertaintyLevel::class, $link['risk']['uncertainty_level']),
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
                fn (array $evidence): string => self::text(':type: :description (:date)', [
                    'type' => $this->label(EvidenceType::class, $evidence['type']),
                    'description' => $evidence['description'],
                    'date' => $evidence['registration_date'],
                ]),
                $link['evidence'],
            )),
            $link['replaces_link_id'],
            $link['replaced_by_link_id'],
            implode(' | ', array_map($this->reassessmentCell(...), $link['reassessments'])),
            implode(' | ', array_map($this->adverseEventCell(...), $link['reverting_adverse_events'])),
        ]), $report['links']);
    }

    /**
     * A progress change of a link, for its composite cell, with the role that
     * recorded it, the adverse event that forced it and the reason, when
     * there are.
     *
     * @param  array{date: string, from: string|null, to: string, recorded_by: string|null, reason: string|null, adverse_event_id: int|null}  $change
     */
    protected function statusChangeCell(array $change): string
    {
        $event = $change['adverse_event_id'] === null
            ? null
            : self::text('adverse event #:event', ['event' => (string) $change['adverse_event_id']]);

        return $this->transition(
            $change['date'],
            $this->label(LinkStatus::class, $change['from']),
            (string) $this->label(LinkStatus::class, $change['to']),
            $this->details([$change['recorded_by']], $change['reason'], $event),
        );
    }

    /**
     * A verification change of a link, for its composite cell: its origin,
     * with the event or the change behind a reversal, the role that recorded
     * it and the reason someone wrote. An automatic reversal's reason only
     * restates its origin, so the cell leaves it out; the JSON keeps it. A
     * move from verified to verified is a renewal (0019, item 2), and the
     * cell names it so, by the glossary term, instead of repeating the state.
     *
     * @param  array{date: string, from: string|null, to: string, origin: string, recorded_by: string|null, reason: string|null, adverse_event_id: int|null, system_change_id: int|null}  $change
     */
    protected function verificationChangeCell(array $change): string
    {
        $origin = (string) $this->label(ChangeOrigin::class, $change['origin']);

        if ($change['adverse_event_id'] !== null) {
            $origin = self::text(':origin #:event', ['origin' => $origin, 'event' => (string) $change['adverse_event_id']]);
        } elseif ($change['system_change_id'] !== null) {
            $origin = self::text(':origin, change #:change', ['origin' => $origin, 'change' => (string) $change['system_change_id']]);
        }

        $renewal = $change['from'] === VerificationStatus::Verified->value
            && $change['to'] === VerificationStatus::Verified->value;

        return $this->transition(
            $change['date'],
            $renewal ? null : $this->label(VerificationStatus::class, $change['from']),
            $renewal ? self::text('Renewal') : (string) $this->label(VerificationStatus::class, $change['to']),
            $this->details(
                [$origin, $change['recorded_by']],
                $change['origin'] === ChangeOrigin::Manual->value ? $change['reason'] : null,
            ),
        );
    }

    /**
     * A reassessment of a link, for its composite cell.
     *
     * @param  array{date: string, outcome: string, owner: string, justification: string, cause_status: string, cause: string|null, cause_phase: string|null, reversal_origin: string}  $reassessment
     */
    protected function reassessmentCell(array $reassessment): string
    {
        $cause = array_filter([
            $this->label(CauseStatus::class, $reassessment['cause_status']),
            $this->label(LifecyclePhase::class, $reassessment['cause_phase']),
            $reassessment['cause'],
        ], fn (?string $part): bool => $part !== null);

        return self::text(':date: :outcome by :owner (reversal: :origin; cause: :cause) :justification', [
            'date' => $reassessment['date'],
            'outcome' => (string) $this->label(ReassessmentOutcome::class, $reassessment['outcome']),
            'owner' => $reassessment['owner'],
            'origin' => (string) $this->label(ChangeOrigin::class, $reassessment['reversal_origin']),
            'cause' => implode(', ', $cause),
            'justification' => $reassessment['justification'],
        ]);
    }

    /**
     * An adverse event that reverted a link, for its composite cell. The
     * description stays out, so the cell does not grow too long; the adverse
     * events CSV has it, under the same event number.
     *
     * @param  array{id: int, nature: string, risk_subdomains: list<string>, occurrence_date: string, detected_at: string|null}  $event
     */
    protected function adverseEventCell(array $event): string
    {
        $replace = [
            'id' => (string) $event['id'],
            'nature' => (string) $this->label(AdverseEventNature::class, $event['nature']),
            'subdomains' => implode(', ', $event['risk_subdomains']),
            'occurred' => $event['occurrence_date'],
        ];

        return $event['detected_at'] === null
            ? self::text('#:id :nature [:subdomains], occurred on :occurred', $replace)
            : self::text('#:id :nature [:subdomains], occurred on :occurred, detected on :detected', [...$replace, 'detected' => $event['detected_at']]);
    }

    /**
     * A change from one state to another, with what went with it in
     * parentheses. The first progress entry has no previous state.
     */
    protected function transition(string $date, ?string $from, string $to, string $details): string
    {
        $key = match (true) {
            $from === null && $details === '' => ':date: :to',
            $from === null => ':date: :to (:details)',
            $details === '' => ':date: :from -> :to',
            default => ':date: :from -> :to (:details)',
        };

        return self::text($key, ['date' => $date, 'from' => (string) $from, 'to' => $to, 'details' => $details]);
    }

    /**
     * The parts that go in a change's parentheses: the parts joined by
     * commas, then the note and the reason, each after a semicolon.
     *
     * @param  list<string|null>  $parts
     */
    protected function details(array $parts, ?string $reason, ?string $note = null): string
    {
        $filled = fn (?string $part): bool => $part !== null && $part !== '';

        return implode('; ', array_filter([
            implode(', ', array_filter($parts, $filled)),
            $note,
            $filled($reason) ? self::text('reason: :reason', ['reason' => (string) $reason]) : null,
        ], $filled));
    }

    /**
     * The interface label of an enum value given by its code, as the screens
     * show it (0022, item 4).
     *
     * @param  class-string<AdverseEventNature|AiSystemCategory|CauseStatus|ChangeOrigin|CostLevel|EvidenceType|LifecyclePhase|LinkStatus|ReassessmentOutcome|SystemChangeType|SystemSourceType|UncertaintyLevel|VerificationStatus>  $enum
     */
    protected function label(string $enum, ?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        return $enum::tryFrom($code)?->label() ?? $code;
    }

    /**
     * Translate a line of the CSV, falling back to its key.
     *
     * @param  array<string, string>  $replace
     */
    protected static function text(string $key, array $replace = []): string
    {
        $line = __($key, $replace);

        return is_string($line) ? $line : $key;
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
            // The first dimension (0013): every progress change, from the
            // entry that opened the trail. A role, never a person (0018).
            'status_changes' => $link->statusChanges->map(fn (StatusHistory $change): array => [
                'date' => $change->change_date->toDateString(),
                'from' => $change->previous_status?->value,
                'to' => $change->new_status?->value,
                'recorded_by' => $change->owner?->organizational_role,
                'reason' => $change->trigger_reason,
                // The adverse event that forced the change, if any.
                'adverse_event_id' => $change->adverse_event_id,
            ])->values()->all(),
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
                    'system_change_id' => $change->system_change_id,
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
     * @return array{id: int, nature: string, description: string, risk_subdomains: list<string>, occurrence_date: string, detected_at: string|null, intercepting_link_id: int|null, reverted_link_ids: list<int>}
     */
    protected function adverseEvent(AdverseEvent $event): array
    {
        return [
            'id' => $event->id,
            'nature' => $event->nature->value,
            'description' => $event->description,
            'risk_subdomains' => array_values($event->riskSubdomains->map(fn (TaxonomyTerm $term): string => $term->code)->all()),
            'occurrence_date' => $event->occurrence_date->toDateString(),
            'detected_at' => $event->detected_at?->toDateString(),
            'intercepting_link_id' => $event->intercepting_link_id,
            'reverted_link_ids' => array_values($event->reversals->map(fn (StatusHistory $reversal): int => $reversal->link_id)->all()),
        ];
    }
}
