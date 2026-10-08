<?php

use App\Actions\CompileTraceabilityReport;
use App\Actions\RecordEvidence;
use App\Actions\RecordReassessment;
use App\Actions\RecordStatusChange;
use App\Enums\AiSystemCategory;
use App\Enums\CauseStatus;
use App\Enums\ChangeOrigin;
use App\Enums\EvidenceType;
use App\Enums\LinkStatus;
use App\Enums\ReassessmentOutcome;
use App\Enums\SystemChangeType;
use App\Enums\VerificationStatus;
use App\Models\AiSystem;
use App\Models\Link;
use App\Models\Owner;
use App\Models\Risk;
use App\Models\SystemChange;
use App\Models\User;
use App\Support\MonitoringProtocol;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * A link of the system with its risk in the subdomain, verified through the
 * actions unless asked otherwise.
 */
function changeLink(AiSystem $aiSystem, string $subdomain, bool $verified = true): Link
{
    $link = Link::factory()
        ->for(Risk::factory()->for($aiSystem)->inSubdomain($subdomain))
        ->create(['status' => LinkStatus::InProgress]);

    if ($verified) {
        app(RecordEvidence::class)->handle($link, ['type' => EvidenceType::Report, 'description' => 'Prova']);
        app(RecordStatusChange::class)->verify($link, Owner::factory()->create());
    }

    return $link->refresh();
}

/**
 * Record a change of the system through the form.
 *
 * @param  array<string, mixed>  $overrides
 */
function recordChange(AiSystem $aiSystem, array $overrides = []): TestResponse
{
    return test()->post(route('ai-systems.system-changes.store', $aiSystem), array_merge([
        'type' => SystemChangeType::ModelVersion->value,
        'description' => 'Troca do modelo pela versão 2.0.',
        'change_date' => today()->subDay()->toDateString(),
    ], $overrides));
}

// The reversal (0021, item 2).

