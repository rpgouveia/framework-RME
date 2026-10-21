<?php

namespace Database\Factories;

use App\Enums\SystemChangeType;
use App\Models\AiSystem;
use App\Models\SystemChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A system change as a record only: it reverts nothing. Tests and seeders
 * that need the reversal go through RecordSystemChange.
 *
 * @extends Factory<SystemChange>
 */
class SystemChangeFactory extends Factory
{
    /**
     * What changes in a system in operation, by type of change (0021), so the
     * description says what the record announces.
     *
     * @var array<string, list<string>>
     */
    public const DESCRIPTIONS = [
        SystemChangeType::ModelVersion->value => [
            'Troca do modelo de classificação pela versão 2.0 do fornecedor, com mudança na janela de contexto.',
            'Retreinamento periódico do modelo com os dados dos últimos doze meses.',
            'Ajuste fino do modelo de base com exemplos revisados pela equipe de qualidade.',
            'Atualização do modelo de terceiros aplicada pelo fornecedor, anunciada com uma semana de antecedência.',
        ],
        SystemChangeType::DataChange->value => [
            'Inclusão dos registros de um novo canal de atendimento na base de treinamento.',
            'Exclusão dos dados de clientes inativos da base de inferência, por retenção.',
            'Troca da fonte de dados cadastrais pelo novo sistema corporativo, com outro formato de campo.',
            'Correção em massa de rótulos da base de treinamento depois da revisão por amostra.',
        ],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(SystemChangeType::cases());

        return [
            'ai_system_id' => AiSystem::factory(),
            'type' => $type,
            'description' => self::describe($type),
            'change_date' => fake()->dateTimeBetween('-6 months'),
        ];
    }

    /**
     * One of the changes a system goes through, by type of change.
     */
    public static function describe(SystemChangeType $type): string
    {
        return fake()->randomElement(self::DESCRIPTIONS[$type->value]);
    }
}
