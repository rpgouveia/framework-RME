# Tutorial: usando o Framework RME

Este tutorial mostra, na ordem em que acontecem, como usar o Framework RME para gerenciar os riscos de um sistema de IA: do cadastro até a reavaliação depois de um incidente. Ele segue um caso de exemplo, o **Modelo de score de crédito**, e dá para refazer cada passo na aplicação.

Os nomes entre aspas são os que aparecem na tela. Os itens do menu lateral ainda estão em inglês (`AI systems`, `Risks`, `Adverse events`, `Mitigations`, `Links`, `Owners`); o restante da interface está em português.

## Antes de começar

1. Suba o ambiente e acesse http://localhost:8000 (veja o [README](../README.md#quick-start)).
2. Entre com `test@example.com` / `password` (ou `teste@teste.com` / `teste123`).
3. O banco já vem com um portfólio de exemplo (`php artisan migrate:fresh --seed` o recria do zero). Você pode seguir o tutorial criando registros novos ao lado dele.

> O catálogo de mitigações é **fictício** por enquanto, até o grupo definir o critério de curadoria. A tela de vínculos avisa isso.

## Os conceitos em dois minutos

- **Sistema de IA**: o que a organização opera. Tem uma **categoria** do EU AI Act (inaceitável, alto, limitado ou mínimo), que define a periodicidade das revisões.
- **Risco**: algo que pode dar errado em _um_ sistema, classificado por um **subdomínio de risco do MIT** (24 subdomínios, em 7 domínios).
- **Mitigação**: uma medida do catálogo (somente consulta), classificada pela taxonomia de Saeri et al.
- **Responsável**: o cargo que responde por uma mitigação.
- **Vínculo**: **a peça central**. Aplica uma mitigação a um risco, sob um responsável. O par risco e mitigação é único, e o vínculo nunca é excluído: termina por cancelamento.
- **Evidência**: o documento que comprova que a mitigação está em vigor.

Cada vínculo tem **duas dimensões de status**:

| Dimensão        | Valores                                                                       | Quem muda                                |
| --------------- | ----------------------------------------------------------------------------- | ---------------------------------------- |
| **Progresso**   | Planejado, Em andamento, Implementado, Em monitoramento, Suspenso, Cancelado  | Você, em "Registrar mudança"             |
| **Verificação** | **Declarada** (alguém afirma que está feito) ou **Verificada** (há evidência) | Você ao verificar; o sistema ao reverter |

A ideia do framework: **um vínculo só vale como verificado enquanto nada mudou**. Quando passa o prazo, ocorre um evento adverso ou o sistema muda, a verificação volta a "Declarada" e o vínculo entra em **Aguardando reavaliação**, até alguém concluir a reavaliação.

---

## Parte 1: montar a cadeia de um risco

### 1. Cadastre o responsável

Menu **Owners** → "Cadastrar responsável".

| Campo | Exemplo                 |
| ----- | ----------------------- |
| Cargo | Gestor de Risco de IA   |
| Área  | Segurança da Informação |

Cargo e área formam uma combinação única. Um responsável que já tem vínculos ou histórico **não pode ser excluído**, só **desativado** (e reativado depois). Responsáveis desativados não aparecem nas listas de escolha.

### 2. Cadastre o sistema de IA

Menu **AI systems** → "Cadastrar sistema de IA".

| Campo                | Exemplo                    |
| -------------------- | -------------------------- |
| Nome                 | Modelo de score de crédito |
| Domínio de aplicação | Serviços financeiros       |
| Origem               | Interno                    |
| Categoria            | Alto                       |
| Data de cadastro     | hoje                       |

A **categoria** importa de verdade: ela define o intervalo de revisão dos vínculos verificados do sistema (Alto, 90 dias; Limitado, 180; Mínimo, 365).

> **Categoria Inaceitável.** São práticas proibidas pelo EU AI Act: o sistema nunca é considerado em operação. Por isso seus vínculos **não podem ser verificados** e não têm revisão periódica; servem para planejar a descontinuação. Se você mudar um sistema para essa categoria, os vínculos já verificados dele são revertidos na hora.

### 3. Cadastre o risco

Na página do sistema, card **Riscos** → "Cadastrar risco".

| Campo                 | Exemplo                                                               |
| --------------------- | --------------------------------------------------------------------- |
| Nome                  | Viés de seleção                                                       |
| Descrição             | O modelo apresenta desempenho inferior para grupos sub-representados… |
| Domínio de risco      | 1. Discriminação e toxicidade                                         |
| Subdomínio de risco   | 1.1 Discriminação injusta e representação distorcida                  |
| Fase do ciclo de vida | Desenvolvimento                                                       |
| Nível de incerteza    | Média                                                                 |

