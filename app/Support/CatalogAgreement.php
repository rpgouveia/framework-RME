<?php

namespace App\Support;

/**
 * The OE1 measure (0025): how far the two independent annotators of the
 * catalogue agree on the MIT risk subdomains of each mitigation, read from
 * the raw double annotation kept in the sheet the group fills (0024).
 *
 * Every row with both annotators' subdomains goes in, whatever its
 * situation. Each code must be an MIT subdomain (level 2); an empty set or an
 * unknown code is refused, and every problem is reported at once, by line of
 * the CSV. A row with only one annotator is left out and listed, or, in the
 * strict mode the official OE1 result comes from, refused as well.
 *
 * @phpstan-import-type Pair from AnnotationAgreement
 * @phpstan-import-type CodeAgreement from AnnotationAgreement
 */
class CatalogAgreement
{
    /** The columns with each annotator's subdomains. */
    public const COLUMNS = ['Subdomínios: anotador A', 'Subdomínios: anotador B'];

    public function __construct(
        protected MitigationCatalogSource $source,
        protected TaxonomyFile $taxonomyFile,
        protected AnnotationAgreement $agreement,
    ) {}

    public static function path(): string
    {
        return database_path('data/catalog-source/oe1-agreement.json');
    }

    /**
     * Every measure of 0025 for the annotated rows of the sheet. Strict, a
     * row with only one annotator is a problem rather than left out.
     *
     * @return array{report: array<string, mixed>, left_out: list<string>}
     *
     * @throws InvalidMitigationCatalog With every problem, by line of the CSV.
     */
    public function measure(string $csvPath, bool $strict = false): array
    {
        [$header, $rows] = $this->source->rows($csvPath);
        $missing = array_diff(self::COLUMNS, $header);

        if ($missing !== []) {
            throw new InvalidMitigationCatalog("O CSV {$csvPath} não tem as colunas: ".implode(', ', $missing).'.');
        }

        $subdomains = $this->subdomains();
        $pairs = [];
        $problems = [];
        $leftOut = [];

        foreach ($rows as [$line, $row]) {
            $filled = array_filter(self::COLUMNS, fn (string $column): bool => $row[$column] !== '');

            if (count($filled) < count(self::COLUMNS)) {
                if ($filled !== [] && $strict) {
                    $problems[] = "linha {$line}: só \"".implode('', $filled).'" preenchida; o cálculo estrito exige os dois anotadores.';
                } elseif ($filled !== []) {
                    $leftOut[] = "linha {$line}: só \"".implode('', $filled).'" preenchida.';
                }

                continue;
            }

            $pair = [];

            foreach (self::COLUMNS as $column) {
                $codes = array_values(array_unique(array_filter(
                    array_map(trim(...), explode(',', $row[$column])),
                    fn (string $code): bool => $code !== '',
                )));

                if ($codes === []) {
                    $problems[] = "linha {$line}: \"{$column}\" não tem nenhum subdomínio.";
                }

                foreach (array_diff($codes, array_keys($subdomains)) as $code) {
                    $problems[] = "linha {$line}: o subdomínio {$code} em \"{$column}\" não existe na taxonomia de domínios do MIT (use um código de nível 2, como 2.2).";
                }

                $pair[] = $codes;
            }

            $pairs[] = [$pair[0], $pair[1]];
        }

        if ($pairs === [] && $problems === []) {
            $problems[] = 'nenhuma linha com os subdomínios dos dois anotadores.';
        }

        if ($problems !== []) {
            throw new InvalidMitigationCatalog(
                "Anotação dupla inválida ({$csvPath}):\n- ".implode("\n- ", $problems),
            );
        }

        /** @var list<Pair> $pairs */
        return ['report' => $this->report($pairs, $subdomains, $strict, count($leftOut)), 'left_out' => $leftOut];
    }

    /**
     * The report file's content: stable formatting and no run date, so the
     * same sheet always gives the same file.
     *
     * @param  array<string, mixed>  $report
     */
    public static function encode(array $report): string
    {
        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * @param  list<Pair>  $pairs
     * @param  array<string, string>  $subdomains
     * @return array<string, mixed>
     */
    protected function report(array $pairs, array $subdomains, bool $strict, int $leftOut): array
    {
        $alpha = $this->agreement->alpha($pairs);
        $interval = $this->agreement->interval($pairs);
        $codes = array_map(fn (string $code): array => $this->agreement->code($pairs, $code), array_keys($subdomains));
        $marked = array_values(array_filter($codes, fn (array $code): bool => $code['positive'] !== null));

        return [
            'measure' => 'Alfa de Krippendorff com a distância MASI (decisão 0025)',
            'strict' => $strict,
            'n' => count($pairs),
            'left_out' => $leftOut,
            'alpha' => self::round($alpha['alpha']),
            'observed_disagreement' => self::round($alpha['observed']),
            'expected_disagreement' => self::round($alpha['expected']),
            'confidence_interval' => [
                'level' => 0.95,
                'lower' => self::round($interval['lower']),
                'upper' => self::round($interval['upper']),
                'method' => 'percentil',
                'resamples' => AnnotationAgreement::RESAMPLES,
                'seed' => AnnotationAgreement::SEED,
                'discarded' => $interval['discarded'],
            ],
            'minimum' => ['threshold' => AnnotationAgreement::MINIMUM, 'met' => $alpha['alpha'] !== null && $alpha['alpha'] >= AnnotationAgreement::MINIMUM],
            'target' => ['threshold' => AnnotationAgreement::TARGET, 'met' => $alpha['alpha'] !== null && $alpha['alpha'] >= AnnotationAgreement::TARGET],
            'identical_sets' => self::round($this->agreement->identical($pairs)),
            'subdomains' => array_map(fn (array $code): array => [
                'code' => $code['code'],
                'name' => $subdomains[$code['code']],
                'both' => $code['both'],
                'only_a' => $code['only_a'],
                'only_b' => $code['only_b'],
                'neither' => $code['neither'],
                'observed_agreement' => self::round($code['observed']),
                'g' => self::round($code['g']),
                'ac1' => self::round($code['ac1']),
                'positive_agreement' => self::round($code['positive']),
            ], $codes),
            'subdomain_summary' => [
                'marked' => count($marked),
                'ac1' => $this->summary(array_column($marked, 'ac1')),
                'g' => $this->summary(array_column($marked, 'g')),
            ],
        ];
    }

    /**
     * The MIT subdomains, by code, with their names.
     *
     * @return array<string, string>
     */
    protected function subdomains(): array
    {
        $terms = array_filter($this->taxonomyFile->read(AiRiskDomains::path())['terms'], fn (array $term): bool => $term['level'] === 2);

        return array_column($terms, 'name', 'code');
    }

    /**
     * Mean, minimum and maximum, or nulls when nothing was marked.
     *
     * @param  list<float>  $values
     * @return array{mean: float|null, min: float|null, max: float|null}
     */
    protected function summary(array $values): array
    {
        return [
            'mean' => $values === [] ? null : self::round(array_sum($values) / count($values)),
            'min' => $values === [] ? null : self::round(min($values)),
            'max' => $values === [] ? null : self::round(max($values)),
        ];
    }

    /** Six decimals, the precision the reference values are checked to. */
    protected static function round(?float $value): ?float
    {
        return $value === null ? null : round($value, 6);
    }
}
