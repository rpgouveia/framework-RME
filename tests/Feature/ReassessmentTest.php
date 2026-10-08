<?php

use App\Actions\RecordEvidence;
use App\Actions\RecordStatusChange;
use App\Enums\AdverseEventNature;
use App\Enums\AiSystemCategory;
use App\Enums\ChangeOrigin;
use App\Enums\EvidenceType;
use App\Enums\LinkStatus;
use App\Enums\VerificationStatus;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\Owner;
use App\Models\Risk;
use App\Models\StatusHistory;
use App\Models\User;
use App\Support\MonitoringProtocol;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * A link of a system with its risk in the given subdomain, verified through
 * the action when asked (with its evidence).
 */
function linkIn(AiSystem $aiSystem, string $subdomain, bool $verified = true): Link
{
    $link = Link::factory()
        ->for(Risk::factory()->for($aiSystem)->inSubdomain($subdomain))
        ->create(['status' => LinkStatus::InProgress]);

    if ($verified) {
        app(RecordEvidence::class)->handle($link, ['type' => EvidenceType::Report, 'description' => 'Relatório']);
        app(RecordStatusChange::class)->verify($link, Owner::factory()->create());
    }

    return $link->refresh();
}

/**
 * Record an adverse event through the form.
 *
 * @param  array<string, mixed>  $overrides
 */
function recordEvent(AiSystem $aiSystem, array $overrides = []): TestResponse
{
    return test()->post(route('adverse-events.store'), array_merge([
        'nature' => AdverseEventNature::Incident->value,
        'risk_subdomains' => ['2.1'],
        'description' => 'Dados pessoais expostos na resposta do assistente.',
        'occurrence_date' => today()->subDays(3)->toDateString(),
        'ai_system_id' => $aiSystem->id,
    ], $overrides));
}

// Directed reassessment (0019, item 3).

