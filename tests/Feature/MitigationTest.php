<?php

use App\Enums\SaeriCategory;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests are redirected to the login page', function () {
    auth()->logout();

    $this->get(route('mitigations.index'))->assertRedirect(route('login'));
});

test('the index lists the mitigation catalogue', function () {
    Mitigation::factory(3)->create();

    $response = $this->get(route('mitigations.index'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('mitigations/index')
            ->has('mitigations.data', 3)
    );
});

test('a mitigation can be registered', function () {
    $response = $this->post(route('mitigations.store'), [
        'description' => 'Quarterly fairness audit of the model output',
        'saeri_category' => SaeriCategory::Governance->value,
    ]);

    $mitigation = Mitigation::sole();

    $response->assertSessionHasNoErrors()->assertRedirect(route('mitigations.show', $mitigation));

    expect($mitigation->description)->toBe('Quarterly fairness audit of the model output')
        ->and($mitigation->saeri_category)->toBe(SaeriCategory::Governance);
});

test('a mitigation rejects a saeri category outside the enum', function () {
    $response = $this->post(route('mitigations.store'), [
        'description' => 'Quarterly fairness audit of the model output',
        'saeri_category' => 'bureaucratic',
    ]);

    $response->assertSessionHasErrors('saeri_category');

    $this->assertDatabaseEmpty('mitigations');
});

test('a mitigation can be updated', function () {
    $mitigation = Mitigation::factory()->create();

    $response = $this->put(route('mitigations.update', $mitigation), [
        'description' => 'Updated mitigation',
        'saeri_category' => SaeriCategory::Technical->value,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('mitigations.show', $mitigation));

    expect($mitigation->refresh()->saeri_category)->toBe(SaeriCategory::Technical);
});

test('a mitigation without links can be deleted', function () {
    $mitigation = Mitigation::factory()->create();

    $this->delete(route('mitigations.destroy', $mitigation))
        ->assertRedirect(route('mitigations.index'));

    $this->assertModelMissing($mitigation);
});

test('a mitigation with links cannot be deleted', function () {
    $link = Link::factory()->create();

    $this->from(route('mitigations.show', $link->mitigation))
        ->delete(route('mitigations.destroy', $link->mitigation))
        ->assertRedirect(route('mitigations.show', $link->mitigation));

    $this->assertModelExists($link->mitigation);
});
