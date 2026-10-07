<?php

namespace Database\Factories;

use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Support\AiRiskDomains;
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
            'description' => fake()->paragraph(),
            'occurrence_date' => fake()->dateTimeBetween('-1 year'),
            'ai_system_id' => AiSystem::factory(),
        ];
    }

    /**
     * Every adverse event materializes at least one risk subdomain.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (AdverseEvent $adverseEvent): void {
            if ($adverseEvent->riskSubdomains()->doesntExist()) {
                $adverseEvent->riskSubdomains()->attach(
                    app(AiRiskDomains::class)->subdomains()->random(fake()->numberBetween(1, 2))->pluck('id'),
                );
            }
        });
    }

    /**
     * Materialize exactly these risk subdomains, such as ["2.1", "2.2"].
     *
     * @param  list<string>  $codes
     */
    public function materializing(array $codes): static
    {
        return $this->afterCreating(fn (AdverseEvent $adverseEvent) => $adverseEvent->riskSubdomains()->sync(
            app(AiRiskDomains::class)->subdomains()->whereIn('code', $codes)->pluck('id'),
        ));
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