test('an event reverts only the verified links of its subdomains in its system', function () {
    $this->travelTo('2026-05-10 10:00');
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $privacy = linkIn($aiSystem, '2.1');
    $security = linkIn($aiSystem, '2.2');
    $declared = linkIn($aiSystem, '2.1', verified: false);
    $otherSystem = linkIn(AiSystem::factory()->highRisk()->create(), '2.1');

    $this->travelTo('2026-05-12 10:00');
    recordEvent($aiSystem)->assertSessionHasNoErrors();

    $event = AdverseEvent::sole();
    $entry = $privacy->lastReversal()->first();

    expect($privacy->refresh()->verification_status)->toBe(VerificationStatus::Declared)
        ->and($privacy->next_review_date)->toBeNull()
        ->and($entry->origin)->toBe(ChangeOrigin::AdverseEvent)
        ->and($entry->owner_id)->toBeNull()
        ->and($entry->adverse_event_id)->toBe($event->id)
        ->and($entry->trigger_reason)->toBe('Evento adverso de 09/05/2026 em um subdomínio do risco deste vínculo.')
        // Another subdomain, a declared link and another system are left alone.
        ->and($security->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        ->and($declared->statusHistories()->whereNotNull('new_verification')->count())->toBe(0)
        ->and($otherSystem->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        ->and($event->reversals()->pluck('link_id')->all())->toBe([$privacy->id]);
});

test('an event registered late still reverts the links verified since it happened', function () {
    $this->travelTo('2026-05-10 10:00');
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = linkIn($aiSystem, '2.1');

    // It happened before the verification, and is only recorded now.
    $this->travelTo('2026-05-20 10:00');
    recordEvent($aiSystem, ['occurrence_date' => '2026-04-01'])->assertSessionHasNoErrors();

    expect($link->refresh()->verification_status)->toBe(VerificationStatus::Declared);
});

test('a near miss does not revert the link that intercepted it', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $interceptor = linkIn($aiSystem, '2.1');
    $other = linkIn($aiSystem, '2.1');

    recordEvent($aiSystem, [
        'nature' => AdverseEventNature::NearMiss->value,
        'intercepting_link_id' => $interceptor->id,
    ])->assertSessionHasNoErrors();

    expect($interceptor->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        ->and($other->refresh()->verification_status)->toBe(VerificationStatus::Declared)
        ->and(AdverseEvent::sole()->intercepting_link_id)->toBe($interceptor->id);
});

test('both natures trigger the reassessment', function (AdverseEventNature $nature) {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = linkIn($aiSystem, '2.1');

    recordEvent($aiSystem, ['nature' => $nature->value])->assertSessionHasNoErrors();

    expect($link->refresh()->verification_status)->toBe(VerificationStatus::Declared);
})->with([AdverseEventNature::Incident, AdverseEventNature::NearMiss]);

test('the intercepting link is refused when it does not fit', function (Closure $arrange, string $message) {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    [$overrides] = [$arrange($aiSystem)];

    recordEvent($aiSystem, $overrides)->assertSessionHasErrors(['intercepting_link_id' => $message]);

    $this->assertDatabaseEmpty('adverse_events');
})->with([
    'an incident' => [fn (AiSystem $aiSystem): array => [
        'nature' => AdverseEventNature::Incident->value,
        'intercepting_link_id' => linkIn($aiSystem, '2.1')->id,
    ], 'Só um quase-incidente pode indicar o vínculo que o interceptou.'],
    'another system' => [fn (AiSystem $aiSystem): array => [
        'nature' => AdverseEventNature::NearMiss->value,
        'intercepting_link_id' => linkIn(AiSystem::factory()->highRisk()->create(), '2.1')->id,
    ], 'O vínculo interceptador deve ser do mesmo sistema do evento.'],
    'a risk outside the subdomains' => [fn (AiSystem $aiSystem): array => [
        'nature' => AdverseEventNature::NearMiss->value,
        'intercepting_link_id' => linkIn($aiSystem, '7.3')->id,
    ], 'O risco do vínculo interceptador deve estar em um dos subdomínios do evento.'],
    'a cancelled link' => [function (AiSystem $aiSystem): array {
        $link = linkIn($aiSystem, '2.1', verified: false);
        $link->update(['status' => LinkStatus::Cancelled]);

        return ['nature' => AdverseEventNature::NearMiss->value, 'intercepting_link_id' => $link->id];
    }, 'Um vínculo cancelado não pode ter interceptado o evento.'],
]);

test('the event form carries what it needs to announce the reassessment', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $verified = linkIn($aiSystem, '2.1');
    $declared = linkIn($aiSystem, '7.3', verified: false);
    $cancelled = linkIn($aiSystem, '2.1', verified: false);
    $cancelled->update(['status' => LinkStatus::Cancelled]);

    $this->get(route('adverse-events.create'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('natures', [
                ['value' => 'incident', 'label' => 'Incidente'],
                ['value' => 'near_miss', 'label' => 'Quase-incidente'],
            ])
            ->has("linksBySystem.{$aiSystem->id}", 2)
            ->where("linksBySystem.{$aiSystem->id}.0.id", $verified->id)
            ->where("linksBySystem.{$aiSystem->id}.0.subdomain", '2.1')
            ->where("linksBySystem.{$aiSystem->id}.0.verification_status", 'verified')
            ->where("linksBySystem.{$aiSystem->id}.1.id", $declared->id)
    );
});

// Nature and detection (0019, items 5 and 8).

test('the nature is required', function () {
    recordEvent(AiSystem::factory()->create(), ['nature' => null])->assertSessionHasErrors('nature');
    recordEvent(AiSystem::factory()->create(), ['nature' => 'accident'])->assertSessionHasErrors('nature');

    $this->assertDatabaseEmpty('adverse_events');
});

test('the detection date lies between the occurrence and today', function (string $detected, ?string $message) {
    $this->travelTo('2026-05-20 10:00');

    $response = recordEvent(AiSystem::factory()->create(), [
        'occurrence_date' => '2026-05-10',
        'detected_at' => $detected,
    ]);

    if ($message === null) {
        $response->assertSessionHasNoErrors();
        expect(AdverseEvent::sole()->detected_at->toDateString())->toBe($detected);
    } else {
        $response->assertSessionHasErrors(['detected_at' => $message]);
        $this->assertDatabaseEmpty('adverse_events');
    }
})->with([
    'before the occurrence' => ['2026-05-09', 'A data de detecção não pode ser anterior à data de ocorrência.'],
    'in the future' => ['2026-05-21', 'A data de detecção não pode ser futura.'],
    'on the occurrence day' => ['2026-05-10', null],
    'today' => ['2026-05-20', null],
]);

test('the event page shows its nature, detection delay, interceptor and reversals', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $interceptor = linkIn($aiSystem, '2.1');
    $reverted = linkIn($aiSystem, '2.1');
    // A subdomain where the system has no risk: not yet mapped.
    recordEvent($aiSystem, [
        'nature' => AdverseEventNature::NearMiss->value,
        'risk_subdomains' => ['2.1', '4.1'],
        'occurrence_date' => today()->subDays(5)->toDateString(),
        'detected_at' => today()->subDays(2)->toDateString(),
        'intercepting_link_id' => $interceptor->id,
    ])->assertSessionHasNoErrors();

    $this->get(route('adverse-events.show', AdverseEvent::sole()))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('adverseEvent.nature', 'near_miss')
            ->where('detectionDelayDays', 3)
            ->where('adverseEvent.intercepting_link.id', $interceptor->id)
            ->has('adverseEvent.intercepting_link.mitigation.name')
            ->has('adverseEvent.reversals', 1)
            ->where('adverseEvent.reversals.0.link_id', $reverted->id)
            ->where('unmappedSubdomainCodes', ['4.1'])
    );
});

