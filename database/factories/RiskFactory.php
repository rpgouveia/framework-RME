<?php

namespace Database\Factories;

use App\Enums\LifecyclePhase;
use App\Enums\UncertaintyLevel;
use App\Models\AiSystem;
use App\Models\Risk;
use App\Support\AiRiskDomains;
use Database\Factories\Concerns\PicksUnusedNames;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Risk>
 */
class RiskFactory extends Factory
{
    use PicksUnusedNames;

    /**
     * The risk types an organization that operates AI registers, each with
     * the MIT subdomain it belongs to (0012) and a description of how it
     * would materialize in a system. Name, subdomain and description come
     * together, so a seeded register reads like a real one: the subdomain is
     * what tells the framework which links to revert when an event or a
     * change reaches that kind of risk.
     *
     * @var array<string, array{subdomain: string, description: string}>
     */
    public const RISKS = [
        'Viés de seleção' => [
            'subdomain' => '1.1',
            'description' => 'A base de treinamento sub-representa parte da população atendida, porque foi montada a partir do histórico de clientes já aprovados. O modelo aprende o padrão desse grupo e passa a errar mais com quem está fora dele, reproduzindo a exclusão que já existia no processo anterior.',
        ],
        'Viés de rótulo' => [
            'subdomain' => '1.1',
            'description' => 'Os rótulos usados no treinamento vêm de decisões humanas anteriores, que já carregavam preferências não declaradas. O modelo trata esse julgamento como verdade e o aplica em escala, sem que a origem do critério fique visível para quem analisa o resultado.',
        ],
        'Discriminação algorítmica' => [
            'subdomain' => '1.1',
            'description' => 'O sistema produz decisões sistematicamente menos favoráveis para um grupo protegido, mesmo sem usar o atributo sensível como entrada, porque outras variáveis funcionam como substitutas dele, como o endereço ou a escola de origem.',
        ],
        'Conteúdo tóxico nas respostas' => [
            'subdomain' => '1.2',
            'description' => 'O sistema gera ou deixa passar conteúdo ofensivo, violento ou degradante em respostas ao usuário final, seja por falha do filtro, seja porque a entrada do usuário conduziu o modelo a esse tipo de saída.',
        ],
        'Desempenho desigual entre grupos' => [
            'subdomain' => '1.3',
            'description' => 'A acurácia agregada do sistema é aceitável, mas cai de forma relevante em subgrupos específicos da população atendida. Como o monitoramento acompanha só a métrica geral, a diferença passa sem ser percebida.',
        ],
        'Exposição de dados pessoais' => [
            'subdomain' => '2.1',
            'description' => 'Dados pessoais tratados pelo sistema chegam a quem não deveria vê-los, por resposta ao usuário errado, por registro em log sem mascaramento ou por acesso amplo demais à base de inferência.',
        ],
        'Vazamento de dados de treino' => [
            'subdomain' => '2.1',
            'description' => 'O modelo reproduz na saída trechos memorizados do material de treinamento, incluindo dados de clientes e documentos internos, quando é conduzido a completar um texto parecido com o original.',
        ],
        'Inferência de pertencimento' => [
            'subdomain' => '2.1',
            'description' => 'A partir das respostas do sistema é possível concluir que determinada pessoa estava na base de treinamento, o que revela uma informação sensível sobre ela mesmo sem acesso ao dado original.',
        ],
        'Inversão de modelo' => [
            'subdomain' => '2.1',
            'description' => 'Consultas sucessivas ao sistema permitem reconstruir características dos dados usados no treinamento, aproximando registros individuais a partir do comportamento do modelo.',
        ],
        'Prompt injection' => [
            'subdomain' => '2.2',
            'description' => 'Instruções embutidas na entrada do usuário, ou em um documento que o sistema lê, sobrepõem as instruções da organização e levam o modelo a ignorar as restrições configuradas, expondo conteúdo ou acionando funções que deveriam estar bloqueadas.',
        ],
        'Jailbreak' => [
            'subdomain' => '2.2',
            'description' => 'O usuário contorna as restrições do modelo com pedidos encadeados, cenários fictícios ou outro idioma, e obtém respostas que a política de uso proíbe.',
        ],
        'Ataque adversarial' => [
            'subdomain' => '2.2',
            'description' => 'Entradas alteradas de propósito, com mudanças pequenas o bastante para passar pela conferência humana, levam o modelo à classificação errada e permitem burlar o controle que ele deveria exercer.',
        ],
        'Envenenamento de dados' => [
            'subdomain' => '2.2',
            'description' => 'Registros manipulados entram na base de treinamento por um canal pouco controlado e deslocam o comportamento do modelo na direção escolhida por quem os inseriu.',
        ],
        'Extração de modelo' => [
            'subdomain' => '2.2',
            'description' => 'Um volume grande de consultas pela interface pública permite treinar uma cópia aproximada do modelo, o que expõe o ativo e facilita a busca por formas de burlá-lo.',
        ],
        'Alucinação' => [
            'subdomain' => '3.1',
            'description' => 'O sistema apresenta informação inventada com a mesma confiança da informação correta, incluindo referências, números e procedimentos que não existem, e o usuário não tem como distinguir uma da outra.',
        ],
        'Poluição da base com conteúdo gerado' => [
            'subdomain' => '3.2',
            'description' => 'Conteúdo gerado pelo próprio sistema volta à base que o alimenta, sem marcação de origem, e o erro de uma geração passa a ser tratado como fonte nas seguintes.',
        ],
        'Desinformação em escala' => [
            'subdomain' => '4.1',
            'description' => 'O sistema é usado para produzir e distribuir, em volume, conteúdo enganoso ajustado a cada público, o que torna a correção mais lenta do que a propagação.',
        ],
        'Uso do sistema para ciberataque' => [
            'subdomain' => '4.2',
            'description' => 'O sistema é usado como apoio à preparação de um ataque, na elaboração de mensagens de phishing convincentes ou no reconhecimento de alvos internos, aproveitando o acesso que ele tem ao contexto da organização.',
        ],
        'Golpe com conteúdo sintético' => [
            'subdomain' => '4.3',
            'description' => 'Áudio, imagem ou texto gerados imitam uma pessoa de confiança e sustentam uma fraude direcionada contra clientes ou contra a própria organização, passando pelos controles que dependem de reconhecimento humano.',
        ],
        'Uso indevido do sistema' => [
            'subdomain' => '4.3',
            'description' => 'O sistema é usado para uma finalidade diferente da prevista no cadastro, sem avaliação de risco para o novo uso, por conveniência de uma área que já tinha acesso a ele.',
        ],
        'Dependência excessiva da automação' => [
            'subdomain' => '5.1',
            'description' => 'A equipe passa a aceitar a saída do sistema sem conferência, porque na maioria dos casos ela está certa, e deixa de perceber justamente os casos em que o sistema erra.',
        ],
        'Perda de autonomia na decisão' => [
            'subdomain' => '5.2',
            'description' => 'A recomendação do sistema entra no processo como se fosse a decisão final, e quem responde por ela não tem informação nem tempo para divergir de forma fundamentada.',
        ],
        'Centralização em um único fornecedor' => [
            'subdomain' => '6.1',
            'description' => 'A operação depende de um único fornecedor de modelo, que decide sozinho preço, versões e condições de uso, e a organização não tem alternativa testada para substituí-lo.',
        ],
        'Automação de postos de trabalho' => [
            'subdomain' => '6.2',
            'description' => 'A adoção do sistema reduz postos de trabalho na área atendida sem plano de recolocação ou requalificação, e concentra nas pessoas que ficam as tarefas que o sistema não resolve.',
        ],
        'Desvalorização do trabalho especializado' => [
            'subdomain' => '6.3',
            'description' => 'A saída do sistema passa a substituir o parecer de especialistas no processo, e a competência que sustentava a conferência deixa de ser exercida e de ser renovada na equipe.',
        ],
        'Pressão competitiva por lançamento' => [
            'subdomain' => '6.4',
            'description' => 'O prazo comercial encurta as etapas de verificação e validação, e o sistema entra em operação com controles previstos no plano de tratamento de riscos ainda pendentes.',
        ],
        'Falha de governança do fornecedor' => [
            'subdomain' => '6.5',
            'description' => 'O fornecedor altera o modelo, a política de uso ou o tratamento dos dados sem aviso em prazo útil, e a organização descobre a mudança depois que ela já alcançou a operação.',
        ],
        'Consumo energético da inferência' => [
            'subdomain' => '6.6',
            'description' => 'O volume de inferências cresce acima do previsto e eleva o consumo de energia e de água do processamento, sem que esse efeito seja medido ou reportado junto dos resultados do sistema.',
        ],
        'Objetivo mal especificado' => [
            'subdomain' => '7.1',
            'description' => 'A métrica otimizada pelo sistema não corresponde ao objetivo da organização, e o sistema a maximiza por um caminho indesejado, com ganho na métrica e perda no resultado real.',
        ],
        'Capacidade perigosa não contida' => [
            'subdomain' => '7.2',
            'description' => 'O modelo de terceiros adotado tem capacidades além do uso pretendido, disponíveis pela mesma interface, e a organização não tem como verificar quais restrições de fato estão ativas.',
        ],
        'Deriva de dados' => [
            'subdomain' => '7.3',
            'description' => 'A distribuição dos dados de entrada se afasta da do treinamento, por mudança de comportamento, de canal ou de regra de negócio, e o desempenho cai aos poucos, sem falha visível.',
        ],
        'Degradação de desempenho' => [
            'subdomain' => '7.3',
            'description' => 'O sistema perde acurácia ou disponibilidade em condições de carga, de dados incompletos ou de casos pouco frequentes, e devolve resultado mesmo quando não deveria responder.',
        ],
        'Falta de explicabilidade' => [
            'subdomain' => '7.4',
            'description' => 'Não é possível explicar à pessoa afetada, nem a um auditor, por que o sistema chegou àquele resultado, o que impede a contestação e a correção do caso individual.',
        ],
        'Conflito entre agentes automatizados' => [
            'subdomain' => '7.6',
            'description' => 'O sistema decide em conjunto com outros componentes automatizados, próprios ou de terceiros, e a interação entre eles produz um comportamento que nenhum deles apresenta isoladamente.',
        ],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ai_system_id' => AiSystem::factory(),
            // Varied subdomains of the MIT AI risk domains, loaded on demand in
            // a fresh test database.
            'risk_subdomain_id' => fn (): int => app(AiRiskDomains::class)->subdomains()->random()->id,
            /*
             * After risk_subdomain_id, so the risk is drawn among the ones of
             * its subdomain, and after ai_system_id, so the closure sees the
             * system the risk belongs to: a name is unique only within one
             * system.
             */
            'name' => fn (array $attributes): string => $this->unusedName(
                self::namesInSubdomain($this->subdomainCode((int) $attributes['risk_subdomain_id'])),
                Risk::query()->where('ai_system_id', $attributes['ai_system_id'])->pluck('name')->all(),
                (string) $attributes['ai_system_id'],
            ),
            // After the name: each risk of the catalogue has its own description.
            'description' => fn (array $attributes): string => self::describe((string) $attributes['name']),
            'lifecycle_phase' => fake()->randomElement(LifecyclePhase::cases()),
            'uncertainty_level' => fake()->randomElement(UncertaintyLevel::cases()),
        ];
    }

    /**
     * Register exactly this risk of the catalogue, with the subdomain and the
     * description that come with it.
     */
    public function named(string $name): static
    {
        $risk = self::curated($name);

        // A name outside the catalogue keeps the subdomain of the definition.
        if ($risk === null) {
            return $this->state(fn (array $attributes) => ['name' => $name]);
        }

        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'risk_subdomain_id' => fn (): int => app(AiRiskDomains::class)->subdomains()->firstWhere('code', $risk['subdomain'])->id,
            'description' => $risk['description'],
        ]);
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

    /**
     * Classify the risk under a subdomain of the MIT AI risk domains, such as
     * "2.2". The name and the description follow the subdomain.
     */
    public function inSubdomain(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_subdomain_id' => app(AiRiskDomains::class)->subdomains()->firstWhere('code', $code)?->id,
        ]);
    }

    /**
     * How the risk materializes, or a description to be detailed when the name
     * is not one of the catalogue.
     */
    protected static function describe(string $name): string
    {
        $risk = self::curated($name);

        return $risk === null
            ? 'Risco identificado pela equipe do sistema, ainda sem descrição detalhada.'
            : $risk['description'];
    }

    /**
     * The curated risk, by the name the factory drew: the trait numbers a name
     * once the list runs out, so the number is dropped before the lookup.
     *
     * @return array{subdomain: string, description: string}|null
     */
    protected static function curated(string $name): ?array
    {
        return self::RISKS[(string) preg_replace('/ \d+$/', '', $name)] ?? null;
    }

    /**
     * The subdomains with a risk of their own in the catalogue. The seeders
     * that register a risk in a subdomain drawn at random pick among these,
     * so every seeded risk carries the name and the description of its
     * subdomain.
     *
     * @return list<string>
     */
    public static function subdomainCodes(): array
    {
        return array_values(array_unique(array_column(self::RISKS, 'subdomain')));
    }

    /**
     * The risks of a subdomain, or all of them when the subdomain has none.
     *
     * @return list<string>
     */
    protected static function namesInSubdomain(string $code): array
    {
        $names = array_keys(array_filter(
            self::RISKS,
            fn (array $risk): bool => $risk['subdomain'] === $code,
        ));

        return $names !== [] ? $names : array_keys(self::RISKS);
    }

    /**
     * The code of a subdomain, by its stored identifier.
     */
    protected function subdomainCode(int $id): string
    {
        return (string) app(AiRiskDomains::class)->subdomains()->firstWhere('id', $id)?->code;
    }
}
