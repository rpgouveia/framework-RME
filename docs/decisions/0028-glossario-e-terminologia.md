# 0028. Glossário e terminologia

- **Situação:** Aceita; implementada
- **Registrada em:** 2026-10-21

## Contexto

Os termos do framework estão definidos de forma dispersa nas decisões, e parte deles não tem definição alguma. Sete enums carregam um `@todo` para conferir os valores contra a definição do framework: `AiSystemCategory`, `CostLevel`, `EvidenceType`, `LifecyclePhase`, `LinkStatus`, `SystemSourceType` e `UncertaintyLevel`. A revisão desses enums e das decisões encontrou seis problemas:

- **Custo:** a rubrica da 0024 define o custo sugerido do catálogo, mas não diz se vale para o custo estimado do vínculo e para o custo observado na evidência, que usam a mesma escala.
- **Incerteza:** o mesmo enum serve à mitigação, onde a rubrica da 0024 o define, e ao risco, onde não há critério.
- **Categoria:** o termo designa a faixa de risco do sistema no EU AI Act e a categoria de Saeri das mitigações. A interface usa a mesma chave de tradução ("Category") nos dois casos.
- **Progresso:** os seis valores não têm definição. Só "cancelado" tem efeito no sistema. "Em monitoramento" não tem comportamento próprio e repete o que a verificação e o ciclo de revisão já representam, sobre dimensões que a 0013 separou de propósito. O termo ainda colide com o "vínculo monitorável" da 0017 e com a fase de monitoramento do ciclo de vida.
- **Ciclo de vida, tipos de evidência e origem do sistema:** as listas não têm definição por valor. A lista de fases é atribuída à ISO/IEC 22989, mas não coincide com as fases da norma (seção 6.2). "Documento" e "relatório" se sobrepõem, assim como "terceiros" e "código aberto".
- **Painel:** a interface chama de "Painel" a tela inicial, e o trabalho usa "painel" para o painel de especialistas (0027).

## Decisão

1. **Glossário único:** `docs/glossary.md` é a fonte da terminologia do projeto, para o artigo, a interface, a documentação e o protocolo do painel de especialistas. Cada termo traz o nome no código e a fonte da definição. Quando o código diverge, a entrada registra a diferença até que ela seja resolvida.
2. **Custo:** é o nível qualitativo do esforço de implementação, não só financeiro. A rubrica da 0024 vale para os três custos, com a mesma regra do nível mais alto entre as quatro dimensões, e muda só a referência: organização típica e primeira implantação no custo sugerido; esta organização e este vínculo, antes de implementar, no estimado; esforço efetivamente empregado, avaliado ao registrar a evidência, no observado. Assim os três ficam comparáveis.
3. **Incerteza:** é o grau de confiança numa avaliação, e incerteza alta significa confiança baixa. O nível resulta da matriz da 0024, que combina a evidência com a concordância entre as fontes. No risco, a avaliação é a de que o risco existe no sistema e de como se materializaria. A evidência é limitada quando há só uma hipótese da equipe, média quando há incidentes em sistemas semelhantes ou norma que aponte o risco, e robusta quando há ocorrência, teste ou auditoria no próprio sistema. Na mitigação, segue valendo a definição da 0024.
4. **Faixa de risco:** é o termo para a classificação do sistema no EU AI Act. "Categoria" fica reservado à taxonomia de Saeri. A coluna `category` não muda; muda o rótulo exibido.
5. **Progresso:**
    - o valor "em monitoramento" é removido; os registros com esse valor passam a "implementado". O acompanhamento da mitigação em operação é representado pela verificação e pelo ciclo de revisão;
    - os cinco valores restantes têm critério de entrada: planejado (implementação não iniciada), em andamento (iniciada e não concluída), implementado (concluída, mitigação em operação), suspenso (interrompida temporariamente, com intenção de retomar) e cancelado (vínculo encerrado, que só volta a planejado).
