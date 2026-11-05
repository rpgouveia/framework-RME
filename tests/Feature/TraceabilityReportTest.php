<?php

use App\Actions\CompileTraceabilityReport;
use App\Actions\RecordAdverseEvent;
use App\Actions\RecordEvidence;
use App\Actions\RecordReassessment;
use App\Actions\RecordStatusChange;
use App\Actions\RecordSystemChange;
use App\Enums\AdverseEventNature;
use App\Enums\AiSystemCategory;
use App\Enums\CauseStatus;
use App\Enums\ChangeOrigin;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\ReassessmentOutcome;
use App\Enums\SystemChangeType;
use App\Enums\SystemSourceType;
use App\Enums\UncertaintyLevel;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Models\Evidence;
use App\Models\Link;
use App\Models\Owner;
use App\Models\Risk;
use App\Models\SystemChange;
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
        ->and($rows[0])->toBe(CompileTraceabilityReport::csvHeader(CompileTraceabilityReport::CSV_HEADER));

    $row = array_combine(CompileTraceabilityReport::CSV_HEADER, $rows[1]);

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
    $row = array_combine(CompileTraceabilityReport::CSV_HEADER, $rows[1]);

    expect($row['risk_description'])->toBe('\'=HYPERLINK("http://evil.test")')
        ->and($row['estimated_cost'])->toBe('Alto')
        ->and($row['observed_cost'])->toBe('');
});

test('a system without links exports empty files', function () {
    $aiSystem = AiSystem::factory()->create();

    $this->get(route('ai-systems.report.json', $aiSystem))
        ->assertOk()
        ->assertJsonPath('links', []);

    $rows = parseCsv($this->get(route('ai-systems.report.csv', $aiSystem))->streamedContent());

    expect($rows)->toBe([CompileTraceabilityReport::csvHeader(CompileTraceabilityReport::CSV_HEADER)]);
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

    expect(array_combine(CompileTraceabilityReport::CSV_HEADER, $rows[1])['system_application_domain'])->toBe('');
});

test('the report quotes the protocol in force and leaves a missing review date empty', function () {
    $aiSystem = AiSystem::factory()->unacceptable()->create();
    Link::factory()->for(Risk::factory()->for($aiSystem))->create();

    $this->get(route('ai-systems.report.json', $aiSystem))
        ->assertJsonPath('protocol', ['key' => 'c3-monitoring-protocol', 'version' => '1.0', 'date' => '2026-10-08'])
        ->assertJsonPath('links.0.next_review_date', null);

    $rows = parseCsv($this->get(route('ai-systems.report.csv', $aiSystem))->streamedContent());
    $row = array_combine(CompileTraceabilityReport::CSV_HEADER, $rows[1]);

    expect($rows[0][0])->toBe('Versão do protocolo')
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
        ->and($rows[0])->toBe(CompileTraceabilityReport::csvHeader(CompileTraceabilityReport::ADVERSE_EVENT_CSV_HEADER))
        // The description comes right after the nature.
        ->and(array_slice($rows[0], 3, 4))->toBe(['Número do evento', 'Natureza do evento', 'Descrição', 'Subdomínios de risco']);

    $first = array_combine(CompileTraceabilityReport::ADVERSE_EVENT_CSV_HEADER, $rows[1]);
    $second = array_combine(CompileTraceabilityReport::ADVERSE_EVENT_CSV_HEADER, $rows[2]);

    expect($first['event_id'])->toBe((string) $incident->id)
        ->and($first['nature'])->toBe('Incidente')
        ->and($first['description'])->toBe('Vazamento')
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
        ->and($second['nature'])->toBe('Quase-incidente')
        ->and($second['description'])->toBe('Tentativa barrada')
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

    expect($rows)->toBe([CompileTraceabilityReport::csvHeader(CompileTraceabilityReport::ADVERSE_EVENT_CSV_HEADER)]);
});

test('guests cannot download the adverse events csv', function () {
    auth()->logout();

    $this->get(route('ai-systems.report.adverse-events', AiSystem::factory()->create()))
        ->assertRedirect(route('login'));
});

// The system in the traceability CSV.

