<?php

use App\Actions\CompileTraceabilityReport;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\LinkStatus;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\Evidence;
use App\Models\Link;
use App\Models\Risk;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests are redirected to the login page', function () {
    auth()->logout();

    $aiSystem = AiSystem::factory()->create();

    $this->get(route('ai-systems.report.json', $aiSystem))->assertRedirect(route('login'));
    $this->get(route('ai-systems.report.csv', $aiSystem))->assertRedirect(route('login'));
});

test('the json report compiles the chain of every link of the system', function () {
    $aiSystem = AiSystem::factory()->create();
    $link = Link::factory()
        ->for(Risk::factory()->for($aiSystem)->inSubdomain('5.1'))
        ->create(['status' => LinkStatus::Implemented]);
    Evidence::factory()->for($link)->create([
        'type' => EvidenceType::cases()[0],
        'description' => 'Relatório de auditoria',
        'registration_date' => '2026-03-01',
    ]);

    $otherLink = Link::factory()->create();

    $response = $this->get(route('ai-systems.report.json', $aiSystem));

    $response->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="traceability-report-'.$aiSystem->id.'-'.now()->toDateString().'.json"')
        ->assertJsonPath('system.id', $aiSystem->id)
        ->assertJsonPath('system.application_domain', $aiSystem->application_domain)
        ->assertJsonCount(1, 'links')
        ->assertJsonPath('links.0.id', $link->id)
        ->assertJsonPath('links.0.status', LinkStatus::Implemented->value)
        ->assertJsonPath('links.0.risk.id', $link->risk_id)
        ->assertJsonPath('links.0.risk.name', $link->risk->name)
        // Traceable to the MIT AI risk domain taxonomy.
        ->assertJsonPath('links.0.risk.domain', ['code' => '5', 'name' => 'Interação humano-computador'])
        ->assertJsonPath('links.0.risk.subdomain', ['code' => '5.1', 'name' => 'Dependência excessiva e uso inseguro'])
        ->assertJsonPath('links.0.mitigation.id', $link->mitigation_id)
        ->assertJsonPath('links.0.mitigation.name', $link->mitigation->name)
        ->assertJsonPath('links.0.mitigation.saeri_subcategory.code', $link->mitigation->saeriSubcategory->code)
        ->assertJsonPath('links.0.mitigation.saeri_subcategory.name', $link->mitigation->saeriSubcategory->name)
        ->assertJsonPath('links.0.mitigation.saeri_category.code', $link->mitigation->saeriSubcategory->parent->code)
        ->assertJsonPath('links.0.mitigation.source_reference', $link->mitigation->source_reference)
        ->assertJsonPath('links.0.mitigation.source_document', $link->mitigation->source_document)
        ->assertJsonPath('links.0.mitigation.estimate_source', $link->mitigation->estimate_source)
        ->assertJsonPath('links.0.owner.organizational_role', $link->owner->organizational_role)
        ->assertJsonPath('links.0.evidence.0.description', 'Relatório de auditoria')
        ->assertJsonPath('links.0.evidence.0.registration_date', '2026-03-01');

    expect($response->json('links.*.id'))->toBe([$link->id])->not->toContain($otherLink->id);
});

