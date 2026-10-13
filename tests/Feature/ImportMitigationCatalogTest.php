<?php

use App\Support\MitigationCatalog;
use App\Support\MitigationCatalogSource;

/** A path for a file the test writes, removed after the test. */
function catalogImportPath(string $extension): string
{
    $path = sys_get_temp_dir().'/catalog-import-'.uniqid().'.'.$extension;
    register_shutdown_function(fn () => @unlink($path));

    return $path;
}

/**
 * The sample sheet, with the given changes: rows keyed by line of the sample
 * (2 and 3 are finished, 5 is in review), each a list of column values.
 *
 * @param  array<int, array<string, string>>  $changes
 */
function sampleCatalogCsv(array $changes = [], string $delimiter = ',', bool $bom = false): string
{
    $source = fopen(base_path('tests/Fixtures/catalog-source/catalog.csv'), 'r');
    $header = fgetcsv($source, null, ',', '"', '');
    $rows = [];
    $lines = [2, 3, 5, 6];

    while (($values = fgetcsv($source, null, ',', '"', '')) !== false) {
        $row = array_combine($header, $values);
        $rows[] = array_values([...$row, ...($changes[$lines[count($rows)]] ?? [])]);
    }

    fclose($source);

    $path = catalogImportPath('csv');
    $target = fopen($path, 'w');
    fwrite($target, $bom ? "\u{FEFF}" : '');
    fputcsv($target, $header, $delimiter, '"', '');

    foreach ($rows as $row) {
        fputcsv($target, $row, $delimiter, '"', '');
    }

    fclose($target);

    return $path;
}

/**
 * Run the import on a sheet, writing to a file of the test's own.
 *
 * @return array{0: int, 1: string, 2: string} the exit code, the output and the written file
 */
function importCatalog(?string $csv = null, ?string $meta = null): array
{
    $output = catalogImportPath('json');

    $code = Artisan::call('mitigations:import-catalog', [
        '--csv' => $csv ?? base_path('tests/Fixtures/catalog-source/catalog.csv'),
        '--meta' => $meta ?? base_path('tests/Fixtures/catalog-source/meta.json'),
        '--output' => $output,
    ]);

    return [$code, Artisan::output(), $output];
}

test('the finished rows of the sheet become the catalogue', function () {
    [$code, , $output] = importCatalog();

    expect($code)->toBe(0);

    $catalogue = json_decode((string) file_get_contents($output), true);

    expect($catalogue['entries'])->toHaveCount(2)
        ->and($catalogue['entries'][0])->toBe([
            'name' => 'Plano de resposta a incidentes',
            'source_name' => 'Incident response plan',
            'source_reference' => 'TEST-001',
            'source_document' => 'nist-2024',
            'saeri_subcategory' => '3.6',
            'description' => 'Definir papéis, prazos e passos para conter e comunicar falhas graves do sistema em produção.',
            'suggested_target_risk' => 'Falhas do sistema em produção sem resposta coordenada',
            'expected_evidence' => 'Plano aprovado e registro de um exercício simulado',
            'suggested_cost' => 'medium',
            'uncertainty_level' => 'low',
            'estimate_source' => 'NIST AI 600-1 (2024)',
            'target_risk_subdomains' => ['3.1', '7.3'],
        ])
        ->and($catalogue['entries'][1]['suggested_cost'])->toBe('low')
        ->and($catalogue['entries'][1]['uncertainty_level'])->toBe('medium')
        ->and($catalogue['entries'][1]['target_risk_subdomains'])->toBe(['7.4']);
});

test('the metadata come from the editable file, with the credit and the catalogue marked as real', function () {
    [, , $output] = importCatalog();

    expect(json_decode((string) file_get_contents($output), true)['meta'])->toBe([
        'saeri_database_version' => 'Preliminar, dezembro de 2025',
        'accessed_at' => '2026-10-12',
        'curation_criterion' => 'Critério de exemplo para os testes.',
        'attribution' => MitigationCatalogSource::ATTRIBUTION,
        'fictional' => false,
    ]);
});

test('the generated file is a valid catalogue', function () {
    [, , $output] = importCatalog();

    $catalogue = app(MitigationCatalog::class)->read($output);

    expect($catalogue['meta']['fictional'])->toBeFalse()
        ->and($catalogue['entries'])->toHaveCount(2);
});

test('the same sheet always gives the same file', function () {
    [, , $first] = importCatalog();
    [, , $second] = importCatalog();

    expect(file_get_contents($second))->toBe(file_get_contents($first));
});

test('the rows not finished are left out and reported, the empty ones skipped', function () {
    [$code, $report] = importCatalog();

    expect($code)->toBe(0)
        ->and($report)->toContain('linha 5 (ID TEST-003, Red teaming, Nº 3): situação "Em revisão".')
        ->and($report)->not->toContain('linha 6')
        ->and($report)->toContain('2 mitigações gravadas');
});

test('every problem is reported at once, by line of the csv, and nothing is written', function () {
    $csv = sampleCatalogCsv([
        2 => ['Código da subcategoria' => '9.9', 'Custo sugerido' => 'Altíssimo'],
        3 => ['ID na base' => 'TEST-001', 'Subdomínios: consenso' => '', 'Chave do documento' => 'nowhere-2030'],
    ]);

    [$code, $report, $output] = importCatalog($csv);

    expect($code)->toBe(1)
        ->and($report)->toContain('linha 2 ("Plano de resposta a incidentes"): A subcategoria 9.9 não existe na taxonomia de Saeri et al.')
        ->and($report)->toContain('linha 2 ("Plano de resposta a incidentes"): O valor selecionado em Custo sugerido é inválido.')
        ->and($report)->toContain('linha 3 ("Documentação com model cards"): O documento nowhere-2030 não está entre os documentos de origem da taxonomia.')
        ->and($report)->toContain('linha 3 ("Documentação com model cards"): Toda mitigação do catálogo deve declarar ao menos um subdomínio de risco em Subdomínios: consenso.')
        ->and($report)->toContain('o ID na base "TEST-001" se repete nas linhas 2, 3.')
        ->and(file_exists($output))->toBeFalse();
});

test('a sheet exported with semicolons and a byte order mark is read the same', function () {
    [, , $expected] = importCatalog();
    [$code, , $output] = importCatalog(sampleCatalogCsv(delimiter: ';', bom: true));

    expect($code)->toBe(0)
        ->and(file_get_contents($output))->toBe(file_get_contents($expected));
});

test('a sheet without one of the columns is refused', function () {
    $csv = catalogImportPath('csv');
    file_put_contents($csv, "Nº,ID na base,Situação\n1,TEST-001,Concluída\n");

    [$code, $report] = importCatalog($csv);

    expect($code)->toBe(1)
        ->and($report)->toContain('não tem as colunas: Responsável, Nome original');
});

test('the metadata must name the version, the access date and the criterion', function () {
    $meta = catalogImportPath('json');
    file_put_contents($meta, json_encode(['saeri_database_version' => 'Preliminar', 'accessed_at' => '12/10/2026']));

    [$code, $report] = importCatalog(meta: $meta);

    expect($code)->toBe(1)
        ->and($report)->toContain('meta: ')
        ->and($report)->toContain('meta.accessed_at')
        ->and($report)->toContain('meta.curation_criterion');
});

test('a sheet with no finished row is refused', function () {
    $csv = sampleCatalogCsv([2 => ['Situação' => 'Rascunho'], 3 => ['Situação' => 'Em revisão']]);

    [$code, $report] = importCatalog($csv);

    expect($code)->toBe(1)
        ->and($report)->toContain('nenhuma linha com situação "Concluída".');
});