Escolha o **subdomínio** com cuidado: é por ele que o framework sabe quais vínculos reverter quando um evento adverso ou uma mudança do sistema atinge aquele tipo de risco. O sistema do risco fica travado depois do primeiro vínculo.

### 4. Crie o vínculo

Menu **Links** → "Criar vínculo" (ou "Criar vínculo" no detalhe do risco).

1. **Risco**: escolha o risco que você acabou de cadastrar.
2. **Mitigação**: escolha do catálogo. Os filtros por categoria e subcategoria de Saeri et al. ajudam a achar. Ao escolher, a tela mostra a descrição, os subdomínios de risco que a medida trata, o custo sugerido, a incerteza e a fonte da estimativa.
3. **Responsável**: o cargo que responde pela mitigação.
4. **Fase do ciclo de vida** e **Custo estimado**: preencha conforme o seu contexto.

O vínculo nasce **Planejado** e **Declarado**, com a data de hoje e sem data de revisão. Você não escolhe esses valores: eles seguem a regra do framework.

> Se escolheu o par errado, não dá para trocar o risco nem a mitigação. O caminho é **Cancelar vínculo** e criar outro. A edição só altera responsável, fase e custo estimado.

### 5. Acompanhe o progresso

No detalhe do vínculo, card **Histórico de status** → "Registrar mudança".

| Campo                      | Observação                                |
| -------------------------- | ----------------------------------------- |
| Novo status                | Por exemplo, Em andamento → Implementado  |
| Motivo                     | Opcional, mas **obrigatório** ao cancelar |
| Registrado por             | O responsável que faz o registro          |
| Data da mudança            | Hoje, por padrão                          |
| Evento adverso relacionado | Opcional                                  |

Toda mudança fica no histórico, **sem possibilidade de edição ou exclusão**, com quem registrou e quando. Um vínculo cancelado só pode ser **reativado** (volta a Planejado), pelo botão "Reativar vínculo".

### 6. Registre a evidência

Card **Evidências** → "Registrar evidência".

| Campo                      | Exemplo                                                                                                     |
| -------------------------- | ----------------------------------------------------------------------------------------------------------- |
| Tipo                       | Relatório (as demais opções: documento, log de auditoria, resultado de teste, certificação, ata de reunião) |
| Descrição                  | Relatório de auditoria de equidade do 1º trimestre, assinado pelo comitê.                                   |
| Custo observado (opcional) | Médio                                                                                                       |

A data de registro é automática. Evidências também não são editáveis nem excluíveis. O **custo observado** deixa o custo estimado e o real comparáveis; a tela do vínculo mostra o mais recente.

### 7. Verifique o vínculo

Na caixa **Verificação dos vínculos** do detalhe do vínculo, clique em **Verificar**, escolha **Quem verifica** e confirme.

O botão fica desabilitado, com o motivo logo abaixo, enquanto a regra não for atendida:

| Mensagem                                                                                                      | O que fazer                                          |
| ------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------- |
| "Registre uma evidência antes de verificar o vínculo."                                                        | Registre a evidência (passo 6).                      |
| "Registre uma evidência nova: a última reversão foi em …" (ou "…para renovar: a última verificação foi em …") | A evidência antiga não vale: registre uma posterior. |
| "Um vínculo cancelado não pode ser verificado."                                                               | Reative o vínculo.                                   |
| "Um vínculo de sistema na faixa inaceitável não pode ser verificado…"                                         | Por desenho: o sistema nunca opera.                  |

Ao verificar, o vínculo passa a **Verificado**, ganha uma **próxima revisão** (a data de hoje mais o intervalo da categoria do sistema) e a verificação entra no histórico.

> **Regra de ouro:** só vale a evidência registrada **depois** da última mudança de verificação. Depois de qualquer reversão, evidência velha não serve.

### 8. Renove antes de vencer

Faltando pouco para a revisão, o vínculo aparece em **Próximas revisões** no Painel. Registre uma nova evidência e use **Renovar verificação**: a contagem recomeça de hoje. Se preferir desfazer a verificação por conta própria, use **Reverter verificação**, escolha **Quem reverte** e informe o **Motivo**.

---

## Parte 2: quando algo muda