test('the csv report has one row per link with the evidence joined', function () {
    $aiSystem = AiSystem::factory()->create(['application_domain' => 'Crédito e concessão financeira']);
    $risk = Risk::factory()->for($aiSystem)->inSubdomain('7.6')->create();
    $link = Link::factory()->for($risk)->create();
    Link::factory()->for($risk)->create();
    Evidence::factory(2)->for($link)->create();

    Link::factory()->create();

    $response = $this->get(route('ai-systems.report.csv', $aiSystem));

    $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    $rows = parseCsv($content);

    expect($content)->toStartWith("\u{FEFF}")
        ->and($rows)->toHaveCount(3)
        ->and($rows[0])->toBe(CompileTraceabilityReport::CSV_HEADER);

    $row = array_combine($rows[0], $rows[1]);

    expect($row['system_id'])->toBe((string) $aiSystem->id)
        ->and($row['system_name'])->toBe($aiSystem->name)
        ->and($row['system_application_domain'])->toBe('Crédito e concessão financeira')
        ->and($row['link_id'])->toBe((string) $link->id)
        ->and($row['risk_id'])->toBe((string) $risk->id)
        ->and($row['risk_name'])->toBe($risk->name)
        ->and($row['risk_domain_code'])->toBe('7')
        ->and($row['risk_domain_name'])->toBe('Segurança, falhas e limitações de sistemas de IA')
        ->and($row['risk_subdomain_code'])->toBe('7.6')
        ->and($row['risk_subdomain_name'])->toBe('Riscos multiagentes')
        ->and($row['mitigation_name'])->toBe($link->mitigation->name)
        // Traceable to Saeri et al.: the subcategory, its category, the entry
        // in their database and the source of the estimates.
        ->and($row['mitigation_saeri_subcategory_code'])->toBe($link->mitigation->saeriSubcategory->code)
        ->and($row['mitigation_saeri_subcategory_name'])->toBe($link->mitigation->saeriSubcategory->name)
        ->and($row['mitigation_saeri_category_code'])->toBe($link->mitigation->saeriSubcategory->parent->code)
        ->and($row['mitigation_source_reference'])->toBe($link->mitigation->source_reference)
        ->and($row['mitigation_source_document'])->toBe($link->mitigation->source_document)
        ->and($row['mitigation_estimate_source'])->toBe($link->mitigation->estimate_source)
        ->and($row['evidence_count'])->toBe('2')
        ->and(substr_count($row['evidence'], ' | '))->toBe(1);
});

test('the csv report neutralises formulas and exports the cost levels', function () {
    $aiSystem = AiSystem::factory()->create();
    Link::factory()
        ->for(Risk::factory()->for($aiSystem)->state(['description' => '=HYPERLINK("http://evil.test")']))
        ->create(['estimated_cost' => CostLevel::High]);

    $rows = parseCsv($this->get(route('ai-systems.report.csv', $aiSystem))->streamedContent());
    $row = array_combine($rows[0], $rows[1]);

    expect($row['risk_description'])->toBe('\'=HYPERLINK("http://evil.test")')
        ->and($row['estimated_cost'])->toBe('high')
        ->and($row['observed_cost'])->toBe('');
});

test('a system without links exports empty files', function () {
    $aiSystem = AiSystem::factory()->create();

    $this->get(route('ai-systems.report.json', $aiSystem))
        ->assertOk()
        ->assertJsonPath('links', []);

    $rows = parseCsv($this->get(route('ai-systems.report.csv', $aiSystem))->streamedContent());

    expect($rows)->toBe([CompileTraceabilityReport::CSV_HEADER]);
});

test('an unknown system returns not found', function () {
    $this->get(route('ai-systems.report.json', 999))->assertNotFound();
    $this->get(route('ai-systems.report.csv', 999))->assertNotFound();
});

test('a system without an application domain exports it empty', function () {
    $aiSystem = AiSystem::factory()->create(['application_domain' => null]);
    Link::factory()->for(Risk::factory()->for($aiSystem))->create();

    $this->get(route('ai-systems.report.json', $aiSystem))
        ->assertJsonPath('system.application_domain', null);

    $rows = parseCsv($this->get(route('ai-systems.report.csv', $aiSystem))->streamedContent());

    expect(array_combine($rows[0], $rows[1])['system_application_domain'])->toBe('');
});

test('the report quotes the protocol in force and leaves a missing review date empty', function () {
    $aiSystem = AiSystem::factory()->unacceptable()->create();
    Link::factory()->for(Risk::factory()->for($aiSystem))->create();

    $this->get(route('ai-systems.report.json', $aiSystem))
        ->assertJsonPath('protocol', ['key' => 'c3-monitoring-protocol', 'version' => '1.0', 'date' => '2026-10-08'])
        ->assertJsonPath('links.0.next_review_date', null);

    $rows = parseCsv($this->get(route('ai-systems.report.csv', $aiSystem))->streamedContent());
    $row = array_combine($rows[0], $rows[1]);

    expect($rows[0][0])->toBe('protocol_version')
        ->and($row['protocol_version'])->toBe('1.0')
        ->and($row['next_review_date'])->toBe('');
});

// The adverse events CSV (0020): one row per event, also those that
// reverted no link.