6. **Fases do ciclo de vida:** a lista segue as oito fases da ISO/IEC 22989 (6.2): concepção; projeto e desenvolvimento; verificação e validação; implantação; operação e monitoramento; validação contínua; reavaliação do sistema; retirada. A coleta e a preparação de dados fazem parte de projeto e desenvolvimento (6.2.3), como no mapeamento da norma para o ciclo de vida da OCDE (Anexo A). A validação contínua só se aplica a sistemas com aprendizado contínuo (6.2.7). A retirada abrange o descomissionamento e a substituição (6.2.9). Os valores atuais têm mapeamento direto:

    | Valor atual                                | Valor novo                    |
    | ------------------------------------------ | ----------------------------- |
    | `inception`                                | `inception`                   |
    | `design`, `data_collection`, `development` | `design_and_development`      |
    | `validation`                               | `verification_and_validation` |
    | `deployment`                               | `deployment`                  |
    | `monitoring`                               | `operation_and_monitoring`    |
    | `decommissioning`                          | `retirement`                  |
    | (novo)                                     | `continuous_validation`       |
    | (novo)                                     | `re_evaluation`               |

    O rótulo de `re_evaluation` é "Reavaliação do sistema", para não se confundir com a reavaliação do vínculo (0020).

7. **Tipos de evidência:** os seis valores ficam, definidos pelo que comprovam. Documento: política, procedimento ou especificação que estabelece como a mitigação deve funcionar. Relatório: resultado de análise ou avaliação. Log de auditoria: registro automático do sistema. Resultado de teste: saída de teste executado. Certificação: atestado de terceiro. Ata de reunião: registro de decisão em reunião.
8. **Origem do sistema:** os quatro valores ficam, definidos pela relação da organização com o sistema, com base nos papéis da ISO/IEC 22989 (5.19) e nas formas de obter componentes (6.2.3). Interno: a organização é a produtora. Terceiros: a organização é cliente de um provedor. Código aberto: a organização incorpora ou adapta software ou modelo aberto. Híbrido: componente próprio combinado com componente de terceiros ou aberto; a norma admite que uma organização acumule papéis.
9. **Tela de acompanhamento e painel de especialistas:** a tela inicial do protótipo é a "tela de acompanhamento", e o rótulo da interface passa de "Painel" para "Acompanhamento". O termo "painel" sem complemento não é usado; nos textos, sempre "painel de especialistas".
10. **Alinhamento com a ISO/IEC 22989:** o glossário adota a definição de sistema de IA da norma (3.1.4). A verificação do vínculo segue a definição de verificação da norma (3.5.17): confirmação, por evidência objetiva, de que requisitos especificados foram atendidos. O risco do framework é o lado negativo da definição da norma (3.5.11), o dano. A incerteza do framework, sobre a avaliação feita pela organização, não se confunde com a incerteza que integra a definição de risco da norma.
11. **Enums:** com as definições do glossário, os sete `@todo` saem do código, e o comentário de cada enum remete ao glossário.

## Consequências

