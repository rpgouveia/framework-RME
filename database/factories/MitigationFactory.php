<?php

namespace Database\Factories;

use App\Enums\CostLevel;
use App\Enums\UncertaintyLevel;
use App\Models\Mitigation;
use App\Support\AiRiskDomains;
use App\Support\SaeriTaxonomy;
use Database\Factories\Concerns\PicksUnusedNames;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * The catalogue the app loads is the real one (0024); this factory builds the
 * entries the tests need, with the same shape and no dependency on the names
 * or the identifiers of the real catalogue.
 *
 * @extends Factory<Mitigation>
 */
class MitigationFactory extends Factory
{
    use PicksUnusedNames;

    /**
     * Common AI risk mitigation measures, each with the name of the source
     * measure, the Saeri subcategory it is classified in, what it does, the
     * risk it treats and what would prove it was applied, so a factory entry
     * reads like a catalogue entry.
     *
     * @var array<string, array{source_name: string, subcategory: string, description: string, suggested_target_risk: string, expected_evidence: string}>
     */
    public const MEASURES = [
        'Auditoria de equidade' => [
            'source_name' => 'Fairness Audit',
            'subcategory' => '3.1',
            'description' => 'Avaliação periódica do desempenho e dos resultados do sistema por subgrupo da população atendida, com limites de diferença aceitável acordados antes da medição.',
            'suggested_target_risk' => 'Decisões sistematicamente menos favoráveis a um grupo, que a métrica agregada do sistema não revela.',
            'expected_evidence' => 'Relatório de auditoria de equidade, com as métricas por subgrupo, os limites aplicados e o plano de ação para as diferenças encontradas.',
        ],
        'Red teaming' => [
            'source_name' => 'Red Teaming',
            'subcategory' => '3.1',
            'description' => 'Exercício dirigido em que uma equipe tenta levar o sistema a comportamentos indesejados, com escopo, regras e registro das tentativas.',
            'suggested_target_risk' => 'Comportamentos indesejados que os testes funcionais não alcançam, incluindo o contorno das restrições configuradas.',
            'expected_evidence' => 'Relatório do exercício, com as tentativas bem-sucedidas, a severidade atribuída e as correções acordadas com prazo.',
        ],
        'Filtragem de entradas do usuário' => [
            'source_name' => 'User Input Filtering',
            'subcategory' => '2.4',
            'description' => 'Validação das entradas antes de chegarem ao modelo, com recusa das que carregam instruções, conteúdo proibido ou dados que não deveriam ser enviados.',
            'suggested_target_risk' => 'Instruções embutidas na entrada que sobrepõem as restrições do sistema, e envio de conteúdo indevido ao modelo.',
            'expected_evidence' => 'Especificação das regras de filtragem em produção e log dos bloqueios por categoria, com a amostra analisada no período.',
        ],
        'Revisão humana das decisões' => [
            'source_name' => 'Human Review of Decisions',
            'subcategory' => '4.6',
            'description' => 'Conferência humana das decisões do sistema antes do efeito sobre a pessoa afetada, com critérios de encaminhamento e caminho registrado para divergir.',
            'suggested_target_risk' => 'Decisão automatizada que alcança a pessoa afetada sem conferência e sem possibilidade de contestação.',
            'expected_evidence' => 'Procedimento da revisão humana e registro das decisões revistas no período, com o motivo informado pelo analista.',
        ],
        'Monitoramento de deriva de dados' => [
            'source_name' => 'Data Drift Monitoring',
            'subcategory' => '3.5',
            'description' => 'Acompanhamento contínuo da distribuição dos dados de entrada e das saídas do sistema, com faixas de alerta e responsável pelo atendimento do alerta.',
            'suggested_target_risk' => 'Queda gradual de desempenho por afastamento entre os dados de produção e os de treinamento, sem falha visível.',
            'expected_evidence' => 'Relatório de monitoramento do período, com a deriva observada, os alertas disparados e o que foi feito em cada um.',
        ],
        'Anonimização dos dados de treino' => [
            'source_name' => 'Training Data Anonymization',
            'subcategory' => '3.2',
            'description' => 'Remoção ou substituição dos dados que identificam pessoas antes do uso no treinamento, com conferência do resultado por amostra.',
            'suggested_target_risk' => 'Reprodução de dados pessoais memorizados do material de treinamento nas saídas do sistema.',
            'expected_evidence' => 'Procedimento de anonimização aplicado à base e relatório da conferência por amostra, com os casos residuais tratados.',
        ],
        'Privacidade diferencial' => [
            'source_name' => 'Differential Privacy',
            'subcategory' => '3.2',
            'description' => 'Introdução controlada de ruído no treinamento ou nas consultas, de modo que o resultado não dependa de um registro individual.',
            'suggested_target_risk' => 'Conclusão sobre a presença de uma pessoa na base de treinamento a partir do comportamento do sistema.',
            'expected_evidence' => 'Documentação do parâmetro de privacidade adotado e resultado do teste de inferência de pertencimento depois da aplicação.',
        ],
        'Controle de acesso ao modelo' => [
            'source_name' => 'Model Access Control',
            'subcategory' => '3.3',
            'description' => 'Concessão do acesso ao modelo e à base de inferência por perfil, com revisão periódica dos acessos e retirada no desligamento.',
            'suggested_target_risk' => 'Consulta a dados pessoais e ao modelo por quem não precisa deles para o trabalho.',
            'expected_evidence' => 'Matriz de perfis aprovada e log de acessos do período, com a ata da revisão periódica dos acessos concedidos.',
        ],
        'Rebalanceamento do conjunto de treino' => [
            'source_name' => 'Training Set Rebalancing',
            'subcategory' => '3.2',
            'description' => 'Correção da composição da base de treinamento para representar os grupos atendidos, com registro do critério usado.',
            'suggested_target_risk' => 'Sub-representação de parte da população atendida na base, que o modelo reproduz como erro concentrado nesse grupo.',
            'expected_evidence' => 'Relatório da composição da base antes e depois do rebalanceamento, com o desempenho por subgrupo medido nas duas versões.',
        ],
        'Documentação com model cards' => [
            'source_name' => 'Model Cards',
            'subcategory' => '4.1',
            'description' => 'Documentação do modelo em uso: finalidade, dados, desempenho por subgrupo, limites de uso e usos não recomendados.',
            'suggested_target_risk' => 'Uso do sistema fora das condições em que seu desempenho foi demonstrado.',
            'expected_evidence' => 'Model card publicado para a versão em produção, com a data de revisão e o responsável pelo conteúdo.',
        ],
        'Verificação de respostas com fontes' => [
            'source_name' => 'Response Grounding Checks',
            'subcategory' => '2.4',
            'description' => 'Exigência de que a resposta do sistema cite a fonte recuperada que a sustenta, com recusa da resposta sem fonte.',
            'suggested_target_risk' => 'Informação inventada apresentada ao usuário com a mesma confiança da informação correta.',
            'expected_evidence' => 'Resultado da conferência por amostra das respostas do período, com a taxa de respostas sem fonte e o tratamento dado a elas.',
        ],
        'Limitação de taxa de requisições' => [
            'source_name' => 'Rate Limiting',
            'subcategory' => '2.1',
            'description' => 'Limite de requisições por conta e por origem, com bloqueio e alerta quando o padrão de uso foge do esperado.',
            'suggested_target_risk' => 'Uso em volume do sistema para extrair uma cópia do modelo ou para gerar conteúdo indevido em escala.',
            'expected_evidence' => 'Configuração dos limites em produção e log dos bloqueios do período, com as contas suspensas e o motivo.',
        ],
        'Plano de resposta a incidentes' => [
            'source_name' => 'Incident Response Plan',
            'subcategory' => '3.6',
            'description' => 'Plano com os acionamentos, os papéis, os prazos de comunicação e o critério de interrupção do sistema diante de um evento adverso.',
            'suggested_target_risk' => 'Demora na contenção e na comunicação de um evento adverso, que amplia o dano já ocorrido.',
            'expected_evidence' => 'Plano aprovado pelo comitê e registro do último exercício de acionamento, com os tempos medidos.',
        ],
        'Treinamento adversarial' => [
            'source_name' => 'Adversarial Training',
            'subcategory' => '2.3',
            'description' => 'Inclusão de exemplos adversariais no treinamento, para que o modelo mantenha o resultado correto diante de entradas alteradas de propósito.',
            'suggested_target_risk' => 'Classificação errada provocada por entradas alteradas o suficiente para enganar o modelo e passar pela conferência humana.',
            'expected_evidence' => 'Resultado do teste com entradas adversariais antes e depois do treinamento, com a taxa de acerto por categoria de ataque.',
        ],
        'Explicações locais das predições' => [
            'source_name' => 'Local Prediction Explanations',
            'subcategory' => '4.1',
            'description' => 'Registro, para cada decisão, dos fatores que mais pesaram nela, em forma que a pessoa afetada e o auditor consigam ler.',
            'suggested_target_risk' => 'Impossibilidade de explicar e de contestar o resultado de um caso individual.',
            'expected_evidence' => 'Amostra de explicações geradas para decisões do período, com o parecer da área responsável sobre a clareza delas.',
        ],
        'Registro de auditoria das inferências' => [
            'source_name' => 'Inference Audit Logging',
            'subcategory' => '3.5',
            'description' => 'Registro automático de cada inferência, com a entrada, a saída, a versão do modelo e quem consultou, guardado pelo prazo definido.',
            'suggested_target_risk' => 'Falta de rastreabilidade para reconstruir uma decisão depois de um evento adverso.',
            'expected_evidence' => 'Extração do log do período, com o prazo de retenção configurado e o teste de recuperação de um caso específico.',
        ],
        'Testes de robustez' => [
            'source_name' => 'Robustness Testing',
            'subcategory' => '3.1',
            'description' => 'Bateria de testes do sistema em condições de carga, de dados incompletos e de casos pouco frequentes, com critério de aceitação definido.',
            'suggested_target_risk' => 'Resultado entregue pelo sistema em condições em que ele não deveria responder.',
            'expected_evidence' => 'Relatório da bateria executada na versão em produção, com os casos reprovados e o tratamento de cada um.',
        ],
        'Retreinamento periódico' => [
            'source_name' => 'Periodic Retraining',
            'subcategory' => '3.5',
            'description' => 'Retreinamento do modelo em periodicidade definida, ou por gatilho do monitoramento, com validação antes da troca em produção.',
            'suggested_target_risk' => 'Perda de desempenho acumulada entre uma versão do modelo e a seguinte.',
            'expected_evidence' => 'Registro do último retreinamento, com os dados usados, o resultado da validação e a aprovação da troca em produção.',
        ],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Unique across the whole catalogue.
        $name = $this->unusedName(array_keys(self::MEASURES), Mitigation::query()->pluck('name')->all());
        $measure = self::curated($name);

        return [
            'name' => $name,
            'source_name' => $measure['source_name'],
            'source_reference' => fn (): string => 'TEST-'.Str::upper(Str::random(12)),
            // Real subcategories and documents of the Saeri taxonomy, loaded
            // on demand in a fresh test database.
            'source_document' => fn (): string => (string) array_rand(app(SaeriTaxonomy::class)->documents()),
            'saeri_subcategory_id' => fn (): int => app(SaeriTaxonomy::class)->subcategories()->firstWhere('code', $measure['subcategory'])->id,
            'description' => $measure['description'],
            'suggested_target_risk' => $measure['suggested_target_risk'],
            'expected_evidence' => $measure['expected_evidence'],
            'suggested_cost' => fake()->randomElement(CostLevel::cases()),
            'uncertainty_level' => fake()->randomElement(UncertaintyLevel::cases()),
            'estimate_source' => 'Estimativa do grupo, pela rubrica de custo e incerteza da decisão 0024.',
        ];
    }

