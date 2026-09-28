<?php

namespace Database\Factories;

use App\Enums\CostLevel;
use App\Enums\SaeriCategory;
use App\Enums\UncertaintyLevel;
use App\Models\Mitigation;
use Database\Factories\Concerns\PicksUnusedNames;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mitigation>
 */
class MitigationFactory extends Factory
{
    use PicksUnusedNames;

    /**
     * Common AI risk mitigation measures, so seeded data reads like a real
     * catalogue.
     *
     * @var list<string>
     */
    protected const NAMES = [
        'Auditoria de equidade',
        'Red teaming',
        'Filtragem de entradas do usuário',
        'Revisão humana das decisões',
        'Monitoramento de deriva de dados',
        'Anonimização dos dados de treino',
        'Privacidade diferencial',
        'Controle de acesso ao modelo',
        'Rebalanceamento do conjunto de treino',
        'Documentação com model cards',
        'Verificação de respostas com fontes',
        'Limitação de taxa de requisições',
        'Plano de resposta a incidentes',
        'Treinamento adversarial',
        'Explicações locais das predições',
        'Registro de auditoria das inferências',
        'Testes de robustez',
        'Retreinamento periódico',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Unique across the whole catalogue.
            'name' => fn (): string => $this->unusedName(self::NAMES, Mitigation::query()->pluck('name')->all()),
            'description' => fake()->sentence(),
            'saeri_category' => fake()->randomElement(SaeriCategory::cases()),
            'suggested_target_risk' => fake()->sentence(),
            'expected_evidence' => fake()->sentence(),
            'suggested_cost' => fake()->randomElement(CostLevel::cases()),
            'uncertainty_level' => fake()->randomElement(UncertaintyLevel::cases()),
            'bibliography_source' => fake()->sentence(4),
        ];
    }

    /**
     * Indicate that the measure is cheap to put in place.
     */
    public function lowCost(): static
    {
        return $this->state(fn (array $attributes) => [
            'suggested_cost' => CostLevel::Low,
        ]);
    }
}
