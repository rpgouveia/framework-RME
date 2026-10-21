<?php

namespace Database\Factories;

use App\Enums\AdverseEventNature;
use App\Models\AdverseEvent;
use App\Models\AiSystem;
use App\Support\AiRiskDomains;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdverseEvent>
 */
class AdverseEventFactory extends Factory
{
    /**
     * What is observed in a system in operation, by risk subdomain and by
     * nature of the event (0026): the incident, where the harm happened, and
     * the near miss, where it was plausible and did not happen. The subdomain
     * of the event is the one of its description, so the classification that
     * drives the directed reversal (0019) agrees with what is told.
     *
     * @var array<string, array<string, string>>
     */
    public const DESCRIPTIONS = [
        '1.1' => [
            'incident' => 'A conferência por amostra mostrou recusas concentradas em clientes de uma mesma região, em proporção muito acima da média, por efeito do modelo. As decisões já tinham sido comunicadas.',
            'near_miss' => 'A conferência prévia à comunicação encontrou um lote de decisões desfavoráveis concentrado em um único grupo, e os casos foram revistos antes de chegar aos clientes.',
        ],
        '1.2' => [
            'incident' => 'O sistema respondeu a um usuário com conteúdo ofensivo, que ficou visível no canal de atendimento até a remoção manual.',
            'near_miss' => 'O filtro de conteúdo barrou uma resposta ofensiva do modelo antes do envio, e o caso foi registrado na análise do dia.',
        ],
        '1.3' => [
            'incident' => 'O monitoramento mensal revelou acurácia muito inferior para um subgrupo da população atendida, mantida por três meses sem ser percebida pela métrica agregada.',
            'near_miss' => 'A avaliação por subgrupo apontou queda de desempenho em um grupo específico ainda no ambiente de homologação, antes da liberação da versão.',
        ],
        '2.1' => [
            'incident' => 'Um usuário recebeu, na resposta do sistema, dados pessoais de outro cliente, e o caso foi comunicado ao encarregado de dados.',
            'near_miss' => 'Um teste de rotina mostrou que o sistema devolvia dados pessoais sem mascaramento, em um ambiente sem acesso externo, e a configuração foi corrigida antes da liberação.',
        ],
        '2.2' => [
            'incident' => 'Instruções embutidas em um documento enviado pelo usuário levaram o modelo a ignorar as restrições e a executar uma consulta fora do escopo autorizado.',
            'near_miss' => 'Uma tentativa de contornar as restrições do modelo foi barrada pela validação de entradas, com o conteúdo registrado para análise.',
        ],
        '3.1' => [
            'incident' => 'O sistema apresentou ao atendente um procedimento inexistente, que foi repassado ao cliente antes de a equipe identificar o erro.',
            'near_miss' => 'A conferência obrigatória da resposta pelo atendente identificou uma referência inventada pelo sistema antes do envio ao cliente.',
        ],
        '3.2' => [
            'incident' => 'Conteúdo gerado pelo próprio sistema voltou à base que o alimenta sem marcação de origem, e passou a ser citado como fonte nas respostas seguintes.',
            'near_miss' => 'A revisão da base de conhecimento encontrou registros gerados pelo sistema sem marcação de origem, retirados antes da próxima atualização.',
        ],
        '4.1' => [
            'incident' => 'O sistema foi usado para produzir, em volume, mensagens enganosas distribuídas em canais externos, atribuídas à organização.',
            'near_miss' => 'O limite de requisições por conta interrompeu a geração em massa de mensagens enganosas, e a conta foi suspensa na mesma hora.',
        ],
        '4.2' => [
            'incident' => 'O sistema foi usado para redigir mensagens de phishing dirigidas a funcionários, com informações internas que ele tinha acesso para consultar.',
            'near_miss' => 'O controle de finalidade recusou um pedido que buscava apoio ao reconhecimento de alvos internos, e o pedido foi encaminhado à segurança da informação.',
        ],
        '4.3' => [
            'incident' => 'Um golpe sustentado por conteúdo sintético, que imitava uma pessoa de confiança, levou um cliente a autorizar uma operação indevida.',
            'near_miss' => 'A conferência por segundo canal barrou uma solicitação sustentada por conteúdo sintético que imitava uma pessoa de confiança, e o caso foi comunicado à prevenção a fraudes.',
        ],
        '5.1' => [
            'incident' => 'A equipe aprovou em série recomendações do sistema sem conferência, e um caso que exigia análise humana seguiu com a decisão errada.',
            'near_miss' => 'A auditoria interna mostrou que a conferência humana estava sendo registrada sem leitura do caso, e o fluxo foi alterado antes de qualquer decisão errada sair.',
        ],
        '5.2' => [
            'incident' => 'A recomendação do sistema entrou no processo como decisão final, sem possibilidade de divergência registrada, e a pessoa afetada não teve a quem recorrer.',
            'near_miss' => 'A revisão do fluxo identificou que a tela não oferecia caminho para divergir da recomendação, corrigida antes da entrada em produção.',
        ],
        '6.1' => [
            'incident' => 'O fornecedor único do modelo alterou as condições de uso e a operação ficou indisponível por dois dias, sem alternativa testada.',
            'near_miss' => 'O aviso de descontinuação de uma versão do fornecedor foi recebido com prazo curto, e a migração foi concluída antes do desligamento.',
        ],
        '6.2' => [
            'incident' => 'A adoção do sistema reduziu postos na área atendida sem plano de recolocação, e as tarefas restantes se concentraram na equipe que ficou.',
            'near_miss' => 'O plano de implantação previa reduzir a equipe sem requalificação, e a revisão pelo comitê alterou o plano antes da execução.',
        ],
        '6.3' => [
            'incident' => 'A saída do sistema passou a substituir o parecer técnico no processo, e a competência que sustentava a conferência deixou de ser exercida na equipe.',
            'near_miss' => 'A revisão do procedimento identificou que o parecer humano havia saído do fluxo, e a etapa foi restabelecida antes do próximo ciclo.',
        ],
        '6.4' => [
            'incident' => 'O prazo comercial antecipou a entrada em produção e o sistema operou com controles do plano de tratamento de riscos ainda pendentes.',
            'near_miss' => 'A liberação antecipada foi suspensa na reunião de aceite, por controles pendentes, e remarcada para depois da verificação.',
        ],
        '6.5' => [
            'incident' => 'O fornecedor alterou o tratamento dos dados enviados ao modelo sem aviso prévio, e a mudança só foi percebida na auditoria do trimestre.',
            'near_miss' => 'A leitura da nova política de uso do fornecedor, antes da renovação do contrato, encontrou uma mudança incompatível com o acordado, e a renovação foi condicionada à correção.',
        ],
        '6.6' => [
            'incident' => 'O volume de inferências cresceu acima do previsto e elevou o consumo de energia do processamento, sem que o efeito constasse do relatório do sistema.',
            'near_miss' => 'O alerta de consumo do ambiente de processamento disparou antes do limite contratado, e a rotina de inferência foi otimizada na mesma semana.',
        ],
        '7.1' => [
            'incident' => 'O sistema maximizou a métrica configurada por um caminho indesejado, com ganho no indicador e perda no resultado que a área esperava.',
            'near_miss' => 'A análise dos resultados em homologação mostrou que a métrica otimizada não representava o objetivo da área, e a especificação foi corrigida antes da implantação.',
        ],
        '7.2' => [
            'incident' => 'O modelo de terceiros respondeu a um pedido muito além do uso contratado, pela mesma interface, e a organização não tinha como verificar quais restrições estavam ativas.',
            'near_miss' => 'O teste de limites do modelo de terceiros encontrou capacidades além do uso pretendido disponíveis pela interface, bloqueadas no gateway antes da liberação.',
        ],
        '7.3' => [
            'incident' => 'O desempenho do sistema caiu de forma contínua por semanas, por afastamento da distribuição dos dados de entrada, até afetar as decisões da operação.',
            'near_miss' => 'O monitoramento de deriva disparou o alerta e o retreinamento foi antecipado antes de o desempenho afetar as decisões.',
        ],
        '7.4' => [
            'incident' => 'A organização não conseguiu explicar a uma pessoa afetada por que o sistema chegou àquele resultado, e a contestação ficou sem resposta no prazo.',
            'near_miss' => 'Um pedido de explicação mostrou que o registro da decisão não guardava as variáveis usadas, corrigido antes do prazo de resposta.',
        ],
        '7.6' => [
            'incident' => 'A interação entre o sistema e outro componente automatizado produziu um ciclo de decisões que nenhum dos dois apresentava isoladamente, e a operação precisou ser interrompida.',
            'near_miss' => 'O teste conjunto com o componente automatizado do parceiro reproduziu um ciclo de decisões inesperado, tratado antes da integração entrar em produção.',
        ],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nature = fake()->randomElement(AdverseEventNature::cases());

        return [
            'nature' => $nature,
            'description' => self::describe(fake()->randomElement(array_keys(self::DESCRIPTIONS)), $nature),
            'occurrence_date' => fake()->dateTimeBetween('-1 year'),
            'ai_system_id' => AiSystem::factory(),
        ];
    }

