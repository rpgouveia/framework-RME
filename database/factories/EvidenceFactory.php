<?php

namespace Database\Factories;

use App\Enums\EvidenceType;
use App\Models\Evidence;
use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evidence>
 */
class EvidenceFactory extends Factory
{
    /**
     * What each kind of record proves about a mitigation in place, by type of
     * evidence, so the description says the same thing the type announces.
     *
     * @var array<string, list<string>>
     */
    public const DESCRIPTIONS = [
        EvidenceType::Document->value => [
            'Política de uso do sistema aprovada pelo comitê de governança de IA, com as restrições de finalidade e o fluxo de exceção.',
            'Procedimento operacional da revisão humana das decisões, com os critérios de encaminhamento e os prazos de resposta.',
            'Especificação do controle implantado, com a configuração vigente em produção e quem responde por mantê-la.',
            'Plano de resposta a incidentes do sistema, com os acionamentos, os papéis e o prazo de comunicação interna.',
        ],
        EvidenceType::Report->value => [
            'Relatório de auditoria de equidade do trimestre, com as métricas por subgrupo e as diferenças encontradas.',
            'Relatório do exercício de red teaming contratado, com as tentativas bem-sucedidas e as correções acordadas.',
            'Relatório trimestral de monitoramento, com a deriva observada nos dados de entrada e as faixas de alerta.',
            'Relatório de avaliação de impacto do sistema, com os grupos afetados e as medidas adotadas para cada um.',
        ],
        EvidenceType::AuditLog->value => [
            'Extração do log de inferências do último mês, com o identificador do caso, a versão do modelo e quem consultou.',
            'Log de acesso à base de inferência, mostrando que apenas os perfis autorizados consultaram os dados pessoais.',
            'Registro automático das decisões revertidas pela revisão humana, com o motivo informado pelo analista.',
            'Log de bloqueios do filtro de conteúdo, com o volume por categoria e os casos encaminhados para análise.',
        ],
        EvidenceType::TestResult->value => [
            'Resultado do teste de robustez com entradas adversariais, com a taxa de bloqueio por categoria de ataque.',
            'Resultado da bateria de regressão da versão em produção, com os casos de borda do controle aprovados.',
            'Resultado do teste de injeção de instruções na versão atual do modelo, sem contorno das restrições configuradas.',
            'Resultado da conferência por amostra das decisões do sistema, com a divergência frente ao parecer humano.',
        ],
        EvidenceType::Certification->value => [
            'Certificado ISO/IEC 27001 do fornecedor do modelo, vigente, com o escopo que cobre o serviço contratado.',
            'Atestado de auditoria independente sobre os controles de acesso ao modelo, sem apontamentos abertos.',
            'Declaração de conformidade do fornecedor com a política de tratamento de dados acordada em contrato.',
        ],
        EvidenceType::MeetingMinutes->value => [
            'Ata do comitê de governança de IA que aprovou a implantação do controle e designou o responsável.',
            'Ata da reunião de revisão do risco, com a decisão de manter a mitigação e o prazo da próxima conferência.',
            'Ata da reunião com o fornecedor sobre a nova versão do modelo, com os compromissos de comunicação prévia.',
        ],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(EvidenceType::cases());

        return [
            'type' => $type,
            'description' => self::describe($type),
            'registration_date' => fake()->dateTimeBetween('-6 months'),
            'observed_cost' => null,
            'link_id' => Link::factory(),
        ];
    }

    /**
     * One of the records that prove a mitigation by this type of evidence.
     */
    public static function describe(EvidenceType $type): string
    {
        return fake()->randomElement(self::DESCRIPTIONS[$type->value]);
    }
}
