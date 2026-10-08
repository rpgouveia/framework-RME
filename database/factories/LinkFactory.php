<?php

namespace Database\Factories;

use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Models\Link;
use App\Models\Mitigation;
use App\Models\Owner;
use App\Models\Risk;
use App\Support\MonitoringProtocol;
use Carbon\CarbonImmutable;
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
            'estimated_cost' => fake()->randomElement(CostLevel::cases()),
            'observed_cost' => null,
            'creation_date' => $creationDate,
            'risk_id' => Risk::factory(),
            'mitigation_id' => Mitigation::factory(),
            'owner_id' => Owner::factory(),
            // R-7, as CreateLink computes it, once the risk is known.
            'next_review_date' => fn (array $attributes): ?CarbonImmutable => app(MonitoringProtocol::class)->nextReviewDate(
                Risk::query()->with('aiSystem')->findOrFail((int) $attributes['risk_id'])->aiSystem,
                CarbonImmutable::parse($attributes['creation_date']),
            ),
        ];
    }

    /**
     * Indicate that the mitigation is already in place and its cost is known.
     */
    public function implemented(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LinkStatus::Implemented,
            'observed_cost' => fake()->randomElement(CostLevel::cases()),
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
