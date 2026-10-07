<?php

use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

// On PostgreSQL a non-numeric id reaching the database is a type error, so
// every model route must reject it before binding: a 404, never a 500.
test('a non-numeric id is not found', function (string $uri) {
    $this->get($uri)->assertNotFound();
})->with([
    '/ai-systems/abc',
    '/risks/abc',
    '/adverse-events/abc',
    '/mitigations/create',
    '/mitigations/abc',
    '/owners/abc',
    '/links/abc',
    '/links/abc/evidence',
    '/links/abc/status-histories',
    '/evidence/abc',
    '/status-histories/abc',
]);
