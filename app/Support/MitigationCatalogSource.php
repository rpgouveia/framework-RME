<?php

namespace App\Support;

/**
 * The catalogue as the group builds it (0024, item 7): the "Catálogo" sheet of
 * the shared spreadsheet, exported as CSV, and an editable metadata file, both
 * versioned in database/data/catalog-source/. The CSV keeps the raw double
 * annotation that grounds OE1; the catalogue file is generated from the two,
 * the same way every time.
 *
 * @phpstan-import-type Catalogue from MitigationCatalog
 */
class MitigationCatalogSource
{
    /** Every column of the sheet, in its order. */
    public const COLUMNS = [
        'Nº',
        'Responsável',
        'ID na base',
        'Nome original',
        'Nome em português',
        'Documento de origem',
        'Subcategoria (Saeri)',
        'Descrição em português',
        'Subdomínios: anotador A',
        'Anotador A',
        'Subdomínios: anotador B',
        'Anotador B',
        'Concordância exata',
        'Subdomínios: consenso',
        'Risco-alvo sugerido',
        'Evidência esperada',
        'Custo sugerido',
        'Evidência da estimativa',
        'Concordância das fontes',
        'Incerteza',
        'Fonte da estimativa',
        'Situação',
        'Observações',
        'Chave do documento',
        'Código da subcategoria',
    ];

    /** The column that fills each field of a catalogue entry. */
    public const FIELD_COLUMNS = [
        'name' => 'Nome em português',
        'source_name' => 'Nome original',
        'source_reference' => 'ID na base',
        'source_document' => 'Chave do documento',
        'saeri_subcategory' => 'Código da subcategoria',
        'description' => 'Descrição em português',
        'suggested_target_risk' => 'Risco-alvo sugerido',
        'expected_evidence' => 'Evidência esperada',
        'suggested_cost' => 'Custo sugerido',
        'uncertainty_level' => 'Incerteza',
        'estimate_source' => 'Fonte da estimativa',
        'target_risk_subdomains' => 'Subdomínios: consenso',
    ];

    /** Only the rows the group finished go into the catalogue. */
    public const DONE = 'Concluída';

    /** The fields of the metadata file. */
    public const META_FIELDS = ['saeri_database_version', 'accessed_at', 'curation_criterion'];

    /** The credit the data's CC BY 4.0 licence asks for (0023, 0024). */
    public const ATTRIBUTION = 'Base de mitigações e taxonomias do MIT AI Risk Initiative (airisk.mit.edu), sob CC BY 4.0: Saeri et al. (2025) e Slattery et al. (2026). Nomes traduzidos e descrições traduzidas e adaptadas pelo grupo.';

    /** The cost and uncertainty levels, as the sheet writes them in either gender. */
    private const LEVELS = [
        'baixo' => 'low',
        'baixa' => 'low',
        'médio' => 'medium',
        'média' => 'medium',
        'alto' => 'high',
        'alta' => 'high',
    ];

    public function __construct(
        protected MitigationCatalog $catalog,
        protected CsvTable $csv,
    ) {}

    public static function csvPath(): string
    {
        return database_path('data/catalog-source/catalog.csv');
    }

    public static function metaPath(): string
    {
        return database_path('data/catalog-source/meta.json');
    }

