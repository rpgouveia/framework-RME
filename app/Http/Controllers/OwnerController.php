<?php

namespace App\Http\Controllers;

use App\Enums\LinkStatus;
use App\Http\Requests\StoreOwnerRequest;
use App\Http\Requests\UpdateOwnerRequest;
use App\Models\Owner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OwnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Owner::class);

        return Inertia::render('owners/index', [
            // Both counts decide whether an owner can still be deleted; the
            // active ones come first.
            'owners' => Owner::query()
                ->withCount(['links', 'statusHistories'])
                ->orderByRaw('case when deactivated_at is null then 0 else 1 end')
                ->orderBy('organizational_role')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Owner::class);

        return Inertia::render('owners/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOwnerRequest $request): RedirectResponse
    {
        Gate::authorize('create', Owner::class);

        $owner = Owner::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Owner registered.')]);

        return to_route('owners.show', $owner);
    }

    /**
     * Display the specified resource.
     */
    public function show(Owner $owner): Response
    {
        Gate::authorize('view', $owner);

        return Inertia::render('owners/show', [
            'owner' => $owner
                ->load(['links.risk', 'links.mitigation'])
                ->loadCount([
                    'links',
                    'statusHistories',
                    'links as active_links_count' => fn ($query) => $query->whereNot('status', LinkStatus::Cancelled),
                ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Owner $owner): Response
    {
        Gate::authorize('update', $owner);

        return Inertia::render('owners/edit', [
            // Once used, the role and area are locked; the form says so.
            'owner' => $owner->loadCount(['links', 'statusHistories']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOwnerRequest $request, Owner $owner): RedirectResponse
    {
        Gate::authorize('update', $owner);

        $owner->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Owner updated.')]);

        return to_route('owners.show', $owner);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Owner $owner): RedirectResponse
    {
        Gate::authorize('delete', $owner);

        if ($owner->links()->exists() || $owner->statusHistories()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This owner is still referenced by a link.')]);

            return back();
        }

        $owner->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Owner deleted.')]);

        return to_route('owners.index');
    }

    /**
     * Retire a role that no longer exists, so it stops being offered for new
     * links and status changes. A used owner cannot be deleted, so this is
     * how it leaves the forms.
     */
    public function deactivate(Owner $owner): RedirectResponse
    {
        Gate::authorize('deactivate', $owner);

        // Cancelled links are closed, so only the others hold the owner back.
        $activeLinks = $owner->links()->whereNot('status', LinkStatus::Cancelled)->count();

        if ($activeLinks > 0) {
            Inertia::flash('toast', ['type' => 'error', 'message' => trans_choice(
                'This owner is still accountable for :count active link. Reassign it before deactivating.|This owner is still accountable for :count active links. Reassign them before deactivating.',
                $activeLinks,
            )]);

            return back();
        }

        $owner->forceFill(['deactivated_at' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Owner deactivated.')]);

        return to_route('owners.show', $owner);
    }

    /**
     * Bring a deactivated owner back to the forms.
     */
    public function reactivate(Owner $owner): RedirectResponse
    {
        Gate::authorize('reactivate', $owner);

        $owner->forceFill(['deactivated_at' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Owner reactivated.')]);

        return to_route('owners.show', $owner);
    }
}
