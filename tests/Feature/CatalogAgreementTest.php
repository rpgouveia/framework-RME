<?php

use App\Support\AnnotationAgreement;
use App\Support\MitigationCatalogSource;

/** A path for a file the test writes, removed after the test. */
function agreementPath(string $extension): string
{
    $path = sys_get_temp_dir().'/catalog-agreement-'.uniqid().'.'.$extension;
    register_shutdown_function(fn () => @unlink($path));

    return $path;
}

/**
 * A sheet with every column of the "Catálogo" sheet, one row per given set
 * of values (the number filled in), the other columns empty.
 *
 * @param  list<array<string, string>>  $rows
 */
function agreementCsv(array $rows, string $delimiter = ',', bool $bom = false): string
{
    $path = agreementPath('csv');
    $target = fopen($path, 'w');
    fwrite($target, $bom ? "\u{FEFF}" : '');
    fputcsv($target, MitigationCatalogSource::COLUMNS, $delimiter, '"', '');

    foreach ($rows as $index => $values) {
        $row = array_fill_keys(MitigationCatalogSource::COLUMNS, '');
        $row['Nº'] = (string) ($index + 1);
        fputcsv($target, array_values([...$row, ...$values]), $delimiter, '"', '');
    }

    fclose($target);

    return $path;
}

/**
 * The twelve mitigations of the reference case, in rows of every situation.
 *
 * @return list<array<string, string>>
 */
function twelveAnnotatedRows(): array
{
    $items = [
        ['2.1', '2.1'], ['1.2', '4.3'], ['1.1, 7.3', '1.1, 7.3'], ['5.1', '5.1'],
        ['1.2', '7.4'], ['2.2', '7.4'], ['1.1, 5.1', '7.3'], ['3.1', '3.1'],
        ['1.2, 3.1', '1.2'], ['2.2, 7.4', '2.2, 7.4'], ['1.1, 1.2', '5.1, 7.3'], ['6.5', '3.1, 4.3'],
    ];
    $situations = ['Concluída', 'Em consenso', '', 'Em anotação'];

    return array_map(fn (array $item, int $index): array => [
        'Subdomínios: anotador A' => $item[0],
        'Subdomínios: anotador B' => $item[1],
        'Situação' => $situations[$index % count($situations)],
    ], $items, array_keys($items));
}

/**
 * Run the measure on a sheet, writing to a file of the test's own.
 *
 * @return array{0: int, 1: string, 2: string} the exit code, the output and the written file
 */
function measureAgreement(string $csv, bool $strict = false): array
{
    $output = agreementPath('json');

    $code = Artisan::call('mitigations:agreement', ['--csv' => $csv, '--output' => $output, '--strict' => $strict]);

    return [$code, Artisan::output(), $output];
}

test('every row with both annotations is measured, whatever its situation', function () {
    [$code, $report, $output] = measureAgreement(agreementCsv(twelveAnnotatedRows()));

    expect($code)->toBe(0);

    $result = json_decode((string) file_get_contents($output), true);

    expect($result['n'])->toBe(12)
        ->and($result['alpha'])->toEqualWithDelta(0.405429, 1e-6)
        ->and($result['observed_disagreement'])->toEqualWithDelta(0.555556, 1e-6)
        ->and($result['confidence_interval'])->toMatchArray([
            'level' => 0.95,
            'resamples' => AnnotationAgreement::RESAMPLES,
            'seed' => AnnotationAgreement::SEED,
            'discarded' => 0,
        ])
        ->and($result['minimum'])->toBe(['threshold' => 0.667, 'met' => false])
        ->and($result['target'])->toBe(['threshold' => 0.8, 'met' => false])
        ->and($result['identical_sets'])->toEqualWithDelta(5 / 12, 1e-6)
        ->and($report)->toContain('0,405')
        ->and($report)->toContain('não atende');
});

test('the report has every subdomain and averages only those someone marked', function () {
    [, , $output] = measureAgreement(agreementCsv(twelveAnnotatedRows()));

    $result = json_decode((string) file_get_contents($output), true);
    $subdomains = collect($result['subdomains'])->keyBy('code');

    expect($subdomains)->toHaveCount(24)
        ->and($subdomains['3.1'])->toMatchArray(['both' => 1, 'only_a' => 1, 'only_b' => 1, 'neither' => 9])
        ->and($subdomains['3.1']['positive_agreement'])->toEqualWithDelta(0.5, 1e-6)
        ->and($subdomains['6.1']['positive_agreement'])->toBeNull()
        ->and($result['subdomain_summary']['marked'])->toBe(10);

    $marked = $subdomains->whereNotNull('positive_agreement');

    expect($result['subdomain_summary']['ac1']['mean'])->toEqualWithDelta($marked->avg('ac1'), 1e-6)
        ->and($result['subdomain_summary']['g']['min'])->toEqualWithDelta($marked->min('g'), 1e-6)
        ->and($result['subdomain_summary']['g']['max'])->toEqualWithDelta($marked->max('g'), 1e-6);
});