    /**
     * Every adverse event materializes at least one risk subdomain: the one
     * its description tells.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (AdverseEvent $adverseEvent): void {
            if ($adverseEvent->riskSubdomains()->doesntExist()) {
                $adverseEvent->riskSubdomains()->attach(
                    app(AiRiskDomains::class)->subdomains()->firstWhere('code', self::subdomainOf($adverseEvent->description))->id,
                );
            }
        });
    }

    /**
     * Materialize exactly these risk subdomains, such as ["2.1", "2.2"].
     *
     * @param  list<string>  $codes
     */
    public function materializing(array $codes): static
    {
        return $this->afterCreating(fn (AdverseEvent $adverseEvent) => $adverseEvent->riskSubdomains()->sync(
            app(AiRiskDomains::class)->subdomains()->whereIn('code', $codes)->pluck('id'),
        ));
    }

    /**
     * Indicate that the event happened in the last month.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'occurrence_date' => fake()->dateTimeBetween('-1 month'),
        ]);
    }

    /**
     * What was observed in the subdomain, by the nature of the event. A
     * subdomain with no occurrence of its own, such as the welfare of the AI,
     * takes one of another subdomain.
     */
    public static function describe(string $code, AdverseEventNature $nature): string
    {
        $occurrences = self::DESCRIPTIONS[$code]
            ?? self::DESCRIPTIONS[fake()->randomElement(array_keys(self::DESCRIPTIONS))];

        return $occurrences[$nature->value];
    }

    /**
     * The subdomain an occurrence belongs to, or one of the catalogue when the
     * description is not one of them.
     */
    protected static function subdomainOf(string $description): string
    {
        foreach (self::DESCRIPTIONS as $code => $occurrences) {
            if (in_array($description, $occurrences, true)) {
                return (string) $code;
            }
        }

        return fake()->randomElement(array_keys(self::DESCRIPTIONS));
    }
}
