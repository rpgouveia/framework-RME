<?php

use App\Enums\LinkStatus;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\Evidence;
use App\Models\Link;
use App\Models\Risk;
use App\Models\User;
use App\Support\MonitoringProtocol;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('with nothing registered the dashboard shows the starting point', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('totals.aiSystems', 0)
            ->where('unlinkedRisks.count', 0)
            ->where('reviews.dueCount', 0)
            ->has('systems', 0)
    );
});

test('the dashboard shows where the chain is incomplete', function () {
    $this->actingAs(User::factory()->create());
    $this->travelTo('2026-06-15 10:00');

    $aiSystem = AiSystem::factory()->create();

    // A risk with no link at all, and one whose only link was cancelled:
    // both still need a mitigation.
    $unlinked = Risk::factory()->for($aiSystem)->create();
    $cancelledOnly = Risk::factory()->for($aiSystem)->create();
    Link::factory()->verified()->for($cancelledOnly)->create([
        'status' => LinkStatus::Cancelled,
        'next_review_date' => '2026-01-01',
    ]);

    $linked = Risk::factory()->for($aiSystem)->create();
    $overdue = Link::factory()->verified()->for($linked)->create([
        'status' => LinkStatus::InProgress,
        'creation_date' => '2026-01-01',
        'next_review_date' => '2026-06-10',
    ]);
    $dueToday = Link::factory()->verified()->for($linked)->create([
        'status' => LinkStatus::Monitoring,
        'creation_date' => '2026-01-02',
        'next_review_date' => '2026-06-15',
    ]);
    $upcoming = Link::factory()->verified()->for($linked)->create([
        'status' => LinkStatus::Planned,
        'creation_date' => '2026-01-03',
        'next_review_date' => '2026-06-20',
    ]);
    $later = Link::factory()->verified()->for($linked)->create([
        'status' => LinkStatus::Implemented,
        'creation_date' => '2026-01-04',
        'next_review_date' => '2026-09-01',
    ]);
    foreach ([$overdue, $dueToday, $later] as $withEvidence) {
        Evidence::factory()->for($withEvidence)->create();
    }

    $recent = AdverseEvent::factory()->for($aiSystem)->materializing(['2.1', '2.2'])->create(['occurrence_date' => '2026-06-01']);
    $edge = AdverseEvent::factory()->for($aiSystem)->create(['occurrence_date' => '2026-05-16']);
    AdverseEvent::factory()->for($aiSystem)->create(['occurrence_date' => '2026-04-01']);

    $this->get(route('dashboard'))->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('dashboard')
            // Cancelled links are left out of every count.
            ->where('totals.aiSystems', 1)
            ->where('totals.risks', 3)
            ->where('totals.links', 4)
            ->where('unlinkedRisks.count', 2)
            ->where('unlinkedRisks.items', fn ($items) => collect($items)->pluck('id')->sort()->values()->all() === [$unlinked->id, $cancelledOnly->id])
            ->has('unlinkedRisks.items.0.ai_system.name')
            ->where('linksByStatus', [
                ['status' => 'planned', 'count' => 1],
                ['status' => 'in_progress', 'count' => 1],
                ['status' => 'implemented', 'count' => 1],
                ['status' => 'monitoring', 'count' => 1],
                ['status' => 'suspended', 'count' => 0],
            ])
            ->where('linksWithoutEvidence.count', 1)
            ->where('linksWithoutEvidence.items.0.id', $upcoming->id)
            ->has('linksWithoutEvidence.items.0.risk.name')
            // Due on or before today, like the daily reassessment check.
            ->where('reviews.dueCount', 2)
            ->where('reviews.due.0.id', $overdue->id)
            ->where('reviews.due.1.id', $dueToday->id)
            ->where('reviews.upcomingCount', 1)
            ->where('reviews.upcoming.0.id', $upcoming->id)
            // Thirty days back is still in; the April event is out.
            ->where('recentEvents.count', 2)
            ->where('recentEvents.items.0.id', $recent->id)
            ->where('recentEvents.items.1.id', $edge->id)
            // Told by the risk subdomains they materialize.
            ->where('recentEvents.items.0.risk_subdomains.0.code', '2.1')
            ->where('recentEvents.items.0.risk_subdomains.1.code', '2.2')
            ->has('recentEvents.items.0.ai_system.name')
            ->where('systems.0.risks_count', 3)
            ->where('systems.0.unlinked_risks_count', 2)
            ->where('systems.0.links_count', 4)
            ->where('systems.0.due_reviews_count', 2)
            ->where('systems.0.recent_events_count', 2)
    );
});

