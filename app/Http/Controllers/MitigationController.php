<?php

namespace App\Http\Controllers;

use App\Enums\SaeriCategory;
use App\Http\Requests\StoreMitigationRequest;
use App\Http\Requests\UpdateMitigationRequest;
use App\Models\Mitigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MitigationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Mitigation::class);

        return Inertia::render('mitigations/index', [
            'mitigations' => Mitigation::query()
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
        Gate::authorize('create', Mitigation::class);

        return Inertia::render('mitigations/create', [
            'saeriCategories' => SaeriCategory::options(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMitigationRequest $request): RedirectResponse
    {
        Gate::authorize('create', Mitigation::class);

        $mitigation = Mitigation::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mitigation registered.')]);

        return to_route('mitigations.show', $mitigation);
    }

    /**
     * Display the specified resource.
     */
    public function show(Mitigation $mitigation): Response
    {
        Gate::authorize('view', $mitigation);

        return Inertia::render('mitigations/show', [
            'mitigation' => $mitigation->load(['links.risk', 'links.owner']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mitigation $mitigation): Response
    {
        Gate::authorize('update', $mitigation);

        return Inertia::render('mitigations/edit', [
            'mitigation' => $mitigation,
            'saeriCategories' => SaeriCategory::options(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMitigationRequest $request, Mitigation $mitigation): RedirectResponse
    {
        Gate::authorize('update', $mitigation);

        $mitigation->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mitigation updated.')]);

        return to_route('mitigations.show', $mitigation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mitigation $mitigation): RedirectResponse
    {
        Gate::authorize('delete', $mitigation);

        if ($mitigation->links()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Delete the mitigation links first.')]);

            return back();
        }

        $mitigation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mitigation deleted.')]);

        return to_route('mitigations.index');
    }
}
