# 0018. Verificação do vínculo: regras da etapa 4a

- **Situação:** Aceita; implementada na etapa 4a
- **Registrada em:** 2026-10-08

## Contexto

A decisão 0013 criou a dimensão de verificação do vínculo, mas deixou em aberto como ela opera: o que significa estar sinalizado para revisão, quando a revisão é contada, quem verifica, como se reverte e quais casos ficam de fora.

## Decisão

1. **Sinalização é reversão.** Um vínculo que já foi verificado e voltou a declarado está aguardando reavaliação; o histórico registra o gatilho. Não existe terceiro estado.
2. **A contagem recomeça a cada verificação:** próxima revisão = data da verificação + intervalo da faixa do sistema (0017).
3. **O gatilho de prazo vale só para vínculos verificados.**
4. **Vínculos declarados não têm data de revisão.** A data nasce na primeira verificação, e a interface mostra "Aguardando verificação". A R-7 passa a valer para a primeira verificação, e não para a criação. Ao reverter, a data volta a ficar vazia.
5. **Mudanças automáticas não têm autor humano.** O responsável é opcional apenas em registros automáticos, e uma coluna de origem indica o gatilho: manual, vencimento, evento adverso ou reclassificação do sistema. A interface mostra "Registrado pelo sistema".
6. **Quem verifica é escolhido na tela de verificação**, entre responsáveis ativos, com o responsável do vínculo pré-preenchido. Cada verificação tem um único responsável, que é um papel organizacional (cargo e área, como em toda a cadeia), e não uma pessoa; outros participantes podem constar na descrição da evidência.
7. **A reversão manual é permitida**, com motivo obrigatório e responsável escolhido. Tem o mesmo efeito da automática: exige evidência nova para reverificar.
8. **Regras de contorno:**
    - vínculo cancelado não pode ser verificado; cancelar um vínculo verificado não altera a verificação, só o tira do monitoramento;
    - vínculo de sistema na faixa inaceitável não pode ser verificado (0017);
    - a evidência posterior à última reversão (0013) é aferida pelo momento exato em que a evidência foi gravada, e não pela data de registro, que só tem o dia.
9. **O custo observado é informado no registro de evidência**, opcionalmente, e deixa a edição do vínculo (0005).

## Consequências

- Vínculos criados antes da etapa ficam declarados e sem data até a primeira verificação, o que resolve os casos de borda de vínculos sem data e de reativações com data vencida.
- O escopo de vínculo monitorável passa a exigir verificação.
- O painel distingue vínculos aguardando a primeira verificação de vínculos aguardando reavaliação.

## Implementação

Implementada na etapa 4a (2026-10-08). As reversões automáticas (vencimento, evento adverso e reclassificação) continuam pendentes (etapa 4b, ver `PENDENTES.md`): a assinatura já as aceita, mas nenhum gatilho as dispara.

- **Ações** (caminho único, 0007): `RecordStatusChange::verify()` e `revert()`, em transação e com bloqueio do vínculo (`lockForUpdate`). `verificationProblem()` devolve o primeiro motivo de recusa, usado também pela interface. `CreateLink` cria o vínculo declarado e sem data. `RecordEvidence` grava a evidência, com custo observado opcional.
- **Itens 2 e 4:** `verify()` define `next_review_date` = hoje + intervalo da faixa atual do sistema (`MonitoringProtocol::nextReviewDate()`); `revert()` volta a data a nula. A interface mostra "Aguardando verificação" para vínculo declarado e mantém "Não se aplica" para a faixa inaceitável (`ReviewDate`).
- **Item 3:** o escopo `Link::monitorable()` exige vínculo verificado; `dueForReview()`, o comando `links:flag-due-for-review` e o painel usam essa regra.
- **Item 5:** enum `ChangeOrigin` (`manual`, `review_due`, `adverse_event`, `system_reclassification`) na coluna `status_histories.origin`, com padrão `manual`. `owner_id` é anulável só fora da origem manual, o que é garantido pela ação e por um CHECK no banco. A interface mostra "Registrado pelo sistema" (`EntryAuthor`).
- **Itens 6 e 7:** `LinkVerificationController` (rotas `links.verification.store` e `links.verification.destroy`), com `VerifyLinkRequest` e `RevertLinkVerificationRequest`. O card "Verificação" do detalhe do vínculo (`LinkVerificationCard`) mostra o estado, a última verificação, o destaque "Aguardando reavaliação" e os diálogos, com o responsável do vínculo pré-preenchido e o motivo da recusa ao lado do botão desabilitado.
- **Item 8:** a verificação é recusada para vínculo cancelado e para sistema na faixa inaceitável; cancelar um vínculo verificado mantém a verificação e só o tira do monitoramento. A evidência é aferida pelo `created_at`.
- **Item 9:** `evidence.observed_cost` anulável; o custo observado do vínculo é o da evidência mais recente que o informou (relação `Link::observedCostEvidence()`). A coluna `links.observed_cost` foi removida, em vez de mantida como cópia: como as evidências são somente de inclusão, o valor derivado nunca diverge, e a relação já entrega a evidência de origem que a edição mostra.
- **Pendências da Tela 3:** escopos `awaitingFirstVerification()`, `awaitingReassessment()` e `verified()`; filtro `verification` na listagem de vínculos e bloco de três contagens no painel. As pendências deixam de fora vínculos cancelados e de sistemas na faixa inaceitável, que não podem ser verificados.
- **Relatório:** estado de verificação, data e papel da última verificação e custo observado, em CSV e JSON.
- **Seeders:** `LinkSeeder` conta a história de cada vínculo pelas ações, com viagem no tempo: declarados sem evidência, prontos para verificar, verificados, com revisão vencida e revertidos com e sem evidência posterior. Substitui os antigos `StatusHistorySeeder` e `EvidenceSeeder`.

## Referências

- Decisões 0005, 0007, 0008, 0013 e 0017; UC005 (R-3, R-7); Especificação, RF03 e RF09; Prototipação, Telas 3 e 5.
