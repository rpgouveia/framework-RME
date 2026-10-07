<?php

namespace Database\Seeders;

use App\Support\TaxonomyFile;
use Illuminate\Database\Seeder;

class TaxonomySeeder extends Seeder
{
    /**
     * Load every reference taxonomy from its versioned file (RNF05).
     *
     * Each file is validated as a whole first, so a broken one stores
     * nothing.
     */
    public function run(TaxonomyFile $taxonomies): void
    {
        foreach (glob(database_path('data/taxonomies/*.json')) ?: [] as $path) {
            $taxonomies->load($path);
        }
    }
}
