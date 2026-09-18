<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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

        /*
         * Order matters: every seeder below depends on the ones above it.
         */
        $this->call([
            AiSystemSeeder::class,
            RiskSeeder::class,
            MitigationSeeder::class,
            OwnerSeeder::class,
            LinkSeeder::class,
            StatusHistorySeeder::class,
            EvidenceSeeder::class,
        ]);
    }
}
