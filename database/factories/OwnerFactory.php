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
     * Organizational roles, never people (R-2), each with the area it belongs
     * to, so a seeded register reads like a real accountability map.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'Gestor de Risco de IA' => 'Governança de IA',
        'Analista de Governança de IA' => 'Governança de IA',
        'Engenheiro de Machine Learning' => 'Engenharia',
        'Líder de Engenharia' => 'Engenharia',
        'Cientista de Dados' => 'Dados',
        'Engenheiro de Dados' => 'Dados',
        'Arquiteto de Segurança' => 'Segurança da Informação',
        'Analista de Resposta a Incidentes' => 'Segurança da Informação',
        'Encarregado de Dados (DPO)' => 'Jurídico',
        'Especialista em Privacidade' => 'Jurídico',
        'Analista de Compliance' => 'Compliance',
        'Auditor Interno' => 'Compliance',
        'Product Owner' => 'Produto',
        'Coordenador de Qualidade' => 'Qualidade',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // A role is unique within its area, ignoring letter case, like the
        // unique index; as each role belongs to one area, picking a role not
        // taken yet is enough.
        $role = $this->unusedName(array_keys(self::ROLES), Owner::query()->pluck('organizational_role')->all());

        return [
            'organizational_role' => $role,
            'area' => $this->areaOf($role),
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

    /**
     * The area of a role, by the name the factory drew: the trait numbers a
     * name once the list runs out, so the number is dropped before the lookup.
     */
    protected function areaOf(string $role): string
    {
        return self::ROLES[(string) preg_replace('/ \d+$/', '', $role)];
    }
}
