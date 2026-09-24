<?php

namespace App\Http\Controllers;

use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Http\Requests\StoreLinkRequest;
use App\Http\Requests\UpdateLinkRequest;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Link::class);

        return Inertia::render('links/index', [
            'links' => Link::query()
                ->with(['risk.aiSystem', 'mitigation', 'owner'])
                ->withCount(['evidence', 'statusHistories'])
                ->orderBy('next_review_date')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Link::class);

        return Inertia::render('links/create', $this->formOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLinkRequest $request): RedirectResponse
    {
        Gate::authorize('create', Link::class);

        $link = Link::create([
            ...$request->validated(),
            'next_review_date' => $request->date('creation_date')
                ?->addDays(Config::integer('rme.review.interval_days')),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link created.')]);

        return to_route('links.show', $link);
    }

    /**
     * Display the specified resource.
     */
    public function show(Link $link): Response
    {
        Gate::authorize('view', $link);

        return Inertia::render('links/show', [
            'link' => $link->load([
                'risk.aiSystem',
                'mitigation',
                'owner',
                'evidence',
                'statusHistories.owner',
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Link $link): Response
    {
        Gate::authorize('update', $link);

        return Inertia::render('links/edit', [
            'link' => $link,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLinkRequest $request, Link $link): RedirectResponse
    {
        Gate::authorize('update', $link);

        $link->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link updated.')]);

        return to_route('links.show', $link);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Link $link): RedirectResponse
    {
        Gate::authorize('delete', $link);

        if ($link->evidence()->exists() || $link->statusHistories()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Delete the link evidence and history first.')]);

            return back();
        }

        $link->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Link deleted.')]);

        return to_route('links.index');
    }

    /**
     * Get the select options shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'risks' => Risk::query()->with('aiSystem')->orderBy('description')->get(),
            'mitigations' => Mitigation::query()->orderBy('description')->get(['id', 'description']),
            'owners' => Owner::query()->orderBy('organizational_role')->get(),
            'lifecyclePhases' => LifecyclePhase::options(),
            'statuses' => LinkStatus::options(),
            'costLevels' => CostLevel::options(),
        ];
    }
}
