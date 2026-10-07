<?php

namespace Database\Seeders;

use App\Models\AiSystem;
use Database\Factories\AiSystemFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class AiSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // A different application domain for each system.
        $domains = fake()->randomElements(AiSystemFactory::APPLICATION_DOMAINS, 5);

        AiSystem::factory(5)
            ->sequence(fn (Sequence $sequence): array => ['application_domain' => $domains[$sequence->index]])
            ->create();
    }
}
