<?php

namespace App\Http\Controllers;

use App\Actions\RecordStatusChange;
use App\Enums\LinkStatus;
use App\Http\Requests\StoreStatusHistoryRequest;
use App\Models\AdverseEvent;
use App\Models\Link;
use App\Models\Owner;
use App\Models\Reassessment;
use App\Models\StatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
    public function index(Request $request, Link $link): Response
    {
        Gate::authorize('viewAny', StatusHistory::class);

        /*
         * The timeline: status and verification changes with the
         * reassessments in between, as entries of their own (0020), newest
         * first by the moment each was recorded.
         */
        $entries = $link->statusHistories()
            ->with(['owner', 'adverseEvent.riskSubdomains'])
            ->get()
            ->map(fn (StatusHistory $entry): array => ['type' => 'change', 'at' => $entry->created_at, 'id' => $entry->id, 'entry' => $entry])
            ->concat($link->reassessments()->with('owner')->get()->map(
                fn (Reassessment $reassessment): array => ['type' => 'reassessment', 'at' => $reassessment->created_at, 'id' => $reassessment->id, 'entry' => $reassessment],
            ))
            ->sortBy([
                fn (array $a, array $b): int => $b['at'] <=> $a['at'],
                // In the same act, the reassessment above the changes it made.
                fn (array $a, array $b): int => ($a['type'] === 'reassessment' ? 0 : 1) <=> ($b['type'] === 'reassessment' ? 0 : 1),
                fn (array $a, array $b): int => $b['id'] <=> $a['id'],
            ])
            ->values()
            ->map(fn (array $item): array => ['type' => $item['type'], 'entry' => $item['entry']]);

        $page = LengthAwarePaginator::resolveCurrentPage();

        return Inertia::render('status-histories/index', [
            'link' => $link->load(['risk', 'mitigation']),
            'entries' => (new LengthAwarePaginator($entries->forPage($page, 15)->values(), $entries->count(), 15, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]))->withQueryString(),
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
