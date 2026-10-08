<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory()->create([
            'name' => 'test',
            'email' => 'teste@teste.com',
            'password' => Hash::make('teste123'),
        ]);

        /*
         * Order matters: every seeder below depends on the ones above it.
         */
        $this->call([
            TaxonomySeeder::class,
            AiSystemSeeder::class,
            RiskSeeder::class,
            // Past events, in subdomains of each system's own risks.
            AdverseEventSeeder::class,
            MitigationSeeder::class,
            OwnerSeeder::class,
            // Links with their trail, evidence and verification, written
            // through the app's actions.
            LinkSeeder::class,
            // The reassessment triggers (0019), on the links as they stand.
            ReassessmentSeeder::class,
        ]);
    }
}