test('the same sheet always gives the same file, the interval included', function () {
    $csv = agreementCsv(twelveAnnotatedRows());

    [, , $first] = measureAgreement($csv);
    [, , $second] = measureAgreement($csv);

    expect(file_get_contents($second))->toBe(file_get_contents($first))
        ->and(json_decode((string) file_get_contents($first), true)['confidence_interval']['lower'])->toBeFloat();
});

test('a sheet exported with semicolons, a byte order mark and cells over several lines is read the same', function () {
    $rows = twelveAnnotatedRows();
    [, , $expected] = measureAgreement(agreementCsv($rows));

    $rows[0]['Descrição em português'] = "Uma descrição\nem duas linhas.";
    [$code, , $output] = measureAgreement(agreementCsv($rows, ';', true));

    expect($code)->toBe(0)
        ->and(file_get_contents($output))->toBe(file_get_contents($expected));
});

test('without the strict option, a row with only one annotator is left out, reported and counted', function () {
    $rows = [...twelveAnnotatedRows(), ['Subdomínios: anotador A' => '2.1'], [], ['Subdomínios: anotador B' => '7.4']];

    [$code, $report, $output] = measureAgreement(agreementCsv($rows));

    $result = json_decode((string) file_get_contents($output), true);

    expect($code)->toBe(0)
        ->and($report)->toContain('linha 14: só "Subdomínios: anotador A" preenchida.')
        ->and($report)->toContain('linha 16: só "Subdomínios: anotador B" preenchida.')
        ->and($report)->not->toContain('linha 15')
        ->and($report)->toContain('não estrito')
        ->and($result['strict'])->toBeFalse()
        ->and($result['left_out'])->toBe(2)
        ->and($result['n'])->toBe(12);
});

test('with the strict option, a row with only one annotator is refused, by line, and nothing is written', function () {
    $rows = [...twelveAnnotatedRows(), ['Subdomínios: anotador A' => '2.1'], [], ['Subdomínios: anotador B' => '7.4']];

    [$code, $report, $output] = measureAgreement(agreementCsv($rows), strict: true);

    expect($code)->toBe(1)
        ->and($report)->toContain('linha 14: só "Subdomínios: anotador A" preenchida; o cálculo estrito exige os dois anotadores.')
        ->and($report)->toContain('linha 16: só "Subdomínios: anotador B" preenchida; o cálculo estrito exige os dois anotadores.')
        ->and($report)->not->toContain('linha 15')
        ->and(file_exists($output))->toBeFalse();
});

test('with the strict option, a complete sheet gives the same measures, recorded as strict', function () {
    $csv = agreementCsv(twelveAnnotatedRows());

    [, , $loose] = measureAgreement($csv);
    [$code, $report, $strict] = measureAgreement($csv, strict: true);

    $looseResult = json_decode((string) file_get_contents($loose), true);
    $strictResult = json_decode((string) file_get_contents($strict), true);

    expect($code)->toBe(0)
        ->and($report)->toMatch('/Cálculo\s*\|\s*estrito\s*\|/')
        ->and($strictResult['strict'])->toBeTrue()
        ->and($strictResult['left_out'])->toBe(0)
        ->and($looseResult['strict'])->toBeFalse()
        ->and(array_diff_key($strictResult, ['strict' => true]))->toBe(array_diff_key($looseResult, ['strict' => true]));
});

test('an empty set and an unknown code are refused, by line, and nothing is written', function () {
    $rows = twelveAnnotatedRows();
    $rows[1]['Subdomínios: anotador B'] = ', ';
    $rows[4]['Subdomínios: anotador A'] = '1.2, 8.1';
    $rows[6]['Subdomínios: anotador B'] = '7';

    [$code, $report, $output] = measureAgreement(agreementCsv($rows));

    expect($code)->toBe(1)
        ->and($report)->toContain('linha 3: "Subdomínios: anotador B" não tem nenhum subdomínio.')
        ->and($report)->toContain('linha 6: o subdomínio 8.1 em "Subdomínios: anotador A" não existe na taxonomia de domínios do MIT')
        ->and($report)->toContain('linha 8: o subdomínio 7 em "Subdomínios: anotador B" não existe')
        ->and(file_exists($output))->toBeFalse();
});

test('a sheet with no row annotated by both is refused', function () {
    [$code, $report] = measureAgreement(agreementCsv([['Subdomínios: anotador A' => '2.1']]));

    expect($code)->toBe(1)
        ->and($report)->toContain('nenhuma linha com os subdomínios dos dois anotadores.');
});

test('a sheet without the annotators\' columns is refused', function () {
    $csv = agreementPath('csv');
    file_put_contents($csv, "Nº,Subdomínios: anotador A\n1,2.1\n");

    [$code, $report] = measureAgreement($csv);

    expect($code)->toBe(1)
        ->and($report)->toContain('não tem as colunas: Subdomínios: anotador B.');
});

test('the annotation guide lists every MIT subdomain by code and name', function () {
    $guide = (string) file_get_contents(base_path('docs/catalog-annotation-guide.md'));
    $terms = json_decode((string) file_get_contents(database_path('data/taxonomies/mit-ai-risk-domains.json')), true)['terms'];

    foreach (array_filter($terms, fn (array $term): bool => $term['level'] === 2) as $term) {
        expect($guide)->toContain("**{$term['code']} {$term['name']}**");
    }
});
