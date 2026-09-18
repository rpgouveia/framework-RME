<?php

namespace Database\Seeders;

use App\Models\Evidence;
use App\Models\Link;
use Illuminate\Database\Seeder;

class EvidenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $links = Link::all();

        if ($links->isEmpty()) {
            $links = Link::factory(3)->create();
        }

        $links->each(
            fn (Link $link) => Evidence::factory(fake()->numberBetween(0, 2))
                ->for($link)
                ->create(),
        );
    }
}