    /**
     * Convert the sheet and the metadata into the catalogue.
     *
     * @return array{catalogue: Catalogue, left_out: list<string>}
     *
     * @throws InvalidMitigationCatalog With every problem, by line of the CSV.
     */
    public function convert(string $csvPath, string $metaPath): array
    {
        $meta = $this->meta($metaPath);
        [$header, $rows] = $this->rows($csvPath);

        $missing = array_diff(self::COLUMNS, $header);

        if ($missing !== []) {
            throw new InvalidMitigationCatalog("O CSV {$csvPath} não tem as colunas: ".implode(', ', $missing).'.');
        }

        $entries = [];
        $lines = [];
        $leftOut = [];

        foreach ($rows as [$line, $row]) {
            // The sheet comes with its rows numbered and otherwise empty.
            if (array_filter($row, fn (string $value, string $column): bool => $value !== '' && $column !== 'Nº', ARRAY_FILTER_USE_BOTH) === []) {
                continue;
            }

            if ($row['Situação'] !== self::DONE) {
                $situation = $row['Situação'] === '' ? 'sem situação' : "situação \"{$row['Situação']}\"";
                $leftOut[] = "linha {$line} ({$this->describe($row)}): {$situation}.";

                continue;
            }

            $entries[] = $this->entry($row);
            $lines[] = $line;
        }

        $catalogue = ['meta' => $meta, 'entries' => $entries];
        $problems = $this->catalog->problems($catalogue, 'linha', $lines, self::FIELD_COLUMNS);

        if ($entries === []) {
            $problems[] = 'nenhuma linha com situação "'.self::DONE.'".';
        }

        if ($problems !== []) {
            throw new InvalidMitigationCatalog(
                "Catálogo de mitigações inválido ({$csvPath}):\n- ".implode("\n- ", $problems),
            );
        }

        /** @var Catalogue $catalogue */
        return ['catalogue' => $catalogue, 'left_out' => $leftOut];
    }

    /**
     * The catalogue file's content: stable formatting, so the same sheet always
     * gives the same file.
     *
     * @param  Catalogue  $catalogue
     */
    public static function encode(array $catalogue): string
    {
        return json_encode($catalogue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * The metadata file's fields, followed by the credit and the flag that
     * the catalogue is real.
     *
     * @return array<string, mixed>
     */
    protected function meta(string $path): array
    {
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        if (! is_array($data)) {
            throw new InvalidMitigationCatalog("Os metadados do catálogo em {$path} devem ser um objeto JSON com: ".implode(', ', self::META_FIELDS).'.');
        }

        $meta = [];

        foreach (self::META_FIELDS as $field) {
            $meta[$field] = $data[$field] ?? null;
        }

        return [...$meta, 'attribution' => self::ATTRIBUTION, 'fictional' => false];
    }

    /**
     * The header and the rows of the CSV, each row keyed by column and paired
     * with the line it starts on. Comma or semicolon separated, as the
     * spreadsheet exports it, with or without a byte order mark. Also read by
     * the OE1 agreement measure (0025).
     *
     * @return array{list<string>, list<array{int, array<string, string>}>}
     *
     * @throws InvalidMitigationCatalog When the file is missing or unreadable.
     */
    public function rows(string $path): array
    {
        try {
            return $this->csv->rows($path, 'do catálogo');
        } catch (InvalidCsvFile $exception) {
            throw new InvalidMitigationCatalog($exception->getMessage(), previous: $exception);
        }
    }

    /**
     * One row as a catalogue entry.
     *
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    protected function entry(array $row): array
    {
        $entry = [];

        foreach (self::FIELD_COLUMNS as $field => $column) {
            $entry[$field] = $row[$column];
        }

        foreach (['suggested_cost', 'uncertainty_level'] as $field) {
            $entry[$field] = self::LEVELS[mb_strtolower($entry[$field])] ?? $entry[$field];
        }

        $entry['target_risk_subdomains'] = array_values(array_filter(
            array_map(trim(...), explode(',', $entry['target_risk_subdomains'])),
            fn (string $code): bool => $code !== '',
        ));

        return $entry;
    }

    /**
     * How a left out row is named in the report.
     *
     * @param  array<string, string>  $row
     */
    protected function describe(array $row): string
    {
        return implode(', ', array_filter([
            $row['ID na base'] !== '' ? "ID {$row['ID na base']}" : null,
            $row['Nome em português'] ?: ($row['Nome original'] ?: null),
            $row['Nº'] !== '' ? "Nº {$row['Nº']}" : null,
        ])) ?: 'sem identificação';
    }
}
