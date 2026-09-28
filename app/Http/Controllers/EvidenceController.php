<?php

namespace App\Http\Controllers;

use App\Enums\EvidenceType;
use App\Http\Requests\StoreEvidenceRequest;
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
            'link' => $link->load(['risk', 'mitigation']),
            'types' => EvidenceType::options(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEvidenceRequest $request, Link $link): RedirectResponse
    {
        Gate::authorize('create', Evidence::class);

        $link->evidence()->create([
            ...$request->validated(),
            'registration_date' => today(),
        ]);

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
            'evidence' => $evidence->load(['link.risk', 'link.mitigation']),
        ]);
    }
}
