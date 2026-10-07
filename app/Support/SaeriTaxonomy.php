<?php

namespace App\Support;

use App\Models\TaxonomyTerm;
use Illuminate\Database\Eloquent\Collection;

/**
 * The AI Risk Mitigation Taxonomy of Saeri et al. (2025), which organizes the
 * mitigation catalogue (C2): four categories (level 1) and 23 subcategories
 * (level 2), plus the 13 source documents of their evidence scan.
 */
class SaeriTaxonomy extends ReferenceTaxonomy
{
    public const KEY = 'saeri-mitigations';

    public static function key(): string
    {
        return self::KEY;
    }

    public static function path(): string
    {
        return database_path('data/taxonomies/saeri-mitigation-taxonomy.json');
    }

    /**
     * The categories, in order, each with its subcategories.
     *
     * @return Collection<int, TaxonomyTerm>
     */
    public function categories(): Collection
    {
        return $this->topLevel();
    }

    /**
     * The subcategories, the level mitigations are classified at.
     *
     * @return Collection<int, TaxonomyTerm>
     */
    public function subcategories(): Collection
    {
        return $this->secondLevel();
    }

    /**
     * The source documents of the evidence scan, keyed by their short key.
     *
     * @return array<string, array<string, mixed>>
     */
    public function documents(): array
    {
        /** @var list<array<string, mixed>> $documents */
        $documents = $this->taxonomy()->metadata['documents'] ?? [];

        return array_column($documents, null, 'key');
    }
}
