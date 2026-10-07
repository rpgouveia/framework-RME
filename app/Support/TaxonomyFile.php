<?php

namespace App\Support;

use App\Models\Taxonomy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * A reference taxonomy read from a versioned data file (RNF05).
 *
 * The file is checked as a whole before anything is stored, and every
 * problem is reported at once: codes unique, each parent present, and each
 * level one below its parent's.
 *
 * @phpstan-type Term array{code: string, parent: string|null, level: int, name: string, original_name: string, description?: string|null}
 * @phpstan-type Definition array{meta: array<string, mixed>, terms: list<Term>}
 */
class TaxonomyFile
{
    /** The fields a term may have. */
    public const TERM_FIELDS = ['code', 'parent', 'level', 'name', 'original_name', 'description'];

    /**
     * Read and validate a taxonomy file.
     *
     * @return Definition
     *
     * @throws InvalidTaxonomy When the file is missing or holds an invalid
     *                         definition.
     */
    public function read(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidTaxonomy("Arquivo de taxonomia não encontrado: {$path}");
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data) || ! is_array($data['meta'] ?? null) || ! is_array($data['terms'] ?? null) || ! array_is_list($data['terms'])) {
            throw new InvalidTaxonomy("A taxonomia em {$path} deve ser um JSON com \"meta\" e uma lista \"terms\".");
        }

        $problems = [...$this->metaProblems($data['meta']), ...$this->termProblems($data['terms'])];

        if ($problems !== []) {
            throw new InvalidTaxonomy("Taxonomia inválida ({$path}):\n- ".implode("\n- ", $problems));
        }

        /** @var Definition $data */
        return $data;
    }

    /**
     * Store a validated definition: the taxonomy, then its terms level by
     * level, so every parent exists before its children.
     *
     * @param  Definition  $definition
     */
    public function store(array $definition): Taxonomy
    {
        return DB::transaction(function () use ($definition): Taxonomy {
            $meta = $definition['meta'];

            $taxonomy = Taxonomy::create([
                'key' => $meta['key'],
                'name' => $meta['name'],
                'citation' => $meta['citation'],
                'version' => $meta['version'],
                'url' => $meta['url'],
                'accessed_at' => $meta['accessed_at'],
                'metadata' => array_diff_key($meta, array_flip(['key', 'name', 'citation', 'version', 'url', 'accessed_at'])),
            ]);

            $terms = collect($definition['terms'])->map(fn (array $term, int $position): array => [...$term, 'position' => $position]);
            $ids = [];

            foreach ($terms->sortBy('level') as $term) {
                $ids[$term['code']] = $taxonomy->terms()->create([
                    'parent_id' => $term['parent'] === null ? null : $ids[$term['parent']],
                    'code' => $term['code'],
                    'level' => $term['level'],
                    'name' => $term['name'],
                    'original_name' => $term['original_name'],
                    'description' => $term['description'] ?? null,
                    'position' => $term['position'],
                ])->id;
            }

            return $taxonomy;
        });
    }

    /**
     * Read, validate and store a taxonomy file.
     */
    public function load(string $path): Taxonomy
    {
        return $this->store($this->read($path));
    }

    /**
     * @param  array<mixed>  $meta
     * @return list<string>
     */
    protected function metaProblems(array $meta): array
    {
        $problems = [];

        $validator = Validator::make($meta, [
            'key' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'citation' => ['required', 'string'],
            'version' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'accessed_at' => ['required', 'date_format:Y-m-d'],
            'documents' => ['sometimes', 'list'],
            'documents.*.key' => ['required', 'string'],
            'documents.*.title' => ['required', 'string'],
        ], [], ['key' => 'meta.key', 'name' => 'meta.name', 'citation' => 'meta.citation', 'version' => 'meta.version', 'url' => 'meta.url', 'accessed_at' => 'meta.accessed_at']);

        foreach ($validator->errors()->all() as $message) {
            $problems[] = "meta: {$message}";
        }

        $keys = array_count_values(array_filter(array_column((array) ($meta['documents'] ?? []), 'key'), 'is_string'));

        foreach ($keys as $key => $count) {
            if ($count > 1) {
                $problems[] = "meta: a chave de documento \"{$key}\" se repete.";
            }
        }

        return $problems;
    }

    /**
     * @param  list<mixed>  $terms
     * @return list<string>
     */
    protected function termProblems(array $terms): array
    {
        $problems = [];
        $byCode = [];

        foreach ($terms as $index => $term) {
            $label = 'termo '.($index + 1);

            if (! is_array($term)) {
                $problems[] = "{$label}: deve ser um objeto com os campos do termo.";

                continue;
            }

            if (is_string($term['code'] ?? null)) {
                $label .= ' ("'.$term['code'].'")';

                if (isset($byCode[$term['code']])) {
                    $problems[] = "{$label}: o código se repete (já usado no termo {$byCode[$term['code']]['position']}).";
                } else {
                    $byCode[$term['code']] = ['position' => $index + 1, 'term' => $term];
                }
            }

            $unknown = array_diff(array_keys($term), self::TERM_FIELDS);

            if ($unknown !== []) {
                $problems[] = "{$label}: campos desconhecidos: ".implode(', ', $unknown).'.';
            }

            $validator = Validator::make($term, [
                'code' => ['required', 'string', 'max:255'],
                'parent' => ['present', 'nullable', 'string'],
                'level' => ['required', 'integer', 'min:1'],
                'name' => ['required', 'string', 'max:255'],
                'original_name' => ['required', 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string'],
            ], [], array_combine(self::TERM_FIELDS, self::TERM_FIELDS));

            foreach ($validator->errors()->all() as $message) {
                $problems[] = "{$label}: {$message}";
            }
        }

        // The tree: each parent exists, and each level is one below it.
        foreach ($byCode as $code => ['position' => $position, 'term' => $term]) {
            $label = "termo {$position} (\"{$code}\")";
            $parent = $term['parent'] ?? null;
            $level = $term['level'] ?? null;

            if (! is_int($level)) {
                continue;
            }

            if ($parent === null) {
                if ($level !== 1) {
                    $problems[] = "{$label}: um termo sem pai deve ter level 1, não {$level}.";
                }

                continue;
            }

            if (! is_string($parent) || ! isset($byCode[$parent])) {
                $problems[] = "{$label}: o pai \"".(is_scalar($parent) ? $parent : '?').'" não existe na taxonomia.';

                continue;
            }

            $parentLevel = $byCode[$parent]['term']['level'] ?? null;

            if (is_int($parentLevel) && $level !== $parentLevel + 1) {
                $problems[] = "{$label}: level {$level} não é coerente com o pai \"{$parent}\" (level {$parentLevel}); deveria ser ".($parentLevel + 1).'.';
            }
        }

        return $problems;
    }
}
