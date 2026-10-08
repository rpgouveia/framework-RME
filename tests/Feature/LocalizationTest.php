<?php

use App\Enums\ReassessmentOutcome;
use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;

test('the react pages receive the lines of the active locale', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('translations.Dashboard', 'Painel de controle')
            ->where('translations.Platform', 'Plataforma')
            ->where('translations.AI systems', 'Sistemas de IA')
            ->where('translations.Log out', 'Sair')
    );
});

test('the lines the auth pages look up come from the localization package', function () {
    $this->get(route('login'))->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('translations.Email address', 'Endereço de e-mail')
            ->where('translations.Log in', 'Entrar')
            ->where('translations.Forgot your password?', 'Esqueceu sua senha?')
    );
});

test('a locale with no json file falls back to the keys', function () {
    $this->app->setLocale('en');
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->where('translations', [])
    );
});

test('the flash messages of this application are still translated', function () {
    expect(__('Risk registered.'))->toBe('Risco cadastrado.')
        ->and(__('Link verified.'))->toBe('Vínculo verificado.')
        ->and(__('Owner deactivated.'))->toBe('Responsável desativado.');
});

test('a domain label is not overwritten by the packages ui wording', function () {
    expect(ReassessmentOutcome::Close->label())->toBe('Encerrar');
});

test('the validation messages name the fields of this application in portuguese', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('risks.store'), [])
        ->assertSessionHasErrors([
            'ai_system_id' => 'O campo sistema de IA é obrigatório.',
            'name' => 'O campo nome é obrigatório.',
            'uncertainty_level' => 'O campo nível de incerteza é obrigatório.',
        ]);
});

test('the landing page receives the lines it presents the framework with', function () {
    $this->get(route('home'))->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('welcome')
            ->where('translations.Risk management for AI systems', 'Gestão de riscos de sistemas de IA')
            ->where('translations.Traceability', 'Rastreabilidade')
            // A key of its own, because the fluent assertion reads the dot as a path.
            ->where('translations', fn (Collection $lines): bool => $lines->get('Applied research project — PUCPR.') === 'Projeto de pesquisa aplicada — PUCPR.')
    );
});
