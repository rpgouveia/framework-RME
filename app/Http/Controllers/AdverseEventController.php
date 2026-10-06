<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdverseEventRequest;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Support\MonitoringProtocol;
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
                'statusHistories.link.mitigation',
                'statusHistories.owner',
            ]),
        ]);
    }

    /**
     * Get the select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        $aiSystems = AiSystem::query()->orderBy('name')->get(['id', 'name']);
        $protocol = app(MonitoringProtocol::class);

        return [
            'aiSystems' => $aiSystems,
            // RF04: the form swaps the type options when the system changes.
            'eventTypesBySystem' => $aiSystems->mapWithKeys(
                fn (AiSystem $aiSystem): array => [$aiSystem->id => $protocol->eventTypeOptionsFor($aiSystem)],
            ),
        ];
    }
}
