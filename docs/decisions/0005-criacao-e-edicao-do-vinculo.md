# 0005. Campos da criação e da edição do vínculo

- **Situação:** Aceita; parcialmente revista pela 0018
- **Registrada em:** 2026-10-08

## Contexto

A edição do vínculo permitia alterar status, data de revisão, risco, mitigação e data de criação. Isso contornava o histórico (RF09), corrompia a identidade do vínculo e tornava a data de revisão incoerente.

## Decisão

**Criação:** status inicial e data de criação são definidos pelo servidor (status `planned` e data de hoje). O vínculo nasce declarado e sem data de revisão, que só é calculada na primeira verificação (0018). O custo observado não é informado na criação (R-4).

**Edição:** só são editáveis responsável, fase do ciclo de vida e custo estimado. Risco, mitigação, status, verificação, data de criação, próxima revisão e custo observado são somente leitura. O custo observado é informado, opcionalmente, no registro de evidência (0018).

## Consequências

- O par risco e mitigação é a identidade do vínculo; se estiver errado, o caminho é cancelar e criar outro.
- Mudança de status só pelo fluxo de histórico.
- O custo observado segue a prototipação da Tela 3, que o libera apenas a partir do registro de evidência.

## Implementação

`StoreLinkRequest`, `UpdateLinkRequest` (regras próprias, campos travados ignorados) e telas `links/create.tsx` e `links/edit.tsx`.

- **Criação:** a Action `CreateLink` grava o vínculo com status `planned`, verificação `declared`, data de criação de hoje e `next_review_date` nula, ignorando o que vier na requisição. Ela não calcula mais a data de revisão: a data é definida na verificação, por `RecordStatusChange::verify()` (0018).
- **Edição:** `UpdateLinkRequest` aceita só responsável, fase do ciclo de vida e custo estimado. A coluna `links.observed_cost` foi removida. A edição mostra o custo observado como leitura, a partir da evidência mais recente que o informou (`Link::observedCostEvidence()`), com link para essa evidência.
- **Custo observado:** o campo opcional fica no registro de evidência (`EvidenceValidationRules`, Action `RecordEvidence`).

## Referências

- UC005 (R-4, R-7); Especificação, RF07 e RF09; Prototipação, Telas 2 e 3.

## Revisão

Em 2026-10-08, a decisão 0018 alterou dois pontos desta decisão: a data de revisão deixou de ser calculada na criação (o vínculo nasce sem data até a primeira verificação) e o custo observado deixou a edição do vínculo, passando ao registro de evidência. O texto acima já reflete a versão revista.