É aqui que o framework se diferencia: **o vínculo não fica "verificado para sempre"**. Seis origens levam um vínculo verificado de volta a "Declarado" e a **Aguardando reavaliação**. Em todas, o histórico registra a origem; nas automáticas, sem autor.

| Origem                         | Como acontece                                                                                                               |
| ------------------------------ | --------------------------------------------------------------------------------------------------------------------------- |
| **Vencimento da revisão**      | Automático: a tarefa diária (07:00) reverte os vínculos cuja data de revisão já passou. "Vence hoje" ainda vale.            |
| **Evento adverso**             | Você registra o evento; reverte os vínculos do sistema cujo risco está nos subdomínios do evento.                           |
| **Nova versão do modelo**      | Você registra a mudança do sistema; reverte todos os vínculos verificados do sistema (ou só os dos subdomínios informados). |
| **Alteração na base de dados** | Idem.                                                                                                                       |
| **Reclassificação do sistema** | Automático ao editar o sistema para a categoria Inaceitável: reverte todos os vínculos verificados.                         |
| **Manual**                     | Você usa "Reverter verificação" no vínculo, com motivo.                                                                     |

### Registrando um evento adverso

Na página do sistema, **"Registrar evento adverso"** (ou menu **Adverse events**).

| Campo                               | O que informar                                                               |
| ----------------------------------- | ---------------------------------------------------------------------------- |
| Sistema de IA                       | Onde ocorreu                                                                 |
| Natureza                            | **Incidente** (o dano ocorreu) ou **Quase-incidente** (foi evitado)          |
| Subdomínios de risco materializados | Um ou mais; os do perfil de risco do sistema aparecem primeiro               |
| Descrição                           | O que aconteceu                                                              |
| Data da ocorrência / de detecção    | Nenhuma pode ser futura; a detecção não pode ser anterior à ocorrência       |
| Vínculo interceptador               | Só no quase-incidente: o vínculo que impediu o dano, que **não** é revertido |

Antes de gravar, um diálogo mostra **quais vínculos serão revertidos**. O evento é permanente: não pode ser editado nem excluído.

Se o subdomínio informado **não tem risco cadastrado** para o sistema, o Painel mostra o caso em **Riscos não mapeados**, com atalho para "Cadastrar risco".

### Registrando uma mudança do sistema

Na página do sistema, card **Mudanças do sistema** → **"Registrar mudança"**.

| Campo                                    | O que informar                                                       |
| ---------------------------------------- | -------------------------------------------------------------------- |
| Tipo de mudança                          | Nova versão do modelo ou Alteração na base de dados                  |
| Descrição                                | Ex.: Troca do modelo de classificação pela versão 2.0 do fornecedor. |
| Data da mudança                          | Hoje ou anterior                                                     |
| Subdomínios de risco afetados (opcional) | Limitam a reversão aos riscos realmente envolvidos                   |

O diálogo de confirmação diz **quantos vínculos serão revertidos**. Sem subdomínios, a regra é conservadora: **todos** os vínculos verificados do sistema voltam a declarados, porque nenhuma prova pode ser dada como ainda válida sem a análise do alcance da mudança. Informar os subdomínios afetados evita reverter além do necessário.

Num sistema da categoria Inaceitável, a mudança é registrada para documentação, mas não reverte nada.

---

## Parte 3: reavaliar

Os vínculos revertidos aparecem em **Links** → filtro **Aguardando reavaliação**, ordenados pelos que esperam há mais tempo, e no card **Verificação dos vínculos** do Painel, por origem. No detalhe do vínculo, o destaque mostra a causa (o evento, a mudança, o vencimento) com link, e o botão **Reavaliar**.

A reavaliação conclui **uma reversão** e escolhe um dos quatro desfechos:

| Desfecho       | Quando usar                              | O que acontece                                                                     |
| -------------- | ---------------------------------------- | ---------------------------------------------------------------------------------- |
| **Manter**     | A mitigação continua adequada            | Verifica de novo. Exige evidência registrada depois da reversão.                   |
| **Ajustar**    | A mitigação serve, mas precisa de ajuste | Altera responsável, custo estimado, fase ou progresso. Verificar junto é opcional. |
| **Substituir** | Outra mitigação resolve melhor           | Cancela o vínculo e abre o cadastro do novo, do mesmo risco, ligado ao anterior.   |
| **Encerrar**   | O vínculo não faz mais sentido           | Cancela o vínculo.                                                                 |

