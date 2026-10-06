<?php

use App\Enums\AdverseEventType;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\StatusHistory;
use App\Models\User;
use App\Support\MonitoringProtocol;
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
        'event_type' => AdverseEventType::BiasedOutcome->value,
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

    expect($adverseEvent->event_type)->toBe(AdverseEventType::BiasedOutcome)
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
        'event_type',
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

test('an event type the protocol does not allow for the system is refused', function () {
    // Stand in for the C3 mapping: this system accepts only malfunctions. The
    // test holds once the real protocol exists, since validation asks it.
    $this->app->instance(MonitoringProtocol::class, new class extends MonitoringProtocol
    {
        public function eventTypesFor(AiSystem $aiSystem): array
        {
            return [AdverseEventType::Malfunction];
        }
    });

    $this->post(route('adverse-events.store'), adverseEventPayload([
        'event_type' => AdverseEventType::DataBreach->value,
    ]))->assertSessionHasErrors([
        'event_type' => __('This event type does not apply to this AI system.'),
    ]);

    $this->post(route('adverse-events.store'), adverseEventPayload([
        'event_type' => AdverseEventType::Malfunction->value,
    ]))->assertSessionHasNoErrors();

    expect(AdverseEvent::sole()->event_type)->toBe(AdverseEventType::Malfunction);
});

test('the create form sends the event types each system accepts', function () {
    [$first, $second] = AiSystem::factory(2)->create();

    $this->app->instance(MonitoringProtocol::class, new class extends MonitoringProtocol
    {
        public function eventTypesFor(AiSystem $aiSystem): array
        {
            return $aiSystem->name === 'Only outages'
                ? [AdverseEventType::ServiceDisruption]
                : parent::eventTypesFor($aiSystem);
        }
    });
    $second->update(['name' => 'Only outages']);

    $this->get(route('adverse-events.create'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('adverse-events/create')
            ->has("eventTypesBySystem.{$first->id}", count(AdverseEventType::cases()))
            ->where("eventTypesBySystem.{$second->id}.0.value", AdverseEventType::ServiceDisruption->value)
            ->has("eventTypesBySystem.{$second->id}", 1)
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
