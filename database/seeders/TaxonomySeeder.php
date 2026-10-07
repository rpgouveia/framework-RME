<?php

namespace Database\Seeders;

use App\Models\Taxonomy;
use App\Support\TaxonomyFile;
use Illuminate\Database\Seeder;

class TaxonomySeeder extends Seeder
{
    /**
     * Load every reference taxonomy from its versioned file (RNF05).
     *
     * Each file is validated as a whole first, so a broken one stores
     * nothing. A taxonomy already stored (for instance, loaded on demand by
     * a factory) is left as it is.
     */
    public function run(TaxonomyFile $taxonomies): void
    {
        foreach (glob(database_path('data/taxonomies/*.json')) ?: [] as $path) {
            $definition = $taxonomies->read($path);

            if (Taxonomy::query()->where('key', $definition['meta']['key'])->doesntExist()) {
                $taxonomies->store($definition);
            }
        }
    }
}
