<?php

use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Models\Evidence;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * Build a valid payload for the link form.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function linkPayload(array $overrides = []): array
{
    return array_merge([
        'lifecycle_phase' => LifecyclePhase::Deployment->value,
        'status' => LinkStatus::Planned->value,
        'estimated_cost' => CostLevel::High->value,
        'observed_cost' => null,
        'creation_date' => '2026-01-10',
        'risk_id' => Risk::factory()->create()->id,
        'mitigation_id' => Mitigation::factory()->create()->id,
        'owner_id' => Owner::factory()->create()->id,
    ], $overrides);
}

test('guests are redirected to the login page', function () {
    auth()->logout();

    $this->get(route('links.index'))->assertRedirect(route('login'));
});

test('the index lists the links with their risk, mitigation and owner', function () {
    Link::factory(2)->create();

    $response = $this->get(route('links.index'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('links/index')
            ->has('links.data', 2)
            ->has('links.data.0.risk')
            ->has('links.data.0.mitigation')
            ->has('links.data.0.owner')
    );
});

test('a link can be created', function () {
    config(['rme.review.interval_days' => 30]);

    $response = $this->post(route('links.store'), linkPayload());

    $link = Link::sole();

    $response->assertSessionHasNoErrors()->assertRedirect(route('links.show', $link));

    expect($link->status)->toBe(LinkStatus::Planned)
        ->and($link->estimated_cost)->toBe(CostLevel::High)
        ->and($link->observed_cost)->toBeNull()
        ->and($link->next_review_date->toDateString())->toBe('2026-02-09');
});

test('the review date posted on creation is ignored', function () {
    config(['rme.review.interval_days' => 30]);

    $this->post(route('links.store'), linkPayload(['next_review_date' => '2030-12-31']));

    expect(Link::sole()->next_review_date->toDateString())->toBe('2026-02-09');
});

test('the review date cannot fall before the creation date', function () {
    $link = Link::factory()->create();

    $response = $this->put(route('links.update', $link), linkPayload([
        'creation_date' => '2026-07-10',
        'next_review_date' => '2026-01-10',
        'risk_id' => $link->risk_id,
        'mitigation_id' => $link->mitigation_id,
        'owner_id' => $link->owner_id,
    ]));

    $response->assertSessionHasErrors([
        'next_review_date' => 'The next review date field must be a date after or equal to creation date.',
    ]);
});

test('an estimated cost outside the scale is rejected', function () {
    $response = $this->post(route('links.store'), linkPayload(['estimated_cost' => 'astronomical']));

    $response->assertSessionHasErrors('estimated_cost');

    $this->assertDatabaseEmpty('links');
});

test('a link can be updated', function () {
    $link = Link::factory()->create();

    $response = $this->put(route('links.update', $link), linkPayload([
        'status' => LinkStatus::Implemented->value,
        'observed_cost' => CostLevel::Low->value,
        'next_review_date' => '2026-07-10',
        'risk_id' => $link->risk_id,
        'mitigation_id' => $link->mitigation_id,
        'owner_id' => $link->owner_id,
    ]));

    $response->assertSessionHasNoErrors()->assertRedirect(route('links.show', $link));

    expect($link->refresh()->status)->toBe(LinkStatus::Implemented)
        ->and($link->observed_cost)->toBe(CostLevel::Low);
});

test('a link without evidence or history can be deleted', function () {
    $link = Link::factory()->create();

    $this->delete(route('links.destroy', $link))->assertRedirect(route('links.index'));

    $this->assertModelMissing($link);
});

test('a link with evidence cannot be deleted', function () {
    $evidence = Evidence::factory()->create();

    $this->from(route('links.show', $evidence->link))
        ->delete(route('links.destroy', $evidence->link))
        ->assertRedirect(route('links.show', $evidence->link));

    $this->assertModelExists($evidence->link);
});
