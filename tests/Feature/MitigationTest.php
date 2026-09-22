<?php

use App\Enums\CostLevel;
use App\Enums\SaeriCategory;
use App\Enums\UncertaintyLevel;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * Build a valid payload for the mitigation form.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function mitigationPayload(array $overrides = []): array
{
    return array_merge([
        'description' => 'Quarterly fairness audit of the model output',
        'saeri_category' => SaeriCategory::Organizational->value,
        'suggested_target_risk' => 'Discriminatory outcomes for protected groups',
        'expected_evidence' => 'Signed audit report with the disparity metrics',
        'suggested_cost' => CostLevel::Medium->value,
        'uncertainty_level' => UncertaintyLevel::Low->value,
        'bibliography_source' => 'SAERI framework, section 4.2',
    ], $overrides);
}

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

test('a mitigation can be registered with its catalogue entry', function () {
    $response = $this->post(route('mitigations.store'), mitigationPayload());

    $mitigation = Mitigation::sole();

    $response->assertSessionHasNoErrors()->assertRedirect(route('mitigations.show', $mitigation));

    expect($mitigation->description)->toBe('Quarterly fairness audit of the model output')
        ->and($mitigation->saeri_category)->toBe(SaeriCategory::Organizational)
        ->and($mitigation->suggested_cost)->toBe(CostLevel::Medium)
        ->and($mitigation->uncertainty_level)->toBe(UncertaintyLevel::Low)
        ->and($mitigation->bibliography_source)->toBe('SAERI framework, section 4.2');
});

test('registering a mitigation requires every catalogue field', function () {
    $response = $this->post(route('mitigations.store'), []);

    $response->assertSessionHasErrors([
        'description',
        'saeri_category',
        'suggested_target_risk',
        'expected_evidence',
        'suggested_cost',
        'uncertainty_level',
        'bibliography_source',
    ]);

    $this->assertDatabaseEmpty('mitigations');
});

test('a mitigation rejects a suggested cost outside the scale', function () {
    $response = $this->post(route('mitigations.store'), mitigationPayload([
        'suggested_cost' => 'astronomical',
    ]));

    $response->assertSessionHasErrors('suggested_cost');

    $this->assertDatabaseEmpty('mitigations');
});

test('a mitigation rejects a saeri category outside the enum', function () {
    $response = $this->post(route('mitigations.store'), mitigationPayload([
        'saeri_category' => 'bureaucratic',
    ]));

    $response->assertSessionHasErrors('saeri_category');

    $this->assertDatabaseEmpty('mitigations');
});

test('a mitigation can be updated', function () {
    $mitigation = Mitigation::factory()->create();

    $response = $this->put(route('mitigations.update', $mitigation), mitigationPayload([
        'description' => 'Updated mitigation',
        'saeri_category' => SaeriCategory::Technical->value,
    ]));

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
