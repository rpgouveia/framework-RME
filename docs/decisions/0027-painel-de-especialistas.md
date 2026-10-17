# 0027. Painel de especialistas (OE6): protocolo e banco de itens

- **Situação:** Aceita; protocolo, banco de itens e cálculo implementados; painel não executado neste trabalho
- **Registrada em:** 2026-10-17

## Contexto

O OE6 prevê avaliar o artefato quanto à validade de conteúdo e à utilidade percebida por um painel de especialistas, com cálculo do Content Validity Ratio (CVR). O mesmo painel atende ao OE3, cujo critério de sucesso exige que a definição operacional de evento adverso seja validada pelos especialistas (decisão 0026).

O planejamento do OE6, mantido pelo grupo fora do repositório, registrou nove decisões pendentes e cinco cuidados levantados na leitura de Moon e Ahn (2025), o precedente citado no PBL3:

- o CVR foi proposto por Lawshe (1975), que o artigo não cita;
- o artigo usa escala Likert de cinco pontos sem dizer quais pontos contam como "essencial";
- o artigo não informa o valor crítico adotado, e, pelas tabelas usuais, painéis de até sete especialistas exigem concordância unânime em cada item;
- o que caracteriza um Delphi são as rodadas sucessivas, e uma rodada única com CVR é um painel de validade de conteúdo;
- o CVR aprova ou reprova afirmações, mas não escolhe valores numéricos, como os intervalos de revisão da decisão 0017.

A execução exige ao menos quatro semanas, a maior parte dependente da agenda de especialistas externos. O PBL8 (refinamento) é entregue em 2026-10-22, e o PBL9 encerra o trabalho. O grupo optou por concentrar o tempo restante na qualidade do artefato e comunicou ao professor, em 2026-10-17, a intenção de declarar a não execução do painel como limitação.

## Decisão

