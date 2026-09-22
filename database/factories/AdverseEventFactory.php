<?php

namespace Database\Factories;

use App\Enums\AdverseEventType;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdverseEvent>
 */
class AdverseEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_type' => fake()->randomElement(AdverseEventType::cases()),
            'description' => fake()->paragraph(),
            'occurrence_date' => fake()->dateTimeBetween('-1 year'),
            'ai_system_id' => AiSystem::factory(),
        ];
    }

    /**
     * Indicate that the event happened in the last month.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'occurrence_date' => fake()->dateTimeBetween('-1 month'),
        ]);
    }
}
