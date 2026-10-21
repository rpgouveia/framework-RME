<?php

namespace Database\Factories;

use App\Enums\LinkStatus;
use App\Models\AdverseEvent;
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
     * What makes a link move, by the progress it moves to, so the reason
     * recorded in the trail explains the change it justifies.
     *
     * @var array<string, list<string>>
     */
    public const TRIGGER_REASONS = [
        LinkStatus::Planned->value => [
            'Vínculo reativado: a mitigação volta ao planejamento com novo prazo.',
            'Implementação replanejada depois da revisão do escopo do controle.',
        ],
        LinkStatus::InProgress->value => [
            'Início da implementação aprovado pelo comitê, com a equipe alocada no trimestre.',
            'A configuração do controle começou no ambiente de homologação.',
            'Fornecedor contratado; a implantação começou nesta semana.',
        ],
        LinkStatus::Implemented->value => [
            'Controle em operação em produção, com o monitoramento ativo.',
            'Implantação concluída e comunicada às áreas afetadas.',
            'A última etapa do controle entrou em produção após o teste de aceitação.',
        ],
        LinkStatus::Suspended->value => [
            'Implementação suspensa até a definição do novo fornecedor do modelo.',
            'Equipe realocada para o atendimento de um incidente; retomada prevista no próximo ciclo.',
            'Implantação pausada por indisponibilidade do ambiente de homologação.',
        ],
        LinkStatus::Cancelled->value => [
            'Vínculo cancelado: outra mitigação do catálogo trata melhor este risco.',
            'Vínculo encerrado por decisão da área responsável, sem substituto.',
            'O sistema deixou de usar o componente que a mitigação protegia.',
        ],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $previousStatus = fake()->randomElement(LinkStatus::cases());
        $newStatus = fake()->randomElement(
            array_values(array_filter(
                LinkStatus::cases(),
                fn (LinkStatus $status): bool => $status !== $previousStatus,
            )),
        );

        return [
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'trigger_reason' => self::reasonFor($newStatus),
            'change_date' => fake()->dateTimeBetween('-6 months'),
            'link_id' => Link::factory(),
            'owner_id' => Owner::factory(),
            'adverse_event_id' => null,
        ];
    }

    /**
     * Indicate that this is the first entry of a link's trail.
     */
    public function opening(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_status' => null,
            'trigger_reason' => null,
        ]);
    }

    /**
     * Indicate that an adverse event forced the change.
     */
    public function triggeredByAdverseEvent(): static
    {
        return $this->state(fn (array $attributes) => [
            'adverse_event_id' => AdverseEvent::factory(),
        ]);
    }

    /**
     * One of the reasons that take a link to this progress.
     */
    public static function reasonFor(LinkStatus $status): string
    {
        return fake()->randomElement(self::TRIGGER_REASONS[$status->value]);
    }
}
