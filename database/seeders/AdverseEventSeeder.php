<?php

namespace Database\Seeders;

use App\Models\AdverseEvent;
use App\Models\AiSystem;
use Illuminate\Database\Seeder;

class AdverseEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $aiSystems = AiSystem::all();

        if ($aiSystems->isEmpty()) {
            $aiSystems = AiSystem::factory(3)->create();
        }

        /*
         * Not every system misbehaves, so only some of them get events.
         */
        $aiSystems->random(max(1, (int) ceil($aiSystems->count() / 2)))->each(
            fn (AiSystem $aiSystem) => AdverseEvent::factory(fake()->numberBetween(1, 3))
                ->for($aiSystem)
                ->create(),
        );
    }
}
