<?php

namespace App\Http\Controllers;

use App\Enums\LinkStatus;
use App\Http\Requests\StoreStatusHistoryRequest;
use App\Http\Requests\UpdateStatusHistoryRequest;
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
                ->with(['owner', 'adverseEvent'])
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
            'link' => $link,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStatusHistoryRequest $request, Link $link): RedirectResponse
    {
        Gate::authorize('create', StatusHistory::class);

        $statusHistory = $link->statusHistories()->create($request->validated());

        $link->update(['status' => $statusHistory->new_status]);

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
            'statusHistory' => $statusHistory->load(['link.risk', 'owner', 'adverseEvent']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StatusHistory $statusHistory): Response
    {
        Gate::authorize('update', $statusHistory);

        return Inertia::render('status-histories/edit', [
            'statusHistory' => $statusHistory,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStatusHistoryRequest $request, StatusHistory $statusHistory): RedirectResponse
    {
        Gate::authorize('update', $statusHistory);

        $statusHistory->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Status change updated.')]);

        return to_route('status-histories.show', $statusHistory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StatusHistory $statusHistory): RedirectResponse
    {
        Gate::authorize('delete', $statusHistory);

        $link = $statusHistory->link;

        $statusHistory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Status change deleted.')]);

        return to_route('links.status-histories.index', $link);
    }

    /**
     * Get the select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'owners' => Owner::query()->orderBy('organizational_role')->get(),
            'statuses' => LinkStatus::options(),
            'adverseEvents' => AdverseEvent::query()
                ->with('aiSystem:id,name')
                ->latest('occurrence_date')
                ->get(['id', 'event_type', 'description', 'occurrence_date', 'ai_system_id']),
        ];
    }
}
