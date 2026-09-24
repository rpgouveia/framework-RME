<?php

use App\Enums\LinkStatus;
use App\Models\AdverseEvent;
use App\Models\Link;
use App\Models\Owner;
use App\Models\StatusHistory;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests are redirected to the login page', function () {
    $link = Link::factory()->create();

    auth()->logout();

    $this->get(route('links.status-histories.index', $link))->assertRedirect(route('login'));
});

test('the index lists only the history of its link', function () {
    $link = Link::factory()->create();
    StatusHistory::factory(2)->for($link)->create();
    StatusHistory::factory()->create();

    $response = $this->get(route('links.status-histories.index', $link));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('status-histories/index')
            ->has('statusHistories.data', 2)
            ->has('statusHistories.data.0.owner')
    );
});

test('recording a change moves the link to its new status', function () {
    $link = Link::factory()->create(['status' => LinkStatus::Planned]);
    $owner = Owner::factory()->create();

    $response = $this->post(route('links.status-histories.store', $link), [
        'previous_status' => LinkStatus::Planned->value,
        'new_status' => LinkStatus::Implemented->value,
        'change_date' => '2026-05-20',
        'owner_id' => $owner->id,
    ]);

    $statusHistory = StatusHistory::sole();

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('links.status-histories.index', $link));

    expect($statusHistory->link_id)->toBe($link->id)
        ->and($statusHistory->new_status)->toBe(LinkStatus::Implemented)
        ->and($link->refresh()->status)->toBe(LinkStatus::Implemented);
});

test('the new status must differ from the previous one', function () {
    $link = Link::factory()->create(['status' => LinkStatus::Planned]);

    $response = $this->post(route('links.status-histories.store', $link), [
        'previous_status' => LinkStatus::Planned->value,
        'new_status' => LinkStatus::Planned->value,
        'change_date' => '2026-05-20',
        'owner_id' => Owner::factory()->create()->id,
    ]);

    $response->assertSessionHasErrors([
        'new_status' => 'The new status field and previous status must be different.',
    ]);

    $this->assertDatabaseEmpty('status_histories');
    expect($link->refresh()->status)->toBe(LinkStatus::Planned);
});

test('recording a change requires the new status, date and owner', function () {
    $link = Link::factory()->create();

    $response = $this->post(route('links.status-histories.store', $link), []);

    $response->assertSessionHasErrors([
        'new_status',
        'change_date',
        'owner_id',
    ]);

    $this->assertDatabaseEmpty('status_histories');
});

test('the first entry of a trail may omit the previous status', function () {
    $link = Link::factory()->create(['status' => LinkStatus::Planned]);

    $response = $this->post(route('links.status-histories.store', $link), [
        'new_status' => LinkStatus::InProgress->value,
        'change_date' => '2026-05-20',
        'owner_id' => Owner::factory()->create()->id,
    ]);

    $response->assertSessionHasNoErrors();

    expect(StatusHistory::sole()->previous_status)->toBeNull()
        ->and($link->refresh()->status)->toBe(LinkStatus::InProgress);
});

test('a change can name the adverse event that forced it', function () {
    $link = Link::factory()->create(['status' => LinkStatus::Implemented]);
    $adverseEvent = AdverseEvent::factory()->create();

    $response = $this->post(route('links.status-histories.store', $link), [
        'previous_status' => LinkStatus::Implemented->value,
        'new_status' => LinkStatus::Suspended->value,
        'trigger_reason' => 'The audit found the control was never enforced',
        'change_date' => '2026-05-20',
        'owner_id' => Owner::factory()->create()->id,
        'adverse_event_id' => $adverseEvent->id,
    ]);

    $response->assertSessionHasNoErrors();

    $statusHistory = StatusHistory::sole();

    expect($statusHistory->adverse_event_id)->toBe($adverseEvent->id)
        ->and($statusHistory->trigger_reason)->toBe('The audit found the control was never enforced')
        ->and($statusHistory->adverseEvent->is($adverseEvent))->toBeTrue();
});

test('a change cannot name an adverse event that does not exist', function () {
    $link = Link::factory()->create();

    $response = $this->post(route('links.status-histories.store', $link), [
        'previous_status' => LinkStatus::Planned->value,
        'new_status' => LinkStatus::Suspended->value,
        'change_date' => '2026-05-20',
        'owner_id' => Owner::factory()->create()->id,
        'adverse_event_id' => 999,
    ]);

    $response->assertSessionHasErrors('adverse_event_id');

    $this->assertDatabaseEmpty('status_histories');
});

test('a status change can be deleted', function () {
    $statusHistory = StatusHistory::factory()->create();

    $this->delete(route('status-histories.destroy', $statusHistory))
        ->assertRedirect(route('links.status-histories.index', $statusHistory->link_id));

    $this->assertModelMissing($statusHistory);
});
