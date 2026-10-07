<?php

use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\StatusHistory;
use App\Models\TaxonomyTerm;
use App\Models\User;
use App\Support\AiRiskDomains;
use App\Support\MonitoringProtocol;
use Database\Seeders\AdverseEventSeeder;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * Build a valid payload for the adverse event form.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function adverseEventPayload(array $overrides = []): array
{
    return array_merge([
        'risk_subdomains' => ['1.1'],
        'description' => 'The screening model rejected every applicant over 50',
        'occurrence_date' => '2026-05-20',
        'ai_system_id' => AiSystem::factory()->create()->id,
    ], $overrides);
}

test('guests are redirected to the login page', function () {
    auth()->logout();

    $this->get(route('adverse-events.index'))->assertRedirect(route('login'));
});

test('the index lists the events with their system', function () {
    AdverseEvent::factory(2)->create();

    $response = $this->get(route('adverse-events.index'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('adverse-events/index')
            ->has('adverseEvents.data', 2)
            ->has('adverseEvents.data.0.ai_system')
    );
});

test('an adverse event can be recorded for a system', function () {
    $aiSystem = AiSystem::factory()->create();

    $response = $this->post(route('adverse-events.store'), adverseEventPayload([
        'ai_system_id' => $aiSystem->id,
    ]));

    $adverseEvent = AdverseEvent::sole();

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('adverse-events.show', $adverseEvent));

    expect($adverseEvent->riskSubdomains->pluck('code')->all())->toBe(['1.1'])
        ->and($adverseEvent->ai_system_id)->toBe($aiSystem->id)
        ->and($adverseEvent->occurrence_date->toDateString())->toBe('2026-05-20');
});

test('an adverse event requires an existing system', function () {
    $response = $this->post(route('adverse-events.store'), adverseEventPayload([
        'ai_system_id' => 999,
    ]));

    $response->assertSessionHasErrors('ai_system_id');

    $this->assertDatabaseEmpty('adverse_events');
});

test('an adverse event cannot be dated in the future', function () {
    $response = $this->post(route('adverse-events.store'), adverseEventPayload([
        'occurrence_date' => now()->addWeek()->toDateString(),
    ]));

    $response->assertSessionHasErrors('occurrence_date');

    $this->assertDatabaseEmpty('adverse_events');
});

test('recording an adverse event requires every field', function () {
    $response = $this->post(route('adverse-events.store'), []);

    $response->assertSessionHasErrors([
        'risk_subdomains',
        'description',
        'occurrence_date',
        'ai_system_id',
    ]);

    $this->assertDatabaseEmpty('adverse_events');
});

test('adverse events are append only', function () {
    // They record what happened and explain the status changes they trigger.
    $adverseEvent = AdverseEvent::factory()->create();

    $this->get("/adverse-events/{$adverseEvent->id}/edit")->assertNotFound();
    $this->put("/adverse-events/{$adverseEvent->id}", [])->assertMethodNotAllowed();
    $this->delete("/adverse-events/{$adverseEvent->id}")->assertMethodNotAllowed();

    $this->assertModelExists($adverseEvent);
});

// Classified by the MIT AI risk subdomains, like the risk register.

test('an event can materialize several risk subdomains', function () {
    // A leak caused by an attack touches both privacy and security.
    $this->post(route('adverse-events.store'), adverseEventPayload([
        'risk_subdomains' => ['2.2', '2.1'],
    ]))->assertSessionHasNoErrors();

    $subdomains = AdverseEvent::sole()->riskSubdomains;

    expect($subdomains->pluck('code')->all())->toBe(['2.1', '2.2'])
        ->and($subdomains->every(fn (TaxonomyTerm $term): bool => $term->level === 2))->toBeTrue();
});

test('an event is refused without a valid list of risk subdomains', function (mixed $subdomains, string $field, string $message) {
    $this->post(route('adverse-events.store'), adverseEventPayload([
        'risk_subdomains' => $subdomains,
    ]))->assertSessionHasErrors([$field => $message]);

    $this->assertDatabaseEmpty('adverse_events');
    $this->assertDatabaseEmpty('adverse_event_risk_subdomains');
})->with([
    // There is no "other": an occurrence that touches no subdomain is not an
    // adverse event.
    'empty list' => [[], 'risk_subdomains', 'Selecione ao menos um subdomínio de risco que a ocorrência materializa.'],
    'unknown code' => [['2.1', '9.9'], 'risk_subdomains.1', 'O código 9.9 não é um subdomínio da taxonomia de domínios de risco do MIT.'],
    'a domain (level 1)' => [['2'], 'risk_subdomains.0', 'O código 2 não é um subdomínio da taxonomia de domínios de risco do MIT.'],
    'a Saeri code' => [['3.6'], 'risk_subdomains.0', 'O código 3.6 não é um subdomínio da taxonomia de domínios de risco do MIT.'],
    'repeated code' => [['2.1', '2.1'], 'risk_subdomains.0', 'O subdomínio de risco 2.1 foi selecionado mais de uma vez.'],
    'not a list' => ['2.1', 'risk_subdomains', 'Selecione ao menos um subdomínio de risco que a ocorrência materializa.'],
]);

