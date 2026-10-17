# Protocolo do painel de especialistas (OE6)

Procedimento para a avaliação do Framework RME por um painel Delphi de especialistas, conforme a decisão [0027](decisions/0027-painel-de-especialistas.md). As escolhas de método (rodadas, escala, valor crítico, critérios de sucesso) estão na decisão; este documento descreve como executá-las.

**Situação:** o painel não foi executado neste trabalho, por limitação de tempo. O protocolo e o banco de itens ficam prontos para execução futura.

## 1. Objetivo

O painel atende a dois objetivos específicos:

- **OE3:** validar a definição operacional de evento adverso (decisão 0026), pelos sete itens DEF do banco.
- **OE6:** avaliar a validade de conteúdo e a utilidade percebida do artefato, pelos 21 itens do banco.

O banco de itens está em [`database/data/expert-panel/items.csv`](../database/data/expert-panel/items.csv). Os intervalos de revisão da decisão 0017 são avaliados à parte, por coleta de valores (Seção 6.3).

## 2. Especialistas

### 2.1 Perfis

| Perfil | Exemplos de atuação |
|---|---|
| Governança e conformidade | Gestão de riscos, auditoria interna, compliance, ISO/IEC 42001 |
| Segurança da informação | Gestão de incidentes, SOC, segurança de sistemas |
| Desenvolvimento ou operação de IA | Ciência de dados, engenharia de aprendizado de máquina, MLOps |
| Jurídico e regulatório | Proteção de dados, regulação de IA, EU AI Act |
| Pesquisa acadêmica | Governança, ética ou segurança de IA |

### 2.2 Critérios

- **Inclusão:** ao menos três anos de experiência profissional no perfil, ou produção acadêmica na área (publicação, orientação ou projeto de pesquisa).
- **Exclusão:** membros do grupo e quem participou da construção do artefato.
- **Composição:** ao menos três perfis representados, nenhum com mais da metade do painel.

### 2.3 Tamanho

A meta é de pelo menos oito respondentes válidos na segunda rodada, idealmente entre dez e doze. Como há desistências entre rodadas, convidar cerca de metade a mais: para dez válidos, quinze convites. O tamanho importa para o resultado: com até sete especialistas, cada item exige unanimidade; com menos de cinco, nenhum item pode ser mantido (decisão 0027, item 4).

## 3. Recrutamento

### 3.1 Lista de candidatos

Planilha à parte, **fora do repositório**, com nome, contato, perfil, critério de inclusão atendido, data do convite e situação (convidado, aceitou, recusou, respondeu à primeira rodada, respondeu à segunda). Cada especialista que aceitar recebe um código (E01, E02 e assim por diante), o único identificador usado nas respostas.

### 3.2 Convite

Modelo, a ajustar com os dados da execução:

> Prezado(a) [nome],
>
> Somos estudantes do curso de [curso] da PUCPR e desenvolvemos, na disciplina de Pesquisa Aplicada, o Framework RME (Risco, Mitigação e Evidência), voltado ao acompanhamento de riscos de sistemas de IA, das mitigações adotadas e das evidências de sua eficácia.
>
> Gostaríamos de convidá-lo(a) a participar de um painel de especialistas que avaliará o framework. A participação é voluntária e anônima e consiste em duas rodadas de um formulário eletrônico, de cerca de [tempo] minutos cada, com intervalo de aproximadamente uma semana. Antes da primeira rodada, enviaremos uma síntese do framework e um vídeo curto do protótipo.
>
> Se aceitar, responda a esta mensagem até [data]. O termo de consentimento acompanha o primeiro formulário.
>
> Atenciosamente,
> [grupo e orientador]

### 3.3 Termo de consentimento

Primeira página do formulário da primeira rodada, com aceite obrigatório. Deve informar: o objetivo da pesquisa; o que se pede ao participante (duas rodadas e o tempo estimado); que a participação é voluntária e pode ser interrompida a qualquer momento, sem prejuízo; que as respostas são identificadas apenas por código e relatadas de forma agregada, e que comentários citados no trabalho não identificam o autor; quem tem acesso aos dados e por quanto tempo serão guardados; e os contatos do grupo e do orientador.

Antes da execução, consultar o orientador sobre a necessidade de registro no sistema de comitês de ética, à luz da Resolução CNS nº 510/2016 (decisão 0027, item 12).

## 4. Material de apoio

Enviado antes da primeira rodada e disponível nas duas:

- **Síntese do framework**, em uma página: o problema (os três elos), os quatro componentes e o ciclo de uso.
- **Glossário:** vínculo, evidência, verificação, reavaliação, evento adverso, quase-incidente, subdomínio de risco, faixa de risco do EU AI Act.
- **Vídeo do protótipo**, de três a cinco minutos, de preferência com um dos incidentes reconstruídos no OE5: cadastro do sistema e dos riscos, vínculos com mitigações do catálogo, evidências e verificação, registro do evento adverso, reversão e reavaliação, relatório de rastreabilidade.

O material descreve o framework, mas não argumenta a favor dele: o painel avalia o artefato, e o material não deve antecipar a resposta.

## 5. Primeira rodada (refinamento)

### 5.1 Formulário

1. Termo de consentimento (Seção 3.3).
2. Código do especialista e perfil (Seção 2.1), sem nome.
3. Para cada item do banco, na ordem do `items.csv`, agrupados por bloco:
    - "A redação deste item é clara?", com as opções **clara** e **precisa de ajuste**;
    - campo aberto opcional: "Sugestão de redação ou comentário".
