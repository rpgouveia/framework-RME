<?php

use App\Enums\AiSystemCategory;
use App\Enums\SystemSourceType;
use App\Models\AiSystem;
use App\Models\Risk;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests are redirected to the login page', function () {
    auth()->logout();

    $this->get(route('ai-systems.index'))->assertRedirect(route('login'));
});

test('the index lists the registered systems', function () {
    AiSystem::factory(3)->create();

    $response = $this->get(route('ai-systems.index'));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('ai-systems/index')
            ->has('aiSystems.data', 3)
    );
});

test('a system can be registered', function () {
    $response = $this->post(route('ai-systems.store'), [
        'name' => 'Credit scoring model',
        'source_type' => SystemSourceType::ThirdParty->value,
        'category' => AiSystemCategory::High->value,
        'registration_date' => '2026-01-15',
    ]);

    $aiSystem = AiSystem::sole();

    $response->assertSessionHasNoErrors()->assertRedirect(route('ai-systems.show', $aiSystem));

    expect($aiSystem->name)->toBe('Credit scoring model')
        ->and($aiSystem->source_type)->toBe(SystemSourceType::ThirdParty)
        ->and($aiSystem->category)->toBe(AiSystemCategory::High)
        ->and($aiSystem->registration_date->toDateString())->toBe('2026-01-15');
});

test('registering a system requires every field', function () {
    $response = $this->post(route('ai-systems.store'), []);

    $response->assertSessionHasErrors(['name', 'source_type', 'category', 'registration_date']);

    $this->assertDatabaseEmpty('ai_systems');
});

test('a system rejects a category outside the enum', function () {
    $response = $this->post(route('ai-systems.store'), [
        'name' => 'Credit scoring model',
        'source_type' => SystemSourceType::Internal->value,
        'category' => 'catastrophic',
        'registration_date' => '2026-01-15',
    ]);

    $response->assertSessionHasErrors('category');

    $this->assertDatabaseEmpty('ai_systems');
});

test('a system can be updated', function () {
    $aiSystem = AiSystem::factory()->create();

    $response = $this->put(route('ai-systems.update', $aiSystem), [
        'name' => 'Renamed system',
        'source_type' => SystemSourceType::OpenSource->value,
        'category' => AiSystemCategory::Minimal->value,
        'registration_date' => '2026-02-20',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('ai-systems.show', $aiSystem));

    expect($aiSystem->refresh()->name)->toBe('Renamed system')
        ->and($aiSystem->source_type)->toBe(SystemSourceType::OpenSource);
});

test('a system without risks can be deleted', function () {
    $aiSystem = AiSystem::factory()->create();

    $response = $this->delete(route('ai-systems.destroy', $aiSystem));

    $response->assertRedirect(route('ai-systems.index'));

    $this->assertModelMissing($aiSystem);
});

test('a system with risks cannot be deleted', function () {
    $aiSystem = AiSystem::factory()->create();
    Risk::factory()->for($aiSystem)->create();

    $response = $this->from(route('ai-systems.show', $aiSystem))
        ->delete(route('ai-systems.destroy', $aiSystem));

    $response->assertRedirect(route('ai-systems.show', $aiSystem));

    $this->assertModelExists($aiSystem);
});