test('a risk subdomain the protocol does not offer for the system is refused', function () {
    // Stand in for the C3 mapping: this system offers only 7.3. The test
    // holds once the real protocol exists, since validation asks it.
    $this->app->instance(MonitoringProtocol::class, new class extends MonitoringProtocol
    {
        public function riskSubdomainsFor(AiSystem $aiSystem): Collection
        {
            return app(AiRiskDomains::class)->subdomains()->where('code', '7.3')->values();
        }
    });

    $this->post(route('adverse-events.store'), adverseEventPayload([
        'risk_subdomains' => ['7.3', '2.1'],
    ]))->assertSessionHasErrors([
        'risk_subdomains.1' => __('The risk subdomain :code is not offered for this AI system.', ['code' => '2.1']),
    ]);

    $this->post(route('adverse-events.store'), adverseEventPayload([
        'risk_subdomains' => ['7.3'],
    ]))->assertSessionHasNoErrors();

    expect(AdverseEvent::sole()->riskSubdomains->pluck('code')->all())->toBe(['7.3']);
});

test('the create form sends the subdomains each system offers, grouped by domain', function () {
    [$first, $second] = AiSystem::factory(2)->create();

    $this->app->instance(MonitoringProtocol::class, new class extends MonitoringProtocol
    {
        public function riskSubdomainsFor(AiSystem $aiSystem): Collection
        {
            return $aiSystem->name === 'Only robustness'
                ? app(AiRiskDomains::class)->subdomains()->where('code', '7.3')->values()
                : parent::riskSubdomainsFor($aiSystem);
        }
    });
    $second->update(['name' => 'Only robustness']);

    $this->get(route('adverse-events.create'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('adverse-events/create')
            // For now every system offers all seven domains.
            ->has("riskDomainsBySystem.{$first->id}", 7)
            ->has("riskDomainsBySystem.{$first->id}.0", fn (AssertableInertia $domain) => $domain
                ->where('code', '1')
                ->where('name', 'Discriminação e toxicidade')
                ->has('children', 3)
                ->has('children.0', fn (AssertableInertia $subdomain) => $subdomain
                    ->where('code', '1.1')
                    ->has('name')
                    ->where('description', fn (string $description) => str_starts_with($description, 'Unequal treatment'))
                )
            )
            ->has("riskDomainsBySystem.{$first->id}.6.children", 6)
            // A domain with nothing to offer is left out.
            ->has("riskDomainsBySystem.{$second->id}", 1)
            ->where("riskDomainsBySystem.{$second->id}.0.code", '7')
            ->where("riskDomainsBySystem.{$second->id}.0.children.0.code", '7.3')
            ->has("riskDomainsBySystem.{$second->id}.0.children", 1)
    );
});

test('the screens show the risk subdomains of the event', function () {
    $adverseEvent = AdverseEvent::factory()->materializing(['2.1', '2.2'])->create();

    $this->get(route('adverse-events.show', $adverseEvent))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('adverseEvent.risk_subdomains', 2)
            ->where('adverseEvent.risk_subdomains.0.code', '2.1')
            ->where('adverseEvent.risk_subdomains.0.parent.code', '2')
            ->where('adverseEvent.risk_subdomains.1.code', '2.2')
    );

    $this->get(route('adverse-events.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('adverseEvents.data.0.risk_subdomains.0.code', '2.1')
            ->where('adverseEvents.data.0.risk_subdomains.0.name', 'Comprometimento da privacidade por obtenção, vazamento ou inferência de informações sensíveis')
            ->has('adverseEvents.data.0.risk_subdomains', 2)
            ->has('riskDomains', 7)
    );
});

test('the index can be narrowed to a risk domain', function () {
    $privacy = AdverseEvent::factory()->materializing(['2.1'])->create();
    $both = AdverseEvent::factory()->materializing(['1.3', '7.3'])->create();
    AdverseEvent::factory()->materializing(['5.1'])->create();

    $ids = fn (string $domain): array => collect(
        $this->get(route('adverse-events.index', ['domain' => $domain]))->viewData('page')['props']['adverseEvents']['data'],
    )->pluck('id')->sort()->values()->all();

    expect($ids('2'))->toBe([$privacy->id])
        ->and($ids('7'))->toBe([$both->id])
        ->and($ids('1'))->toBe([$both->id])
        ->and($ids('6'))->toBe([])
        // An unknown domain is ignored.
        ->and($ids('9'))->toHaveCount(3);

    $this->get(route('adverse-events.index', ['domain' => '7']))->assertInertia(
        fn (AssertableInertia $page) => $page->where('filters.domain', '7')
    );
});

test('the factory and the seeder give every event one or two subdomains', function () {
    $this->seed(AdverseEventSeeder::class);
    AdverseEvent::factory(5)->create();

    AdverseEvent::query()->withCount('riskSubdomains')->get()->each(
        fn (AdverseEvent $event) => expect($event->risk_subdomains_count)->toBeBetween(1, 2),
    );
});

test('today is a valid occurrence date', function () {
    $this->post(route('adverse-events.store'), adverseEventPayload([
        'occurrence_date' => today()->toDateString(),
    ]))->assertSessionHasNoErrors();
});

test('the detail page loads the status changes the event triggered', function () {
    $adverseEvent = AdverseEvent::factory()->create();
    StatusHistory::factory(2)->create(['adverse_event_id' => $adverseEvent->id]);

    $this->get(route('adverse-events.show', $adverseEvent))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('adverse-events/show')
            ->has('adverseEvent.status_histories', 2)
            ->has('adverseEvent.status_histories.0.link.risk')
            ->has('adverseEvent.status_histories.0.link.mitigation')
            ->has('adverseEvent.status_histories.0.owner')
    );
});