test('the event list filters by nature', function () {
    $incident = AdverseEvent::factory()->create(['nature' => AdverseEventNature::Incident]);
    $nearMiss = AdverseEvent::factory()->create(['nature' => AdverseEventNature::NearMiss]);

    $ids = fn (?string $nature): array => collect(
        $this->get(route('adverse-events.index', array_filter(['nature' => $nature])))->viewData('page')['props']['adverseEvents']['data'],
    )->pluck('id')->sort()->values()->all();

    expect($ids('incident'))->toBe([$incident->id])
        ->and($ids('near_miss'))->toBe([$nearMiss->id])
        ->and($ids(null))->toHaveCount(2)
        ->and($ids('unknown'))->toHaveCount(2);
});

// Reclassification (0019, item 9).

test('reclassifying a system into the unacceptable tier reverts its verified links', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $verified = linkIn($aiSystem, '2.1');
    $other = linkIn(AiSystem::factory()->highRisk()->create(), '2.1');

    $this->get(route('ai-systems.edit', $aiSystem))->assertInertia(
        fn (AssertableInertia $page) => $page->where('verifiedLinksCount', 1)
    );

    $this->put(route('ai-systems.update', $aiSystem), [
        'name' => $aiSystem->name,
        'source_type' => $aiSystem->source_type->value,
        'category' => AiSystemCategory::Unacceptable->value,
        'registration_date' => $aiSystem->registration_date->toDateString(),
    ])->assertSessionHasNoErrors();

    $entry = $verified->lastReversal()->first();

    expect($verified->refresh()->verification_status)->toBe(VerificationStatus::Declared)
        ->and($verified->next_review_date)->toBeNull()
        ->and($entry->origin)->toBe(ChangeOrigin::SystemReclassification)
        ->and($entry->owner_id)->toBeNull()
        ->and($other->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        // It awaits reassessment, though it cannot be verified again.
        ->and(Link::revertedBy(ChangeOrigin::SystemReclassification)->pluck('id')->all())->toBe([$verified->id]);
});

test('a link reverted by reclassification carries what its page needs to point at the discontinuation', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = linkIn($aiSystem, '2.1');

    $this->put(route('ai-systems.update', $aiSystem), [
        'name' => $aiSystem->name,
        'source_type' => $aiSystem->source_type->value,
        'category' => AiSystemCategory::Unacceptable->value,
        'registration_date' => $aiSystem->registration_date->toDateString(),
    ])->assertSessionHasNoErrors();

    // Still counted as awaiting reassessment (0019, addendum).
    expect(Link::awaitingReassessment()->pluck('id')->all())->toBe([$link->id]);

    $this->get(route('links.show', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('link.verification_status', 'declared')
            ->where('link.risk.ai_system.id', $aiSystem->id)
            ->where('link.risk.ai_system.category', 'unacceptable')
            ->where('link.last_reversal.origin', 'system_reclassification')
            ->where('verification.problem', 'Um vínculo de sistema na faixa inaceitável não pode ser verificado: o sistema nunca opera.')
    );
});

test('moving a system between operable tiers reverts nothing', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = linkIn($aiSystem, '2.1');
    $date = $link->next_review_date->toDateString();

    $this->put(route('ai-systems.update', $aiSystem), [
        'name' => $aiSystem->name,
        'source_type' => $aiSystem->source_type->value,
        'category' => AiSystemCategory::Minimal->value,
        'registration_date' => $aiSystem->registration_date->toDateString(),
    ])->assertSessionHasNoErrors();

    expect($link->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        ->and($link->next_review_date->toDateString())->toBe($date);
});

// Renewal (0019, item 2).