Preencha também **Quem reavalia** e a **Justificativa**. Quando a reversão veio de um **evento adverso** ou foi **manual**, a tela pede a análise de causa: **Apurada** (com a **Causa apurada** e a **Fase do ciclo de vida em que a causa se originou**) ou **Não apurada**. Nos demais casos (vencimento, reclassificação, mudança do sistema), a causa **não se aplica** e a tela não pergunta.

Em sistemas na categoria Inaceitável, **Manter** e **Substituir** ficam indisponíveis; o ajuste leva o vínculo à fase **Descomissionamento**, registrando o plano de descontinuação.

No detalhe do evento ou da mudança, o andamento aparece como "**2 de 4 reavaliados**".

---

## Parte 4: acompanhar e prestar contas

### O Painel

O **Painel** é o ponto de partida do dia a dia:

- totais do portfólio, **riscos sem vínculo** e **vínculos sem evidência**;
- **Próximas revisões** (nos próximos 14 dias) e **Eventos recentes** (nos últimos 30);
- **Verificação dos vínculos**: aguardando verificação, aguardando reavaliação (por origem), verificados e os que esperam há mais tempo;
- **Riscos não mapeados**: subdomínios de eventos e de mudanças sem risco cadastrado no sistema.

### O relatório de rastreabilidade

Na página de cada sistema, card **Relatório de rastreabilidade**:

| Botão                                | Conteúdo                                                                                      |
| ------------------------------------ | --------------------------------------------------------------------------------------------- |
| **Baixar JSON**                      | A cadeia completa: riscos, vínculos, evidências, histórico, reavaliações, eventos e mudanças. |
| **Baixar CSV**                       | Uma linha por vínculo.                                                                        |
| **Baixar eventos adversos (CSV)**    | Uma linha por evento, inclusive os que não reverteram vínculos.                               |
| **Baixar mudanças do sistema (CSV)** | Uma linha por mudança, inclusive as que não reverteram vínculos.                              |

Os arquivos trazem a versão do protocolo de monitoramento com que foram gerados. Os CSV abrem direto no Excel em português (UTF-8 com BOM e `;` como separador).

---

## Roteiro de 15 minutos com os dados de exemplo

Com o banco populado (`php artisan migrate:fresh --seed`):

1. **Painel**: veja os cartões de "Verificação dos vínculos" e o alerta "Riscos não mapeados".
2. **Links** → filtro **Aguardando reavaliação**: use os chips de origem para ver os vínculos revertidos por evento adverso, vencimento, reclassificação e mudança do sistema.
3. Abra um vínculo aguardando reavaliação: leia o destaque, a **linha do tempo** do histórico e clique em **Reavaliar**.
4. **AI systems** → **Triagem automática de chamados**: veja o card **Mudanças do sistema** e abra cada mudança para ver os vínculos revertidos e o andamento da reavaliação.
5. **AI systems** → **Pontuação de comportamento de cidadãos**: um sistema da categoria Inaceitável, com o alerta de descontinuação e vínculos reavaliados rumo ao Descomissionamento.
6. Baixe o **JSON** e os **CSV** de um desses sistemas e confira o que o relatório traz.
7. Para ver o encadeamento inteiro, escolha um sistema com vínculos verificados e **registre uma mudança do sistema sem informar subdomínios**: o diálogo mostra todos os vínculos que serão revertidos. Depois reavalie um deles com **Manter**: o botão de verificar só habilita depois de uma **evidência nova**.

## Dúvidas comuns

- **"Não consigo verificar o vínculo."** Leia a mensagem abaixo do botão: quase sempre falta uma evidência posterior à última reversão.
- **"Cadastrei o par errado."** Cancele o vínculo (o motivo é obrigatório) e crie outro. Um vínculo cancelado pode ser reativado.
- **"Quero editar um evento, uma evidência ou uma mudança."** Não é possível, por desenho: são registros de auditoria. Registre um novo, quando couber.
- **"O vínculo voltou a declarado sozinho."** Veja a origem no histórico: provavelmente o prazo de revisão passou ou um evento ou mudança do sistema atingiu o risco.
- **"Os vínculos vencidos não são revertidos."** A tarefa diária precisa estar rodando (o serviço `scheduler` em desenvolvimento; um cron com `schedule:run` em produção). Para forçar, rode `php artisan links:flag-due-for-review`.

## Para aprofundar

As regras de negócio e o porquê de cada uma estão em [`docs/decisions`](decisions/README.md): vínculos permanentes (0006), duas dimensões de status (0013, 0018), gatilhos de reavaliação (0019), reavaliação e desfechos (0020) e mudanças do sistema (0021).
