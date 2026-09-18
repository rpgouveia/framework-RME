<?php

namespace Database\Factories;

use App\Enums\EvidenceType;
use App\Models\Evidence;
use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evidence>
 */
class EvidenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(EvidenceType::cases()),
            'description' => fake()->sentence(),
            'registration_date' => fake()->dateTimeBetween('-6 months'),
            'link_id' => Link::factory(),
        ];
    }
}