- Os itens 1 a 4 e 7 a 10 não mudam regra nem dado: valem já para o artigo e a documentação.
- Os itens 5 e 6 mudam enum, dados, seeds, traduções e testes. Devem ser combinados com a atualização da base de dados do OE5, para que os dados sejam regerados uma única vez. No protótipo, os dados vêm dos seeds, e a troca não fere a regra de somente inclusão (0007, 0008 e 0020). Numa base em uso, histórico e reavaliações conservariam os valores antigos como legado, e só os registros novos usariam a lista nova.
- A rubrica de custo passa a orientar quem preenche o vínculo e a evidência no protótipo; cabe como texto de apoio nos formulários, na tarefa de UX (#95).
- A definição de "suspenso" levanta uma questão de comportamento: hoje, um vínculo suspenso e verificado continua verificado e na revisão periódica, embora a mitigação esteja parada. Vai para `PENDENTES.md`, para reavaliação após o MVP.
- A colisão entre a fase "reavaliação do sistema" e a reavaliação do vínculo fica resolvida pelo rótulo, sem renomear a entidade `Reassessment`.

## Implementação

- **Item 5 (progresso):** o caso `LinkStatus::Monitoring` foi removido; os cinco restantes mantêm valores e ordem. Saíram também o valor do espelho TypeScript (`resources/js/types/models.ts`), a cor do selo em `resources/js/lib/labels.ts` e a tradução "Em monitoramento". O valor não tinha regra, escopo, validação ou consulta própria, então nenhum comportamento mudou; a tela de acompanhamento passa a mostrar cinco barras de progresso.
- **Item 6 (fases):** `LifecyclePhase` tem as oito fases da ISO/IEC 22989 (6.2), na ordem da norma, com os valores da tabela do item 6 e os rótulos do glossário, inclusive "Reavaliação do sistema". O enum serve a `risks.lifecycle_phase`, `links.lifecycle_phase`, `reassessments.cause_phase` e às mudanças gravadas em `reassessments.changes`. As colunas são texto, então não houve migration; no protótipo, `migrate:fresh --seed` regera os dados com os valores novos. Nos seeds, só as referências aos casos mudaram, pelo mapeamento do item 6: a causa em projeto passa a projeto e desenvolvimento, e o ajuste que leva o vínculo de um sistema na faixa inaceitável à descontinuação passa a levá-lo à fase de retirada, também no texto de apoio da tela de reavaliação. A restrição da validação contínua a sistemas com aprendizado contínuo fica só na definição do glossário, sem regra.
- **Item 11 (enums):** os sete `@todo` saíram. O comentário de cada enum diz o que ele representa e remete a `docs/glossary.md` e à decisão que o define. O de `CostLevel` registra que o custo observado fica na evidência (0018) e que a rubrica da 0024 vale para os três custos; o de `UncertaintyLevel`, que a incerteza é o grau de confiança numa avaliação, de risco ou das estimativas de uma mitigação; o de `LifecyclePhase` cita a seção 6.2 da norma.
- **Item 4 (faixa de risco):** as três telas de sistemas de IA (formulário, detalhe e lista) usam a chave própria `Risk tier`, traduzida como "Faixa de risco"; o detalhe da mitigação continua com `Category`, "Categoria", para a categoria de Saeri. O texto de seleção do formulário passou a "Selecione a faixa de risco (EU AI Act)", e o nome do campo `category` nas mensagens de validação passou a "faixa de risco".
- **Item 9 (acompanhamento):** `Dashboard` passou a "Acompanhamento", e os textos que chamavam a tela inicial de "painel" passaram a "acompanhamento": "Abrir o acompanhamento" e o estado vazio da tela. A mensagem de recusa do comando `expert-panel:cvr` passou a "Respostas do painel de especialistas inválidas".
- **Traduções fixadas:** `Dashboard` difere da Laravel Lang e já era fixada; `tests/Fixtures/pinned-translations.php` passou a "Acompanhamento", conforme a 0023.
- **Documentação:** o tutorial usa os cinco valores de progresso, "faixa" para o sistema, a fase "Retirada", a fase "Projeto e desenvolvimento" no exemplo de risco e "Acompanhamento" para a tela inicial, inclusive no título da seção. O glossário perdeu as quatro observações "Situação no código" resolvidas. O README perdeu `monitoring` da lista de progresso e a nota sobre os `@todo`, e ganhou a rubrica da 0024 no parágrafo sobre custo, o parágrafo sobre o glossário em "Domain decisions" e as linhas de acompanhamento, faixa de risco e painel de especialistas na tabela de vocabulário. A questão do vínculo suspenso e verificado foi para `PENDENTES.md`.
- **Testes:** os testes passaram a usar os valores novos. O vínculo em operação do teste da tela de acompanhamento passou de `monitoring` a `implemented`, e a contagem por progresso tem cinco valores. O teste de rótulo de um caso cujo nome coincide com um arquivo de tradução usava `LifecyclePhase::Validation`, que deixou de existir; passou a usar um enum de apoio declarado no próprio teste, com o caso `Validation` e uma tradução registrada só para o teste. O teste de localização confere "Acompanhamento".

## Referências

- ISO; IEC. ISO/IEC 22989:2022: Information technology. Artificial intelligence. Artificial intelligence concepts and terminology. Genebra: ISO, 2022. Itens 3.1.4, 3.1.9, 3.5.11, 3.5.17, 5.19, 6 e Anexo A.
- MASTRANDREA, M. D. et al. Guidance Note for Lead Authors of the IPCC Fifth Assessment Report on Consistent Treatment of Uncertainties. IPCC, 2010.
- NIST. Guide for Conducting Risk Assessments. SP 800-30 Rev. 1, 2012.
- UNIÃO EUROPEIA. Regulamento (UE) 2024/1689 (EU AI Act).
- Decisões 0005, 0011, 0012, 0013, 0016, 0017, 0018, 0019, 0020, 0021, 0024, 0026 e 0027.
