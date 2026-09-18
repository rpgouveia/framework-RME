<?php

namespace Database\Factories;

use App\Enums\SaeriCategory;
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
        ];
    }
}
