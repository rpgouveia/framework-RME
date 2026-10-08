<?php

namespace Database\Factories;

use App\Enums\CostLevel;
use App\Enums\LifecyclePhase;
use App\Enums\LinkStatus;
use App\Enums\VerificationStatus;
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
            // Born declared, with no review date until verified (0018).
            'verification_status' => VerificationStatus::Declared,
            'creation_date' => $creationDate,
            'risk_id' => Risk::factory(),
            'mitigation_id' => Mitigation::factory(),
            'owner_id' => Owner::factory(),
            // Last, so a state can compute it once the risk is known.
            'next_review_date' => null,
        ];
    }

    /**
     * Indicate that the mitigation is already in place.
     */
    public function implemented(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LinkStatus::Implemented,
        ]);
    }

    /**
     * Indicate that the link was verified when it was created, so its
     * review is due one interval of its system's tier later (R-7). A test
     * that needs the trail entry should verify through RecordStatusChange.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Verified,
            'next_review_date' => fn (array $attributes): ?CarbonImmutable => app(MonitoringProtocol::class)->nextReviewDate(
                Risk::query()->with('aiSystem')->findOrFail((int) $attributes['risk_id'])->aiSystem,
                CarbonImmutable::parse($attributes['creation_date']),
            ),
        ]);
    }

    /**
     * Indicate that the link is verified and past its review date.
     */
    public function dueForReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Verified,
            'next_review_date' => fake()->dateTimeBetween('-3 months', '-1 day'),
        ]);
    }
}