test('a change naming no subdomain reverts every verified link of the system', function () {
    $this->travelTo('2026-05-10 10:00');
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $privacy = changeLink($aiSystem, '2.1');
    $robustness = changeLink($aiSystem, '7.3');
    $declared = changeLink($aiSystem, '2.1', verified: false);
    $cancelled = changeLink($aiSystem, '2.1');
    $cancelled->update(['status' => LinkStatus::Cancelled]);
    $otherSystem = changeLink(AiSystem::factory()->highRisk()->create(), '2.1');

    $this->travelTo('2026-05-12 10:00');
    recordChange($aiSystem, ['change_date' => '2026-05-11'])->assertSessionHasNoErrors();

    $change = SystemChange::sole();
    $entry = $privacy->lastReversal()->first();

    expect($privacy->refresh()->verification_status)->toBe(VerificationStatus::Declared)
        ->and($robustness->refresh()->verification_status)->toBe(VerificationStatus::Declared)
        ->and($entry->origin)->toBe(ChangeOrigin::ModelVersion)
        ->and($entry->owner_id)->toBeNull()
        ->and($entry->system_change_id)->toBe($change->id)
        ->and($entry->trigger_reason)->toBe('Nova versão do modelo de 11/05/2026.')
        // Declared, cancelled and other systems' links are left alone.
        ->and($declared->statusHistories()->whereNotNull('new_verification')->count())->toBe(0)
        ->and($cancelled->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        ->and($otherSystem->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        ->and($change->reversals()->pluck('link_id')->sort()->values()->all())->toBe([$privacy->id, $robustness->id]);
});

test('a change naming subdomains reverts only the links of those risks', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $privacy = changeLink($aiSystem, '2.1');
    $robustness = changeLink($aiSystem, '7.3');

    recordChange($aiSystem, [
        'type' => SystemChangeType::DataChange->value,
        'risk_subdomains' => ['2.1'],
    ])->assertSessionHasNoErrors();

    expect($privacy->refresh()->verification_status)->toBe(VerificationStatus::Declared)
        ->and($privacy->lastReversal->origin)->toBe(ChangeOrigin::DataChange)
        ->and($robustness->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        ->and(SystemChange::sole()->riskSubdomains->pluck('code')->all())->toBe(['2.1']);
});

test('a system in the unacceptable tier records the change and reverts nothing', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = changeLink($aiSystem, '2.1');
    // Reclassified outside UpdateAiSystem, so the link kept its verification.
    $aiSystem->update(['category' => AiSystemCategory::Unacceptable]);

    recordChange($aiSystem)->assertSessionHasNoErrors();

    expect(SystemChange::count())->toBe(1)
        ->and($link->refresh()->verification_status)->toBe(VerificationStatus::Verified)
        ->and(SystemChange::sole()->reversals()->count())->toBe(0);
});

// Validation.

test('a change is refused without its fields or with a date in the future', function (array $overrides, string $field, ?string $message) {
    $this->travelTo('2026-05-10 10:00');

    $response = recordChange(AiSystem::factory()->create(), $overrides);

    $message === null
        ? $response->assertSessionHasErrors($field)
        : $response->assertSessionHasErrors([$field => $message]);

    expect(SystemChange::count())->toBe(0);
})->with([
    'no description' => [['description' => ''], 'description', null],
    'no type' => [['type' => null], 'type', null],
    'unknown type' => [['type' => 'new_hardware'], 'type', null],
    'future date' => [['change_date' => '2026-05-11'], 'change_date', 'A data da mudança não pode ser futura.'],
    'unknown subdomain' => [['risk_subdomains' => ['9.9']], 'risk_subdomains.0', 'O código 9.9 não é um subdomínio da taxonomia de domínios de risco do MIT.'],
    'a domain' => [['risk_subdomains' => ['2']], 'risk_subdomains.0', 'O código 2 não é um subdomínio da taxonomia de domínios de risco do MIT.'],
]);

test('today is a valid change date', function () {
    recordChange(AiSystem::factory()->create(), ['change_date' => today()->toDateString()])->assertSessionHasNoErrors();
});

// Reassessment (0021, item 5).

test('a link reverted by a system change is reassessed with no cause to find', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = changeLink($aiSystem, '2.1');
    recordChange($aiSystem)->assertSessionHasNoErrors();

    $this->get(route('links.reassessments.create', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('causeApplies', false)
            ->where('reversal.origin', 'model_version')
            ->where('reversal.system_change.id', SystemChange::sole()->id)
    );

    $reassessment = app(RecordReassessment::class)->handle($link->refresh(), [
        'outcome' => ReassessmentOutcome::Adjust,
        'owner_id' => $link->owner_id,
        'justification' => 'A nova versão segue coberta pela mitigação.',
        'cause_status' => CauseStatus::Identified,
        'cause' => 'Ignorada',
        'cause_phase' => 'design',
    ]);

    expect($reassessment->cause_status)->toBe(CauseStatus::NotApplicable)
        ->and($reassessment->cause)->toBeNull();
});

test('links reverted by a change await reassessment under its origin', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = changeLink($aiSystem, '2.1');
    recordChange($aiSystem, ['type' => SystemChangeType::DataChange->value])->assertSessionHasNoErrors();

    $ids = fn (string $origin): array => collect(
        $this->get(route('links.index', ['verification' => 'awaiting_reassessment', 'origin' => $origin]))->viewData('page')['props']['links']['data'],
    )->pluck('id')->all();

    expect($ids('data_change'))->toBe([$link->id])
        ->and($ids('model_version'))->toBe([]);

    $this->get(route('dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page->where(
            'verification.awaitingReassessmentByOrigin',
            fn ($rows) => collect($rows)->firstWhere('origin', 'data_change')['count'] === 1
                && collect($rows)->firstWhere('origin', 'model_version')['count'] === 0,
        )
    );

    $this->get(route('links.show', $link))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('link.last_reversal.origin', 'data_change')
            ->where('link.last_reversal.system_change.id', SystemChange::sole()->id)
    );
});

// Risks not yet mapped (0021, item 4).

test('a subdomain named by a change with no risk shows as not yet mapped, with its source', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create(['name' => 'Triagem']);
    Risk::factory()->for($aiSystem)->inSubdomain('2.1')->create();

    recordChange($aiSystem, ['risk_subdomains' => ['2.1', '6.2']])->assertSessionHasNoErrors();
    $change = SystemChange::sole();

    $this->get(route('dashboard'))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->has('unmappedRisks', 1)
            ->where('unmappedRisks.0.subdomain.code', '6.2')
            ->where('unmappedRisks.0.sources', ['system_change'])
            ->where('unmappedRisks.0.changes_count', 1)
            ->where('unmappedRisks.0.latest_change.id', $change->id)
            ->where('unmappedRisks.0.latest_event', null)
    );

    $this->get(route('system-changes.show', $change))->assertInertia(
        fn (AssertableInertia $page) => $page->where('unmappedSubdomainCodes', ['6.2'])
    );

    // An event in the same gap adds itself as a second source.
    $this->post(route('adverse-events.store'), [
        'nature' => 'incident',
        'risk_subdomains' => ['6.2'],
        'description' => 'Evento',
        'occurrence_date' => today()->toDateString(),
        'ai_system_id' => $aiSystem->id,
    ])->assertSessionHasNoErrors();

    $risks = app(MonitoringProtocol::class)->unmappedRisks();

    expect($risks)->toHaveCount(1)
        ->and($risks[0]['sources'])->toBe(['adverse_event', 'system_change'])
        ->and($risks[0]['events_count'])->toBe(1);
});

// Screens.

