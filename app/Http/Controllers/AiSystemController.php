<?php

namespace App\Http\Controllers;

use App\Enums\AiSystemCategory;
use App\Enums\SystemSourceType;
use App\Http\Requests\StoreAiSystemRequest;
use App\Http\Requests\UpdateAiSystemRequest;
use App\Models\AiSystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AiSystemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', AiSystem::class);

        return Inertia::render('ai-systems/index', [
            'aiSystems' => AiSystem::query()
                ->withCount('risks')
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
        Gate::authorize('create', AiSystem::class);

        return Inertia::render('ai-systems/create', [
            'sourceTypes' => SystemSourceType::options(),
            'categories' => AiSystemCategory::options(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAiSystemRequest $request): RedirectResponse
    {
        Gate::authorize('create', AiSystem::class);

        $aiSystem = AiSystem::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI system registered.')]);

        return to_route('ai-systems.show', $aiSystem);
    }

    /**
     * Display the specified resource.
     */
    public function show(AiSystem $aiSystem): Response
    {
        Gate::authorize('view', $aiSystem);

        return Inertia::render('ai-systems/show', [
            'aiSystem' => $aiSystem->load(['risks' => fn ($query) => $query->withCount('links'),]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AiSystem $aiSystem): Response
    {
        Gate::authorize('update', $aiSystem);

        return Inertia::render('ai-systems/edit', [
            'aiSystem' => $aiSystem,
            'sourceTypes' => SystemSourceType::options(),
            'categories' => AiSystemCategory::options(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAiSystemRequest $request, AiSystem $aiSystem): RedirectResponse
    {
        Gate::authorize('update', $aiSystem);

        $aiSystem->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI system updated.')]);

        return to_route('ai-systems.show', $aiSystem);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AiSystem $aiSystem): RedirectResponse
    {
        Gate::authorize('delete', $aiSystem);

        if ($aiSystem->risks()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Delete the system risks first.')]);

            return back();
        }

        $aiSystem->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('AI system deleted.')]);

        return to_route('ai-systems.index');
    }
}
