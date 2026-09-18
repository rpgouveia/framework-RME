<?php

namespace Database\Seeders;

use App\Models\Link;
use App\Models\Owner;
use App\Models\StatusHistory;
use Illuminate\Database\Seeder;

class StatusHistorySeeder extends Seeder
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

        $owners = Owner::all();

        if ($owners->isEmpty()) {
            $owners = Owner::factory(3)->create();
        }

        $links->each(
            fn (Link $link) => StatusHistory::factory(fake()->numberBetween(1, 3))
                ->for($link)
                ->recycle($owners)
                ->create(),
        );
    }
}
