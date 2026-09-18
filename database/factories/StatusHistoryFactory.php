<?php

namespace Database\Factories;

use App\Enums\LinkStatus;
use App\Models\Link;
use App\Models\Owner;
use App\Models\StatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatusHistory>
 */
class StatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $previousStatus = fake()->randomElement(LinkStatus::cases());

        return [
            'previous_status' => $previousStatus,
            'new_status' => fake()->randomElement(
                array_values(array_filter(
                    LinkStatus::cases(),
                    fn (LinkStatus $status): bool => $status !== $previousStatus,
                )),
            ),
            'change_date' => fake()->dateTimeBetween('-6 months'),
            'link_id' => Link::factory(),
            'owner_id' => Owner::factory(),
        ];
    }
}
