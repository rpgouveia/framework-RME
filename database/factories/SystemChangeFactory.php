<?php

namespace Database\Factories;

use App\Enums\SystemChangeType;
use App\Models\AiSystem;
use App\Models\SystemChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A system change as a record only: it reverts nothing. Tests and seeders
 * that need the reversal go through RecordSystemChange.
 *
 * @extends Factory<SystemChange>
 */
class SystemChangeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ai_system_id' => AiSystem::factory(),
            'type' => fake()->randomElement(SystemChangeType::cases()),
            'description' => fake()->sentence(),
            'change_date' => fake()->dateTimeBetween('-6 months'),
        ];
    }
}
