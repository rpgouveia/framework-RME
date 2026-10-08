<?php

use App\Enums\AiSystemCategory;
use App\Enums\SystemSourceType;
use App\Models\AiSystem;
use App\Models\Risk;
use App\Models\User;
use Database\Factories\AiSystemFactory;
use Database\Seeders\AiSystemSeeder;
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

// The application domain: descriptive, optional.

/**
 * Build a valid payload for the system form.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function aiSystemPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Assistente de atendimento',
        'source_type' => SystemSourceType::ThirdParty->value,
        'category' => AiSystemCategory::Limited->value,
        'registration_date' => '2026-01-15',
    ], $overrides);
}

test('a system can be registered with or without an application domain', function (?string $domain) {
    $this->post(route('ai-systems.store'), aiSystemPayload([
        'application_domain' => $domain,
    ]))->assertSessionHasNoErrors();

    expect(AiSystem::sole()->application_domain)->toBe($domain === '' ? null : $domain);
})->with([
    'with a domain' => ['Atendimento ao cliente'],
    'left empty' => [''],
    'not sent' => [null],
]);

test('an application domain over 255 characters is refused', function () {
    $this->post(route('ai-systems.store'), aiSystemPayload([
        'application_domain' => str_repeat('a', 256),
    ]))->assertSessionHasErrors('application_domain');

    $this->assertDatabaseEmpty('ai_systems');

    $this->post(route('ai-systems.store'), aiSystemPayload([
        'application_domain' => str_repeat('a', 255),
    ]))->assertSessionHasNoErrors();
});

test('editing a system changes and clears its application domain', function () {
    $aiSystem = AiSystem::factory()->create(['application_domain' => 'Suporte técnico']);

    $this->put(route('ai-systems.update', $aiSystem), aiSystemPayload([
        'application_domain' => 'Triagem de currículos',
    ]))->assertSessionHasNoErrors();

    expect($aiSystem->refresh()->application_domain)->toBe('Triagem de currículos');

    $this->put(route('ai-systems.update', $aiSystem), aiSystemPayload([
        'application_domain' => '',
    ]))->assertSessionHasNoErrors();

    expect($aiSystem->refresh()->application_domain)->toBeNull();
});

test('the screens show the application domain of the system', function () {
    $aiSystem = AiSystem::factory()->create(['application_domain' => 'Apoio a decisões clínicas']);

    $this->get(route('ai-systems.index'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('aiSystems.data.0.application_domain', 'Apoio a decisões clínicas')
    );
    $this->get(route('ai-systems.show', $aiSystem))->assertInertia(
        fn (AssertableInertia $page) => $page->where('aiSystem.application_domain', 'Apoio a decisões clínicas')
    );
    $this->get(route('ai-systems.edit', $aiSystem))->assertInertia(
        fn (AssertableInertia $page) => $page->where('aiSystem.application_domain', 'Apoio a decisões clínicas')
    );
    $this->get(route('dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page->where('systems.0.application_domain', 'Apoio a decisões clínicas')
    );
});

test('the seeder includes one system in the unacceptable tier', function () {
    $this->seed(AiSystemSeeder::class);

    expect(AiSystem::where('category', AiSystemCategory::Unacceptable)->count())->toBe(1);
});

test('the factory picks an operable tier unless asked for unacceptable', function () {
    expect(AiSystem::factory(20)->create()->every(fn (AiSystem $aiSystem): bool => $aiSystem->category->isOperable()))->toBeTrue()
        ->and(AiSystem::factory()->unacceptable()->create()->category)->toBe(AiSystemCategory::Unacceptable);
});

test('the seeder gives each system a different application domain', function () {
    $this->seed(AiSystemSeeder::class);

    $domains = AiSystem::pluck('application_domain');

    expect($domains)->toHaveCount(5)
        ->and($domains->unique())->toHaveCount(5)
        ->and($domains->every(fn (?string $domain): bool => in_array($domain, AiSystemFactory::APPLICATION_DOMAINS, true)))->toBeTrue();
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
