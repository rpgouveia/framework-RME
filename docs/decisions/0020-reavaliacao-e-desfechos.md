# 0020. Reavaliação do vínculo e seus desfechos

- **Situação:** Aceita; implementada na etapa 4c
- **Registrada em:** 2026-10-08

## Contexto

As decisões 0018 e 0019 definiram quando um vínculo é sinalizado (a reversão para declarada) e o que dispara a sinalização. Faltava o que acontece depois: as saídas existiam espalhadas (registrar evidência e reverificar, editar o vínculo, cancelar), mas nada registrava o que foi concluído na reavaliação, por quem e por quê, como pede o UC009.

## Decisão

1. **Registro próprio:** a reavaliação é um registro somente de inclusão, ligado ao vínculo e à reversão que a motivou, com desfecho, responsável, justificativa e, quando couber, análise de causa.
2. **Desfechos:** manter, ajustar, substituir e encerrar.
3. **Efeito de cada desfecho:**
    - **Manter:** conclui a reavaliação e verifica o vínculo no mesmo ato, exigindo a evidência posterior à reversão (0018). Sem essa evidência, o desfecho não fica disponível.
    - **Ajustar:** aplica no mesmo ato as mudanças (responsável, custo estimado, fase do ciclo de vida, status de progresso) e registra na reavaliação o que mudou. Verificar no mesmo ato é opcional; se a mudança ainda precisa ser implementada, o vínculo fica declarado, aguardando a prova.
    - **Substituir:** cancela o vínculo, com a justificativa como motivo, e leva à criação de vínculo para o mesmo risco; o vínculo novo registra qual vínculo substitui.
    - **Encerrar:** cancela o vínculo, com a justificativa como motivo, sem substituto.
4. **Estados:** "Aguardando reavaliação" significa vínculo declarado cuja última reversão ainda não tem reavaliação concluída. Reavaliado sem verificação, o vínculo passa a "Aguardando verificação", junto dos que aguardam a primeira verificação. Assim se distinguem as pendências de decidir e de comprovar.
5. **Quem reavalia:** um responsável ativo escolhido na tela, pré-preenchido com o responsável do vínculo; um único papel organizacional por reavaliação.
6. **Análise de causa:** campos opcionais de causa apurada e de fase do ciclo de vida em que ela se originou, com a opção explícita "não apurada". Aparecem quando a reversão veio de evento adverso ou de reversão manual; para vencimento, não há causa a apurar. A causalidade apurada fica na reavaliação, e não no evento, que é somente de inclusão.
7. **Evento com vários vínculos:** cada vínculo é reavaliado separadamente. O detalhe do evento mostra o andamento das reavaliações.
8. **Sistemas na faixa inaceitável:** só os desfechos "Ajustar", para registrar o plano de descontinuação, e "Encerrar".
9. **Prazo para reavaliar:** não há, por ora. A listagem mostra há quantos dias cada vínculo aguarda, e o painel ordena pelos mais antigos. Será reavaliado após o MVP.
10. **Relatório de rastreabilidade:** passa a incluir os eventos adversos do sistema (natureza, subdomínios, ocorrência, detecção, vínculo interceptador e vínculos revertidos) e, em cada vínculo, as reavaliações (desfecho, responsável, justificativa, causa e fase de origem, e vínculo substituto).

## Consequências

- Complementa a 0018 e a 0019: a reversão continua sendo a sinalização, e a reavaliação é o ato que a encerra.
- Mudanças de status de progresso feitas por um ajuste passam pelo caminho único de gravação (0007).
- Substituir por outra mitigação para o mesmo risco é permitido; manter a mesma mitigação é feito por "Manter" ou "Ajustar", já que a R-6 impede recriar o mesmo par.
- O relatório passa a reconstruir a história completa de cada sistema: risco, mitigação, prova, ocorrência, decisão e justificativa.
- **Adendo (2026-10-08), regras acrescentadas na implementação:**
    - um ajuste não leva o vínculo a "cancelado": para isso existem os desfechos "Encerrar" e "Substituir", que registram a decisão como tal;
    - um vínculo cancelado só pode ser substituído uma vez: o vínculo novo registra qual substitui, e um segundo vínculo com o mesmo substituído é recusado.

## Implementação

Implementada na etapa 4c (2026-10-08).

