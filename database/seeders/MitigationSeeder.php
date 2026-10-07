<?php

namespace Database\Seeders;

use App\Models\Mitigation;
use App\Models\ReferenceDataset;
use App\Support\MitigationCatalog;
use App\Support\SaeriTaxonomy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MitigationSeeder extends Seeder
{
    /**
     * Load the curated catalogue (C2) from its versioned data file.
     *
     * The file is validated as a whole first, so a broken entry stores
     * nothing. Its metadata is kept too, so the app can tell whether the
     * catalogue it holds is still fictional. The factory stays for tests.
     */
    public function run(MitigationCatalog $catalog, SaeriTaxonomy $saeri): void
    {
        $data = $catalog->read(MitigationCatalog::path());
        $subcategories = $saeri->subcategories()->pluck('id', 'code');

        DB::transaction(function () use ($data, $subcategories): void {
            foreach ($data['entries'] as $entry) {
                Mitigation::create([
                    ...Arr::except($entry, 'saeri_subcategory'),
                    'saeri_subcategory_id' => $subcategories[$entry['saeri_subcategory']],
                ]);
            }

            ReferenceDataset::query()->updateOrCreate(['key' => MitigationCatalog::DATASET], ['metadata' => $data['meta']]);
        });
    }
}
