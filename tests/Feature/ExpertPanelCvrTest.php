<?php

use App\Support\ExpertPanel;

/** A path for a file the test writes, removed after the test. */
function panelPath(string $extension): string
{
    $path = sys_get_temp_dir().'/expert-panel-'.uniqid().'.'.$extension;
    register_shutdown_function(fn () => @unlink($path));

    return $path;
}

/**
 * A CSV with the given header and rows.
 *
 * @param  list<string>  $header
 * @param  list<list<string>>  $rows
 */
function panelCsv(array $header, array $rows, string $delimiter = ',', bool $bom = false): string
{
    $path = panelPath('csv');
    $target = fopen($path, 'w');
    fwrite($target, $bom ? "\u{FEFF}" : '');
    fputcsv($target, $header, $delimiter, '"', '');

    foreach ($rows as $row) {
        fputcsv($target, $row, $delimiter, '"', '');
    }

    fclose($target);

    return $path;
}

/** The test's item bank: one item of each of three blocks, only DEF-C1 serving OE3. */
function panelItems(): string
{
    return panelCsv(ExpertPanel::ITEM_COLUMNS, [
        ['MOD-1', 'A. Modelo de vínculo (C1)', 'OE6', 'Toda mitigação adotada é registrada como vínculo.', 'Decisão 0004'],
        ['DEF-C1', 'C. Protocolo de monitoramento (C3)', 'OE3; OE6', 'A ocorrência é observada em um sistema em operação.', 'Decisão 0026'],
        ['PRO-1', 'D. Protótipo (C4)', 'OE6', 'O protótipo exporta um relatório de rastreabilidade.', 'Relatório'],
    ]);
}

/**
 * The columns of the form's export, by a short key, with their headers: a
 * timestamp and a comment without brackets, which are left out.
 *
 * @return array<string, string>
 */
function panelColumns(): array
{
    return [
        'time' => 'Carimbo de data/hora',
        'who' => 'Respondente',
        'MOD-1' => '[MOD-1] Toda mitigação adotada é registrada como vínculo.',
        'DEF-C1' => '[DEF-C1] A ocorrência é observada em um sistema em operação.',
        'PRO-1' => '[PRO-1] O protótipo exporta um relatório de rastreabilidade.',
        'INT-ALTO' => '[INT-ALTO] Em quantos dias, em um sistema de alto risco?',
        'INT-LIMITADO' => '[INT-LIMITADO] Em quantos dias, em um sistema de risco limitado?',
        'INT-MINIMO' => '[INT-MINIMO] Em quantos dias, em um sistema de risco mínimo?',
        'note' => 'Comentários gerais',
    ];
}

/**
 * The eight respondents of the reference case, keyed as panelColumns(); one
 * answer is written in another case and with spaces around it.
 *
 * @return list<array<string, string>>
 */
function panelResponses(): array
{
    $e = 'Essencial';
    $u = 'Útil, mas não essencial';
    $d = 'Desnecessário';
    $mod = [$e, $e, $e, $e, $e, $e, $e, $u];
    $def = [$e, $e, '  essencial ', $e, $e, $e, $e, $e];
    $pro = [$e, $e, $e, $e, $e, $e, $u, $d];
    $high = [60, 90, 90, 90, 120, 180, 90, 60];
    $limited = [90, 120, 180, 180, 240, 180, 150, 180];
    $minimal = [180, 180, 240, 365, 180, 180, 365, 180];

    return array_map(fn (int $index): array => [
        'time' => '2027/03/0'.($index + 1).' 10:00:00',
        'who' => sprintf('E%02d', $index + 1),
        'MOD-1' => $mod[$index],
        'DEF-C1' => $def[$index],
        'PRO-1' => $pro[$index],
        'INT-ALTO' => (string) $high[$index],
        'INT-LIMITADO' => (string) $limited[$index],
        'INT-MINIMO' => (string) $minimal[$index],
        'note' => $index === 7 ? "Um comentário\nem duas linhas." : '',
    ], range(0, 7));
}

/**
 * The responses as the form exports them, in the order of the columns.
 *
 * @param  array<string, string>  $columns
 * @param  list<array<string, string>>  $rows
 */
function panelResponsesCsv(array $columns, array $rows, string $delimiter = ',', bool $bom = false): string
{
    return panelCsv(
        array_values($columns),
        array_map(fn (array $row): array => array_map(fn (string $key): string => $row[$key] ?? '', array_keys($columns)), $rows),
        $delimiter,
        $bom,
    );
}

