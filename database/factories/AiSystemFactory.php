<?php

namespace Database\Factories;

use App\Enums\AiSystemCategory;
use App\Enums\SystemSourceType;
use App\Models\AiSystem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiSystem>
 */
class AiSystemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'source_type' => fake()->randomElement(SystemSourceType::cases()),
            'category' => fake()->randomElement(AiSystemCategory::cases()),
            'registration_date' => fake()->dateTimeBetween('-2 years'),
        ];
    }

    /**
     * Indicate that the system falls in the high risk tier.
     */
    public function highRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => AiSystemCategory::High,
        ]);
    }
}