test('the csv repeats the tier, origin and registration date of the system on every row', function () {
    $aiSystem = AiSystem::factory()->create([
        'application_domain' => 'Saúde populacional',
        'category' => AiSystemCategory::High,
        'source_type' => SystemSourceType::ThirdParty,
        'registration_date' => '2026-01-15',
    ]);
    $risk = Risk::factory()->for($aiSystem)->create();
    Link::factory(2)->for($risk)->create();

    $this->get(route('ai-systems.report.json', $aiSystem))
        ->assertJsonPath('system.category', 'high')
        ->assertJsonPath('system.source_type', 'third_party')
        ->assertJsonPath('system.registration_date', '2026-01-15');

    $rows = parseCsv($this->get(route('ai-systems.report.csv', $aiSystem))->streamedContent());

    // Right after the application domain.
    expect(array_slice($rows[0], 3, 5))->toBe(['Domínio de aplicação', 'Faixa de risco', 'Origem do sistema', 'Data de cadastro do sistema', 'Número do vínculo'])
        ->and($rows)->toHaveCount(3);

    foreach ([$rows[1], $rows[2]] as $values) {
        $row = array_combine(CompileTraceabilityReport::CSV_HEADER, $values);

        expect($row['system_application_domain'])->toBe('Saúde populacional')
            ->and($row['system_category'])->toBe('Alto')
            ->and($row['system_source_type'])->toBe('Terceiros')
            ->and($row['system_registration_date'])->toBe('2026-01-15');
    }
});

// The CSV files speak the interface language; the JSON keeps the codes
// (0022, addendum of 2026-11-05).

dataset('csv columns', function (): array {
    $columns = [];

    foreach (['CSV_HEADER', 'ADVERSE_EVENT_CSV_HEADER', 'SYSTEM_CHANGE_CSV_HEADER'] as $header) {
        foreach (constant(CompileTraceabilityReport::class.'::'.$header) as $column) {
            $columns["{$header} {$column}"] = [$column];
        }
    }

    return $columns;
});

test('every column of the three csv files is translated', function (string $column) {
    $key = CompileTraceabilityReport::CSV_COLUMN_KEY_PREFIX.$column;

    expect(CompileTraceabilityReport::csvHeader([$column])[0])->not->toBe($key)->not->toBe('');
})->with('csv columns');

test('each csv names every column differently', function (array $columns) {
    $header = CompileTraceabilityReport::csvHeader($columns);

    expect(array_values(array_unique($header)))->toBe($header);
})->with([
    'traceability' => [CompileTraceabilityReport::CSV_HEADER],
    'adverse events' => [CompileTraceabilityReport::ADVERSE_EVENT_CSV_HEADER],
    'system changes' => [CompileTraceabilityReport::SYSTEM_CHANGE_CSV_HEADER],
]);

/**
 * A link of a high-risk system, through the actions, with every kind of
 * entry the report exports: progress changes, verifications, a reversal by
 * an adverse event, one by a change of the system and a manual one with a
 * reason, and their reassessments. Everything happens on 2026-10-22.
 *
 * @return array{AiSystem, Link, AdverseEvent, SystemChange}
 */
function linkWithFullHistory(): array
{
    test()->travelTo('2026-10-22 10:00');

    $aiSystem = AiSystem::factory()->highRisk()->create(['source_type' => SystemSourceType::ThirdParty]);
    $analyst = Owner::factory()->create(['organizational_role' => 'Analista de dados clínicos']);
    $manager = Owner::factory()->create(['organizational_role' => 'Gerente de saúde populacional']);
    $link = Link::factory()
        ->for(Risk::factory()->for($aiSystem)->inSubdomain('1.1')->state([
            'lifecycle_phase' => LifecyclePhase::DesignAndDevelopment,
            'uncertainty_level' => UncertaintyLevel::Medium,
        ]))
        ->for($analyst, 'owner')
        ->create([
            'status' => LinkStatus::Planned,
            'creation_date' => '2026-10-22',
            'lifecycle_phase' => LifecyclePhase::OperationAndMonitoring,
            'estimated_cost' => CostLevel::Medium,
        ]);

    $status = app(RecordStatusChange::class);
    $evidence = app(RecordEvidence::class);
    $step = fn () => test()->travel(1)->minutes();

    $status->open($link);
    $step();
    $status->handle($link, LinkStatus::InProgress, ['change_date' => today(), 'owner_id' => $analyst->id, 'trigger_reason' => 'Início da implantação']);
    $step();
    $evidence->handle($link, ['type' => EvidenceType::Report, 'description' => 'Relatório de teste de viés']);
    $step();
    $status->verify($link, $analyst);
    $step();

    $event = app(RecordAdverseEvent::class)->handle([
        'nature' => AdverseEventNature::Incident,
        'risk_subdomains' => ['1.1', '1.3'],
        'description' => 'O escore subestimou a necessidade de cuidado de pacientes negros.',
        'occurrence_date' => '2026-10-20',
        'detected_at' => '2026-10-21',
        'ai_system_id' => $aiSystem->id,
    ]);
    $step();
    $evidence->handle($link, ['type' => EvidenceType::TestResult, 'description' => 'Novo teste de viés']);
    $step();
    app(RecordReassessment::class)->handle($link, [
        'outcome' => ReassessmentOutcome::Maintain,
        'owner_id' => $analyst->id,
        'justification' => 'O novo teste não mostrou viés.',
        'cause_status' => CauseStatus::Identified,
        'cause' => 'Custo usado como substituto da necessidade',
        'cause_phase' => LifecyclePhase::DesignAndDevelopment,
    ]);
    $step();

    $change = app(RecordSystemChange::class)->handle($aiSystem, [
        'type' => SystemChangeType::ModelVersion,
        'description' => 'Modelo 2.0, sem o custo entre as variáveis.',
        'change_date' => '2026-10-22',
        'risk_subdomains' => ['1.1'],
    ]);
    $step();
    $evidence->handle($link, ['type' => EvidenceType::AuditLog, 'description' => 'Log da nova versão']);
    $step();
    $status->verify($link, $analyst);
    $step();

    $status->revert($link, ChangeOrigin::Manual, $manager, 'Controle desativado na auditoria');
    $step();
    app(RecordReassessment::class)->handle($link, [
        'outcome' => ReassessmentOutcome::Close,
        'owner_id' => $manager->id,
        'justification' => 'Mitigação descontinuada.',
        'cause_status' => CauseStatus::NotIdentified,
    ]);

    return [$aiSystem, $link->refresh(), $event, $change];
}

