<?php

namespace Database\Seeders;

use App\Models\Owner;
use Illuminate\Database\Seeder;

class OwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Enough roles for a portfolio where the same role does not answer for
     * everything, plus one retired role: it stays in the history of what it
     * signed and out of the lists of choice (0009).
     */
    public function run(): void
    {
        Owner::factory(10)->create();

        Owner::factory()->inactive()->create();
    }
}
