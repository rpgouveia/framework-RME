<?php

namespace App\Support;

use App\Enums\CostLevel;
use App\Enums\UncertaintyLevel;
use App\Models\ReferenceDataset;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The curated mitigation catalogue (C2), read from a versioned data file.
 *
 * Mitigations only come from the catalogue (UC004, R-8): the app offers no
 * screen to create or change them. Each entry is traceable to Saeri et al.:
 * its subcategory in their taxonomy, its literal name and identifier in their
 * database, and the source document. The target risk, evidence, cost and
 * uncertainty are the framework's own contribution, backed by the estimate
 * source (RNF03).
 *
 * The file is checked as a whole against the Saeri taxonomy file before
 * anything is stored, and every problem is reported at once.
 *
 * @phpstan-type Catalogue array{meta: array{saeri_database_version: string, accessed_at: string, curation_criterion: string, fictional: bool}, entries: list<array<string, string>>}
 */
class MitigationCatalog
{
    /** The key the loaded catalogue's metadata is kept under. */
    public const DATASET = 'mitigation-catalog';

    /** The fields every entry must have, and no others. */
    public const FIELDS = [
        'name',
        'source_name',
        'source_reference',
        'source_document',
        'saeri_subcategory',
        'description',
        'suggested_target_risk',
        'expected_evidence',
        'suggested_cost',
        'uncertainty_level',
        'estimate_source',
    ];

    public function __construct(
        protected TaxonomyFile $taxonomyFile,
    ) {}

    public static function path(): string
    {
        return database_path('data/mitigation-catalog.json');
    }

    /**
     * The metadata of the catalogue as it was loaded into the database.
     *
     * @return array<string, mixed>
     */
    public static function loadedMeta(): array
    {
        return ReferenceDataset::query()->where('key', self::DATASET)->value('metadata') ?? [];
    }

    /**
     * Read and validate the catalogue file.
     *
     * @return Catalogue
     *
     * @throws InvalidMitigationCatalog When the file is missing, unreadable or
     *                                  holds an invalid entry.
     */
    public function read(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidMitigationCatalog("Arquivo do catálogo de mitigações não encontrado: {$path}");
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data) || ! is_array($data['meta'] ?? null) || ! is_array($data['entries'] ?? null) || ! array_is_list($data['entries'])) {
            throw new InvalidMitigationCatalog("O catálogo de mitigações em {$path} deve ser um JSON com \"meta\" e uma lista \"entries\".");
        }

        $taxonomy = $this->taxonomyFile->read(SaeriTaxonomy::path());
        $subcategories = array_column(array_filter($taxonomy['terms'], fn (array $term): bool => $term['level'] === 2), 'code');
        /** @var list<array{key: string}> $documents */
        $documents = $taxonomy['meta']['documents'] ?? [];
        $documentKeys = array_column($documents, 'key');

        $problems = $this->metaProblems($data['meta']);
        $names = [];
        $references = [];

        foreach ($data['entries'] as $index => $entry) {
            $label = 'entrada '.($index + 1);

            if (! is_array($entry)) {
                $problems[] = "{$label}: deve ser um objeto com os campos da mitigação.";

                continue;
            }

            if (is_string($entry['name'] ?? null)) {
                $label .= ' ("'.$entry['name'].'")';
                $names[mb_strtolower(trim($entry['name']))][] = $index + 1;
            }

            if (is_string($entry['source_reference'] ?? null)) {
                $references[trim($entry['source_reference'])][] = $index + 1;
            }

            $unknown = array_diff(array_keys($entry), self::FIELDS);

            if ($unknown !== []) {
                $problems[] = "{$label}: campos desconhecidos: ".implode(', ', $unknown).'.';
            }

            // Name each field by its key, so the message points at the file.
            $validator = Validator::make($entry, $this->entryRules($subcategories, $documentKeys), [
                'saeri_subcategory.in' => 'A subcategoria :input não existe na taxonomia de Saeri et al. (use um código de nível 2, como 1.2).',
                'source_document.in' => 'O documento :input não está entre os documentos de origem da taxonomia.',
            ], array_combine(self::FIELDS, self::FIELDS));

            foreach ($validator->errors()->all() as $message) {
                $problems[] = "{$label}: {$message}";
            }
        }

        // The catalogue is picked by name, and each entry is one mitigation
        // of the Saeri database.
        foreach ($names as $name => $positions) {
            if (count($positions) > 1) {
                $problems[] = "o nome \"{$name}\" se repete nas entradas ".implode(', ', $positions).'.';
            }
        }

        foreach ($references as $reference => $positions) {
            if (count($positions) > 1) {
                $problems[] = "o source_reference \"{$reference}\" se repete nas entradas ".implode(', ', $positions).'.';
            }
        }

        if ($problems !== []) {
            throw new InvalidMitigationCatalog(
                "Catálogo de mitigações inválido ({$path}):\n- ".implode("\n- ", $problems),
            );
        }

        /** @var Catalogue $data */
        return $data;
    }

    /**
     * @param  array<mixed>  $meta
     * @return list<string>
     */
    protected function metaProblems(array $meta): array
    {
        $validator = Validator::make($meta, [
            'saeri_database_version' => ['required', 'string', 'max:255'],
            'accessed_at' => ['required', 'date_format:Y-m-d'],
            'curation_criterion' => ['required', 'string'],
            'fictional' => ['required', 'boolean:strict'],
        ], [], array_combine(
            ['saeri_database_version', 'accessed_at', 'curation_criterion', 'fictional'],
            ['meta.saeri_database_version', 'meta.accessed_at', 'meta.curation_criterion', 'meta.fictional'],
        ));

        return array_values(array_map(fn (string $message): string => "meta: {$message}", $validator->errors()->all()));
    }

    /**
     * The same limits the mitigations table and model expect.
     *
     * @param  list<string>  $subcategories
     * @param  list<string>  $documentKeys
     * @return array<string, array<mixed>>
     */
    protected function entryRules(array $subcategories, array $documentKeys): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'source_name' => ['required', 'string', 'max:255'],
            'source_reference' => ['required', 'string', 'max:255'],
            'source_document' => ['required', 'string', Rule::in($documentKeys)],
            'saeri_subcategory' => ['required', 'string', Rule::in($subcategories)],
            'description' => ['required', 'string', 'max:2000'],
            'suggested_target_risk' => ['required', 'string', 'max:2000'],
            'expected_evidence' => ['required', 'string', 'max:2000'],
            'suggested_cost' => ['required', Rule::enum(CostLevel::class)],
            'uncertainty_level' => ['required', Rule::enum(UncertaintyLevel::class)],
            'estimate_source' => ['required', 'string', 'max:1000'],
        ];
    }
}
