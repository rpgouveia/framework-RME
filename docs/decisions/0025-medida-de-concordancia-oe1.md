# 0025. Medida de concordância do OE1

- **Situação:** Aceita; implementada (cálculo e rascunho do guia)
- **Registrada em:** 2026-10-12

## Contexto

O critério de sucesso do OE1 exige um índice de concordância aceitável entre dois avaliadores independentes, sem fixar a medida nem o valor. A decisão 0024 definiu o objeto da medida: o mapeamento de cada mitigação do catálogo para os subdomínios de risco do MIT, anotado por dois membros de forma independente. A medida precisa ser escolhida antes de existirem dados, para que não seja escolhida pelo resultado.

O PBL3 diz seguir o protocolo de Arnold et al. (2024), do AGORA. Conferido o artigo, o AGORA testou a confiabilidade com 21 instrumentos codificados por dois pares independentes nos 77 códigos da taxonomia e, por se tratar de distribuições muito assimétricas, dados nominais e dois codificadores, relatou o g de Guilford (91,4) e o AC1 de Gwet (0,83, variando de 0,43 a 1 entre os códigos), e não o kappa de Cohen.

A situação do catálogo é parecida, mas o AC1 calculado código a código tem um limite: com 24 subdomínios e uma ou duas marcações por mitigação, quase todas as decisões são "não se aplica", e o AC1 fica alto mesmo quando os anotadores discordam no que importa. Exemplo: se cada anotador marca o subdomínio 3.1 em 5 das 40 mitigações, coincidindo em 2, a concordância simples é 0,85 e o AC1 é 0,81, enquanto a concordância positiva, que olha só para as marcações, é 0,40.

## Decisão

1. **Unidade de análise:** a mitigação. O valor atribuído por cada anotador é o conjunto de subdomínios (nível 2 da taxonomia do MIT) que ele marcou, de forma independente, antes da reunião de consenso.
2. **Medida principal:** alfa de Krippendorff com a distância MASI (Passonneau, 2006), que compara conjuntos, dá crédito parcial à sobreposição e desconta a concordância por acaso. Pesos da MASI: 1 para conjuntos iguais, 2/3 quando um contém o outro, 1/3 quando há interseção sem inclusão e 0 quando são disjuntos. A implementação de referência é a da biblioteca NLTK.
3. **Valor mínimo aceitável:** alfa de 0,667, mínimo indicado por Krippendorff para conclusões provisórias, com 0,800 como meta. O resultado é relatado com intervalo de confiança de 95%, por reamostragem das mitigações (10.000 reamostragens, semente fixa, método percentil).
4. **Medidas complementares:**
    - AC1 de Gwet e g de Guilford por subdomínio, com média e variação, calculados como no AGORA, para comparação. A média considera só os subdomínios marcados ao menos uma vez por algum anotador; os nunca marcados teriam concordância perfeita trivial.
    - Concordância positiva por subdomínio, como diagnóstico dos subdomínios que confundem os anotadores (Cicchetti; Feinstein, 1990).
    - Proporção de mitigações com conjuntos idênticos, como descrição.
5. **Se o mínimo não for atingido:** o resultado é relatado como está, sem repetir a anotação para melhorar o número. As divergências são analisadas pela concordância positiva, o guia de anotação é revisto nos subdomínios problemáticos e isso é registrado como achado. Uma segunda medição, se houver tempo, é feita pelos membros que ainda não viram aquelas mitigações e relatada separadamente, sem substituir a primeira.
6. **Guia de anotação:** antes da anotação, um guia curto com as definições dos subdomínios e a regra central: marcar apenas os subdomínios cujo risco a mitigação reduz diretamente.
7. **Calibração:** feita com mitigações elegíveis que não estão entre as 40 do catálogo; as mitigações medidas não são usadas no treino.
8. **Cálculo reprodutível:** um comando do projeto calcula todas as medidas a partir do `catalog.csv` versionado, com testes baseados em valores de referência conferidos contra a NLTK.

## Consequências

- O resultado do OE1 fica comparável ao do AGORA, como o PBL3 promete, sem depender de uma medida que se infla com códigos raros.
- O texto do OE1 no PBL3 deve passar a nomear a medida, o valor mínimo e o procedimento em caso de insucesso.

## Implementação

