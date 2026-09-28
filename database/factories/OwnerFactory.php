<?php

namespace Database\Factories;

use App\Models\Owner;
use Database\Factories\Concerns\PicksUnusedNames;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Owner>
 */
class OwnerFactory extends Factory
{
    use PicksUnusedNames;

    /**
     * Organizational roles, never people (R-2), so seeded data reads like a
     * real accountability map.
     *
     * @var list<string>
     */
    protected const ROLES = [
        'Gestor de Risco de IA',
        'Cientista de Dados',
        'Engenheiro de Machine Learning',
        'Encarregado de Dados (DPO)',
        'Analista de Compliance',
        'Arquiteto de Segurança',
        'Product Owner',
        'Líder de Engenharia',
        'Auditor Interno',
        'Analista de Governança de IA',
        'Especialista em Privacidade',
        'Coordenador de Qualidade',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'area' => fake()->randomElement([
                'Engenharia',
                'Produto',
                'Dados',
                'Jurídico',
                'Compliance',
                'Segurança da Informação',
            ]),
            // After area: a role is unique only within its area, ignoring
            // letter case, like the unique index.
            'organizational_role' => fn (array $attributes): string => $this->unusedName(
                self::ROLES,
                Owner::query()->whereRaw('lower(area) = lower(?)', [$attributes['area']])->pluck('organizational_role')->all(),
                mb_strtolower((string) $attributes['area']),
            ),
        ];
    }

    /**
     * Indicate that the role was retired.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'deactivated_at' => now(),
        ]);
    }
}
