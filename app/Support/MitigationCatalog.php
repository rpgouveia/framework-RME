<?php

namespace App\Support;

use App\Enums\CostLevel;
use App\Enums\SaeriCategory;
use App\Enums\UncertaintyLevel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The curated mitigation catalogue (C2), read from a versioned data file.
 *
 * Mitigations only come from the catalogue (UC004, R-8): the app offers no
 * screen to create or change them. The group curates the file, so it is
 * checked as a whole before anything is stored, and every problem is reported
 * at once with the entry it belongs to.
 */
class MitigationCatalog
{
    /** The fields every entry must have, and no others. */
    public const FIELDS = [
        'name',
        'saeri_category',
        'description',
        'suggested_target_risk',
        'expected_evidence',
        'suggested_cost',
        'uncertainty_level',
        'bibliography_source',
    ];

    /**
     * Read and validate the catalogue file.
     *
     * @return list<array<string, string>>
     *
     * @throws InvalidMitigationCatalog When the file is missing, unreadable or
     *                                  holds an invalid entry.
     */
    public function entries(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidMitigationCatalog("Arquivo do catálogo de mitigações não encontrado: {$path}");
        }

        $entries = json_decode((string) file_get_contents($path), true);

        if (! is_array($entries) || ! array_is_list($entries)) {
            throw new InvalidMitigationCatalog("O catálogo de mitigações em {$path} deve ser um JSON com uma lista de mitigações.");
        }

        $problems = [];
        $names = [];

        foreach ($entries as $index => $entry) {
            $label = 'entrada '.($index + 1);

            if (! is_array($entry)) {
                $problems[] = "{$label}: deve ser um objeto com os campos da mitigação.";

                continue;
            }

            if (is_string($entry['name'] ?? null)) {
                $label .= ' ("'.$entry['name'].'")';
                $names[mb_strtolower(trim($entry['name']))][] = $index + 1;
            }

            $unknown = array_diff(array_keys($entry), self::FIELDS);

            if ($unknown !== []) {
                $problems[] = "{$label}: campos desconhecidos: ".implode(', ', $unknown).'.';
            }

            // Name each field by its key, so the message points at the file.
            $validator = Validator::make($entry, $this->rules(), [], array_combine(self::FIELDS, self::FIELDS));

            foreach ($validator->errors()->all() as $message) {
                $problems[] = "{$label}: {$message}";
            }
        }

        // The catalogue is picked by name, so two entries may not share one.
        foreach ($names as $name => $positions) {
            if (count($positions) > 1) {
                $problems[] = "o nome \"{$name}\" se repete nas entradas ".implode(', ', $positions).'.';
            }
        }

        if ($problems !== []) {
            throw new InvalidMitigationCatalog(
                "Catálogo de mitigações inválido ({$path}):\n- ".implode("\n- ", $problems),
            );
        }

        /** @var list<array<string, string>> $entries */
        return $entries;
    }

    /**
     * The same limits the mitigations table and model expect.
     *
     * @return array<string, array<mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'saeri_category' => ['required', Rule::enum(SaeriCategory::class)],
            'description' => ['required', 'string', 'max:2000'],
            'suggested_target_risk' => ['required', 'string', 'max:2000'],
            'expected_evidence' => ['required', 'string', 'max:2000'],
            'suggested_cost' => ['required', Rule::enum(CostLevel::class)],
            'uncertainty_level' => ['required', Rule::enum(UncertaintyLevel::class)],
            'bibliography_source' => ['required', 'string', 'max:255'],
        ];
    }
}