test('the change form announces the links it may revert', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $verified = changeLink($aiSystem, '2.1');
    changeLink($aiSystem, '7.3', verified: false);

    $this->get(route('ai-systems.system-changes.create', $aiSystem))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('system-changes/create')
            ->has('verifiedLinks', 1)
            ->where('verifiedLinks.0.id', $verified->id)
            ->where('verifiedLinks.0.subdomain', '2.1')
            ->where('expected.0.code', '2.1')
            ->has('riskDomains', 7)
            ->where('types', [
                ['value' => 'model_version', 'label' => 'Nova versão do modelo'],
                ['value' => 'data_change', 'label' => 'Alteração na base de dados'],
            ])
    );
});

test('the change page shows its reversals and how far their reassessment went', function () {
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $first = changeLink($aiSystem, '2.1');
    changeLink($aiSystem, '7.3');
    recordChange($aiSystem)->assertSessionHasNoErrors();
    $this->travel(1)->minutes();

    app(RecordReassessment::class)->handle($first->refresh(), [
        'outcome' => ReassessmentOutcome::Close,
        'owner_id' => $first->owner_id,
        'justification' => 'Encerrado',
    ]);

    $change = SystemChange::sole();

    $this->get(route('system-changes.show', $change))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('system-changes/show')
            ->has('systemChange.reversals', 2)
            ->where('systemChange.reversals', fn ($reversals) => collect($reversals)->filter(fn ($r) => $r['reassessment'] !== null)->count() === 1)
    );

    $this->get(route('ai-systems.show', $aiSystem))->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('aiSystem.system_changes.0.id', $change->id)
            ->where('aiSystem.system_changes.0.reversals_count', 2)
    );

    $this->get(route('ai-systems.system-changes.index', $aiSystem))->assertInertia(
        fn (AssertableInertia $page) => $page->has('systemChanges.data', 1)
    );
});

test('changes are append only', function () {
    $change = SystemChange::factory()->create();

    $this->get("/system-changes/{$change->id}/edit")->assertNotFound();
    $this->put("/system-changes/{$change->id}", [])->assertMethodNotAllowed();
    $this->delete("/system-changes/{$change->id}")->assertMethodNotAllowed();
});

// Report (0021).

test('the report exports the changes, also those that reverted nothing', function () {
    $this->travelTo('2026-05-12 10:00');
    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = changeLink($aiSystem, '2.1');

    recordChange($aiSystem, ['type' => 'data_change', 'change_date' => '2026-05-10', 'risk_subdomains' => ['2.1', '6.2']])->assertSessionHasNoErrors();
    // Naming a subdomain no verified link is in: nothing to revert.
    recordChange($aiSystem, ['type' => 'model_version', 'change_date' => '2026-05-11', 'risk_subdomains' => ['7.3']])->assertSessionHasNoErrors();
    [$reverting, $silent] = SystemChange::query()->orderBy('id')->get();

    $this->get(route('ai-systems.report.json', $aiSystem))
        ->assertJsonPath('system_changes.0', [
            'id' => $reverting->id,
            'type' => 'data_change',
            'description' => 'Troca do modelo pela versão 2.0.',
            'change_date' => '2026-05-10',
            'risk_subdomains' => ['2.1', '6.2'],
            'reverted_link_ids' => [$link->id],
        ])
        ->assertJsonPath('system_changes.1.reverted_link_ids', [])
        ->assertJsonPath('links.0.verification.changes.1.origin', 'data_change')
        ->assertJsonPath('links.0.verification.changes.1.system_change_id', $reverting->id);

    $response = $this->get(route('ai-systems.report.system-changes', $aiSystem));
    $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $rows = parseCsv($response->streamedContent());

    expect($rows)->toHaveCount(3)
        ->and($rows[0])->toBe(CompileTraceabilityReport::SYSTEM_CHANGE_CSV_HEADER);

    $first = array_combine($rows[0], $rows[1]);
    $second = array_combine($rows[0], $rows[2]);

    expect($first['change_id'])->toBe((string) $reverting->id)
        ->and($first['type'])->toBe('data_change')
        ->and($first['change_date'])->toBe('2026-05-10')
        ->and($first['risk_subdomains'])->toStartWith('2.1 ')->toContain(' | 6.2 ')
        ->and($first['reverted_link_ids'])->toBe((string) $link->id)
        ->and($first['reverted_links'])->toBe("{$link->risk->name} -> {$link->mitigation->name}")
        ->and($first['unmapped_subdomains_at_export'])->toBe('6.2')
        ->and($second['change_id'])->toBe((string) $silent->id)
        ->and($second['reverted_link_ids'])->toBe('')
        ->and($second['unmapped_subdomains_at_export'])->toBe('7.3');

    $csv = parseCsv($this->get(route('ai-systems.report.csv', $aiSystem))->streamedContent());

    expect(array_combine($csv[0], $csv[1])['verification_changes'])->toContain('verified -> declared (data_change)');
});