1. **Situação no trabalho:** o painel não é executado. O protocolo e o banco de itens ficam prontos e documentados para execução futura, e o texto do trabalho declara que dois critérios de sucesso não são verificados: a validação da definição de evento adverso pelos especialistas (OE3) e a validade de conteúdo do artefato (OE6). A definição continua implementada e aplicada no protótipo; o que falta é a validação externa.
2. **Rodadas:** duas, o que mantém a denominação de painel Delphi. A primeira refina a redação dos itens e coleta os intervalos de revisão sugeridos; a segunda avalia os itens pelo CVR e coleta de novo os intervalos, com o resumo da primeira como retorno. Só participa da segunda rodada quem respondeu à primeira. Se uma execução futura tiver uma rodada só, deve se chamar painel de especialistas com CVR.
3. **Escala:** a original de Lawshe (1975), com três categorias: essencial; útil, mas não essencial; desnecessário. Conta como concordância apenas "essencial", o que dispensa declarar um corte. Todos os itens são de resposta obrigatória, de modo que o número de especialistas (N) é o mesmo em todos os itens.
4. **CVR e valor crítico:** CVR = (nₑ − N/2) / (N/2), em que nₑ é o número de especialistas que marcaram "essencial". O valor crítico segue o método de Ayre e Scally (2014): o menor nₑ cuja probabilidade de ser igualado ou superado por acaso, numa binomial com N tentativas e p = 0,5, é inferior a 0,05 (teste unilateral). O item é mantido se nₑ for igual ou superior a esse mínimo. O valor é calculado pelo comando do projeto, e não transcrito de tabela, para evitar erro de cópia. Com N de 5 a 7, o mínimo é a unanimidade; com N = 8, são 7 de 8 (CVR crítico 0,75); com N = 11, 9 de 11 (0,636). Com N menor que 5, nenhum item pode ser mantido, e o cálculo é recusado.
5. **Respondentes:** meta de pelo menos oito respondentes válidos na segunda rodada, idealmente entre dez e doze, convidando cerca de metade a mais. Respondente válido é quem completa a segunda rodada; as desistências entre as rodadas são relatadas.
6. **Perfis e inclusão:** cinco perfis complementares (governança e conformidade; segurança da informação; desenvolvimento ou operação de sistemas de IA; jurídico e regulatório; pesquisa acadêmica em governança ou ética de IA). Inclusão: ao menos três anos de experiência profissional no perfil ou produção acadêmica na área. Exclusão: membros do grupo e quem participou da construção do artefato. A composição final é relatada por perfil, e a meta é de ao menos três perfis representados, nenhum com mais da metade do painel.
7. **Banco de itens:** 21 itens em quatro blocos, versionados em `database/data/expert-panel/items.csv`: modelo de vínculo (C1, cinco itens), catálogo (C2, três), protocolo de monitoramento (C3, dez, dos quais os sete da decisão 0026 são obrigatórios) e protótipo (C4, três). Cada item tem identificador, bloco, objetivo atendido, enunciado e fundamento.
8. **Consolidação da primeira rodada:** a redação pode ser ajustada para clareza, sem mudar o conteúdo avaliado, e deve ser revista quando mais da metade dos respondentes indicar que precisa de ajuste; um item só sai ou entra do banco nessa consolidação, com o motivo registrado. As alterações ficam no registro de alterações do protocolo, com a redação anterior, e o `items.csv` passa a conter a redação submetida à segunda rodada.
9. **Critérios de sucesso:** o OE3 é atendido se os sete itens da definição forem mantidos; o OE6, se todos os itens forem mantidos, como exige o PBL3 ao pedir que os elementos do modelo alcancem o valor crítico. Como complemento, relata-se o índice de validade de conteúdo (CVI) de Lawshe, média do CVR, por bloco e geral, tanto sobre todos os itens quanto sobre os mantidos.
10. **Item não mantido:** é relatado como está e tratado como achado para o refinamento, sem nova rodada para aprová-lo. É o mesmo princípio da decisão 0025, item 5.
11. **Intervalos de revisão:** não são avaliados pelo CVR. Cada especialista informa, em dias, o intervalo que considera adequado para as faixas de alto risco, risco limitado e risco mínimo, nas duas rodadas. O resultado é a mediana e o intervalo interquartil da segunda rodada. O valor da decisão 0017 é considerado sustentado pelo painel se estiver dentro do intervalo interquartil da faixa. Se não estiver, o resultado é relatado como achado; uma mudança dos intervalos exige nova decisão.
12. **Ética e dados:** termo de consentimento livre e esclarecido e anonimato em qualquer caso. Antes de uma execução, consultar o orientador sobre a necessidade de registro no sistema de comitês de ética, à luz da Resolução CNS nº 510/2016. As respostas são identificadas por código (E01, E02 e assim por diante); nome e contato ficam em lista à parte, fora do repositório, e só o CSV anonimizado é versionado.
13. **Instrumento:** formulário eletrônico, exportado em CSV no formato descrito no protocolo, com cada pergunta iniciada pelo identificador do item entre colchetes.
14. **Cálculo reprodutível:** um comando do projeto, no padrão do `mitigations:agreement`, calcula o CVR, o valor crítico, os itens mantidos, os critérios do OE3 e do OE6, o CVI e o resumo dos intervalos a partir do CSV da segunda rodada.

## Consequências

- O texto do trabalho (PBL8 e PBL9) declara a limitação nos termos do item 1, e o OE3 e o OE6 aparecem como critérios não verificados, e não como atingidos.
- Os slides 6 e 9 podem manter a denominação Delphi, porque o protocolo prevê duas rodadas, mas devem deixar claro que o painel foi planejado e não executado.
- A citação do CVR passa a ser Lawshe (1975), com Ayre e Scally (2014) para o valor crítico e Moon e Ahn (2025) como precedente de aplicação em governança e viés de IA.
- A validação dos intervalos pendente na decisão 0017 continua pendente, agora com procedimento definido.
- O planejamento do painel sai de `PENDENTES.md`.

## Implementação

