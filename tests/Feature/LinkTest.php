<?php

use App\Actions\CreateLink;
use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Models\Evidence;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
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
        'estimated_cost' => CostLevel::High->value,
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
    $this->travelTo('2026-01-10 09:00');

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
    $this->travelTo('2026-01-10 09:00');

    $this->post(route('links.store'), linkPayload(['next_review_date' => '2030-12-31']));

    expect(Link::sole()->next_review_date->toDateString())->toBe('2026-02-09');
});

test('the server sets the status and dates of a new link', function () {
    config(['rme.review.interval_days' => 30]);
    $this->travelTo('2026-01-10 09:00');

    $this->post(route('links.store'), linkPayload([
        'status' => LinkStatus::Implemented->value,
        'creation_date' => '2020-05-01',
        'observed_cost' => CostLevel::Low->value,
    ]))->assertSessionHasNoErrors();

    $link = Link::sole();

    expect($link->status)->toBe(LinkStatus::Planned)
        ->and($link->creation_date->toDateString())->toBe('2026-01-10')
        ->and($link->next_review_date->toDateString())->toBe('2026-02-09')
        ->and($link->observed_cost)->toBeNull();
});

test('the create form offers only pairs that can still be linked', function () {
    config(['rme.review.interval_days' => 30]);
    $existing = Link::factory()->create();

    $this->get(route('links.create'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('links/create')
            ->where('risks.0.linked_mitigation_ids', [$existing->mitigation_id])
            ->has('risks.0.ai_system.name')
            ->has('mitigations.0', fn (AssertableInertia $mitigation) => $mitigation
                ->hasAll(['id', 'name', 'category', 'subcategory', 'suggested_cost', 'uncertainty_level', 'estimate_source'])
            )
            ->has('saeriCategories', 4)
            ->has('saeriCategories.0.children', 7)
            ->where('reviewIntervalDays', 30)
    );
});

test('the create form lists each mitigation once and each risk with exactly its links', function () {
    [$first, $second, $third, $unlinked] = Mitigation::factory(4)->create();
    $linkedRisk = Risk::factory()->create();
    $otherRisk = Risk::factory()->create();
    $freeRisk = Risk::factory()->create();

    Link::factory()->for($linkedRisk)->for($first)->create();
    Link::factory()->for($linkedRisk)->for($second)->create();
    Link::factory()->for($otherRisk)->for($third)->create();

    $page = $this->get(route('links.create'))->viewData('page')['props'];

    $mitigationIds = collect($page['mitigations'])->pluck('id');

    expect($mitigationIds->sort()->values()->all())
        ->toBe(Mitigation::query()->orderBy('id')->pluck('id')->all());

    $linkedIds = collect($page['risks'])->mapWithKeys(
        fn (array $risk): array => [$risk['id'] => collect($risk['linked_mitigation_ids'])->sort()->values()->all()],
    );

    expect($linkedIds[$linkedRisk->id])->toBe(collect([$first->id, $second->id])->sort()->values()->all())
        ->and($linkedIds[$otherRisk->id])->toBe([$third->id])
        ->and($linkedIds[$freeRisk->id])->toBe([]);
});

test('an inactive owner is neither offered nor accepted for a new link', function () {
    $inactive = Owner::factory()->inactive()->create();
    $active = Owner::factory()->create();

    $this->get(route('links.create'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('owners', fn ($owners) => collect($owners)->pluck('id')->all() === [$active->id])
    );

    $this->post(route('links.store'), linkPayload(['owner_id' => $inactive->id]))
        ->assertSessionHasErrors(['owner_id' => __('Choose an active owner.')]);

    $this->assertDatabaseEmpty('links');
});

test('a link can keep its owner after the owner is deactivated', function () {
    $link = Link::factory()->create();
    $link->owner->forceFill(['deactivated_at' => now()])->save();

    $this->get(route('links.edit', $link))->assertInertia(
        fn (AssertableInertia $page) => $page->where('owners', fn ($owners) => collect($owners)->contains('id', $link->owner_id))
    );

    $this->put(route('links.update', $link), [
        'owner_id' => $link->owner_id,
        'lifecycle_phase' => $link->lifecycle_phase->value,
        'estimated_cost' => $link->estimated_cost->value,
    ])->assertSessionHasNoErrors();
});

test('a link cannot move to an inactive owner', function () {
    $link = Link::factory()->create();
    $inactive = Owner::factory()->inactive()->create();

    $this->put(route('links.update', $link), [
        'owner_id' => $inactive->id,
        'lifecycle_phase' => $link->lifecycle_phase->value,
        'estimated_cost' => $link->estimated_cost->value,
    ])->assertSessionHasErrors(['owner_id' => __('Choose an active owner.')]);

    expect($link->refresh()->owner_id)->not->toBe($inactive->id);
});

test('an estimated cost outside the scale is rejected', function () {
    $response = $this->post(route('links.store'), linkPayload(['estimated_cost' => 'astronomical']));

    $response->assertSessionHasErrors('estimated_cost');

    $this->assertDatabaseEmpty('links');
});

test('a link can be updated', function () {
    $link = Link::factory()->create(['observed_cost' => null]);
    $owner = Owner::factory()->create();

    $response = $this->put(route('links.update', $link), [
        'owner_id' => $owner->id,
        'lifecycle_phase' => LifecyclePhase::Monitoring->value,
        'estimated_cost' => CostLevel::Low->value,
        'observed_cost' => CostLevel::Medium->value,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('links.show', $link));

    expect($link->refresh()->owner_id)->toBe($owner->id)
        ->and($link->lifecycle_phase)->toBe(LifecyclePhase::Monitoring)
        ->and($link->estimated_cost)->toBe(CostLevel::Low)
        ->and($link->observed_cost)->toBe(CostLevel::Medium);
});

test('the observed cost can be cleared', function () {
    $link = Link::factory()->create(['observed_cost' => CostLevel::High]);

    $this->put(route('links.update', $link), [
        'owner_id' => $link->owner_id,
        'lifecycle_phase' => $link->lifecycle_phase->value,
        'estimated_cost' => $link->estimated_cost->value,
        'observed_cost' => '',
    ])->assertSessionHasNoErrors();

    expect($link->refresh()->observed_cost)->toBeNull();
});

test('updating a link leaves its identity, status and dates untouched', function () {
    $link = Link::factory()->create([
        'status' => LinkStatus::Planned,
        'creation_date' => '2026-01-10',
        'next_review_date' => '2026-02-09',
    ]);
    $original = $link->only(['risk_id', 'mitigation_id']);

    $response = $this->put(route('links.update', $link), [
        'owner_id' => $link->owner_id,
        'lifecycle_phase' => $link->lifecycle_phase->value,
        'estimated_cost' => $link->estimated_cost->value,
        // None of these are editable: they must be ignored.
        'status' => LinkStatus::Implemented->value,
        'next_review_date' => '2030-01-01',
        'creation_date' => '2025-01-01',
        'risk_id' => Risk::factory()->create()->id,
        'mitigation_id' => Mitigation::factory()->create()->id,
    ]);

    $response->assertSessionHasNoErrors();

    $link->refresh();

    expect($link->status)->toBe(LinkStatus::Planned)
        ->and($link->next_review_date->toDateString())->toBe('2026-02-09')
        ->and($link->creation_date->toDateString())->toBe('2026-01-10')
        ->and($link->only(['risk_id', 'mitigation_id']))->toBe($original);

    $this->assertDatabaseEmpty('status_histories');
});

test('the detail page counts the link evidence and history', function () {
    $link = Link::factory()->create();
    Evidence::factory(2)->for($link)->create();
    StatusHistory::factory()->for($link)->create();

    $this->get(route('links.show', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('links/show')
            ->where('link.evidence_count', 2)
            ->where('link.status_histories_count', 1)
            ->has('link.risk.ai_system')
    );
});

test('a link cannot repeat a risk and mitigation pair', function () {
    $existing = Link::factory()->create();

    $response = $this->post(route('links.store'), linkPayload([
        'risk_id' => $existing->risk_id,
        'mitigation_id' => $existing->mitigation_id,
    ]));

    $response->assertSessionHasErrors([
        'mitigation_id' => __('A link between this risk and this mitigation already exists.'),
    ]);

    expect(Link::count())->toBe(1);
    $this->assertDatabaseEmpty('status_histories');
});

test('the database refuses a repeated pair that skips validation', function () {
    $existing = Link::factory()->create();

    expect(fn () => Link::factory()->create([
        'risk_id' => $existing->risk_id,
        'mitigation_id' => $existing->mitigation_id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

test('a risk and a mitigation can each be linked more than once', function () {
    $existing = Link::factory()->create();

    $this->post(route('links.store'), linkPayload(['risk_id' => $existing->risk_id]))
        ->assertSessionHasNoErrors();

    $this->post(route('links.store'), linkPayload(['mitigation_id' => $existing->mitigation_id]))
        ->assertSessionHasNoErrors();

    expect(Link::count())->toBe(3);
});

test('creating a link opens its status trail', function () {
    $this->travelTo('2026-01-10 09:00');

    $response = $this->post(route('links.store'), linkPayload());

    $link = Link::sole();
    $history = $link->statusHistories()->sole();

    $response->assertSessionHasNoErrors();

    expect($history->previous_status)->toBeNull()
        ->and($history->new_status)->toBe(LinkStatus::Planned)
        ->and($history->change_date->toDateString())->toBe('2026-01-10')
        ->and($history->owner_id)->toBe($link->owner_id)
        ->and($history->trigger_reason)->toBeNull()
        ->and($history->adverse_event_id)->toBeNull();
});

test('a link is not kept when its opening history entry fails', function () {
    StatusHistory::creating(fn () => throw new RuntimeException('history write failed'));

    expect(fn () => app(CreateLink::class)->handle(linkPayload()))
        ->toThrow(RuntimeException::class, 'history write failed');

    $this->assertDatabaseEmpty('links');
});

test('links are permanent and cannot be deleted', function () {
    $link = Link::factory()->create();

    // Closing a link means cancelling it through the status history.
    $this->delete("/links/{$link->id}")->assertMethodNotAllowed();

    $this->assertModelExists($link);
});
