<?php

namespace App\Http\Controllers;

use App\Enums\EvidenceType;
use App\Http\Requests\StoreEvidenceRequest;
use App\Http\Requests\UpdateEvidenceRequest;
use App\Models\Evidence;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Evidence is nested under a link: it is always collected for one specific
 * risk/mitigation pair, so it is created and listed through its parent.
 */
class EvidenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Link $link): Response
    {
        Gate::authorize('viewAny', Evidence::class);

        return Inertia::render('evidence/index', [
            'link' => $link->load(['risk', 'mitigation']),
            'evidence' => $link->evidence()->latest()->paginate(15)->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Link $link): Response
    {
        Gate::authorize('create', Evidence::class);

        return Inertia::render('evidence/create', [
            'link' => $link,
            'types' => EvidenceType::options(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEvidenceRequest $request, Link $link): RedirectResponse
    {
        Gate::authorize('create', Evidence::class);

        $link->evidence()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evidence registered.')]);

        return to_route('links.evidence.index', $link);
    }

    /**
     * Display the specified resource.
     */
    public function show(Evidence $evidence): Response
    {
        Gate::authorize('view', $evidence);

        return Inertia::render('evidence/show', [
            'evidence' => $evidence->load('link.risk'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Evidence $evidence): Response
    {
        Gate::authorize('update', $evidence);

        return Inertia::render('evidence/edit', [
            'evidence' => $evidence,
            'types' => EvidenceType::options(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEvidenceRequest $request, Evidence $evidence): RedirectResponse
    {
        Gate::authorize('update', $evidence);

        $evidence->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evidence updated.')]);

        return to_route('evidence.show', $evidence);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Evidence $evidence): RedirectResponse
    {
        Gate::authorize('delete', $evidence);

        $link = $evidence->link;

        $evidence->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evidence deleted.')]);

        return to_route('links.evidence.index', $link);
    }
}
