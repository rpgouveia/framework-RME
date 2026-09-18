<?php

namespace Database\Seeders;

use App\Models\AiSystem;
use App\Models\Risk;
use Illuminate\Database\Seeder;

class RiskSeeder extends Seeder
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

        $aiSystems->each(
            fn (AiSystem $aiSystem) => Risk::factory(fake()->numberBetween(2, 4))
                ->for($aiSystem)
                ->create(),
        );
    }
}