test('the csv writes enum values and composite cells in portuguese, with no code', function () {
    [$aiSystem, $link, $event, $change] = linkWithFullHistory();

    $content = $this->get(route('ai-systems.report.csv', $aiSystem))->streamedContent();
    $rows = parseCsv($content);
    $row = array_combine(CompileTraceabilityReport::CSV_HEADER, $rows[1]);

    expect($rows)->toHaveCount(2)
        // The progress history, right after the current progress.
        ->and(array_slice($rows[0], 8, 3))->toBe(['Progresso', 'Mudanças de progresso', 'Verificação'])
        ->and($row['system_category'])->toBe('Alto')
        ->and($row['system_source_type'])->toBe('Terceiros')
        ->and($row['status'])->toBe('Cancelado')
        ->and($row['status_changes'])->toBe(implode(' | ', [
            '2026-10-22: Planejado (Analista de dados clínicos)',
            '2026-10-22: Planejado -> Em andamento (Analista de dados clínicos; motivo: Início da implantação)',
            '2026-10-22: Em andamento -> Cancelado (Gerente de saúde populacional; motivo: Mitigação descontinuada.)',
        ]))
        ->and($row['verification_status'])->toBe('Declarada')
        // The reversals say what caused them: the event, the change, or the
        // role and the reason of a manual one.
        ->and($row['verification_changes'])->toBe(implode(' | ', [
            '2026-10-22: Declarada -> Verificada (Manual, Analista de dados clínicos)',
            "2026-10-22: Verificada -> Declarada (Evento adverso #{$event->id})",
            '2026-10-22: Declarada -> Verificada (Manual, Analista de dados clínicos)',
            "2026-10-22: Verificada -> Declarada (Nova versão do modelo, mudança #{$change->id})",
            '2026-10-22: Declarada -> Verificada (Manual, Analista de dados clínicos)',
            '2026-10-22: Verificada -> Declarada (Manual, Gerente de saúde populacional; motivo: Controle desativado na auditoria)',
        ]))
        ->and($row['lifecycle_phase'])->toBe('Operação e monitoramento')
        ->and($row['estimated_cost'])->toBe('Médio')
        ->and($row['risk_lifecycle_phase'])->toBe('Projeto e desenvolvimento')
        ->and($row['risk_uncertainty_level'])->toBe('Média')
        ->and($row['evidence'])->toBe(implode(' | ', [
            'Relatório: Relatório de teste de viés (2026-10-22)',
            'Resultado de teste: Novo teste de viés (2026-10-22)',
            'Log de auditoria: Log da nova versão (2026-10-22)',
        ]))
        ->and($row['reassessments'])->toBe(implode(' | ', [
            '2026-10-22: Manter, por Analista de dados clínicos (reversão: Evento adverso; causa: Apurada, Projeto e desenvolvimento, Custo usado como substituto da necessidade) O novo teste não mostrou viés.',
            '2026-10-22: Encerrar, por Gerente de saúde populacional (reversão: Manual; causa: Não apurada) Mitigação descontinuada.',
        ]))
        // Without the description, which the adverse events CSV has.
        ->and($row['reverting_adverse_events'])->toBe("#{$event->id} Incidente [1.1, 1.3], ocorrido em 2026-10-20, detectado em 2026-10-21");

    $codes = collect(glob(app_path('Enums/*.php')))
        ->flatMap(fn (string $path): array => ('App\\Enums\\'.basename($path, '.php'))::values())
        ->unique();
    $pattern = '/\b('.$codes->map(fn (string $code): string => preg_quote($code, '/'))->implode('|').')\b/';
    $enumCells = array_intersect_key($row, array_flip([
        'system_category', 'system_source_type', 'status', 'status_changes', 'verification_status',
        'verification_changes', 'lifecycle_phase', 'estimated_cost', 'observed_cost', 'risk_lifecycle_phase',
        'risk_uncertainty_level', 'evidence', 'reassessments', 'reverting_adverse_events',
    ]));

    // No code in the cells that carry enum values, nor a code with an
    // underscore anywhere in the file, nor the English connectives.
    foreach ($enumCells as $column => $cell) {
        expect(preg_match($pattern, $cell))->toBe(0, "{$column}: {$cell}");
    }

    foreach ($codes->filter(fn (string $code): bool => str_contains($code, '_')) as $code) {
        expect($content)->not->toContain($code);
    }

    expect($content)->not->toContain(' by ')
        ->not->toContain('reversal:')
        ->not->toContain('cause:')
        ->not->toContain('reason:')
        ->not->toContain('occurred')
        ->not->toContain('detected');
});

