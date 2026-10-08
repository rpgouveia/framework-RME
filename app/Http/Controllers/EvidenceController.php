<?php

namespace App\Http\Controllers;

use App\Actions\RecordEvidence;
use App\Actions\RecordStatusChange;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\VerificationStatus;
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

        $link->load(['risk.aiSystem', 'mitigation']);

        return Inertia::render('evidence/index', [
            'link' => $link,
            'evidence' => $link->evidence()->latest()->latest('id')->paginate(15)->withQueryString(),
            // A declared link whose evidence now allows it is offered a
            // shortcut to its verification.
            'canVerify' => $link->verification_status === VerificationStatus::Declared
                && app(RecordStatusChange::class)->verificationProblem($link) === null,
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
            'costLevels' => CostLevel::options(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEvidenceRequest $request, Link $link, RecordEvidence $recordEvidence): RedirectResponse
    {
        Gate::authorize('create', Evidence::class);

        $recordEvidence->handle($link, $request->validated());

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
