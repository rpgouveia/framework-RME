<?php

namespace Database\Factories;

use App\Enums\LifecyclePhase;
use App\Enums\RiskCategory;
use App\Enums\UncertaintyLevel;
use App\Models\AiSystem;
use App\Models\Risk;
use Database\Factories\Concerns\PicksUnusedNames;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Risk>
 */
class RiskFactory extends Factory
{
    use PicksUnusedNames;

    /**
     * Well known AI risk types, so seeded data reads like a real register.
     *
     * @var list<string>
     */
    protected const NAMES = [
        'Prompt injection',
        'Jailbreak',
        'Viés de seleção',
        'Viés de rótulo',
        'Discriminação algorítmica',
        'Alucinação',
        'Envenenamento de dados',
        'Vazamento de dados de treino',
        'Exposição de dados pessoais',
        'Inferência de pertencimento',
        'Inversão de modelo',
        'Extração de modelo',
        'Ataque adversarial',
        'Deriva de dados',
        'Degradação de desempenho',
        'Falta de explicabilidade',
        'Dependência excessiva da automação',
        'Uso indevido do sistema',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Varied lengths up to the 2000 character limit, so seeded data shows
            // how the screens cope with long descriptions.
            'description' => fake()->text(fake()->numberBetween(100, 2000)),
            'category' => fake()->randomElement(RiskCategory::cases()),
            'lifecycle_phase' => fake()->randomElement(LifecyclePhase::cases()),
            'uncertainty_level' => fake()->randomElement(UncertaintyLevel::cases()),
            'ai_system_id' => AiSystem::factory(),
            // After ai_system_id, so the closure sees the system the risk
            // belongs to: a name is unique only within one system.
            'name' => fn (array $attributes): string => $this->unusedName(
                self::NAMES,
                Risk::query()->where('ai_system_id', $attributes['ai_system_id'])->pluck('name')->all(),
                (string) $attributes['ai_system_id'],
            ),
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