test('the adverse events csv has one row per event of the system', function () {
    $this->actingAs(User::factory()->create());
    $this->travelTo('2026-05-20 10:00');

    $aiSystem = AiSystem::factory()->highRisk()->create();
    $link = Link::factory()->verified()
        ->for(Risk::factory()->for($aiSystem)->inSubdomain('2.1'))
        ->create(['status' => LinkStatus::InProgress, 'next_review_date' => '2026-08-01']);
    $interceptor = Link::factory()->verified()
        ->for(Risk::factory()->for($aiSystem)->inSubdomain('2.2'))
        ->create(['status' => LinkStatus::InProgress, 'next_review_date' => '2026-08-01']);

    // An incident that reverts the 2.1 link, also touching 4.1, where the
    // system has no risk.
    $this->post(route('adverse-events.store'), [
        'nature' => 'incident',
        'risk_subdomains' => ['2.1', '4.1'],
        'description' => 'Vazamento',
        'occurrence_date' => '2026-05-10',
        'detected_at' => '2026-05-12',
        'ai_system_id' => $aiSystem->id,
    ])->assertSessionHasNoErrors();
    // A near miss intercepted by the 2.2 link: it reverts nothing.
    $this->post(route('adverse-events.store'), [
        'nature' => 'near_miss',
        'risk_subdomains' => ['2.2'],
        'description' => 'Tentativa barrada',
        'occurrence_date' => '2026-05-15',
        'ai_system_id' => $aiSystem->id,
        'intercepting_link_id' => $interceptor->id,
    ])->assertSessionHasNoErrors();
    // Another system's event stays out.
    AdverseEvent::factory()->create();

    [$incident, $nearMiss] = AdverseEvent::query()->where('ai_system_id', $aiSystem->id)->orderBy('id')->get();

    $response = $this->get(route('ai-systems.report.adverse-events', $aiSystem));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename=adverse-events-'.$aiSystem->id.'-2026-05-20.csv');

    $rows = parseCsv($response->streamedContent());

    expect($rows)->toHaveCount(3)
        ->and($rows[0])->toBe(CompileTraceabilityReport::ADVERSE_EVENT_CSV_HEADER);

    $first = array_combine($rows[0], $rows[1]);
    $second = array_combine($rows[0], $rows[2]);

    expect($first['event_id'])->toBe((string) $incident->id)
        ->and($first['nature'])->toBe('incident')
        ->and($first['risk_subdomains'])->toStartWith('2.1 ')->toContain(' | 4.1 ')
        ->and($first['occurrence_date'])->toBe('2026-05-10')
        ->and($first['detected_at'])->toBe('2026-05-12')
        ->and($first['intercepting_link_id'])->toBe('')
        ->and($first['reverted_link_ids'])->toBe((string) $link->id)
        ->and($first['reverted_links'])->toBe("{$link->risk->name} -> {$link->mitigation->name}")
        ->and($first['unmapped_subdomains_at_export'])->toBe('4.1')
        ->and($first['protocol_version'])->toBe('1.0')
        ->and($first['system_name'])->toBe($aiSystem->name)
        // Reverting nothing, it is still there.
        ->and($second['event_id'])->toBe((string) $nearMiss->id)
        ->and($second['nature'])->toBe('near_miss')
        ->and($second['detected_at'])->toBe('')
        ->and($second['intercepting_link_id'])->toBe((string) $interceptor->id)
        ->and($second['intercepting_link'])->toBe("{$interceptor->risk->name} -> {$interceptor->mitigation->name}")
        ->and($second['reverted_link_ids'])->toBe('')
        ->and($second['unmapped_subdomains_at_export'])->toBe('');
});

test('a system without events exports only the header of the adverse events csv', function () {
    $this->actingAs(User::factory()->create());
    $aiSystem = AiSystem::factory()->create();

    $rows = parseCsv($this->get(route('ai-systems.report.adverse-events', $aiSystem))->streamedContent());

    expect($rows)->toBe([CompileTraceabilityReport::ADVERSE_EVENT_CSV_HEADER]);
});

test('guests cannot download the adverse events csv', function () {
    auth()->logout();

    $this->get(route('ai-systems.report.adverse-events', AiSystem::factory()->create()))
        ->assertRedirect(route('login'));
});
