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

    $response->assertSessionHasErrors(['type', 'description']);

    $this->assertDatabaseEmpty('evidence');
});

test('the registration date is stamped by the system', function () {
    $this->travelTo('2026-06-15 10:00');
    $link = Link::factory()->create();

    $this->post(route('links.evidence.store', $link), [
        'type' => EvidenceType::AuditLog->value,
        'description' => 'Fairness audit report for Q1',
        'registration_date' => '2020-01-01',
    ])->assertSessionHasNoErrors();

    expect(Evidence::sole()->registration_date->toDateString())->toBe('2026-06-15');
});

test('evidence is append only', function () {
    // It backs the link's verification, so it is never edited or deleted.
    $evidence = Evidence::factory()->create();

    $this->get("/evidence/{$evidence->id}/edit")->assertNotFound();
    $this->put("/evidence/{$evidence->id}", [])->assertMethodNotAllowed();
    $this->delete("/evidence/{$evidence->id}")->assertMethodNotAllowed();

    $this->assertModelExists($evidence);
});
