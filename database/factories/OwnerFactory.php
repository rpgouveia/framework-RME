<?php

namespace Database\Factories;

use App\Models\Owner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Owner>
 */
class OwnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organizational_role' => fake()->jobTitle(),
            'area' => fake()->randomElement([
                'Engenharia',
                'Produto',
                'Dados',
                'Jurídico',
                'Compliance',
                'Segurança da Informação',
            ]),
        ];
    }
}
