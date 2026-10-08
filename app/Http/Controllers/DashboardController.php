<?php

namespace App\Http\Controllers;

use App\Enums\AiSystemCategory;
use App\Enums\ChangeOrigin;
use App\Enums\LinkStatus;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use App\Support\MonitoringProtocol;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The status dashboard: where the traceability chain is still incomplete,
 * along the framework's cycle (register, link, evidence, monitor, reassess).
 *
 * Cancelled links are closed, so they owe nothing and stay out of every
 * pending count. Links awaiting reassessment are split by what reverted
 * them (0019), and adverse events in subdomains with no risk registered for
 * the system show up as risks not yet mapped.
 */
class DashboardController extends Controller
{
    /** How many items each short list shows. */
    protected const LIST_SIZE = 5;

    public function __invoke(MonitoringProtocol $protocol): Response
    {
        // The windows come from the C3 protocol file.
        $recentDays = $protocol->recentEventDays();
        $upcomingDays = $protocol->upcomingReviewDays();
        $today = today();
        $recentSince = $today->subDays($recentDays);
        $upcomingUntil = $today->addDays($upcomingDays);

        $unlinkedRisks = Risk::query()->whereDoesntHave('links', fn (Builder $query) => $query->notCancelled());
        $linksWithoutEvidence = Link::query()->notCancelled()->doesntHave('evidence');
        $recentEvents = AdverseEvent::query()->where('occurrence_date', '>=', $recentSince);
        // Only monitorable links count: the same rule as the daily command.
        // Overdue ones are reverted every day (0019), so the dashboard shows
        // what falls due from today on.
        $upcomingReviews = Link::query()
            ->monitorable()
            ->where('next_review_date', '>=', $today)
            ->where('next_review_date', '<=', $upcomingUntil);

        $statusCounts = Link::query()
            ->notCancelled()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('dashboard', [
            'totals' => [
                'aiSystems' => AiSystem::count(),
                'risks' => Risk::count(),
                'links' => Link::query()->notCancelled()->count(),
                'mitigations' => Mitigation::count(),
                'owners' => Owner::query()->active()->count(),
            ],
            'unlinkedRisks' => [
                'count' => (clone $unlinkedRisks)->count(),
                'items' => $unlinkedRisks->with('aiSystem:id,name')->oldest()->oldest('id')->limit(self::LIST_SIZE)->get(),
            ],
            // Every progress status, even with no links, so the bars line up.
            'linksByStatus' => collect(LinkStatus::cases())
                ->reject(fn (LinkStatus $status): bool => $status === LinkStatus::Cancelled)
                ->map(fn (LinkStatus $status): array => [
                    'status' => $status->value,
                    'count' => (int) ($statusCounts[$status->value] ?? 0),
                ])
                ->values(),
            'linksWithoutEvidence' => [
                'count' => (clone $linksWithoutEvidence)->count(),
                'items' => $linksWithoutEvidence->with(['risk:id,name', 'mitigation:id,name'])
                    ->oldest('creation_date')->oldest('id')->limit(self::LIST_SIZE)->get(),
            ],
            'recentEvents' => [
                'days' => $recentDays,
                'count' => (clone $recentEvents)->count(),
                'items' => $recentEvents->with(['aiSystem:id,name', 'riskSubdomains'])
                    ->latest('occurrence_date')->latest('id')->limit(self::LIST_SIZE)->get(),
            ],
            // Verification (0018): the pending lists of Tela 3, with the same
            // rules as the filters of the link list.
            'verification' => [
                'awaitingVerification' => Link::query()->awaitingVerification()->count(),
                'awaitingReassessment' => Link::query()->awaitingReassessment()->count(),
                // Split by the origin of the last reversal (0019, item 11).
                'awaitingReassessmentByOrigin' => collect([
                    ChangeOrigin::ReviewDue,
                    ChangeOrigin::AdverseEvent,
                    ChangeOrigin::SystemReclassification,
                    ChangeOrigin::ModelVersion,
                    ChangeOrigin::DataChange,
                    ChangeOrigin::Manual,
                ])->map(fn (ChangeOrigin $origin): array => [
                    'origin' => $origin->value,
                    'count' => Link::query()->revertedBy($origin)->count(),
                ])->values(),
                'verified' => Link::query()->verified()->count(),
                // The links waiting longest for their reassessment (0020).
                'longestAwaiting' => Link::query()->awaitingReassessment()->longestAwaitingFirst()
                    ->with(['risk:id,name', 'mitigation:id,name', 'lastReversal'])
                    ->limit(self::LIST_SIZE)
                    ->get(),
            ],
            // Events in subdomains where the system has no risk (0019, item 4).
            'unmappedRisks' => $protocol->unmappedRisks(),
            'reviews' => [
                'upcomingDays' => $upcomingDays,
                'upcomingCount' => (clone $upcomingReviews)->count(),
                'upcoming' => $upcomingReviews->with(['risk:id,name', 'mitigation:id,name'])
                    ->oldest('next_review_date')->oldest('id')->limit(self::LIST_SIZE)->get(),
            ],
            'systems' => AiSystem::query()
                ->withCount([
                    'risks',
                    'risks as unlinked_risks_count' => fn (Builder $query) => $query->whereDoesntHave(
                        'links',
                        fn (Builder $links) => $links->scopes('notCancelled'),
                    ),
                    // Through the risks, so the link scopes are applied by name.
                    'links as links_count' => fn (Builder $query) => $query->scopes('notCancelled'),
                    'links as awaiting_reassessment_count' => fn (Builder $query) => $query->scopes('awaitingReassessment'),
                    'adverseEvents as recent_events_count' => fn (Builder $query) => $query->where('occurrence_date', '>=', $recentSince),
                ])
                ->orderBy('name')
                ->get(['id', 'name', 'application_domain', 'category']),
            'unacceptableSystems' => AiSystem::query()
                ->where('category', AiSystemCategory::Unacceptable)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
