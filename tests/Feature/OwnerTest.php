<?php

use App\Enums\LinkStatus;
use App\Models\Link;
use App\Models\Owner;
use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests are redirected to the login page', function () {
    auth()->logout();

    $this->get(route('owners.index'))->assertRedirect(route('login'));
});

test('the index lists the owners', function () {
    Owner::factory(2)->create();

    $response = $this->get(route('owners.index'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('owners/index')
            ->has('owners.data', 2)
    );
});

test('an owner can be registered', function () {
    $response = $this->post(route('owners.store'), [
        'organizational_role' => 'Data Protection Officer',
        'area' => 'Compliance',
    ]);

    $owner = Owner::sole();

    $response->assertSessionHasNoErrors()->assertRedirect(route('owners.show', $owner));

    expect($owner->organizational_role)->toBe('Data Protection Officer')
        ->and($owner->area)->toBe('Compliance');
});

test('registering an owner requires every field', function () {
    $response = $this->post(route('owners.store'), []);

    $response->assertSessionHasErrors(['organizational_role', 'area']);

    $this->assertDatabaseEmpty('owners');
});

test('an owner can be updated', function () {
    $owner = Owner::factory()->create();

    $response = $this->put(route('owners.update', $owner), [
        'organizational_role' => 'Head of AI Governance',
        'area' => 'Dados',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('owners.show', $owner));

    expect($owner->refresh()->organizational_role)->toBe('Head of AI Governance');
});

test('an owner without links can be deleted', function () {
    $owner = Owner::factory()->create();

    $this->delete(route('owners.destroy', $owner))->assertRedirect(route('owners.index'));

    $this->assertModelMissing($owner);
});

test('an owner accountable for a link cannot be deleted', function () {
    $link = Link::factory()->create();

    $this->from(route('owners.show', $link->owner))
        ->delete(route('owners.destroy', $link->owner))
        ->assertRedirect(route('owners.show', $link->owner));

    $this->assertModelExists($link->owner);
});

// Rule 1: a role within an area names one owner.

test('a role cannot repeat within an area, in any letter case', function () {
    Owner::factory()->create(['organizational_role' => 'Auditor Interno', 'area' => 'Compliance']);

    $response = $this->post(route('owners.store'), [
        'organizational_role' => 'AUDITOR interno',
        'area' => 'compliance',
    ]);

    $response->assertSessionHasErrors([
        'organizational_role' => __('An owner with this role already exists in this area.'),
    ]);

    expect(Owner::count())->toBe(1);
});

test('the same role can exist in another area', function () {
    Owner::factory()->create(['organizational_role' => 'Auditor Interno', 'area' => 'Compliance']);

    $this->post(route('owners.store'), [
        'organizational_role' => 'Auditor Interno',
        'area' => 'Jurídico',
    ])->assertSessionHasNoErrors();

    expect(Owner::count())->toBe(2);
});

test('an owner can be updated keeping its own role and area', function () {
    $owner = Owner::factory()->create(['organizational_role' => 'Auditor Interno', 'area' => 'Compliance']);

    $this->put(route('owners.update', $owner), [
        'organizational_role' => 'Auditor Interno',
        'area' => 'Compliance',
    ])->assertSessionHasNoErrors();
});

test('the pair of an inactive owner points to reactivating it', function () {
    Owner::factory()->inactive()->create(['organizational_role' => 'Auditor Interno', 'area' => 'Compliance']);

    $this->post(route('owners.store'), [
        'organizational_role' => 'Auditor Interno',
        'area' => 'Compliance',
    ])->assertSessionHasErrors([
        'organizational_role' => __('An inactive owner has this role in this area. Reactivate it instead of registering another.'),
    ]);
});

test('the database refuses a repeated role in an area', function () {
    Owner::factory()->create(['organizational_role' => 'Auditor Interno', 'area' => 'Compliance']);

    expect(fn () => Owner::factory()->create(['organizational_role' => 'auditor INTERNO', 'area' => 'COMPLIANCE']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('the factory never repeats a role within an area', function () {
    Owner::factory(30)->create();

    $repeated = Owner::query()
        ->selectRaw('lower(organizational_role), lower(area)')
        ->groupByRaw('lower(organizational_role), lower(area)')
        ->havingRaw('count(*) > 1')
        ->count();

    expect($repeated)->toBe(0);
});

// Rule 2: a used owner keeps its role and area.

test('an unused owner can change its role and area', function () {
    $owner = Owner::factory()->create();

    $this->put(route('owners.update', $owner), [
        'organizational_role' => 'Auditor Interno',
        'area' => 'Jurídico',
    ])->assertSessionHasNoErrors();

    expect($owner->refresh()->area)->toBe('Jurídico');
});

test('an owner accountable for a link keeps its role and area', function () {
    // Fixed values, so both fields really change in the request below.
    $owner = Owner::factory()->create(['organizational_role' => 'Product Owner', 'area' => 'Produto']);
    Link::factory()->for($owner)->create();
    $original = $owner->only(['organizational_role', 'area']);

    $this->put(route('owners.update', $owner), [
        'organizational_role' => 'Auditor Interno',
        'area' => 'Jurídico',
    ])->assertSessionHasErrors([
        'organizational_role' => __('The role and area cannot change once the owner is used in the traceability chain. Register a new owner and reassign the active links.'),
        'area',
    ]);

    expect($owner->refresh()->only(['organizational_role', 'area']))->toBe($original);
});

test('an owner that only recorded status changes keeps its role and area', function () {
    $owner = Owner::factory()->create();
    StatusHistory::factory()->for($owner)->create();
    $original = $owner->organizational_role;

    // Even a change of letter case would rewrite the trail.
    $this->put(route('owners.update', $owner), [
        'organizational_role' => mb_strtoupper($original),
        'area' => $owner->area,
    ])->assertSessionHasErrors('organizational_role');

    expect($owner->refresh()->organizational_role)->toBe($original);
});

test('the index and the detail page count links and history entries', function () {
    $owner = Link::factory()->create()->owner;
    StatusHistory::factory(2)->for($owner)->create();

    // The history factory brings links with other owners, so find this one.
    $this->get(route('owners.index'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('owners.data', function ($owners) use ($owner): bool {
            $row = collect($owners)->firstWhere('id', $owner->id);

            return $row['links_count'] === 1 && $row['status_histories_count'] === 2;
        })
    );

    $this->get(route('owners.show', $owner))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('owner.links_count', 1)
            ->where('owner.status_histories_count', 2)
            ->has('owner.links.0.risk')
            ->has('owner.links.0.mitigation')
    );
});

// Rule 3: used owners are retired, not deleted.

test('an owner without active links can be deactivated', function () {
    $this->travelTo('2026-06-15 10:00');
    $owner = Owner::factory()->create();

    $this->post(route('owners.deactivate', $owner))->assertRedirect(route('owners.show', $owner));

    expect($owner->refresh()->deactivated_at?->toDateTimeString())->toBe('2026-06-15 10:00:00');
});

test('an owner still accountable for an active link cannot be deactivated', function () {
    $owner = Link::factory()->create(['status' => LinkStatus::InProgress])->owner;

    $this->from(route('owners.show', $owner))
        ->post(route('owners.deactivate', $owner))
        ->assertRedirect(route('owners.show', $owner))
        ->assertSessionHas('inertia.flash_data.toast.message', trans_choice(
            'This owner is still accountable for :count active link. Reassign it before deactivating.|This owner is still accountable for :count active links. Reassign them before deactivating.',
            1,
        ));

    expect($owner->refresh()->deactivated_at)->toBeNull();
});

test('cancelled links do not keep an owner from being deactivated', function () {
    $owner = Link::factory()->create(['status' => LinkStatus::Cancelled])->owner;

    $this->post(route('owners.deactivate', $owner));

    expect($owner->refresh()->deactivated_at)->not->toBeNull();
});

test('reactivating an owner clears the deactivation date', function () {
    $owner = Owner::factory()->inactive()->create();

    $this->post(route('owners.reactivate', $owner))->assertRedirect(route('owners.show', $owner));

    expect($owner->refresh()->deactivated_at)->toBeNull();
});

test('active owners are listed before inactive ones', function () {
    Owner::factory()->inactive()->create(['organizational_role' => 'Analista de Compliance']);
    Owner::factory()->create(['organizational_role' => 'Product Owner']);

    $this->get(route('owners.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('owners.data.0.organizational_role', 'Product Owner')
            ->where('owners.data.1.deactivated_at', fn ($value) => $value !== null)
    );
});
