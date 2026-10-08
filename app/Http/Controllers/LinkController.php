<?php

namespace App\Http\Controllers;

use App\Actions\CreateLink;
use App\Actions\RecordStatusChange;
use App\Enums\ChangeOrigin;
use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Http\Requests\StoreLinkRequest;
use App\Http\Requests\UpdateLinkRequest;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use App\Models\TaxonomyTerm;
use App\Support\MonitoringProtocol;
use App\Support\SaeriTaxonomy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LinkController extends Controller
{
    /** The verification filters of the list: the pending lists of Tela 3. */
    public const VERIFICATION_FILTERS = ['awaiting_verification', 'awaiting_reassessment', 'verified'];

    /**
     * Display a listing of the resource, optionally narrowed by verification:
     * awaiting the first verification, awaiting reassessment (reverted), or
     * verified. None of them shows cancelled links; the first verification
     * also leaves out links of unacceptable systems, which cannot be
     * verified, while the reassessment list keeps those a reclassification
     * reverted (0019).
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Link::class);

        $verification = in_array($request->query('verification'), self::VERIFICATION_FILTERS, true)
            ? (string) $request->query('verification')
            : null;
        // Within the reassessment list, the origin of the last reversal
        // (0019): manual, review due, adverse event or reclassification.
        $origin = $verification === 'awaiting_reassessment'
            ? ChangeOrigin::tryFrom((string) $request->query('origin'))
            : null;

        return Inertia::render('links/index', [
            'filters' => ['verification' => $verification, 'origin' => $origin?->value],
            'links' => Link::query()
                ->when($verification === 'awaiting_verification', fn (Builder $query) => $query->awaitingVerification())
                ->when($verification === 'awaiting_reassessment' && $origin === null, fn (Builder $query) => $query->awaitingReassessment())
                ->when($origin !== null, fn (Builder $query) => $query->revertedBy($origin ?? ChangeOrigin::Manual))
                ->when($verification === 'verified', fn (Builder $query) => $query->verified())
                // How long each has waited shows in the reassessment list,
                // which starts with the oldest (0020, item 9).
                ->when($verification === 'awaiting_reassessment', fn (Builder $query) => $query->longestAwaitingFirst())
                ->with(['risk.aiSystem', 'mitigation', 'owner', 'lastReversal'])
                ->withCount(['evidence', 'statusHistories'])
                // Links with no review date (unacceptable systems) last, the
                // same on every database.
                ->orderByRaw('case when next_review_date is null then 1 else 0 end')
                ->orderBy('next_review_date')
                ->orderBy('id')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Link::class);

        // After a reassessment chose to replace a link (0020), the form comes
        // with `?risk=` and `?replaces=`; a link that cannot be replaced is
        // ignored.
        $replaced = Link::query()->with(['risk', 'mitigation'])->find((int) $request->query('replaces'));
        $replacing = $replaced !== null && StoreLinkRequest::replacementProblem($replaced, $replaced->risk_id) === null
            ? $replaced->only(['id', 'risk_id']) + ['risk' => $replaced->risk->name, 'mitigation' => $replaced->mitigation->name]
            : null;

        return Inertia::render('links/create', [
            ...$this->formOptions(),
            'replacing' => $replacing,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLinkRequest $request, CreateLink $createLink): RedirectResponse
    {
        Gate::authorize('create', Link::class);

        $link = $createLink->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link created.')]);

        return to_route('links.show', $link);
    }

    /**
     * Display the specified resource.
     */
    public function show(Link $link): Response
    {
        Gate::authorize('view', $link);

        $link->load([
            'risk.aiSystem',
            'mitigation',
            'owner',
            'observedCostEvidence',
            'lastVerification.owner',
            'lastReversal.owner',
            'lastReversal.adverseEvent.riskSubdomains',
            'lastReversal.systemChange',
            'lastReversal.reassessment',
            'reassessments.owner',
            'reassessments.reversal',
            'replaces.mitigation',
            'replacedBy.mitigation',
        ])->loadCount(['evidence', 'statusHistories']);

        return Inertia::render('links/show', [
            'link' => $link,
            // A reversal awaits its reassessment (0020).
            'awaitingReassessment' => Link::query()->whereKey($link->id)->awaitingReassessment()->exists(),
            'verification' => [
                // Why the link cannot be verified (or renewed) now, shown
                // next to the disabled button; null when it can.
                'problem' => app(RecordStatusChange::class)->verificationProblem($link),
                // Who may verify or revert: active roles, the link's owner
                // first in the dialog.
                'owners' => Owner::query()->active()->orderBy('organizational_role')->get(['id', 'organizational_role', 'area']),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Link $link): Response
    {
        Gate::authorize('update', $link);

        // Only the follow-up fields are editable; the pair is shown read only.
        return Inertia::render('links/edit', [
            'link' => $link->load(['risk.aiSystem', 'mitigation', 'observedCostEvidence']),
            // Active owners, plus the current one even if retired, so the
            // link can keep it (marked as inactive on the form).
            'owners' => Owner::query()
                ->where(fn ($query) => $query->active()->orWhere('id', $link->owner_id))
                ->orderBy('organizational_role')
                ->get(['id', 'organizational_role', 'area', 'deactivated_at']),
            'lifecyclePhases' => LifecyclePhase::options(),
            'costLevels' => CostLevel::options(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLinkRequest $request, Link $link): RedirectResponse
    {
        Gate::authorize('update', $link);

        $link->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link updated.')]);

        return to_route('links.show', $link);
    }

    /**
     * Get what the create form needs to offer only valid links.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            // Each risk carries the mitigations it already has, so the form
            // never offers a pair that R-6 would refuse, and its MIT
            // subdomain, so the form can recommend what treats it.
            'risks' => Risk::query()
                ->with(['aiSystem:id,name,category', 'links:id,risk_id,mitigation_id', 'riskSubdomain.parent'])
                ->orderBy('name')
                ->get(['id', 'name', 'ai_system_id', 'risk_subdomain_id'])
                ->map(fn (Risk $risk): array => [
                    'id' => $risk->id,
                    'name' => $risk->name,
                    // The tier sets the review interval and the unacceptable
                    // alert.
                    'ai_system' => $risk->aiSystem->only(['id', 'name', 'category']),
                    'domain' => $risk->riskSubdomain->parent?->only(['code', 'name']),
                    'subdomain' => $risk->riskSubdomain->only(['code', 'name']),
                    'linked_mitigation_ids' => $risk->links->pluck('mitigation_id')->all(),
                ]),
            // The catalogue fields the form shows next to the choice (RNF03),
            // with the Saeri category derived from the subcategory.
            'mitigations' => Mitigation::query()
                ->with(['saeriSubcategory.parent', 'targetRiskSubdomains'])
                ->orderBy('name')
                ->get()
                ->map(fn (Mitigation $mitigation): array => [
                    'id' => $mitigation->id,
                    'name' => $mitigation->name,
                    'category' => $mitigation->saeriSubcategory->parent?->code,
                    'subcategory' => $mitigation->saeriSubcategory->only(['code', 'name']),
                    'suggested_cost' => $mitigation->suggested_cost,
                    'uncertainty_level' => $mitigation->uncertainty_level,
                    'estimate_source' => $mitigation->estimate_source,
                    'target_risk_subdomains' => $mitigation->targetRiskSubdomains
                        ->map(fn (TaxonomyTerm $term): array => $term->only(['code', 'name']))
                        ->all(),
                ]),
            // Retired roles take no new links.
            'owners' => Owner::query()->active()->orderBy('organizational_role')->get(['id', 'organizational_role', 'area']),
            'saeriCategories' => app(SaeriTaxonomy::class)->tree(),
            'lifecyclePhases' => LifecyclePhase::options(),
            'costLevels' => CostLevel::options(),
            // R-7: days to the first review for each tier, null when none.
            'reviewIntervals' => app(MonitoringProtocol::class)->reviewIntervals(),
        ];
    }
}
