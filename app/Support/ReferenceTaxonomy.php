<?php

namespace App\Support;

use App\Models\Taxonomy;
use App\Models\TaxonomyTerm;
use Illuminate\Database\Eloquent\Collection;

/**
 * A two-level reference taxonomy shipped as a versioned data file (RNF05),
 * such as Saeri's mitigation categories or the MIT AI risk domains.
 */
abstract class ReferenceTaxonomy
{
    /** The key in the taxonomy file's metadata. */
    abstract public static function key(): string;

    /** The versioned data file it is loaded from. */
    abstract public static function path(): string;

    /**
     * The taxonomy as stored, loading it from its file when it is missing,
     * as in a fresh test database.
     */
    public function taxonomy(): Taxonomy
    {
        return Taxonomy::query()->where('key', static::key())->first()
            ?? app(TaxonomyFile::class)->load(static::path());
    }

    /**
     * The first level, in order, each with its children.
     *
     * @return Collection<int, TaxonomyTerm>
     */
    public function topLevel(): Collection
    {
        return $this->taxonomy()->terms()
            ->where('level', 1)
            ->orderBy('position')
            ->with('children')
            ->get();
    }

    /**
     * The second level, the one things are classified at.
     *
     * @return Collection<int, TaxonomyTerm>
     */
    public function secondLevel(): Collection
    {
        return $this->taxonomy()->terms()->where('level', 2)->orderBy('position')->get();
    }

    /**
     * The first level as a tree for filters and selects.
     *
     * @return array<int, array{code: string, name: string, children: array<int, array<string, mixed>>}>
     */
    public function tree(bool $withIds = false, bool $withDescriptions = false): array
    {
        $fields = [...($withIds ? ['id'] : []), 'code', 'name', ...($withDescriptions ? ['description'] : [])];

        return $this->topLevel()->map(fn (TaxonomyTerm $term): array => [
            'code' => $term->code,
            'name' => $term->name,
            'children' => $term->children->map->only($fields)->values()->all(),
        ])->values()->all();
    }
}