test('the json keeps the english codes and exports the progress history and the event description', function () {
    [$aiSystem, $link, $event, $change] = linkWithFullHistory();

    $this->get(route('ai-systems.report.json', $aiSystem))
        ->assertJsonPath('system.category', 'high')
        ->assertJsonPath('system.source_type', 'third_party')
        ->assertJsonPath('links.0.status', 'cancelled')
        // Oldest first, from the entry that opened the trail; a role, never
        // a person.
        ->assertJsonPath('links.0.status_changes', [
            ['date' => '2026-10-22', 'from' => null, 'to' => 'planned', 'recorded_by' => 'Analista de dados clínicos', 'reason' => null],
            ['date' => '2026-10-22', 'from' => 'planned', 'to' => 'in_progress', 'recorded_by' => 'Analista de dados clínicos', 'reason' => 'Início da implantação'],
            ['date' => '2026-10-22', 'from' => 'in_progress', 'to' => 'cancelled', 'recorded_by' => 'Gerente de saúde populacional', 'reason' => 'Mitigação descontinuada.'],
        ])
        ->assertJsonPath('links.0.verification.status', 'declared')
        ->assertJsonPath('links.0.verification.changes.*.origin', ['manual', 'adverse_event', 'manual', 'model_version', 'manual', 'manual'])
        ->assertJsonPath('links.0.verification.changes.1.adverse_event_id', $event->id)
        ->assertJsonPath('links.0.verification.changes.3.system_change_id', $change->id)
        // The JSON keeps every reason, also those of automatic reversals.
        ->assertJsonPath('links.0.verification.changes.5.reason', 'Controle desativado na auditoria')
        ->assertJsonPath('links.0.lifecycle_phase', 'operation_and_monitoring')
        ->assertJsonPath('links.0.estimated_cost', 'medium')
        ->assertJsonPath('links.0.risk.uncertainty_level', 'medium')
        ->assertJsonPath('links.0.evidence.*.type', ['report', 'test_result', 'audit_log'])
        ->assertJsonPath('links.0.reassessments.*.outcome', ['maintain', 'close'])
        ->assertJsonPath('links.0.reassessments.*.cause_status', ['identified', 'not_identified'])
        ->assertJsonPath('links.0.reassessments.0.cause_phase', 'design_and_development')
        ->assertJsonPath('links.0.reassessments.*.reversal_origin', ['adverse_event', 'manual'])
        ->assertJsonPath('links.0.reverting_adverse_events.0.nature', 'incident')
        ->assertJsonPath('links.0.reverting_adverse_events.0.description', 'O escore subestimou a necessidade de cuidado de pacientes negros.')
        ->assertJsonPath('adverse_events.0.description', 'O escore subestimou a necessidade de cuidado de pacientes negros.')
        ->assertJsonPath('system_changes.0.type', 'model_version');
});
