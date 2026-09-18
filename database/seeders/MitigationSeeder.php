<?php

namespace Database\Seeders;

use App\Models\Mitigation;
use Illuminate\Database\Seeder;

class MitigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Mitigation::factory(8)->create();
    }
}
