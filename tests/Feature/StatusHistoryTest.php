<?php

use App\Actions\RecordStatusChange;
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

test('the new status must differ from the current one', function () {
    $link = Link::factory()->create(['status' => LinkStatus::Planned]);

    $response = $this->post(route('links.status-histories.store', $link), [
        'new_status' => LinkStatus::Planned->value,
        'change_date' => '2026-05-20',
        'owner_id' => Owner::factory()->create()->id,
    ]);

    $response->assertSessionHasErrors([
        'new_status' => __('The new status must differ from the current one.'),
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

test('the previous status comes from the link, not from the request', function () {
    $link = Link::factory()->create(['status' => LinkStatus::InProgress]);

    $response = $this->post(route('links.status-histories.store', $link), [
        'previous_status' => LinkStatus::Suspended->value,
        'new_status' => LinkStatus::Implemented->value,
        'change_date' => '2026-05-20',
        'owner_id' => Owner::factory()->create()->id,
    ]);

    $response->assertSessionHasNoErrors();

    expect(StatusHistory::sole()->previous_status)->toBe(LinkStatus::InProgress);
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

test('the status trail is append only', function () {
    $statusHistory = StatusHistory::factory()->create();

    $this->get("/status-histories/{$statusHistory->id}/edit")->assertNotFound();
    $this->put("/status-histories/{$statusHistory->id}", [])->assertMethodNotAllowed();
    $this->delete("/status-histories/{$statusHistory->id}")->assertMethodNotAllowed();

    expect($statusHistory->fresh())->not->toBeNull();
});

/**
 * Build a valid status change for the given link.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function statusChange(Link $link, array $overrides = []): array
{
    return array_merge([
        'change_date' => '2026-05-20',
        'owner_id' => $link->owner_id,
    ], $overrides);
}

test('cancelling a link requires a reason', function () {
    $link = Link::factory()->create(['status' => LinkStatus::InProgress]);

    $response = $this->post(route('links.status-histories.store', $link), statusChange($link, [
        'new_status' => LinkStatus::Cancelled->value,
    ]));

    $response->assertSessionHasErrors([
        'trigger_reason' => __('Say why the link is being cancelled.'),
    ]);

    expect($link->refresh()->status)->toBe(LinkStatus::InProgress);
    $this->assertDatabaseEmpty('status_histories');
});

test('other changes still take an optional reason', function () {
    $link = Link::factory()->create(['status' => LinkStatus::Planned]);

    $this->post(route('links.status-histories.store', $link), statusChange($link, [
        'new_status' => LinkStatus::InProgress->value,
    ]))->assertSessionHasNoErrors();
});

test('cancelling a link records the reason and drops it from the review queue', function () {
    $link = Link::factory()->dueForReview()->create(['status' => LinkStatus::InProgress]);

    expect(Link::dueForReview()->pluck('id'))->toContain($link->id);

    $response = $this->post(route('links.status-histories.store', $link), statusChange($link, [
        'new_status' => LinkStatus::Cancelled->value,
        'trigger_reason' => 'The system was decommissioned',
    ]));

    $response->assertSessionHasNoErrors();

    $history = StatusHistory::sole();

    expect($history->previous_status)->toBe(LinkStatus::InProgress)
        ->and($history->new_status)->toBe(LinkStatus::Cancelled)
        ->and($history->trigger_reason)->toBe('The system was decommissioned')
        ->and($link->refresh()->status)->toBe(LinkStatus::Cancelled)
        ->and(Link::dueForReview()->pluck('id'))->not->toContain($link->id);
});

test('a cancelled link can be reactivated', function () {
    $link = Link::factory()->create(['status' => LinkStatus::Cancelled]);

    $response = $this->post(route('links.status-histories.store', $link), statusChange($link, [
        'new_status' => LinkStatus::Planned->value,
    ]));

    $response->assertSessionHasNoErrors();

    expect($link->refresh()->status)->toBe(LinkStatus::Planned)
        ->and(StatusHistory::sole()->previous_status)->toBe(LinkStatus::Cancelled);
});

test('the change form names the link it belongs to', function () {
    $link = Link::factory()->create();

    $this->get(route('links.status-histories.create', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('status-histories/create')
            ->has('link.risk')
            ->has('link.mitigation')
            ->has('statuses')
    );
});

test('a cancelled link cannot move anywhere but planned', function (LinkStatus $status) {
    $link = Link::factory()->create(['status' => LinkStatus::Cancelled]);

    $response = $this->post(route('links.status-histories.store', $link), statusChange($link, [
        'new_status' => $status->value,
        'trigger_reason' => 'Trying to skip the reactivation',
    ]));

    $response->assertSessionHasErrors([
        'new_status' => __('A cancelled link can only be reactivated, going back to planned.'),
    ]);

    expect($link->refresh()->status)->toBe(LinkStatus::Cancelled);
    $this->assertDatabaseEmpty('status_histories');
})->with([
    LinkStatus::InProgress,
    LinkStatus::Implemented,
    LinkStatus::Monitoring,
    LinkStatus::Suspended,
]);

test('the change form offers only the moves the link allows', function () {
    $cancelled = Link::factory()->create(['status' => LinkStatus::Cancelled]);
    $planned = Link::factory()->create(['status' => LinkStatus::Planned]);

    $this->get(route('links.status-histories.create', $cancelled))->assertInertia(
        fn (AssertableInertia $page) => $page->where('statuses', [
            ['value' => 'planned', 'label' => LinkStatus::Planned->label()],
        ])
    );

    $this->get(route('links.status-histories.create', $planned))->assertInertia(
        fn (AssertableInertia $page) => $page->has('statuses', count(LinkStatus::cases()) - 1)
    );
});

test('the action refuses a move the rule forbids', function () {
    $link = Link::factory()->create(['status' => LinkStatus::Cancelled]);

    expect(fn () => app(RecordStatusChange::class)->handle($link, LinkStatus::Implemented, [
        'change_date' => '2026-05-20',
        'owner_id' => $link->owner_id,
    ]))->toThrow(InvalidArgumentException::class);

    $this->assertDatabaseEmpty('status_histories');
});

test('an inactive owner is neither offered nor accepted for a status change', function () {
    $link = Link::factory()->create(['status' => LinkStatus::Planned]);
    $inactive = Owner::factory()->inactive()->create();

    $this->get(route('links.status-histories.create', $link))->assertInertia(
        fn (AssertableInertia $page) => $page->where('owners', fn ($owners) => ! collect($owners)->contains('id', $inactive->id))
    );

    $this->post(route('links.status-histories.store', $link), statusChange($link, [
        'new_status' => LinkStatus::InProgress->value,
        'owner_id' => $inactive->id,
    ]))->assertSessionHasErrors(['owner_id' => __('Choose an active owner.')]);

    $this->assertDatabaseEmpty('status_histories');
});
