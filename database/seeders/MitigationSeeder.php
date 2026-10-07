<?php

namespace Database\Seeders;

use App\Models\Mitigation;
use App\Support\MitigationCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MitigationSeeder extends Seeder
{
    /**
     * Load the curated catalogue (C2) from its versioned data file.
     *
     * The file is validated as a whole first, so a broken entry stores
     * nothing. The factory stays for tests only.
     */
    public function run(MitigationCatalog $catalog): void
    {
        $entries = $catalog->entries(database_path('data/mitigation-catalog.json'));

        DB::transaction(function () use ($entries): void {
            foreach ($entries as $entry) {
                Mitigation::create($entry);
            }
        });
    }
}
