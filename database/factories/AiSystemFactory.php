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
     * Plausible application domains for demonstration data.
     *
     * @var list<string>
     */
    public const APPLICATION_DOMAINS = [
        'Atendimento ao cliente',
        'Suporte técnico',
        'Crédito e concessão financeira',
        'Triagem de currículos',
        'Apoio a decisões clínicas',
        'Detecção de fraudes',
        'Recomendação de conteúdo',
        'Previsão de demanda',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'application_domain' => fake()->randomElement(self::APPLICATION_DOMAINS),
            'source_type' => fake()->randomElement(SystemSourceType::cases()),
            // An operable tier, so a link gets a review date; ask for
            // unacceptable() explicitly.
            'category' => fake()->randomElement(array_values(array_filter(
                AiSystemCategory::cases(),
                fn (AiSystemCategory $tier): bool => $tier->isOperable(),
            ))),
            'registration_date' => fake()->dateTimeBetween('-2 years'),
        ];
    }

    /**
     * Indicate that the system falls in the unacceptable tier: prohibited
     * practices, never in operation.
     */
    public function unacceptable(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => AiSystemCategory::Unacceptable,
        ]);
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
