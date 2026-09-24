<?php

use App\Enums\AdverseEventType;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\StatusHistory;
use App\Models\User;
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

test('an adverse event can be updated', function () {
    $adverseEvent = AdverseEvent::factory()->create();

    $response = $this->put(route('adverse-events.update', $adverseEvent), adverseEventPayload([
        'event_type' => AdverseEventType::DataBreach->value,
        'description' => 'Updated description',
        'ai_system_id' => $adverseEvent->ai_system_id,
    ]));

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('adverse-events.show', $adverseEvent));

    expect($adverseEvent->refresh()->event_type)->toBe(AdverseEventType::DataBreach)
        ->and($adverseEvent->description)->toBe('Updated description');
});

test('an adverse event that triggered no status change can be deleted', function () {
    $adverseEvent = AdverseEvent::factory()->create();

    $this->delete(route('adverse-events.destroy', $adverseEvent))
        ->assertRedirect(route('adverse-events.index'));

    $this->assertModelMissing($adverseEvent);
});

test('an adverse event that triggered a status change cannot be deleted', function () {
    $statusHistory = StatusHistory::factory()->triggeredByAdverseEvent()->create();
    $adverseEvent = $statusHistory->adverseEvent;

    $this->from(route('adverse-events.show', $adverseEvent))
        ->delete(route('adverse-events.destroy', $adverseEvent))
        ->assertRedirect(route('adverse-events.show', $adverseEvent));

    $this->assertModelExists($adverseEvent);
});
