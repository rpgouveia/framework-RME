<?php

use App\Models\Link;
use App\Models\Owner;
use App\Models\User;
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