/**
 * Run the calculation, writing to a file of the test's own.
 *
 * @return array{0: int, 1: string, 2: string} the exit code, the output and the written file
 */
function measurePanel(string $csv, ?string $items = null): array
{
    $output = panelPath('json');

    $code = Artisan::call('expert-panel:cvr', ['--csv' => $csv, '--items' => $items ?? panelItems(), '--output' => $output]);

    return [$code, Artisan::output(), $output];
}

test('the reference case gives the cvr, the retained items, the criteria and the cvi', function () {
    [$code, $report, $output] = measurePanel(panelResponsesCsv(panelColumns(), panelResponses()));

    expect($code)->toBe(0);

    $result = json_decode((string) file_get_contents($output), true);
    $items = collect($result['items'])->keyBy('id');

    expect($result['n'])->toBe(8)
        ->and($result['critical'])->toMatchArray(['essential' => 7, 'alpha' => 0.05, 'method' => 'Ayre e Scally (2014): binomial exata, unilateral'])
        ->and($result['critical']['cvr'])->toEqualWithDelta(0.75, 1e-6)
        ->and(array_column($result['items'], 'id'))->toBe(['MOD-1', 'DEF-C1', 'PRO-1'])
        ->and($items['DEF-C1'])->toMatchArray(['block' => 'C. Protocolo de monitoramento (C3)', 'objectives' => ['OE3', 'OE6'], 'essential' => 8, 'useful' => 0, 'unnecessary' => 0, 'retained' => true])
        ->and($items['DEF-C1']['cvr'])->toEqualWithDelta(1.0, 1e-6)
        ->and($items['MOD-1'])->toMatchArray(['objectives' => ['OE6'], 'essential' => 7, 'useful' => 1, 'unnecessary' => 0, 'retained' => true])
        ->and($items['MOD-1']['cvr'])->toEqualWithDelta(0.75, 1e-6)
        ->and($items['PRO-1'])->toMatchArray(['essential' => 6, 'useful' => 1, 'unnecessary' => 1, 'retained' => false])
        ->and($items['PRO-1']['cvr'])->toEqualWithDelta(0.5, 1e-6)
        ->and($result['cvi']['all'])->toEqualWithDelta(0.75, 1e-6)
        ->and($result['cvi']['retained'])->toEqualWithDelta(0.875, 1e-6)
        ->and($result['oe3'])->toBe(['items' => 1, 'retained' => 1, 'met' => true])
        ->and($result['oe6'])->toBe(['items' => 3, 'retained' => 2, 'met' => false])
        ->and($report)->toContain('não atendido (2 de 3 mantidos)')
        ->and($report)->toContain('Resultado gravado em');
});

test('the blocks follow the item bank, with their cvi over every item and over those retained', function () {
    [, , $output] = measurePanel(panelResponsesCsv(panelColumns(), panelResponses()));

    $blocks = json_decode((string) file_get_contents($output), true)['blocks'];

    expect($blocks)->toBe([
        ['block' => 'A. Modelo de vínculo (C1)', 'items' => 1, 'retained' => 1, 'cvi_all' => 0.75, 'cvi_retained' => 0.75],
        ['block' => 'C. Protocolo de monitoramento (C3)', 'items' => 1, 'retained' => 1, 'cvi_all' => 1.0, 'cvi_retained' => 1.0],
        ['block' => 'D. Protótipo (C4)', 'items' => 1, 'retained' => 0, 'cvi_all' => 0.5, 'cvi_retained' => null],
    ]);
});

test('the intervals of each tier are set against those of the monitoring protocol', function () {
    [, $report, $output] = measurePanel(panelResponsesCsv(panelColumns(), panelResponses()));

    $intervals = json_decode((string) file_get_contents($output), true)['intervals'];

    expect($intervals)->toBe([
        ['tier' => 'high', 'protocol_days' => 90, 'median' => 90.0, 'q1' => 82.5, 'q3' => 97.5, 'sustained' => true],
        // The protocol's value on the edge of the interquartile range counts.
        ['tier' => 'limited', 'protocol_days' => 180, 'median' => 180.0, 'q1' => 142.5, 'q3' => 180.0, 'sustained' => true],
        ['tier' => 'minimal', 'protocol_days' => 365, 'median' => 180.0, 'q1' => 180.0, 'q3' => 271.25, 'sustained' => false],
    ])->and($report)->toContain('271,25');
});

