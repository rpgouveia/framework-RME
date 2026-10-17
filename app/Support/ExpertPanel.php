<?php

namespace App\Support;

use App\Enums\AiSystemCategory;

/**
 * The expert panel's second round (0027): the CVR of every item of the bank,
 * the critical value for the panel's size, the items retained, the OE3 and
 * OE6 criteria, the CVI, and the review intervals the experts suggest, set
 * against those of the monitoring protocol (0017).
 *
 * The responses come from the form's CSV export (protocol, Section 6.2): a
 * "Respondente" column, one column per item and per tier, each starting with
 * its identifier in brackets; columns without brackets are left out. Every
 * answer is mandatory, so the run is always strict: every problem is reported
 * at once, by line of the CSV, and nothing is computed while there is one.
 *
 * @phpstan-type Item array{line: int, id: string, block: string, objectives: list<string>}
 */
class ExpertPanel
{
    /** Every column of the item bank. */
    public const ITEM_COLUMNS = ['id', 'bloco', 'objetivo', 'enunciado', 'fundamento'];

    /** The objectives an item may serve. */
    public const OBJECTIVES = ['OE3', 'OE6'];

    /** The column with each expert's code. */
    public const RESPONDENT = 'Respondente';

    /** Lawshe's three ratings, as the form writes them. */
    public const RATINGS = [
        'essential' => 'Essencial',
        'useful' => 'Útil, mas não essencial',
        'unnecessary' => 'Desnecessário',
    ];

    /** The review interval questions, by identifier, and the tier each one asks about. */
    public const INTERVALS = [
        'INT-ALTO' => AiSystemCategory::High,
        'INT-LIMITADO' => AiSystemCategory::Limited,
        'INT-MINIMO' => AiSystemCategory::Minimal,
    ];

    public const METHOD = 'Ayre e Scally (2014): binomial exata, unilateral';

    public function __construct(
        protected CsvTable $csv,
        protected MonitoringProtocol $protocol,
        protected ContentValidity $validity,
    ) {}

    public static function itemsPath(): string
    {
        return database_path('data/expert-panel/items.csv');
    }

    public static function responsesPath(): string
    {
        return database_path('data/expert-panel/round-2-responses.csv');
    }

    public static function path(): string
    {
        return database_path('data/expert-panel/oe6-cvr.json');
    }

    /**
     * Every measure of 0027 for the second round's responses.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidExpertPanel With every problem, by line of the CSV.
     */
    public function measure(string $responsesPath, string $itemsPath): array
    {
        $items = $this->items($itemsPath);
        [$n, $ratings, $intervals] = $this->responses($responsesPath, $items);

        return $this->report($items, $n, $ratings, $intervals);
    }

    /**
     * The report file's content, serialized as the OE1 report is: stable
     * formatting and no run date, so the same CSV always gives the same file.
     *
     * @param  array<string, mixed>  $report
     */
    public static function encode(array $report): string
    {
        return CatalogAgreement::encode($report);
    }

    /**
     * The item bank, in its order.
     *
     * @return list<Item>
     *
     * @throws InvalidExpertPanel
     */
    public function items(string $path): array
    {
        [$header, $rows] = $this->read($path, 'do banco de itens');
        $missing = array_diff(self::ITEM_COLUMNS, $header);

        if ($missing !== []) {
            throw new InvalidExpertPanel("O CSV {$path} não tem as colunas: ".implode(', ', $missing).'.');
        }

        $items = [];
        $lines = [];
        $problems = [];

        foreach ($rows as [$line, $row]) {
            if (array_filter($row, fn (string $value): bool => $value !== '') === []) {
                continue;
            }

            $id = $row['id'];
            $objectives = array_values(array_unique(array_filter(
                array_map(trim(...), explode(';', $row['objetivo'])),
                fn (string $objective): bool => $objective !== '',
            )));

            if ($id === '') {
                $problems[] = "linha {$line}: o item não tem id.";
            } elseif (isset($lines[$id])) {
                $problems[] = "linha {$line}: o id {$id} repete o da linha {$lines[$id]}.";
            } elseif (isset(self::INTERVALS[$id])) {
                $problems[] = "linha {$line}: o id {$id} é reservado à pergunta de intervalo de revisão.";
            }

            if ($row['bloco'] === '') {
                $problems[] = "linha {$line}: o item {$id} não tem bloco.";
            }

            if ($objectives === []) {
                $problems[] = "linha {$line}: o item {$id} não tem objetivo (use ".implode(', ', self::OBJECTIVES).', separados por ponto e vírgula).';
            }

            foreach (array_diff($objectives, self::OBJECTIVES) as $objective) {
                $problems[] = "linha {$line}: o objetivo {$objective} do item {$id} não existe (use ".implode(', ', self::OBJECTIVES).').';
            }

            $lines[$id] ??= $line;
            $items[] = ['line' => $line, 'id' => $id, 'block' => $row['bloco'], 'objectives' => $objectives];
        }

        if ($items === []) {
            $problems[] = 'o banco não tem nenhum item.';
        }

        if ($problems !== []) {
            throw new InvalidExpertPanel("Banco de itens inválido ({$path}):\n- ".implode("\n- ", $problems));
        }

        return $items;
    }

