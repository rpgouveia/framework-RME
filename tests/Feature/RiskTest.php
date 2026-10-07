<?php

use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\UncertaintyLevel;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\Risk;
use App\Models\User;
use App\Support\AiRiskDomains;
use App\Support\SaeriTaxonomy;
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
        'risk_subdomain_id' => riskSubdomainId('1.1'),
        'lifecycle_phase' => LifecyclePhase::Deployment->value,
        'uncertainty_level' => UncertaintyLevel::High->value,
        'ai_system_id' => $aiSystem->id,
    ]);

    $risk = Risk::sole();

    $response->assertSessionHasNoErrors()->assertRedirect(route('risks.show', $risk));

    expect($risk->name)->toBe('Viés de seleção')
        ->and($risk->riskSubdomain->code)->toBe('1.1')
        ->and($risk->riskSubdomain->parent->code)->toBe('1')
        ->and($risk->uncertainty_level)->toBe(UncertaintyLevel::High)
        ->and($risk->ai_system_id)->toBe($aiSystem->id);
});

test('a risk description may run up to 2000 characters', function (int $length, bool $valid) {
    $response = $this->post(route('risks.store'), [
        'name' => 'Viés de seleção',
        'description' => str_repeat('a', $length),
        'risk_subdomain_id' => riskSubdomainId('1.1'),
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
        'risk_subdomain_id' => riskSubdomainId('1.1'),
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
        'risk_subdomain_id',
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
        'risk_subdomain_id' => riskSubdomainId('2.1'),
        'lifecycle_phase' => LifecyclePhase::Development->value,
        'uncertainty_level' => UncertaintyLevel::Low->value,
        'ai_system_id' => $risk->ai_system_id,
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('risks.show', $risk));

    expect($risk->refresh()->name)->toBe('Updated name')
        ->and($risk->description)->toBe('Updated description')
        ->and($risk->riskSubdomain->code)->toBe('2.1');
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
        'risk_subdomain_id' => riskSubdomainId('2.2'),
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

test('the system of a risk without links can change', function () {
    $risk = Risk::factory()->create();
    $other = AiSystem::factory()->create();

    $this->put(route('risks.update', $risk), riskPayload($other, ['name' => $risk->name]))
        ->assertSessionHasNoErrors();

    expect($risk->refresh()->ai_system_id)->toBe($other->id);
});

test('the system of a risk with links cannot change', function () {
    $risk = Risk::factory()->create(['name' => 'Prompt injection']);
    Link::factory()->for($risk)->create();
    $original = $risk->ai_system_id;

    $response = $this->put(route('risks.update', $risk), riskPayload(AiSystem::factory()->create(), [
        'name' => 'Jailbreak',
    ]));

    $response->assertSessionHasErrors([
        'ai_system_id' => __('The AI system cannot change once the risk has links.'),
    ]);

    $risk->refresh();

    expect($risk->ai_system_id)->toBe($original)
        ->and($risk->name)->toBe('Prompt injection');
});

test('cancelled links still lock the system of their risk', function () {
    $risk = Risk::factory()->create();
    Link::factory()->for($risk)->create(['status' => LinkStatus::Cancelled]);
    $original = $risk->ai_system_id;

    $this->put(route('risks.update', $risk), riskPayload(AiSystem::factory()->create(), [
        'name' => $risk->name,
    ]))->assertSessionHasErrors('ai_system_id');

    expect($risk->refresh()->ai_system_id)->toBe($original);
});

test('a risk with links can be edited keeping its system', function () {
    $risk = Risk::factory()->create();
    Link::factory()->for($risk)->create();

    $this->put(route('risks.update', $risk), riskPayload($risk->aiSystem, [
        'name' => 'Jailbreak',
        'uncertainty_level' => UncertaintyLevel::High->value,
    ]))->assertSessionHasNoErrors();

    expect($risk->refresh()->name)->toBe('Jailbreak')
        ->and($risk->uncertainty_level)->toBe(UncertaintyLevel::High);
});

test('a risk can be edited leaving the system out, which keeps it', function () {
    $risk = Risk::factory()->create();
    Link::factory()->for($risk)->create();
    $original = $risk->ai_system_id;

    $payload = riskPayload($risk->aiSystem, ['name' => 'Jailbreak']);
    unset($payload['ai_system_id']);

    $this->put(route('risks.update', $risk), $payload)->assertSessionHasNoErrors();

    expect($risk->refresh()->ai_system_id)->toBe($original)
        ->and($risk->name)->toBe('Jailbreak');
});

test('leaving the system out still checks the name within the current system', function () {
    $risk = Risk::factory()->create(['name' => 'Prompt injection']);
    Risk::factory()->for($risk->aiSystem)->create(['name' => 'Jailbreak']);

    $payload = riskPayload($risk->aiSystem, ['name' => 'JAILBREAK']);
    unset($payload['ai_system_id']);

    $this->put(route('risks.update', $risk), $payload)->assertSessionHasErrors([
        'name' => __('This AI system already has a risk with this name.'),
    ]);
});

test('moving a risk checks its name in the new system', function () {
    $risk = Risk::factory()->create(['name' => 'Prompt injection']);
    $other = AiSystem::factory()->create();
    Risk::factory()->for($other)->create(['name' => 'Prompt injection']);

    $this->put(route('risks.update', $risk), riskPayload($other))->assertSessionHasErrors('name');

    expect($risk->refresh()->ai_system_id)->not->toBe($other->id);
});

test('the edit form knows how many links the risk has', function () {
    $risk = Risk::factory()->create();
    Link::factory(2)->for($risk)->create();

    $this->get(route('risks.edit', $risk))->assertInertia(
        fn (AssertableInertia $page) => $page->where('risk.links_count', 2)
    );
});

// The MIT AI risk domain taxonomy (Slattery et al.).

test('a risk is classified by an MIT subdomain, and its domain is derived', function () {
    $this->post(route('risks.store'), riskPayload(AiSystem::factory()->create(), [
        'risk_subdomain_id' => riskSubdomainId('7.6'),
    ]))->assertSessionHasNoErrors();

    $subdomain = Risk::sole()->riskSubdomain;

    expect($subdomain->code)->toBe('7.6')
        ->and($subdomain->original_name)->toBe('Multi-agent risks')
        ->and($subdomain->parent->code)->toBe('7')
        ->and($subdomain->parent->original_name)->toBe('AI system safety, failures & limitations');
});

test('a risk requires an existing subdomain of the MIT taxonomy', function (int $subdomainId) {
    $this->post(route('risks.store'), riskPayload(AiSystem::factory()->create(), [
        'risk_subdomain_id' => $subdomainId,
    ]))->assertSessionHasErrors([
        'risk_subdomain_id' => __('Choose a subdomain of the MIT AI risk domain taxonomy.'),
    ]);

    $this->assertDatabaseEmpty('risks');
})->with([
    'unknown id' => [fn () => 999_999],
    // A domain (level 1) is too broad: the risk takes a subdomain.
    'a domain' => [fn () => app(AiRiskDomains::class)->domains()->firstWhere('code', '2')->id],
    // A term of another taxonomy is not a risk subdomain.
    'a Saeri subcategory' => [fn () => app(SaeriTaxonomy::class)->subcategories()->firstWhere('code', '2.1')->id],
]);

test('the risk forms offer the MIT domains with their subdomains', function () {
    $risk = Risk::factory()->inSubdomain('4.3')->create();

    foreach ([route('risks.create'), route('risks.edit', $risk)] as $url) {
        $this->get($url)->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('riskDomains', 7)
                ->where('riskDomains.6.code', '7')
                ->has('riskDomains.6.children', 6)
                ->where('riskDomains.6.children.5.code', '7.6')
                ->has('riskDomains.0.children.0', fn (AssertableInertia $term) => $term
                    ->where('code', '1.1')
                    ->where('name', 'Discriminação injusta e representação distorcida')
                    ->has('id')
                    ->where('description', fn (string $description) => str_starts_with($description, 'Unequal treatment'))
                )
        );
    }
});

test('the risk screens show its domain and subdomain', function () {
    $risk = Risk::factory()->inSubdomain('3.1')->create();

    $this->get(route('risks.show', $risk))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('risk.risk_subdomain.code', '3.1')
            ->where('risk.risk_subdomain.parent.code', '3')
    );

    $this->get(route('risks.index'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('risks.data.0.risk_subdomain.code', '3.1')
            ->where('risks.data.0.risk_subdomain.parent.code', '3')
    );

    $this->get(route('ai-systems.show', $risk->aiSystem))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('aiSystem.risks.0.risk_subdomain.code', '3.1')
            ->where('aiSystem.risks.0.risk_subdomain.parent.code', '3')
    );
});

test('the risk seeder spreads the risks over several subdomains', function () {
    $this->seed([AiSystemSeeder::class, RiskSeeder::class]);

    expect(Risk::query()->distinct()->count('risk_subdomain_id'))->toBeGreaterThan(1);
});