test('a renewal needs evidence stored after the last verification and restarts the review', function () {
    $this->travelTo('2026-03-01 09:00');
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = linkIn($aiSystem, '2.1');
    $firstVerification = $link->lastVerification()->first();
    $owner = Owner::factory()->create();

    $this->travelTo('2026-04-01 09:00');
    $this->post(route('links.verification.store', $link), ['owner_id' => $owner->id])
        ->assertSessionHasErrors(['verification' => 'Registre uma evidência nova para renovar: a última verificação foi em 01/03/2026.']);

    $new = app(RecordEvidence::class)->handle($link, ['type' => EvidenceType::Report, 'description' => 'Nova auditoria']);

    $this->travelTo('2026-04-02 09:00');
    $this->post(route('links.verification.store', $link), ['owner_id' => $owner->id])->assertSessionHasNoErrors();

    $renewal = $link->lastVerification()->first();

    expect($link->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        // High risk: 90 days from the renewal.
        ->and($link->next_review_date->toDateString())->toBe('2026-07-01')
        ->and($renewal->id)->not->toBe($firstVerification->id)
        ->and($renewal->previous_verification)->toBe(VerificationStatus::Verified)
        ->and($renewal->new_verification)->toBe(VerificationStatus::Verified)
        ->and($renewal->isRenewal())->toBeTrue()
        ->and($renewal->owner_id)->toBe($owner->id)
        // It rested on the evidence stored since the last verification.
        ->and($renewal->supportingEvidence()->pluck('id')->all())->toBe([$new->id]);

    $this->get(route('status-histories.show', $renewal))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('statusHistory.previous_verification', 'verified')
            ->where('statusHistory.new_verification', 'verified')
            ->where('supportingEvidence.0.id', $new->id)
    );
});

test('a renewal keeps the rules of the verification', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = linkIn($aiSystem, '2.1');
    $this->travel(1)->minutes();
    app(RecordEvidence::class)->handle($link, ['type' => EvidenceType::Report, 'description' => 'Nova']);

    // An inactive owner cannot renew.
    $this->post(route('links.verification.store', $link), [
        'owner_id' => Owner::factory()->create(['deactivated_at' => now()])->id,
    ])->assertSessionHasErrors('owner_id');

    // Nor can a cancelled link.
    $link->update(['status' => LinkStatus::Cancelled]);
    $this->post(route('links.verification.store', $link), ['owner_id' => $link->owner_id])
        ->assertSessionHasErrors(['verification' => 'Um vínculo cancelado não pode ser verificado.']);
});

