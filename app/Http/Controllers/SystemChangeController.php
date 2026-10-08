<?php

namespace App\Http\Controllers;

use App\Actions\RecordSystemChange;
use App\Enums\SystemChangeType;
use App\Http\Requests\StoreSystemChangeRequest;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\SystemChange;
use App\Support\MonitoringProtocol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Changes of an AI system that trigger reassessment (0021): append only,
 * nested under the system. RecordSystemChange writes each one and reverts
 * the links it reaches.
 */
class SystemChangeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(AiSystem $aiSystem): Response
    {
        Gate::authorize('view', $aiSystem);

        return Inertia::render('system-changes/index', [
            'aiSystem' => $aiSystem,
            'systemChanges' => $aiSystem->systemChanges()
                ->with('riskSubdomains')
                ->withCount('reversals')
                ->latest('change_date')
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(AiSystem $aiSystem, MonitoringProtocol $protocol): Response
    {
        Gate::authorize('update', $aiSystem);

        return Inertia::render('system-changes/create', [
            'aiSystem' => $aiSystem,
            'types' => SystemChangeType::options(),
            'riskDomains' => $protocol->riskSubdomainOptions(),
            // The subdomains of the system's risks come first, as in the
            // event form.
            'expected' => $protocol->expectedRiskSubdomainsFor($aiSystem),
            // The verified links the change may revert, with their risk's
            // subdomain: the confirmation counts them (0021, item 3).
            'verifiedLinks' => Link::query()
                ->verified()
                ->ofSystem($aiSystem->id)
                ->with(['risk:id,name,risk_subdomain_id', 'risk.riskSubdomain:id,code', 'mitigation:id,name'])
                ->orderBy('id')
                ->get()
                ->map(fn (Link $link): array => [
                    'id' => $link->id,
                    'risk' => $link->risk->name,
                    'mitigation' => $link->mitigation->name,
                    'subdomain' => $link->risk->riskSubdomain->code,
                ]),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSystemChangeRequest $request, AiSystem $aiSystem, RecordSystemChange $recordSystemChange): RedirectResponse
    {
        Gate::authorize('update', $aiSystem);

        $systemChange = $recordSystemChange->handle($aiSystem, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('System change recorded.')]);

        return to_route('system-changes.show', $systemChange);
    }

    /**
     * Display the specified resource.
     */
    public function show(SystemChange $systemChange, MonitoringProtocol $protocol): Response
    {
        Gate::authorize('view', $systemChange->aiSystem);

        $systemChange->load([
            'aiSystem',
            'riskSubdomains.parent',
            'reversals.link.risk',
            'reversals.link.mitigation',
            // How far the reassessment of each reverted link went (0020).
            'reversals.reassessment.owner',
        ]);

        return Inertia::render('system-changes/show', [
            'systemChange' => $systemChange,
            'unmappedSubdomainCodes' => $protocol->unmappedSubdomainCodes($systemChange),
        ]);
    }
}
