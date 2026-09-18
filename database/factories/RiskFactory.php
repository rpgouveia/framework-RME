<?php

namespace Database\Factories;

use App\Enums\LifecyclePhase;
use App\Enums\RiskCategory;
use App\Enums\UncertaintyLevel;
use App\Models\AiSystem;
use App\Models\Risk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Risk>
 */
class RiskFactory extends Factory
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
            'category' => fake()->randomElement(RiskCategory::cases()),
            'lifecycle_phase' => fake()->randomElement(LifecyclePhase::cases()),
            'uncertainty_level' => fake()->randomElement(UncertaintyLevel::cases()),
            'ai_system_id' => AiSystem::factory(),
        ];
    }

    /**
     * Indicate that little is known about the risk.
     */
    public function highUncertainty(): static
    {
        return $this->state(fn (array $attributes) => [
            'uncertainty_level' => UncertaintyLevel::High,
        ]);
    }
}
