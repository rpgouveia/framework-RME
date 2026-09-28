<?php

use App\Enums\LifecyclePhase;
use App\Enums\RiskCategory;
use App\Enums\UncertaintyLevel;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\Risk;
use App\Models\User;
use Database\Seeders\AiSystemSeeder;
use Database\Seeders\RiskSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
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
        'name' => 'Viés de seleção',
        'description' => 'The model degrades for under represented groups',
        'category' => RiskCategory::Fairness->value,
        'lifecycle_phase' => LifecyclePhase::Deployment->value,
        'uncertainty_level' => UncertaintyLevel::High->value,
        'ai_system_id' => $aiSystem->id,
    ]);

    $risk = Risk::sole();

    $response->assertSessionHasNoErrors()->assertRedirect(route('risks.show', $risk));

    expect($risk->name)->toBe('Viés de seleção')
        ->and($risk->category)->toBe(RiskCategory::Fairness)
        ->and($risk->uncertainty_level)->toBe(UncertaintyLevel::High)
        ->and($risk->ai_system_id)->toBe($aiSystem->id);
});

test('a risk description may run up to 2000 characters', function (int $length, bool $valid) {
    $response = $this->post(route('risks.store'), [
        'name' => 'Viés de seleção',
        'description' => str_repeat('a', $length),
        'category' => RiskCategory::Fairness->value,
        'lifecycle_phase' => LifecyclePhase::Deployment->value,
        'uncertainty_level' => UncertaintyLevel::High->value,
        'ai_system_id' => AiSystem::factory()->create()->id,
    ]);

    if ($valid) {
        $response->assertSessionHasNoErrors();
        expect(Risk::sole()->description)->toHaveLength($length);
    } else {
        $response->assertSessionHasErrors('description');
        $this->assertDatabaseEmpty('risks');
    }
})->with([
    'at the limit' => [2000, true],
    'over the limit' => [2001, false],
]);

test('a risk requires an existing system', function () {
    $response = $this->post(route('risks.store'), [
        'name' => 'Viés de seleção',
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
        'name',
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
        'name' => 'Updated name',
        'description' => 'Updated description',
        'category' => RiskCategory::Privacy->value,
        'lifecycle_phase' => LifecyclePhase::Development->value,
        'uncertainty_level' => UncertaintyLevel::Low->value,
        'ai_system_id' => $risk->ai_system_id,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('risks.show', $risk));

    expect($risk->refresh()->name)->toBe('Updated name')
        ->and($risk->description)->toBe('Updated description')
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

/**
 * Build a valid risk payload for the given system.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function riskPayload(AiSystem $aiSystem, array $overrides = []): array
{
    return array_merge([
        'name' => 'Prompt injection',
        'description' => 'Crafted inputs override the system instructions',
        'category' => RiskCategory::Security->value,
        'lifecycle_phase' => LifecyclePhase::Deployment->value,
        'uncertainty_level' => UncertaintyLevel::Medium->value,
        'ai_system_id' => $aiSystem->id,
    ], $overrides);
}

test('a risk name is unique within its system, in any letter case', function () {
    $aiSystem = AiSystem::factory()->create();
    Risk::factory()->for($aiSystem)->create(['name' => 'Prompt injection']);

    $response = $this->post(route('risks.store'), riskPayload($aiSystem, ['name' => 'PROMPT Injection']));

    $response->assertSessionHasErrors([
        'name' => __('This AI system already has a risk with this name.'),
    ]);

    expect(Risk::count())->toBe(1);
});

test('the same risk name can be used by different systems', function () {
    Risk::factory()->create(['name' => 'Prompt injection']);

    $this->post(route('risks.store'), riskPayload(AiSystem::factory()->create()))
        ->assertSessionHasNoErrors();

    expect(Risk::where('name', 'Prompt injection')->count())->toBe(2);
});

test('a risk can be updated keeping its own name', function () {
    $risk = Risk::factory()->create(['name' => 'Prompt injection']);

    $this->put(route('risks.update', $risk), riskPayload($risk->aiSystem))
        ->assertSessionHasNoErrors();
});

test('the database refuses a risk name repeated within a system', function () {
    $aiSystem = AiSystem::factory()->create();
    Risk::factory()->for($aiSystem)->create(['name' => 'Prompt injection']);

    expect(fn () => Risk::factory()->for($aiSystem)->create(['name' => 'prompt INJECTION']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('seeded and factory risks never repeat a name within a system', function () {
    $this->seed([AiSystemSeeder::class, RiskSeeder::class]);
    Risk::factory(25)->for(AiSystem::first())->create();

    $repeated = Risk::query()
        ->selectRaw('ai_system_id, lower(name) as name')
        ->groupByRaw('ai_system_id, lower(name)')
        ->havingRaw('count(*) > 1')
        ->count();

    expect($repeated)->toBe(0);
});
