<?php

use App\Actions\CompileTraceabilityReport;
use App\Enums\CostLevel;
use App\Enums\EvidenceType;
use App\Enums\LinkStatus;
use App\Models\AiSystem;
use App\Models\Evidence;
use App\Models\Link;
use App\Models\Risk;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * Parse a streamed CSV download into rows, dropping the byte order mark.
 *
 * @return list<list<string|null>>
 */
function parseCsv(string $content): array
{
    $lines = preg_split('/\R/', trim(str_replace("\u{FEFF}", '', $content)));

    return array_map(fn (string $line): array => str_getcsv($line, ';', escape: ''), $lines);
}

test('guests are redirected to the login page', function () {
    auth()->logout();

    $aiSystem = AiSystem::factory()->create();

    $this->get(route('ai-systems.report.json', $aiSystem))->assertRedirect(route('login'));
    $this->get(route('ai-systems.report.csv', $aiSystem))->assertRedirect(route('login'));
});

test('the json report compiles the chain of every link of the system', function () {
    $aiSystem = AiSystem::factory()->create();
    $link = Link::factory()
        ->for(Risk::factory()->for($aiSystem))
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
        ->assertJsonCount(1, 'links')
        ->assertJsonPath('links.0.id', $link->id)
        ->assertJsonPath('links.0.status', LinkStatus::Implemented->value)
        ->assertJsonPath('links.0.risk.id', $link->risk_id)
        ->assertJsonPath('links.0.mitigation.id', $link->mitigation_id)
        ->assertJsonPath('links.0.owner.organizational_role', $link->owner->organizational_role)
        ->assertJsonPath('links.0.evidence.0.description', 'Relatório de auditoria')
        ->assertJsonPath('links.0.evidence.0.registration_date', '2026-03-01');

    expect($response->json('links.*.id'))->toBe([$link->id])->not->toContain($otherLink->id);
});

test('the csv report has one row per link with the evidence joined', function () {
    $aiSystem = AiSystem::factory()->create();
    $risk = Risk::factory()->for($aiSystem)->create();
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

    expect($row['link_id'])->toBe((string) $link->id)
        ->and($row['risk_id'])->toBe((string) $risk->id)
        ->and($row['evidence_count'])->toBe('2')
        ->and(substr_count($row['evidence'], ' | '))->toBe(1);
});

test('the csv report neutralises formulas and exports the cost levels', function () {
    $aiSystem = AiSystem::factory()->create();
    Link::factory()
        ->for(Risk::factory()->for($aiSystem)->state(['description' => '=HYPERLINK("http://evil.test")']))
        ->create(['estimated_cost' => CostLevel::High, 'observed_cost' => null]);

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
