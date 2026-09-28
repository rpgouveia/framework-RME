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
        // The factory hands out distinct names, as the catalogue requires.
        Mitigation::factory(8)->create();
    }
}
