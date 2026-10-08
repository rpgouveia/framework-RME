# 0013. Duas dimensões de status do vínculo

- **Situação:** Aceita; detalhada pela 0018; implementada na etapa 4a
- **Registrada em:** 2026-10-08

## Contexto

Os documentos do grupo descrevem o vínculo nascendo declarado e passando a verificado mediante evidência (R-3, RF03). O código tinha uma escala de progresso de seis estados, de origem não documentada. As duas medem coisas diferentes: andamento da implementação e estado probatório.

## Decisão

- O vínculo tem duas dimensões: **progresso** (escala atual) e **verificação** (declarada ou verificada).
- A passagem a verificada exige ao menos uma **evidência registrada depois da última reversão** para declarada; no primeiro ciclo, vale qualquer evidência.
- A verificação é **reversível**: evento adverso ou vencimento da revisão devolvem o vínculo a declarado.
- As mudanças de verificação vão para o **mesmo histórico**, em colunas paralelas, e cada registro altera uma única dimensão.

## Consequências

- As regras de verificação, reversão e revisão estão detalhadas na decisão 0018.

## Implementação

Implementada na etapa 4a (2026-10-08):

- Enum `VerificationStatus` (`declared`, `verified`) e coluna `links.verification_status`, com padrão `declared` e fora de qualquer formulário.
- `status_histories` com `previous_verification` e `new_verification`; `previous_status` e `new_status` passaram a anuláveis. Cada registro altera uma única dimensão, o que é garantido pelo `RecordStatusChange` e por um CHECK no banco (SQLite e PostgreSQL).
- A passagem a verificada exige evidência gravada (`created_at`) depois da última reversão (relação `Link::lastReversal()`); no primeiro ciclo, vale qualquer evidência.
- A interface mostra as duas dimensões lado a lado (`LinkStatusBadges`) e as mudanças de verificação na mesma linha do tempo do histórico (`StatusTransition`).

## Referências

- UC005 (R-3); Especificação, RF03 e RF09; Prototipação, Tela 3; decisões 0007, 0008 e 0018.
