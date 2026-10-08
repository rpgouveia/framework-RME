<?php

namespace App\Http\Controllers;

use App\Actions\RecordAdverseEvent;
use App\Enums\AdverseEventNature;
use App\Http\Requests\StoreAdverseEventRequest;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\Link;
use App\Support\AiRiskDomains;
use App\Support\MonitoringProtocol;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $nature = AdverseEventNature::tryFrom((string) $request->query('nature'));

        return Inertia::render('adverse-events/index', [
            'adverseEvents' => AdverseEvent::query()
                ->when($domain, fn (Builder $query) => $query->whereHas(
                    'riskSubdomains',
                    fn (Builder $subdomains) => $subdomains->where('parent_id', $domain?->id),
                ))
                ->when($nature, fn (Builder $query) => $query->where('nature', $nature))
                ->with(['aiSystem', 'riskSubdomains'])
                ->withCount('statusHistories')
                ->latest('occurrence_date')
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'filters' => ['domain' => $domain?->code, 'nature' => $nature?->value],
            'natures' => AdverseEventNature::options(),
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
    public function store(StoreAdverseEventRequest $request, RecordAdverseEvent $recordAdverseEvent): RedirectResponse
    {
        Gate::authorize('create', AdverseEvent::class);

        // Recording it reverts the verified links of its subdomains (0019).
        $adverseEvent = $recordAdverseEvent->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Adverse event recorded.')]);

        return to_route('adverse-events.show', $adverseEvent);
    }

    /**
     * Display the specified resource.
     */
    public function show(AdverseEvent $adverseEvent): Response
    {
        Gate::authorize('view', $adverseEvent);

        $adverseEvent->load([
            'aiSystem',
            'riskSubdomains.parent',
            'interceptingLink.risk',
            'interceptingLink.mitigation',
            'reversals.link.risk',
            'reversals.link.mitigation',
            // How far the reassessment of each reverted link went (0020).
            'reversals.reassessment.owner',
            'statusHistories.link.risk',
            'statusHistories.link.mitigation',
            'statusHistories.owner',
        ]);

        return Inertia::render('adverse-events/show', [
            'adverseEvent' => $adverseEvent,
            'detectionDelayDays' => $adverseEvent->detectionDelayDays(),
            // Subdomains with no risk registered for the system: a risk not
            // yet mapped (0019, item 4).
            'unmappedSubdomainCodes' => app(MonitoringProtocol::class)->unmappedSubdomainCodes($adverseEvent),
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
            'natures' => AdverseEventNature::options(),
            // The links still in the chain of each system, with their risk's
            // subdomain: the form counts the ones the event will revert and
            // offers the eligible interceptors of a near miss (0019).
            'linksBySystem' => Link::query()
                ->notCancelled()
                ->with(['risk:id,name,ai_system_id,risk_subdomain_id', 'risk.riskSubdomain:id,code', 'mitigation:id,name'])
                ->orderBy('id')
                ->get()
                ->groupBy('risk.ai_system_id')
                ->map(fn ($links) => $links->map(fn (Link $link): array => [
                    'id' => $link->id,
                    'risk' => $link->risk->name,
                    'mitigation' => $link->mitigation->name,
                    'subdomain' => $link->risk->riskSubdomain->code,
                    'verification_status' => $link->verification_status,
                ])->values()),
            'riskDomains' => $protocol->riskSubdomainOptions(),
            // RF04: the form puts the system's risk profile first when the
            // system changes.
            'expectedRiskSubdomainsBySystem' => $protocol->expectedRiskSubdomainsBySystem($aiSystems),
        ];
    }
}