test('the summary counts each system on its own', function () {
    $this->actingAs(User::factory()->create());

    [$first, $second] = AiSystem::factory(2)->create();
    Risk::factory()->for($first)->create();
    Link::factory()->for(Risk::factory()->for($second))->create(['status' => LinkStatus::Planned]);

    $this->get(route('dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('systems', function ($systems) use ($first, $second): bool {
            $rows = collect($systems)->keyBy('id');

            return $rows[$first->id]['unlinked_risks_count'] === 1
                && $rows[$first->id]['links_count'] === 0
                && $rows[$second->id]['unlinked_risks_count'] === 0
                && $rows[$second->id]['links_count'] === 1;
        })
    );
});

// The C3 protocol: windows from its file, unacceptable systems apart.

test('the dashboard windows follow the protocol file', function () {
    $this->actingAs(User::factory()->create());
    $this->travelTo('2026-06-15 10:00');

    $definition = json_decode((string) file_get_contents(MonitoringProtocol::path()), true);
    $definition['dashboard'] = ['recent_event_days' => 7, 'upcoming_review_days' => 3];
    $path = tempnam(sys_get_temp_dir(), 'protocol');
    file_put_contents($path, json_encode($definition));
    $this->app->instance(MonitoringProtocol::class, new MonitoringProtocol($path));

    $aiSystem = AiSystem::factory()->highRisk()->create();
    $inside = AdverseEvent::factory()->for($aiSystem)->create(['occurrence_date' => '2026-06-08']);
    AdverseEvent::factory()->for($aiSystem)->create(['occurrence_date' => '2026-06-07']);
    $risk = Risk::factory()->for($aiSystem)->create();
    $soon = Link::factory()->verified()->for($risk)->create(['status' => LinkStatus::Planned, 'next_review_date' => '2026-06-18']);
    Link::factory()->verified()->for($risk)->create(['status' => LinkStatus::Planned, 'next_review_date' => '2026-06-19']);

    $this->get(route('dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('recentEvents.days', 7)
            ->where('recentEvents.count', 1)
            ->where('recentEvents.items.0.id', $inside->id)
            ->where('reviews.upcomingDays', 3)
            ->where('reviews.upcomingCount', 1)
            ->where('reviews.upcoming.0.id', $soon->id)
    );
});

test('unacceptable systems stay out of the review indicators and are flagged', function () {
    $this->actingAs(User::factory()->create());
    $this->travelTo('2026-06-15 10:00');

    $operable = AiSystem::factory()->highRisk()->create(['name' => 'A operável']);
    $prohibited = AiSystem::factory()->unacceptable()->create(['name' => 'B proibido']);

    $due = Link::factory()->verified()->for(Risk::factory()->for($operable))->create(['status' => LinkStatus::Planned, 'next_review_date' => '2026-06-01']);
    // Created while the system was unacceptable: no date at all.
    Link::factory()->for(Risk::factory()->for($prohibited))->create(['status' => LinkStatus::Planned]);
    // Dated before the system was reclassified as unacceptable.
    Link::factory()->verified()->for(Risk::factory()->for($prohibited))->create(['status' => LinkStatus::Planned, 'next_review_date' => '2026-06-01']);
    Link::factory()->verified()->for(Risk::factory()->for($prohibited))->create(['status' => LinkStatus::Planned, 'next_review_date' => '2026-06-20']);

    $this->get(route('dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('reviews.dueCount', 1)
            ->where('reviews.due.0.id', $due->id)
            ->where('reviews.upcomingCount', 0)
            ->where('systems.0.id', $operable->id)
            ->where('systems.0.due_reviews_count', 1)
            ->where('systems.1.id', $prohibited->id)
            ->where('systems.1.category', 'unacceptable')
            ->where('systems.1.due_reviews_count', 0)
            // Still counted as links of the chain.
            ->where('systems.1.links_count', 3)
            ->where('unacceptableSystems', [['id' => $prohibited->id, 'name' => 'B proibido']])
    );
});

test('with no unacceptable system the dashboard shows no alert', function () {
    $this->actingAs(User::factory()->create());
    AiSystem::factory()->highRisk()->create();

    $this->get(route('dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('unacceptableSystems', [])
    );
});
