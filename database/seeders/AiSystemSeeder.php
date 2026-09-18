<?php

namespace Database\Seeders;

use App\Models\AiSystem;
use Illuminate\Database\Seeder;

class AiSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AiSystem::factory(5)->create();
    }
}
