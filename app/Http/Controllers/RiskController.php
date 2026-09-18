<?php

namespace App\Http\Controllers;

use App\Enums\LifecyclePhase;
use App\Enums\RiskCategory;
use App\Enums\UncertaintyLevel;
use App\Http\Requests\StoreRiskRequest;
use App\Http\Requests\UpdateRiskRequest;
use App\Models\AiSystem;
use App\Models\Risk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RiskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Risk::class);

        return Inertia::render('risks/index', [
            'risks' => Risk::query()
                ->with('aiSystem')
                ->withCount('links')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Risk::class);

        return Inertia::render('risks/create', $this->formOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRiskRequest $request): RedirectResponse
    {
        Gate::authorize('create', Risk::class);

        $risk = Risk::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Risk registered.')]);

        return to_route('risks.show', $risk);
    }

    /**
     * Display the specified resource.
     */
    public function show(Risk $risk): Response
    {
        Gate::authorize('view', $risk);

        return Inertia::render('risks/show', [
            'risk' => $risk->load(['aiSystem', 'links.mitigation', 'links.owner']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Risk $risk): Response
    {
        Gate::authorize('update', $risk);

        return Inertia::render('risks/edit', [
            'risk' => $risk,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRiskRequest $request, Risk $risk): RedirectResponse
    {
        Gate::authorize('update', $risk);

        $risk->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Risk updated.')]);

        return to_route('risks.show', $risk);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Risk $risk): RedirectResponse
    {
        Gate::authorize('delete', $risk);

        if ($risk->links()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Delete the risk links first.')]);

            return back();
        }

        $risk->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Risk deleted.')]);

        return to_route('risks.index');
    }

    /**
     * Get the select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'aiSystems' => AiSystem::query()->orderBy('name')->get(['id', 'name']),
            'categories' => RiskCategory::options(),
            'lifecyclePhases' => LifecyclePhase::options(),
            'uncertaintyLevels' => UncertaintyLevel::options(),
        ];
    }
}
