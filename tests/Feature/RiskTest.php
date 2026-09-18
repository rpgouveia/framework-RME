<?php

use App\Enums\LifecyclePhase;
use App\Enums\RiskCategory;
use App\Enums\UncertaintyLevel;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\Risk;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests are redirected to the login page', function () {
    auth()->logout();

    $this->get(route('risks.index'))->assertRedirect(route('login'));
});

test('the index lists the risks with their system', function () {
    Risk::factory(2)->create();

    $response = $this->get(route('risks.index'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('risks/index')
            ->has('risks.data', 2)
            ->has('risks.data.0.ai_system')
    );
});

test('a risk can be registered for a system', function () {
    $aiSystem = AiSystem::factory()->create();

    $response = $this->post(route('risks.store'), [
        'description' => 'The model degrades for under represented groups',
        'category' => RiskCategory::Fairness->value,
        'lifecycle_phase' => LifecyclePhase::Deployment->value,
        'uncertainty_level' => UncertaintyLevel::High->value,
        'ai_system_id' => $aiSystem->id,
    ]);

    $risk = Risk::sole();

    $response->assertSessionHasNoErrors()->assertRedirect(route('risks.show', $risk));

    expect($risk->category)->toBe(RiskCategory::Fairness)
        ->and($risk->uncertainty_level)->toBe(UncertaintyLevel::High)
        ->and($risk->ai_system_id)->toBe($aiSystem->id);
});

test('a risk requires an existing system', function () {
    $response = $this->post(route('risks.store'), [
        'description' => 'The model degrades for under represented groups',
        'category' => RiskCategory::Fairness->value,
        'lifecycle_phase' => LifecyclePhase::Deployment->value,
        'uncertainty_level' => UncertaintyLevel::High->value,
        'ai_system_id' => 999,
    ]);

    $response->assertSessionHasErrors('ai_system_id');

    $this->assertDatabaseEmpty('risks');
});

test('registering a risk requires every field', function () {
    $response = $this->post(route('risks.store'), []);

    $response->assertSessionHasErrors([
        'description',
        'category',
        'lifecycle_phase',
        'uncertainty_level',
        'ai_system_id',
    ]);

    $this->assertDatabaseEmpty('risks');
});

test('a risk can be updated', function () {
    $risk = Risk::factory()->create();

    $response = $this->put(route('risks.update', $risk), [
        'description' => 'Updated description',
        'category' => RiskCategory::Privacy->value,
        'lifecycle_phase' => LifecyclePhase::Development->value,
        'uncertainty_level' => UncertaintyLevel::Low->value,
        'ai_system_id' => $risk->ai_system_id,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('risks.show', $risk));

    expect($risk->refresh()->description)->toBe('Updated description')
        ->and($risk->category)->toBe(RiskCategory::Privacy);
});

test('a risk without links can be deleted', function () {
    $risk = Risk::factory()->create();

    $this->delete(route('risks.destroy', $risk))->assertRedirect(route('risks.index'));

    $this->assertModelMissing($risk);
});

test('a risk with links cannot be deleted', function () {
    $link = Link::factory()->create();

    $this->from(route('risks.show', $link->risk))
        ->delete(route('risks.destroy', $link->risk))
        ->assertRedirect(route('risks.show', $link->risk));

    $this->assertModelExists($link->risk);
});
