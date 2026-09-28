<?php

namespace Database\Seeders;

use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use Illuminate\Database\Seeder;

class LinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Reuses the existing mitigations and owners instead of creating a new one
     * per link, so the seeded data looks like a real portfolio.
     */
    public function run(): void
    {
        $risks = Risk::all();

        if ($risks->isEmpty()) {
            $risks = Risk::factory(3)->create();
        }

        $mitigations = Mitigation::all();

        if ($mitigations->isEmpty()) {
            $mitigations = Mitigation::factory(5)->create();
        }

        $owners = Owner::all();

        if ($owners->isEmpty()) {
            $owners = Owner::factory(3)->create();
        }

        /*
         * Draw distinct mitigations for each risk: a risk and a mitigation may
         * be linked only once.
         */
        $risks->each(
            fn (Risk $risk) => $mitigations
                ->random(min(fake()->numberBetween(1, 2), $mitigations->count()))
                ->each(fn (Mitigation $mitigation) => Link::factory()
                    ->for($risk)
                    ->for($mitigation)
                    ->recycle($owners)
                    ->create()),
        );
    }
}
