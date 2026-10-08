<?php

namespace App\Http\Controllers;

use App\Actions\RecordStatusChange;
use App\Enums\LinkStatus;
use App\Http\Requests\StoreStatusHistoryRequest;
use App\Models\AdverseEvent;
use App\Models\Link;
use App\Models\Owner;
use App\Models\StatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The audit trail of status changes on a link, nested under its parent link.
 *
 * Recording an entry also moves the link to its new status, so the trail and
 * the link itself can never disagree.
 */
class StatusHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Link $link): Response
    {
        Gate::authorize('viewAny', StatusHistory::class);

        return Inertia::render('status-histories/index', [
            'link' => $link->load(['risk', 'mitigation']),
            'statusHistories' => $link->statusHistories()
                ->with(['owner', 'adverseEvent.riskSubdomains'])
                ->latest('change_date')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Link $link): Response
    {
        Gate::authorize('create', StatusHistory::class);

        return Inertia::render('status-histories/create', [
            'link' => $link->load(['risk', 'mitigation']),
            // Only the moves the link's current status allows.
            'statuses' => $link->status->transitionOptions(),
            // Retired roles record no new changes.
            'owners' => Owner::query()->active()->orderBy('organizational_role')->get(),
            'adverseEvents' => AdverseEvent::query()
                ->with(['aiSystem:id,name', 'riskSubdomains'])
                ->latest('occurrence_date')
                ->get(['id', 'description', 'occurrence_date', 'ai_system_id']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStatusHistoryRequest $request, Link $link, RecordStatusChange $recordStatusChange): RedirectResponse
    {
        Gate::authorize('create', StatusHistory::class);

        $recordStatusChange->handle(
            $link,
            $request->enum('new_status', LinkStatus::class),
            $request->safe()->except('new_status'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Status change recorded.')]);

        return to_route('links.status-histories.index', $link);
    }

    /**
     * Display the specified resource.
     */
    public function show(StatusHistory $statusHistory): Response
    {
        Gate::authorize('view', $statusHistory);

        return Inertia::render('status-histories/show', [
            'statusHistory' => $statusHistory->load(['link.risk', 'link.mitigation', 'owner', 'adverseEvent.aiSystem', 'adverseEvent.riskSubdomains.parent']),
            // A verification needs no reason: it rests on evidence (0018).
            'supportingEvidence' => $statusHistory->supportingEvidence(),
        ]);
    }
}