4. **Intervalos de revisão:** "Em quantos dias uma mitigação verificada deveria ser revista, na ausência de eventos, em um sistema de...", com uma resposta numérica para cada faixa (alto risco, risco limitado, risco mínimo) e um campo aberto opcional de justificativa. Os valores da decisão 0017 **não** são mostrados nesta rodada, para não ancorar as respostas.
5. Campo aberto: "Há algum elemento essencial ao acompanhamento de riscos de sistemas de IA que o framework não contempla?"

Nesta rodada não se pergunta se o item é essencial: a primeira rodada cuida da redação, e a avaliação fica para a segunda.

### 5.2 Consolidação

Regras da decisão 0027, item 8:

- A redação de um item pode ser ajustada para clareza, sem mudar o conteúdo avaliado. Um item que mais da metade dos respondentes marcar como "precisa de ajuste" deve ser revisto.
- Um item só sai ou entra do banco nesta consolidação, com o motivo registrado. Sugestões de elementos novos (pergunta 5) entram como item novo apenas se o grupo concordar que fazem parte do artefato como ele é; caso contrário, são registradas como achado.
- Toda alteração vai para o registro de alterações (Seção 9), com a redação anterior. O `items.csv` passa a conter a redação submetida à segunda rodada.
- Para os intervalos, calcular a mediana e o intervalo interquartil de cada faixa, que serão o retorno da segunda rodada.

O resumo enviado com a segunda rodada informa o número de respondentes, os itens alterados (com o motivo) e a mediana e o intervalo interquartil dos intervalos sugeridos, sem identificar ninguém.

## 6. Segunda rodada (avaliação)

### 6.1 Formulário

1. Resumo da primeira rodada (Seção 5.2).
2. Código do especialista.
3. Para cada item: "Considerando o propósito do Framework RME, este elemento é:", com as opções **Essencial**, **Útil, mas não essencial** e **Desnecessário**. Todas as perguntas são obrigatórias.
4. **Intervalos de revisão:** a mesma pergunta da primeira rodada, agora com a mediana e o intervalo interquartil da primeira como referência.
5. Campo aberto opcional para comentários gerais.

Só participa quem respondeu à primeira rodada.

### 6.2 Formato da exportação

O cálculo (Seção 7) lê o CSV exportado do formulário, desde que:

- a coluna do código se chame `Respondente`, com valores únicos;
- cada pergunta de item comece pelo identificador entre colchetes, seguido do texto, por exemplo `[DEF-C1] A ocorrência é observada...`;
- as respostas dos itens sejam exatamente `Essencial`, `Útil, mas não essencial` ou `Desnecessário`;
- as perguntas de intervalo comecem por `[INT-ALTO]`, `[INT-LIMITADO]` e `[INT-MINIMO]`, com números inteiros de dias.

Colunas sem colchetes, como o carimbo de data e hora do formulário e os comentários, são ignoradas. Antes de versionar, remover do CSV qualquer coluna que identifique o respondente além do código, inclusive comentários que mencionem nome ou organização.

O arquivo anonimizado é versionado como `database/data/expert-panel/round-2-responses.csv`.

### 6.3 Intervalos de revisão

Não são avaliados pelo CVR (decisão 0027, item 11). O resultado é a mediana e o intervalo interquartil da segunda rodada, por faixa. O valor da decisão 0017 (90, 180 e 365 dias) é considerado sustentado se estiver dentro do intervalo interquartil da faixa.

## 7. Análise

O comando do projeto calcula, a partir do CSV da segunda rodada e do `items.csv`:

- para cada item, nₑ, CVR e se o item é mantido;
- o valor crítico para o N do painel, pelo método de Ayre e Scally (2014);
- se os critérios do OE3 (os sete itens DEF mantidos) e do OE6 (todos os itens mantidos) foram atendidos;
- o CVI, média do CVR, por bloco e geral, sobre todos os itens e sobre os mantidos;
- a mediana e o intervalo interquartil dos intervalos, por faixa, e se o valor da decisão 0017 está dentro dele.

Item não mantido é relatado como está, sem nova rodada (decisão 0027, item 10).

## 8. Relato

O texto do trabalho deve trazer:

- número de convites, aceites e respondentes em cada rodada, com as desistências;
- composição do painel por perfil;
- escala, regra do valor crítico e valor crítico aplicado ao N final;
- CVR de cada item, com a indicação dos mantidos, e o CVI por bloco e geral;
- resultado do OE3 e do OE6 segundo os critérios da decisão 0027;
- mediana e intervalo interquartil dos intervalos nas duas rodadas;
- alterações feitas entre as rodadas (Seção 9);
- síntese dos comentários abertos e dos elementos sugeridos.

## 9. Registro de alterações da primeira rodada

| Item | Redação anterior | Redação nova | Motivo |
|---|---|---|---|
| | | | |

## 10. Cronograma de referência

| Semana | Atividades |
|---|---|
| 1 | Consulta ao orientador sobre ética; lista de candidatos; convites |
| 2 | Termo e formulários; material de apoio |
| 3 | Primeira rodada aberta (sete dias) |
| 4 | Consolidação; segunda rodada aberta (sete dias) |
| 5 | Exportação, cálculo e relato |

## Referências

As da decisão [0027](decisions/0027-painel-de-especialistas.md).
