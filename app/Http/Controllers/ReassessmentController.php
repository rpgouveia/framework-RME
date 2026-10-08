<?php

namespace App\Http\Controllers;

use App\Actions\RecordReassessment;
use App\Actions\RecordStatusChange;
use App\Enums\CauseStatus;
use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\ReassessmentOutcome;
use App\Http\Requests\StoreReassessmentRequest;
use App\Models\Link;
use App\Models\Owner;
use App\Models\Reassessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reassessments of a link (0020): append only, nested under the link. Each
 * one concludes the link's last reversal; RecordReassessment writes it and
 * applies its outcome.
 */
class ReassessmentController extends Controller
{
    public function __construct(
        protected RecordReassessment $recordReassessment,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Link $link): Response
    {
        Gate::authorize('view', $link);

        return Inertia::render('reassessments/index', [
            'link' => $link->load(['risk', 'mitigation']),
            'reassessments' => $link->reassessments()
                ->with(['owner', 'reversal', 'verification'])
                ->latest()
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Link $link): Response|RedirectResponse
    {
        Gate::authorize('update', $link);

        $link->load([
            'risk.aiSystem',
            'mitigation',
            'owner',
            'lastReversal.owner',
            'lastReversal.adverseEvent.riskSubdomains',
            'lastReversal.systemChange',
            'lastReversal.reassessment',
        ]);

        try {
            $reversal = $this->recordReassessment->pendingReversal($link);
        } catch (ValidationException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => collect($exception->errors())->flatten()->first()]);

            return to_route('links.show', $link);
        }

        $problems = $this->recordReassessment->outcomeProblems($link);

        return Inertia::render('reassessments/create', [
            'link' => $link,
            'reversal' => $reversal,
            // Each outcome with why it is not available now, if it is not.
            'outcomes' => array_map(fn (ReassessmentOutcome $outcome): array => [
                'value' => $outcome->value,
                'label' => $outcome->label(),
                'problem' => $problems[$outcome->value],
            ], ReassessmentOutcome::cases()),
            'causeApplies' => $this->recordReassessment->causeApplies($reversal),
            // An adjustment may verify in the same act when the evidence
            // allows it.
            'verificationProblem' => app(RecordStatusChange::class)->verificationProblem($link),
            'owners' => Owner::query()->active()->orderBy('organizational_role')->get(['id', 'organizational_role', 'area']),
            'costLevels' => CostLevel::options(),
            'lifecyclePhases' => LifecyclePhase::options(),
            // Progress moves an adjustment may make: never to cancelled,
            // which is what closing and replacing are for.
            'statuses' => array_values(array_filter(
                $link->status->transitionOptions(),
                fn (array $option): bool => $option['value'] !== LinkStatus::Cancelled->value,
            )),
            'causeStatuses' => [
                ['value' => CauseStatus::Identified->value, 'label' => CauseStatus::Identified->label()],
                ['value' => CauseStatus::NotIdentified->value, 'label' => CauseStatus::NotIdentified->label()],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReassessmentRequest $request, Link $link): RedirectResponse
    {
        Gate::authorize('update', $link);

        $validated = $request->validated();

        $reassessment = $this->recordReassessment->handle($link, [
            'outcome' => $validated['outcome'],
            'owner_id' => (int) $validated['owner_id'],
            'justification' => $validated['justification'],
            'cause_status' => $validated['cause_status'] ?? null,
            'cause' => $validated['cause'] ?? null,
            'cause_phase' => $validated['cause_phase'] ?? null,
            'verify' => $request->boolean('verify'),
            'changes' => $validated['changes'] ?? [],
        ]);

        // Replacing goes on to the new link, for the same risk (0020).
        if ($reassessment->outcome === ReassessmentOutcome::Replace) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Reassessment recorded.').' '.__('Create the link that replaces it.')]);

            return to_route('links.create', ['risk' => $link->risk_id, 'replaces' => $link->id]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reassessment recorded.')]);

        return to_route('reassessments.show', $reassessment);
    }

    /**
     * Display the specified resource.
     */
    public function show(Reassessment $reassessment): Response
    {
        Gate::authorize('view', $reassessment->link);

        $reassessment->load([
            'link.risk.aiSystem',
            'link.mitigation',
            'link.replacedBy.mitigation',
            'reversal.owner',
            'reversal.adverseEvent.riskSubdomains',
            'reversal.systemChange',
            'owner',
            'verification.owner',
        ]);

        // The roles named in an owner change, to show them by name.
        $ownerIds = collect($reassessment->changes ?? [])
            ->where('field', 'owner_id')
            ->flatMap(fn (array $change): array => [$change['before'], $change['after']])
            ->filter()
            ->unique();

        return Inertia::render('reassessments/show', [
            'reassessment' => $reassessment,
            'changedOwners' => Owner::query()->whereIn('id', $ownerIds)->get(['id', 'organizational_role', 'area'])->keyBy('id'),
        ]);
    }
}
