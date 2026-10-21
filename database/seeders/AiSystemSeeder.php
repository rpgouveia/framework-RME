<?php

namespace Database\Seeders;

use App\Models\AiSystem;
use Database\Factories\AiSystemFactory;
use Illuminate\Database\Seeder;

class AiSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * The whole demonstration portfolio is registered, each system with its
     * own application domain, origin and tier, plus one system in the
     * unacceptable tier, to show how the app treats a system that may not
     * operate.
     */
    public function run(): void
    {
        $portfolio = array_map(
            fn (string $name): array => ['name' => $name, ...AiSystemFactory::SYSTEMS[$name]],
            array_keys(AiSystemFactory::SYSTEMS),
        );

        AiSystem::factory(count($portfolio))->sequence(...$portfolio)->create();

        // Named, and not drawn, so the tutorial can point at it.
        $prohibited = array_key_first(AiSystemFactory::PROHIBITED);

        AiSystem::factory()->unacceptable()->create([
            'name' => $prohibited,
            ...AiSystemFactory::PROHIBITED[$prohibited],
        ]);
    }
}
