<?php

namespace Database\Factories;

use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Link>
 */
class LinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $creationDate = fake()->dateTimeBetween('-1 year');

        return [
            'lifecycle_phase' => fake()->randomElement(LifecyclePhase::cases()),
            'status' => fake()->randomElement(LinkStatus::cases()),
            'estimated_cost' => fake()->randomFloat(2, 500, 50000),
            'observed_cost' => null,
            'creation_date' => $creationDate,
            'next_review_date' => fake()->dateTimeBetween($creationDate, '+1 year'),
            'risk_id' => Risk::factory(),
            'mitigation_id' => Mitigation::factory(),
            'owner_id' => Owner::factory(),
        ];
    }

    /**
     * Indicate that the mitigation is already in place and its cost is known.
     */
    public function implemented(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LinkStatus::Implemented,
            'observed_cost' => fake()->randomFloat(2, 500, 50000),
        ]);
    }

    /**
     * Indicate that the link is past its review date.
     */
    public function dueForReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_review_date' => fake()->dateTimeBetween('-3 months', '-1 day'),
        ]);
    }
}