test('the same responses always give the same file, byte for byte', function () {
    $csv = panelResponsesCsv(panelColumns(), panelResponses());

    [, , $first] = measurePanel($csv);
    [, , $second] = measurePanel($csv);

    expect(file_get_contents($second))->toBe(file_get_contents($first))
        ->and(file_get_contents($first))->toContain('"cvr": 1.0,')
        ->and(file_get_contents($first))->toEndWith("}\n");
});

test('responses exported with semicolons and a byte order mark are read the same', function () {
    [, , $expected] = measurePanel(panelResponsesCsv(panelColumns(), panelResponses()));
    [$code, , $output] = measurePanel(panelResponsesCsv(panelColumns(), panelResponses(), ';', true));

    expect($code)->toBe(0)
        ->and(file_get_contents($output))->toBe(file_get_contents($expected));
});

test('fewer than five respondents are refused and nothing is written', function () {
    [$code, $report, $output] = measurePanel(panelResponsesCsv(panelColumns(), array_slice(panelResponses(), 0, 4)));

    expect($code)->toBe(1)
        ->and($report)->toContain('Respostas do painel de especialistas inválidas')
        ->and($report)->toContain('4 respondentes (linhas 2 a 5); o cálculo exige de 5 a 60.')
        ->and(file_exists($output))->toBeFalse();
});

test('a repeated or empty respondent code is refused, by line', function () {
    $rows = panelResponses();
    $rows[2]['who'] = 'E01';
    $rows[5]['who'] = ' ';

    [$code, $report, $output] = measurePanel(panelResponsesCsv(panelColumns(), $rows));

    expect($code)->toBe(1)
        ->and($report)->toContain('linha 4: o respondente E01 repete o da linha 2.')
        ->and($report)->toContain('linha 7: "Respondente" está vazio.')
        ->and(file_exists($output))->toBeFalse();
});

test('an answer other than the three ratings is refused, by line', function () {
    $rows = panelResponses();
    $rows[3]['MOD-1'] = 'Talvez';

    [$code, $report, $output] = measurePanel(panelResponsesCsv(panelColumns(), $rows));

    expect($code)->toBe(1)
        ->and($report)->toContain('linha 5: a resposta "Talvez" ao item MOD-1 não é "Essencial", "Útil, mas não essencial", "Desnecessário".')
        ->and(file_exists($output))->toBeFalse();
});

test('an item without an answer is refused, by line', function () {
    $rows = panelResponses();
    $rows[6]['PRO-1'] = '';

    [$code, $report, $output] = measurePanel(panelResponsesCsv(panelColumns(), $rows));

    expect($code)->toBe(1)
        ->and($report)->toContain('linha 8: o item PRO-1 está sem resposta.')
        ->and(file_exists($output))->toBeFalse();
});

test('a missing item or interval column is refused', function () {
    $columns = panelColumns();
    unset($columns['PRO-1'], $columns['INT-MINIMO']);

    [$code, $report, $output] = measurePanel(panelResponsesCsv($columns, panelResponses()));

    expect($code)->toBe(1)
        ->and($report)->toContain('linha 1: falta a coluna de [PRO-1].')
        ->and($report)->toContain('linha 1: falta a coluna de [INT-MINIMO].')
        ->and(file_exists($output))->toBeFalse();
});

test('an item in more than one column is refused', function () {
    $columns = [...panelColumns(), 'again' => '[MOD-1] A mesma pergunta, repetida.'];
    $rows = array_map(fn (array $row): array => [...$row, 'again' => 'Essencial'], panelResponses());

    [$code, $report, $output] = measurePanel(panelResponsesCsv($columns, $rows));

    expect($code)->toBe(1)
        ->and($report)->toContain('linha 1: [MOD-1] aparece em 2 colunas; deve aparecer em uma só.')
        ->and(file_exists($output))->toBeFalse();
});

test('a column starting with an unknown identifier in brackets is refused', function () {
    $columns = [...panelColumns(), 'unknown' => '[XYZ-9] Um item que não está no banco.'];

    [$code, $report, $output] = measurePanel(panelResponsesCsv($columns, panelResponses()));

    expect($code)->toBe(1)
        ->and($report)->toContain('linha 1: a coluna "[XYZ-9] Um item que não está no banco." começa por [XYZ-9], que não é item do banco nem pergunta de intervalo')
        ->and(file_exists($output))->toBeFalse();
});

