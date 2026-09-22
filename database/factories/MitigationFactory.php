<?php

namespace Database\Factories;

use App\Enums\CostLevel;
use App\Enums\SaeriCategory;
use App\Enums\UncertaintyLevel;
use App\Models\Mitigation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mitigation>
 */
class MitigationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(),
            'saeri_category' => fake()->randomElement(SaeriCategory::cases()),
            'suggested_target_risk' => fake()->sentence(),
            'expected_evidence' => fake()->sentence(),
            'suggested_cost' => fake()->randomElement(CostLevel::cases()),
            'uncertainty_level' => fake()->randomElement(UncertaintyLevel::cases()),
            'bibliography_source' => fake()->sentence(4),
        ];
    }

    /**
     * Indicate that the measure is cheap to put in place.
     */
    public function lowCost(): static
    {
        return $this->state(fn (array $attributes) => [
            'suggested_cost' => CostLevel::Low,
        ]);
    }
}