- **Protocolo:** `docs/expert-panel-protocol.md`, com perfis, recrutamento, convite, termo de consentimento, material de apoio, estrutura das rodadas, formato da exportação, análise, relato e registro de alterações.
- **Banco de itens:** `database/data/expert-panel/items.csv`.
- **Item 14 (cálculo reprodutível):** o comando `php artisan expert-panel:cvr` lê o CSV da segunda rodada (`database/data/expert-panel/round-2-responses.csv`) e o banco de itens (`database/data/expert-panel/items.csv`) com a mesma leitura do catálogo (vírgula ou ponto e vírgula, com ou sem BOM, células com várias linhas), agora em `App\Support\CsvTable`, compartilhada com os comandos das decisões 0024 e 0025 sem mudar o comportamento deles. A leitura e a validação ficam em `App\Support\ExpertPanel`. Uma coluna é de item quando o cabeçalho começa pelo `id` entre colchetes, e de intervalo quando começa por `[INT-ALTO]`, `[INT-LIMITADO]` ou `[INT-MINIMO]`, ligadas às faixas de alto risco, risco limitado e risco mínimo; colunas sem colchetes são ignoradas. O cálculo é sempre estrito, porque as respostas são obrigatórias (item 3): coluna de item ou de intervalo ausente ou repetida, identificador desconhecido entre colchetes, respondente vazio ou repetido, resposta fora das três categorias (aceitando diferença de maiúsculas e espaços nas pontas) ou vazia, intervalo que não seja inteiro positivo e painel com menos de 5 ou mais de 60 respondentes são erros, relatados de uma vez, com a linha do CSV, e nada é gravado. O banco de itens também é validado: `id` não vazio e único, bloco preenchido e objetivos `OE3` ou `OE6`.
- **Itens 4 e 9 (CVR, valor crítico e critérios):** o cálculo fica em `App\Support\ContentValidity`, sem dependências do framework. O valor crítico é o menor nₑ com 20 · Σ C(N, k), k = nₑ..N, menor que 2^N, calculado só com inteiros (triângulo de Pascal), de modo que não depende de arredondamento; a busca parte de nₑ = N e para no primeiro valor que não atende, e, com o limite de 60 respondentes, a soma não passa de cerca de 1,8·10¹⁸, dentro dos inteiros de 64 bits. O item é mantido se nₑ for igual ou superior ao crítico, comparando inteiros. O CVI é a média do CVR, por bloco e geral, sobre todos os itens e sobre os mantidos (nulo quando nenhum é mantido). O OE3 é atendido se todos os itens com `OE3` forem mantidos; o OE6, se todos os itens forem mantidos.
- **Item 11 (intervalos):** por faixa, a mediana e o primeiro e o terceiro quartis por interpolação linear entre as posições vizinhas, com a mesma função dos percentis da decisão 0025. O valor da faixa vem do arquivo do protocolo de monitoramento (`MonitoringProtocol::reviewIntervalDays()`), e é considerado sustentado se estiver entre os quartis, limites incluídos.
- **Arquivo gerado:** o resultado sai em tabelas no terminal e em `database/data/expert-panel/oe6-cvr.json`, sem data de execução, com valores arredondados a seis casas e a mesma serialização do `oe1-agreement.json`, de modo que o mesmo CSV gera sempre o mesmo arquivo. Itens e blocos seguem a ordem do `items.csv`. O arquivo fica fora do formatador do projeto. Como o painel não foi executado, nem o CSV de respostas nem o arquivo gerado estão versionados.
- **Testes:** o valor crítico para N de 5, 7, 8, 9, 10, 11, 12, 15, 20 e 40, comparado com os valores de referência e, para todo N de 5 a 60, com um cálculo independente em ponto flutuante; um caso completo de três itens e oito respondentes (CVR 1, 0,75 e 0,5; crítico 7 de 8; CVI 0,75 e 0,875; OE3 atendido e OE6 não; um intervalo sustentado, outro sustentado no limite do quartil e outro não), com tolerância de 1e-6; as recusas, cada uma com a linha e sem gravar arquivo, inclusive várias de uma vez; a reprodutibilidade byte a byte; e o banco de itens versionado, com 21 itens de `id` único e os sete itens da definição (decisão 0026) como os únicos do OE3.

## Referências

- AYRE, C.; SCALLY, A. J. Critical values for Lawshe's content validity ratio: revisiting the original methods of calculation. Measurement and Evaluation in Counseling and Development, v. 47, n. 1, p. 79-86, 2014. DOI: 10.1177/0748175613513808.
- BRASIL. Conselho Nacional de Saúde. Resolução nº 510, de 7 de abril de 2016.
- LAWSHE, C. H. A quantitative approach to content validity. Personnel Psychology, v. 28, n. 4, p. 563-575, 1975.
- MOON, D.; AHN, S. Metrics and Algorithms for Identifying and Mitigating Bias in AI Design: A Counterfactual Fairness Approach. IEEE Access, v. 13, p. 59118-59129, 2025. DOI: 10.1109/ACCESS.2025.3556082.
- WILSON, F. R.; PAN, W.; SCHUMSKY, D. A. Recalculation of the critical values for Lawshe's content validity ratio. Measurement and Evaluation in Counseling and Development, v. 45, n. 3, p. 197-210, 2012. DOI: 10.1177/0748175612440286.
- Decisões 0017, 0025 e 0026; documento de planejamento do OE6.