test('an interval that is empty, decimal or zero is refused, by line', function (string $answer, string $message) {
    $rows = panelResponses();
    $rows[1]['INT-LIMITADO'] = $answer;

    [$code, $report, $output] = measurePanel(panelResponsesCsv(panelColumns(), $rows));

    expect($code)->toBe(1)
        ->and($report)->toContain("linha 3: o intervalo [INT-LIMITADO] {$message}")
        ->and(file_exists($output))->toBeFalse();
})->with([
    'empty' => ['', 'está sem resposta.'],
    'decimal' => ['90.5', '"90.5" deve ser um número inteiro positivo de dias.'],
    'decimal comma' => ['90,5', '"90,5" deve ser um número inteiro positivo de dias.'],
    'zero' => ['0', '"0" deve ser um número inteiro positivo de dias.'],
    'negative' => ['-30', '"-30" deve ser um número inteiro positivo de dias.'],
]);

test('every problem is reported at once', function () {
    $columns = [...panelColumns(), 'unknown' => '[XYZ-9] Outro item.'];
    unset($columns['PRO-1']);
    $rows = panelResponses();
    $rows[0]['MOD-1'] = 'Talvez';
    $rows[3]['who'] = 'E02';
    $rows[4]['DEF-C1'] = '';
    $rows[7]['INT-ALTO'] = '0';

    [$code, $report, $output] = measurePanel(panelResponsesCsv($columns, $rows));

    expect($code)->toBe(1)
        ->and($report)->toContain('começa por [XYZ-9]')
        ->and($report)->toContain('linha 1: falta a coluna de [PRO-1].')
        ->and($report)->toContain('linha 2: a resposta "Talvez" ao item MOD-1')
        ->and($report)->toContain('linha 5: o respondente E02 repete o da linha 3.')
        ->and($report)->toContain('linha 6: o item DEF-C1 está sem resposta.')
        ->and($report)->toContain('linha 9: o intervalo [INT-ALTO] "0"')
        ->and(file_exists($output))->toBeFalse();
});

test('an item bank with a repeated id, no id or an unknown objective is refused, by line', function () {
    $items = panelCsv(ExpertPanel::ITEM_COLUMNS, [
        ['MOD-1', 'A. Modelo de vínculo (C1)', 'OE6', 'Enunciado.', 'Fundamento.'],
        ['MOD-1', 'A. Modelo de vínculo (C1)', 'OE6', 'Enunciado.', 'Fundamento.'],
        ['', 'A. Modelo de vínculo (C1)', 'OE6', 'Enunciado.', 'Fundamento.'],
        ['DEF-C1', 'C. Protocolo de monitoramento (C3)', 'OE3; OE7', 'Enunciado.', 'Fundamento.'],
    ]);

    [$code, $report, $output] = measurePanel(panelResponsesCsv(panelColumns(), panelResponses()), $items);

    expect($code)->toBe(1)
        ->and($report)->toContain('linha 3: o id MOD-1 repete o da linha 2.')
        ->and($report)->toContain('linha 4: o item não tem id.')
        ->and($report)->toContain('linha 5: o objetivo OE7 do item DEF-C1 não existe')
        ->and(file_exists($output))->toBeFalse();
});

test('missing files are refused', function () {
    [$code, $report] = measurePanel(sys_get_temp_dir().'/no-such-responses.csv');

    expect($code)->toBe(1)
        ->and($report)->toContain('CSV de respostas não encontrado');
});

test('the versioned item bank has the 21 items, the seven of the definition serving OE3', function () {
    $items = app(ExpertPanel::class)->items(ExpertPanel::itemsPath());
    $ids = array_column($items, 'id');
    $oe3 = array_values(array_filter($items, fn (array $item): bool => in_array('OE3', $item['objectives'], true)));

    expect($items)->toHaveCount(21)
        ->and(array_unique($ids))->toHaveCount(21)
        ->and(array_column($oe3, 'id'))->toBe(['DEF-C1', 'DEF-C2', 'DEF-C3a', 'DEF-C3b', 'DEF-C4', 'DEF-C5', 'DEF-C6']);
});
