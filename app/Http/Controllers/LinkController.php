<?php

namespace App\Http\Controllers;

use App\Actions\CreateLink;
use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Http\Requests\StoreLinkRequest;
use App\Http\Requests\UpdateLinkRequest;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use App\Models\TaxonomyTerm;
use App\Support\SaeriTaxonomy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Link::class);

        return Inertia::render('links/index', [
            'links' => Link::query()
                ->with(['risk.aiSystem', 'mitigation', 'owner'])
                ->withCount(['evidence', 'statusHistories'])
                ->orderBy('next_review_date')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Link::class);

        return Inertia::render('links/create', $this->formOptions());
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

        return Inertia::render('links/show', [
            'link' => $link
                ->load(['risk.aiSystem', 'mitigation', 'owner'])
                ->loadCount(['evidence', 'statusHistories']),
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
            'link' => $link->load(['risk.aiSystem', 'mitigation']),
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
            // never offers a pair that R-6 would refuse.
            'risks' => Risk::query()
                ->with(['aiSystem:id,name', 'links:id,risk_id,mitigation_id'])
                ->orderBy('name')
                ->get(['id', 'name', 'ai_system_id'])
                ->map(fn (Risk $risk): array => [
                    'id' => $risk->id,
                    'name' => $risk->name,
                    'ai_system' => $risk->aiSystem->only(['id', 'name']),
                    'linked_mitigation_ids' => $risk->links->pluck('mitigation_id')->all(),
                ]),
            // The catalogue fields the form shows next to the choice (RNF03),
            // with the Saeri category derived from the subcategory.
            'mitigations' => Mitigation::query()
                ->with('saeriSubcategory.parent')
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
                ]),
            // Retired roles take no new links.
            'owners' => Owner::query()->active()->orderBy('organizational_role')->get(['id', 'organizational_role', 'area']),
            'saeriCategories' => app(SaeriTaxonomy::class)->categories()->map(fn (TaxonomyTerm $term): array => [
                'code' => $term->code,
                'name' => $term->name,
                'children' => $term->children->map->only(['code', 'name'])->values(),
            ]),
            'lifecyclePhases' => LifecyclePhase::options(),
            'costLevels' => CostLevel::options(),
            'reviewIntervalDays' => Config::integer('rme.review.interval_days'),
        ];
    }
}