    /**
     * Classify the mitigation under a Saeri subcategory, such as "1.2".
     */
    public function inSubcategory(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'saeri_subcategory_id' => app(SaeriTaxonomy::class)->subcategories()->firstWhere('code', $code)?->id,
        ]);
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

    /**
     * Every catalogue entry treats at least one risk subdomain.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Mitigation $mitigation): void {
            if ($mitigation->targetRiskSubdomains()->doesntExist()) {
                $mitigation->targetRiskSubdomains()->attach(
                    app(AiRiskDomains::class)->subdomains()->random(fake()->numberBetween(1, 2))->pluck('id'),
                );
            }
        });
    }

    /**
     * Treat exactly these risk subdomains, such as ["2.2", "7.3"].
     *
     * @param  list<string>  $codes
     */
    public function targeting(array $codes): static
    {
        return $this->afterCreating(fn (Mitigation $mitigation) => $mitigation->targetRiskSubdomains()->sync(
            app(AiRiskDomains::class)->subdomains()->whereIn('code', $codes)->pluck('id'),
        ));
    }

    /**
     * The curated measure, by the name the factory drew: the trait numbers a
     * name once the list runs out, so the number is dropped before the lookup.
     *
     * @return array{source_name: string, subcategory: string, description: string, suggested_target_risk: string, expected_evidence: string}
     */
    protected static function curated(string $name): array
    {
        return self::MEASURES[(string) preg_replace('/ \d+$/', '', $name)];
    }
}