- **Itens 1 e 2 (registro):** tabela `reassessments`, somente de inclusão, com rotas `links.reassessments` restritas a `index`, `create`, `store` e `show`, aninhadas no vínculo com `shallow`. Cada reavaliação aponta para a reversão que conclui (`reversal_id`, único: uma reavaliação por reversão), com desfecho (enum `ReassessmentOutcome`), responsável, justificativa e, num ajuste, a lista das mudanças, com antes e depois de cada campo (`changes`).
- **Item 3 (efeitos):** a Action `RecordReassessment` é o caminho único; age em transação e com bloqueio do vínculo, e toda mudança de status e de verificação passa pelo `RecordStatusChange` (0007).
    - **Manter:** chama `verify()`, com a mesma regra de evidência posterior à reversão.
    - **Ajustar:** aplica responsável, custo estimado e fase, e muda o progresso por `handle()`, respeitando as transições e recusando o cancelamento. A verificação no mesmo ato é opcional.
    - **Substituir e encerrar:** cancelam o vínculo com a justificativa como motivo. Substituir redireciona para `links/create?risk=&replaces=`. A coluna `links.replaces_link_id` só é aceita para um vínculo do mesmo risco, cancelado por uma reavaliação de substituição e ainda não substituído.
    - **Verificação no mesmo ato:** fica ligada à reavaliação por `reassessments.verification_id` e pela relação inversa `StatusHistory::concludedReassessment()`. Usei uma só chave estrangeira, em vez de uma em cada tabela, para não criar um ciclo de chaves com o histórico.
- **Item 4 (estados):** escopo `awaitingReassessment()`, para vínculo declarado, não cancelado, cuja última reversão não tem reavaliação; e escopo `awaitingVerification()`, que substitui o filtro da primeira verificação e inclui os reavaliados ainda sem verificação. A listagem, os filtros e o painel seguem essas definições.
- **Item 5:** responsável ativo escolhido na tela, pré-preenchido com o do vínculo.
- **Item 6 (causa):** coluna `cause_status` (enum `CauseStatus`: `identified`, `not_identified`, `not_applicable`), com `cause` e `cause_phase`. Em `identified`, a causa e a fase são obrigatórias; nos outros dois, as duas ficam nulas. A regra é garantida pela Action, pela validação e por um CHECK no banco. `not_applicable` é definido pela Action quando a reversão veio de vencimento ou de reclassificação; a tela só mostra os campos para evento adverso e reversão manual.
- **Item 7:** o detalhe do evento mostra o andamento ("2 de 5 reavaliados") e o desfecho de cada vínculo revertido.
- **Item 8:** `RecordReassessment::outcomeProblems()` torna Manter e Substituir indisponíveis para sistema na faixa inaceitável, com o motivo; a tela mostra os desfechos indisponíveis desabilitados.
- **Item 9:** a listagem de aguardando reavaliação e o painel ordenam pelos que esperam há mais tempo (escopo `longestAwaitingFirst()`) e mostram há quantos dias cada vínculo aguarda.
- **Item 10 (relatório):** seção `adverse_events` no JSON. Em cada vínculo, as reavaliações (`reassessments`), os eventos que o reverteram (`reverting_adverse_events`) e o vínculo substituído e o substituto, nos dois formatos. O CSV, uma linha por vínculo, traz os eventos dentro dos vínculos que eles reverteram. Uma exportação CSV à parte, só de eventos adversos (`ai-systems/{id}/adverse-events.csv`, `CompileTraceabilityReport::adverseEventRows()`), traz uma linha por evento, inclusive os que não reverteram vínculos: natureza, subdomínios, ocorrência, detecção, vínculo interceptador, vínculos revertidos e subdomínios sem risco cadastrado no sistema.
- **Telas:** página de reavaliação, com o contexto da reversão, desfechos com explicação, causa e ajustes; detalhe da reavaliação; botão "Reavaliar" e card "Reavaliações" no detalhe do vínculo; vínculo relacionado na substituição; reavaliações intercaladas na linha do tempo do histórico.
- **Seeders:** o `ReassessmentSeeder` gera um evento com quatro vínculos revertidos, dos quais três foram reavaliados (manter; ajustar sem verificação; substituir, com o vínculo novo); um encerramento; um ajuste em sistema reclassificado para a faixa inaceitável; e vínculos ainda aguardando reavaliação, com idades diferentes.

## Referências

- Casos de Uso, UC009; Especificação, RF02, RF05, RF07 e RF09; Prototipação, Tela 5.
- Decisões 0005, 0006, 0007, 0013, 0017, 0018 e 0019.
- Fase de origem da causa: HE, PAN e CHU (2026), concentração do risco de governança nas fases iniciais do ciclo de vida.
