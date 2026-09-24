<?php

namespace App\Http\Controllers;

use App\Enums\AdverseEventType;
use App\Http\Requests\StoreAdverseEventRequest;
use App\Http\Requests\UpdateAdverseEventRequest;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Adverse events observed on a system in production.
 *
 * They hang off an AI system the same way risks do, so they are managed as a
 * top level resource with the system picked on the form.
 */
class AdverseEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', AdverseEvent::class);

        return Inertia::render('adverse-events/index', [
            'adverseEvents' => AdverseEvent::query()
                ->with('aiSystem')
                ->withCount('statusHistories')
                ->latest('occurrence_date')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', AdverseEvent::class);

        return Inertia::render('adverse-events/create', $this->formOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdverseEventRequest $request): RedirectResponse
    {
        Gate::authorize('create', AdverseEvent::class);

        $adverseEvent = AdverseEvent::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Adverse event recorded.')]);

        return to_route('adverse-events.show', $adverseEvent);
    }

    /**
     * Display the specified resource.
     */
    public function show(AdverseEvent $adverseEvent): Response
    {
        Gate::authorize('view', $adverseEvent);

        return Inertia::render('adverse-events/show', [
            'adverseEvent' => $adverseEvent->load([
                'aiSystem',
                'statusHistories.link.risk',
                'statusHistories.owner',
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AdverseEvent $adverseEvent): Response
    {
        Gate::authorize('update', $adverseEvent);

        return Inertia::render('adverse-events/edit', [
            'adverseEvent' => $adverseEvent,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdverseEventRequest $request, AdverseEvent $adverseEvent): RedirectResponse
    {
        Gate::authorize('update', $adverseEvent);

        $adverseEvent->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Adverse event updated.')]);

        return to_route('adverse-events.show', $adverseEvent);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AdverseEvent $adverseEvent): RedirectResponse
    {
        Gate::authorize('delete', $adverseEvent);

        if ($adverseEvent->statusHistories()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Detach the status changes it triggered first.')]);

            return back();
        }

        $adverseEvent->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Adverse event deleted.')]);

        return to_route('adverse-events.index');
    }

    /**
     * Get the select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'aiSystems' => AiSystem::query()->orderBy('name')->get(['id', 'name']),
            'eventTypes' => AdverseEventType::options(),
        ];
    }
}
