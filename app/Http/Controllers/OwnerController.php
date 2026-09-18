<?php

namespace App\Http\Controllers;

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
            'owners' => Owner::query()
                ->withCount('links')
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
            'owner' => $owner->load(['links.risk', 'links.mitigation']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Owner $owner): Response
    {
        Gate::authorize('update', $owner);

        return Inertia::render('owners/edit', [
            'owner' => $owner,
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
}
