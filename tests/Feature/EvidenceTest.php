<?php

use App\Enums\EvidenceType;
use App\Models\Evidence;
use App\Models\Link;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests are redirected to the login page', function () {
    $link = Link::factory()->create();

    auth()->logout();

    $this->get(route('links.evidence.index', $link))->assertRedirect(route('login'));
});

test('the index lists only the evidence of its link', function () {
    $link = Link::factory()->create();
    Evidence::factory(2)->for($link)->create();
    Evidence::factory()->create();

    $response = $this->get(route('links.evidence.index', $link));

    $response->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('evidence/index')
            ->has('evidence.data', 2)
    );
});

test('evidence is attached to the link it was created under', function () {
    $link = Link::factory()->create();

    $response = $this->post(route('links.evidence.store', $link), [
        'type' => EvidenceType::AuditLog->value,
        'description' => 'Fairness audit report for Q1',
        'registration_date' => '2026-03-31',
    ]);

    $evidence = Evidence::sole();

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('links.evidence.index', $link));

    expect($evidence->link_id)->toBe($link->id)
        ->and($evidence->type)->toBe(EvidenceType::AuditLog)
        ->and($evidence->description)->toBe('Fairness audit report for Q1');
});

test('registering evidence requires every field', function () {
    $link = Link::factory()->create();

    $response = $this->post(route('links.evidence.store', $link), []);

    $response->assertSessionHasErrors(['type', 'description', 'registration_date']);

    $this->assertDatabaseEmpty('evidence');
});

test('evidence can be updated', function () {
    $evidence = Evidence::factory()->create();

    $response = $this->put(route('evidence.update', $evidence), [
        'type' => EvidenceType::Certification->value,
        'description' => 'ISO certificate',
        'registration_date' => '2026-04-01',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('evidence.show', $evidence));

    expect($evidence->refresh()->type)->toBe(EvidenceType::Certification)
        ->and($evidence->description)->toBe('ISO certificate');
});

test('deleting evidence returns to its link', function () {
    $evidence = Evidence::factory()->create();

    $this->delete(route('evidence.destroy', $evidence))
        ->assertRedirect(route('links.evidence.index', $evidence->link_id));

    $this->assertModelMissing($evidence);
});