    /**
     * The number of respondents (N), the ratings of each item and the
     * intervals of each tier, every answer checked.
     *
     * @param  list<Item>  $items
     * @return array{int, array<string, list<string>>, array<string, list<int>>}
     *
     * @throws InvalidExpertPanel
     */
    protected function responses(string $path, array $items): array
    {
        [$header, $rows] = $this->read($path, 'de respostas');
        $ids = array_column($items, 'id');
        $problems = [];

        // The column of each identifier, from the header itself, where a
        // repeated column is still there.
        $columns = [];

        foreach ($header as $column) {
            if (preg_match('/^\[([^\]]*)\]/u', $column, $match) !== 1) {
                continue;
            }

            $id = trim($match[1]);

            if (! in_array($id, $ids, true) && ! isset(self::INTERVALS[$id])) {
                $problems[] = "linha 1: a coluna \"{$column}\" começa por [{$id}], que não é item do banco nem pergunta de intervalo (".implode(', ', array_keys(self::INTERVALS)).').';

                continue;
            }

            $columns[$id][] = $column;
        }

        $respondentColumns = count(array_keys($header, self::RESPONDENT, true));

        if ($respondentColumns !== 1) {
            $problems[] = $respondentColumns === 0
                ? 'linha 1: falta a coluna "'.self::RESPONDENT.'".'
                : 'linha 1: a coluna "'.self::RESPONDENT.'" aparece mais de uma vez.';
        }

        foreach ([...$ids, ...array_keys(self::INTERVALS)] as $id) {
            $count = count($columns[$id] ?? []);

            if ($count === 0) {
                $problems[] = "linha 1: falta a coluna de [{$id}].";
            } elseif ($count > 1) {
                $problems[] = "linha 1: [{$id}] aparece em {$count} colunas; deve aparecer em uma só.";
            }
        }

        // Only an identifier with a single column is read.
        $columns = array_map(fn (array $found): string => $found[0], array_filter($columns, fn (array $found): bool => count($found) === 1));
        $ratingOf = array_combine(array_map(mb_strtolower(...), self::RATINGS), self::RATINGS);
        $ratings = array_fill_keys($ids, []);
        $intervals = array_fill_keys(array_keys(self::INTERVALS), []);
        $respondents = [];
        $lines = [];

        foreach ($rows as [$line, $row]) {
            // A blank line, such as one a spreadsheet leaves at the end.
            if (array_filter($row, fn (string $value): bool => $value !== '') === []) {
                continue;
            }

            $lines[] = $line;

            if ($respondentColumns === 1) {
                $respondent = $row[self::RESPONDENT];

                if ($respondent === '') {
                    $problems[] = "linha {$line}: \"".self::RESPONDENT.'" está vazio.';
                } elseif (isset($respondents[$respondent])) {
                    $problems[] = "linha {$line}: o respondente {$respondent} repete o da linha {$respondents[$respondent]}.";
                } else {
                    $respondents[$respondent] = $line;
                }
            }

            foreach ($ids as $id) {
                if (! isset($columns[$id])) {
                    continue;
                }

                $answer = $row[$columns[$id]];
                $rating = $ratingOf[mb_strtolower($answer)] ?? null;

                if ($rating === null) {
                    $problems[] = $answer === ''
                        ? "linha {$line}: o item {$id} está sem resposta."
                        : "linha {$line}: a resposta \"{$answer}\" ao item {$id} não é ".implode(', ', array_map(fn (string $rating): string => "\"{$rating}\"", self::RATINGS)).'.';
                } else {
                    $ratings[$id][] = $rating;
                }
            }

            foreach (array_keys(self::INTERVALS) as $id) {
                if (! isset($columns[$id])) {
                    continue;
                }

                $answer = $row[$columns[$id]];
                $days = filter_var($answer, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

                if ($days === false) {
                    $problems[] = $answer === ''
                        ? "linha {$line}: o intervalo [{$id}] está sem resposta."
                        : "linha {$line}: o intervalo [{$id}] \"{$answer}\" deve ser um número inteiro positivo de dias.";
                } else {
                    $intervals[$id][] = $days;
                }
            }
        }

        $n = count($lines);

        if ($n < ContentValidity::MINIMUM_PANEL || $n > ContentValidity::MAXIMUM_PANEL) {
            $where = $n === 0 ? 'nenhum respondente' : ($n === 1 ? "1 respondente (linha {$lines[0]})" : "{$n} respondentes (linhas {$lines[0]} a {$lines[$n - 1]})");
            $problems[] = "{$where}; o cálculo exige de ".ContentValidity::MINIMUM_PANEL.' a '.ContentValidity::MAXIMUM_PANEL.'. Com menos de '.ContentValidity::MINIMUM_PANEL.', nenhum item pode atingir o valor crítico (decisão 0027, item 4).';
        }

        if ($problems !== []) {
            throw new InvalidExpertPanel("Respostas do painel inválidas ({$path}):\n- ".implode("\n- ", $problems));
        }

        return [$n, $ratings, $intervals];
    }

    /**
     * @param  list<Item>  $items
     * @param  array<string, list<string>>  $ratings
     * @param  array<string, list<int>>  $intervals
     * @return array<string, mixed>
     */
    protected function report(array $items, int $n, array $ratings, array $intervals): array
    {
        $critical = (int) $this->validity->criticalEssential($n);

        $results = array_map(function (array $item) use ($ratings, $n): array {
            $counts = array_count_values($ratings[$item['id']]);
            $essential = $counts[self::RATINGS['essential']] ?? 0;

            return [
                'id' => $item['id'],
                'block' => $item['block'],
                'objectives' => $item['objectives'],
                'essential' => $essential,
                'useful' => $counts[self::RATINGS['useful']] ?? 0,
                'unnecessary' => $counts[self::RATINGS['unnecessary']] ?? 0,
                'cvr' => $this->validity->cvr($essential, $n),
                'retained' => $this->validity->retained($essential, $n),
            ];
        }, $items);

        $blocks = [];

        foreach ($results as $result) {
            $blocks[$result['block']][] = $result;
        }

        $retained = fn (array $results): array => array_values(array_filter($results, fn (array $result): bool => $result['retained']));
        $objective = function (string $objective) use ($results, $retained): array {
            $serving = array_values(array_filter($results, fn (array $result): bool => in_array($objective, $result['objectives'], true)));
            $kept = count($retained($serving));

            return ['items' => count($serving), 'retained' => $kept, 'met' => $serving !== [] && $kept === count($serving)];
        };

        return [
            'n' => $n,
            'critical' => [
                'essential' => $critical,
                'cvr' => self::round($this->validity->cvr($critical, $n)),
                'alpha' => ContentValidity::ALPHA,
                'method' => self::METHOD,
            ],
            'items' => array_map(fn (array $result): array => [...$result, 'cvr' => self::round($result['cvr'])], $results),
            'blocks' => array_map(fn (string $block, array $results): array => [
                'block' => $block,
                'items' => count($results),
                'retained' => count($retained($results)),
                'cvi_all' => self::round($this->validity->cvi(array_column($results, 'cvr'))),
                'cvi_retained' => self::round($this->validity->cvi(array_column($retained($results), 'cvr'))),
            ], array_keys($blocks), array_values($blocks)),
            'cvi' => [
                'all' => self::round($this->validity->cvi(array_column($results, 'cvr'))),
                'retained' => self::round($this->validity->cvi(array_column($retained($results), 'cvr'))),
            ],
            'oe3' => $objective('OE3'),
            'oe6' => ['items' => count($results), 'retained' => count($retained($results)), 'met' => count($retained($results)) === count($results)],
            'intervals' => array_map(function (string $id, AiSystemCategory $tier) use ($intervals): array {
                $quartiles = $this->validity->quartiles($intervals[$id]);
                $days = $this->protocol->reviewIntervalDays($tier);

                return [
                    'tier' => $tier->value,
                    'protocol_days' => $days,
                    'median' => self::round($quartiles['median']),
                    'q1' => self::round($quartiles['q1']),
                    'q3' => self::round($quartiles['q3']),
                    'sustained' => $days !== null && $quartiles['q1'] <= $days && $days <= $quartiles['q3'],
                ];
            }, array_keys(self::INTERVALS), array_values(self::INTERVALS)),
        ];
    }

    /**
     * @return array{list<string>, list<array{int, array<string, string>}>}
     *
     * @throws InvalidExpertPanel When the file is missing or unreadable.
     */
    protected function read(string $path, string $name): array
    {
        try {
            return $this->csv->rows($path, $name);
        } catch (InvalidCsvFile $exception) {
            throw new InvalidExpertPanel($exception->getMessage(), previous: $exception);
        }
    }

    /** Six decimals, the precision the reference values are checked to. */
    protected static function round(?float $value): ?float
    {
        return $value === null ? null : round($value, 6);
    }
}