test('the database accepts a renewal as a single change of verification', function () {
    $link = linkIn(AiSystem::factory()->highRisk()->create(), '2.1');

    StatusHistory::query()->insert([
        'link_id' => $link->id,
        'owner_id' => $link->owner_id,
        'change_date' => today(),
        'origin' => 'manual',
        'previous_verification' => 'verified',
        'new_verification' => 'verified',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(StatusHistory::query()->where('previous_verification', 'verified')->where('new_verification', 'verified')->count())->toBe(1);
});

// Risks not yet mapped (0019, item 4).

test('an event outside the risk profile shows a risk not yet mapped until it is registered', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create(['name' => 'Assistente']);
    Risk::factory()->for($aiSystem)->inSubdomain('2.1')->create();

    recordEvent($aiSystem, ['risk_subdomains' => ['2.1', '4.1']])->assertSessionHasNoErrors();
    $event = AdverseEvent::sole();

    $this->get(route('dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('unmappedRisks', 1)
            ->where('unmappedRisks.0.ai_system', ['id' => $aiSystem->id, 'name' => 'Assistente'])
            ->where('unmappedRisks.0.subdomain.code', '4.1')
            ->where('unmappedRisks.0.events_count', 1)
            ->where('unmappedRisks.0.latest_event.id', $event->id)
    );

    // The shortcut prefills the system and the subdomain on the risk form.
    $this->get(route('risks.create', ['ai_system' => $aiSystem->id, 'subdomain' => '4.1']))->assertOk();

    $this->post(route('risks.store'), [
        'name' => 'Campanha de desinformação',
        'description' => 'O assistente pode ser usado para gerar desinformação em escala.',
        'risk_subdomain_id' => riskSubdomainId('4.1'),
        'lifecycle_phase' => 'deployment',
        'uncertainty_level' => 'medium',
        'ai_system_id' => $aiSystem->id,
    ])->assertSessionHasNoErrors();

    expect(app(MonitoringProtocol::class)->unmappedRisks())->toBe([]);
});

test('a risk not yet mapped counts every event of the subdomain', function () {
    $aiSystem = AiSystem::factory()->create();
    AdverseEvent::factory()->for($aiSystem)->materializing(['6.3'])->create(['occurrence_date' => '2026-01-01']);
    $latest = AdverseEvent::factory()->for($aiSystem)->materializing(['6.3'])->create(['occurrence_date' => '2026-02-01']);
    // Another system has the same gap on its own.
    AdverseEvent::factory()->materializing(['6.3'])->create(['occurrence_date' => '2025-12-01']);

    $risks = app(MonitoringProtocol::class)->unmappedRisks();

    expect($risks)->toHaveCount(2)
        ->and($risks[0]['ai_system']['id'])->toBe($aiSystem->id)
        ->and($risks[0]['events_count'])->toBe(2)
        ->and($risks[0]['latest_event'])->toBe(['id' => $latest->id, 'occurrence_date' => '2026-02-01']);
});

// Dashboard and links.

test('the dashboard splits the links awaiting reassessment by origin', function () {
    $this->travelTo('2026-05-10 10:00');
    $owner = Owner::factory()->create();
    $aiSystem = AiSystem::factory()->highRisk()->create();

    $manual = linkIn($aiSystem, '7.3');
    app(RecordStatusChange::class)->revert($manual, ChangeOrigin::Manual, $owner, 'Motivo');
    $byEvent = linkIn($aiSystem, '2.1');
    recordEvent($aiSystem)->assertSessionHasNoErrors();
    $byReview = linkIn($aiSystem, '5.1');
    $byReview->update(['next_review_date' => '2026-05-01']);
    $this->artisan('links:flag-due-for-review')->assertSuccessful();
    $byReclassification = linkIn(AiSystem::factory()->highRisk()->create(), '1.1');
    $byReclassification->risk->aiSystem->update(['category' => AiSystemCategory::Unacceptable]);
    app(RecordStatusChange::class)->revert($byReclassification, ChangeOrigin::SystemReclassification);

    $this->get(route('dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('verification.awaitingReassessment', 4)
            ->where('verification.awaitingReassessmentByOrigin', [
                ['origin' => 'review_due', 'count' => 1],
                ['origin' => 'adverse_event', 'count' => 1],
                ['origin' => 'system_reclassification', 'count' => 1],
                ['origin' => 'model_version', 'count' => 0],
                ['origin' => 'data_change', 'count' => 0],
                ['origin' => 'manual', 'count' => 1],
            ])
            ->missing('reviews.dueCount')
    );

    $ids = fn (string $origin): array => collect(
        $this->get(route('links.index', ['verification' => 'awaiting_reassessment', 'origin' => $origin]))->viewData('page')['props']['links']['data'],
    )->pluck('id')->all();

    expect($ids('manual'))->toBe([$manual->id])
        ->and($ids('adverse_event'))->toBe([$byEvent->id])
        ->and($ids('review_due'))->toBe([$byReview->id])
        ->and($ids('system_reclassification'))->toBe([$byReclassification->id]);

    $this->get(route('links.index', ['verification' => 'awaiting_reassessment', 'origin' => 'adverse_event']))->assertInertia(
        fn (AssertableInertia $page) => $page->where('filters.origin', 'adverse_event')
    );
});

test('the link page shows the event behind a reversal', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = linkIn($aiSystem, '2.1');
    recordEvent($aiSystem)->assertSessionHasNoErrors();

    $this->get(route('links.show', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('link.last_reversal.origin', 'adverse_event')
            ->where('link.last_reversal.adverse_event.id', AdverseEvent::sole()->id)
            ->where('link.last_reversal.adverse_event.risk_subdomains.0.code', '2.1')
    );
});

test('the report exports the origin of each verification change', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = linkIn($aiSystem, '2.1');
    recordEvent($aiSystem)->assertSessionHasNoErrors();

    $this->get(route('ai-systems.report.json', $aiSystem))
        ->assertJsonPath('links.0.verification.changes.1.origin', 'adverse_event')
        ->assertJsonPath('links.0.verification.changes.1.recorded_by', null)
        ->assertJsonPath('links.0.verification.changes.1.adverse_event_id', AdverseEvent::sole()->id);

    $rows = parseCsv($this->get(route('ai-systems.report.csv', $aiSystem))->streamedContent());
    $row = array_combine($rows[0], $rows[1]);

    expect($row['verification_changes'])->toContain('(manual, ')
        ->toContain('verified -> declared (adverse_event)');
});
