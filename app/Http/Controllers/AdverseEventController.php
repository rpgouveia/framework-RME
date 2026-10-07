<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdverseEventRequest;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Support\AiRiskDomains;
use App\Support\MonitoringProtocol;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function index(Request $request, AiRiskDomains $riskDomains): Response
    {
        Gate::authorize('viewAny', AdverseEvent::class);

        // An unknown domain is ignored rather than emptying the list.
        $domain = $riskDomains->domains()->firstWhere('code', (string) $request->query('domain'));

        return Inertia::render('adverse-events/index', [
            'adverseEvents' => AdverseEvent::query()
                ->when($domain, fn (Builder $query) => $query->whereHas(
                    'riskSubdomains',
                    fn (Builder $subdomains) => $subdomains->where('parent_id', $domain?->id),
                ))
                ->with(['aiSystem', 'riskSubdomains'])
                ->withCount('statusHistories')
                ->latest('occurrence_date')
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'filters' => ['domain' => $domain?->code],
            'riskDomains' => $riskDomains->tree(),
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
    public function store(StoreAdverseEventRequest $request, AiRiskDomains $riskDomains): RedirectResponse
    {
        Gate::authorize('create', AdverseEvent::class);

        $adverseEvent = DB::transaction(function () use ($request, $riskDomains): AdverseEvent {
            $adverseEvent = AdverseEvent::create($request->safe()->except('risk_subdomains'));

            $adverseEvent->riskSubdomains()->attach(
                $riskDomains->subdomains()->whereIn('code', $request->validated('risk_subdomains'))->pluck('id'),
            );

            return $adverseEvent;
        });

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
                'riskSubdomains.parent',
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
        $aiSystems = AiSystem::query()->orderBy('name')->get(['id', 'name', 'application_domain']);
        $protocol = app(MonitoringProtocol::class);

        return [
            'aiSystems' => $aiSystems,
            'riskDomains' => $protocol->riskSubdomainOptions(),
            // RF04: the form puts the system's risk profile first when the
            // system changes.
            'expectedRiskSubdomainsBySystem' => $protocol->expectedRiskSubdomainsBySystem($aiSystems),
        ];
    }
}