- **Item 8 (cálculo reprodutível):** o comando `php artisan mitigations:agreement` lê `database/data/catalog-source/catalog.csv` com a mesma leitura do importador da 0024 (vírgula ou ponto e vírgula, com ou sem BOM, células com várias linhas) e usa as colunas "Subdomínios: anotador A" e "Subdomínios: anotador B". Entram as linhas com as duas preenchidas, qualquer que seja a situação; as que têm só uma são relatadas. Com a opção `--strict`, usada no resultado oficial do OE1, a linha com só um anotador é erro e nada é gravado; o arquivo gerado registra se o cálculo foi estrito e quantas linhas ficaram de fora. Conjunto vazio ou código que não seja subdomínio de nível 2 da taxonomia do MIT é erro, relatado com a linha, e nada é gravado. O resultado sai em tabelas no terminal e em `database/data/catalog-source/oe1-agreement.json`, sem data de execução e com valores arredondados a seis casas, de modo que o mesmo CSV gera sempre o mesmo arquivo. O cálculo fica em `App\Support\AnnotationAgreement`, sem dependências novas.
- **Itens 2 e 3 (alfa e intervalo):** a MASI usa as frações exatas 2/3 e 1/3. A discordância esperada é a média da distância sobre os pares ordenados de posições distintas dos 2N valores agrupados. O intervalo reamostra as N mitigações com reposição 10.000 vezes, com `Random\Randomizer` sobre o motor Mt19937 e semente 2026, recalculando a discordância observada e a esperada a cada vez; os percentis 2,5 e 97,5 são obtidos por interpolação linear entre as posições vizinhas (o padrão do R e do NumPy). Reamostragens com discordância esperada nula não têm alfa: são descartadas e contadas no relatório. O relatório indica se o alfa atende ao mínimo (0,667) e se atinge a meta (0,800); se a discordância esperada da amostra completa for nula, o alfa fica indefinido e nenhum dos dois é atendido.
- **Item 4 (medidas complementares):** para cada um dos 24 subdomínios, tomado como decisão sim ou não, o relatório traz as contagens, a concordância simples, o g de Guilford, o AC1 de Gwet e a concordância positiva (indefinida quando ninguém marcou o subdomínio). As médias, mínimos e máximos de AC1 e g consideram só os subdomínios marcados ao menos uma vez, e o relatório informa quantos são. Inclui ainda a proporção de mitigações com conjuntos idênticos.
- **Testes:** valores de referência conferidos de forma independente, inclusive contra a NLTK, com tolerância de 1e-6: três mitigações (alfa 0,390244), concordância perfeita (alfa 1), doze mitigações (alfa 0,405429) e um subdomínio em 40 mitigações (po 0,85, g 0,70, AC1 0,808, concordância positiva 0,40). Os testes também conferem que o mesmo CSV gera o mesmo arquivo, inclusive o intervalo, e que conjunto vazio e código inválido são recusados com a linha.
- **Item 6 (guia de anotação):** rascunho em `docs/catalog-annotation-guide.md`, para revisão do grupo, com a regra central, a regra de independência (o anotador B não vê a coluna do A, e nenhuma ferramenta de IA sugere ou confere subdomínios), o formato dos códigos, a lista dos 24 subdomínios gerada a partir do arquivo de taxonomia do projeto e a indicação de que a calibração usa mitigações fora das 40. Um teste confere que a lista do guia acompanha o arquivo de taxonomia.

## Referências

- ARNOLD, Z. et al. Introducing the AI Governance and Regulatory Archive (AGORA). Proceedings of the AAAI/ACM Conference on AI, Ethics, and Society, v. 7, n. 1, p. 39-48, 2024. DOI: 10.1609/aies.v7i1.31615.
- CICCHETTI, D. V.; FEINSTEIN, A. R. High agreement but low kappa: II. Resolving the paradoxes. Journal of Clinical Epidemiology, v. 43, n. 6, p. 551-558, 1990. DOI: 10.1016/0895-4356(90)90159-M.
- GWET, K. L. Computing inter-rater reliability and its variance in the presence of high agreement. British Journal of Mathematical and Statistical Psychology, v. 61, n. 1, p. 29-48, 2008. DOI: 10.1348/000711006X126600.
- HOLLEY, J. W.; GUILFORD, J. P. A note on the G index of agreement. Educational and Psychological Measurement, v. 24, n. 4, p. 749-753, 1964. DOI: 10.1177/001316446402400402.
- KRIPPENDORFF, K. Content Analysis: An Introduction to Its Methodology. 2. ed. Thousand Oaks: Sage, 2004. ISBN 978-0-7619-1544-7.
- PASSONNEAU, R. Measuring agreement on set-valued items (MASI) for semantic and pragmatic annotation. In: Proceedings of the Fifth International Conference on Language Resources and Evaluation (LREC'06). Genoa: ELRA, 2006. Disponível em: https://aclanthology.org/L06-1392/.
- Decisão 0024.
