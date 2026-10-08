# 0004. Par risco e mitigação único e histórico inicial do vínculo

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

O UC005 define duas regras que não estavam no código: a R-6, que proíbe dois vínculos com o mesmo par de risco e mitigação, e a pós-condição de que a criação do vínculo grava um registro no histórico de status (RF09).

## Decisão

- **R-6:** o par risco e mitigação é único, com índice no banco e validação com mensagem legível. A R-5 continua valendo: a mesma mitigação pode servir a vários riscos, e um risco pode ter várias mitigações.
- **Pós-condição:** criar um vínculo grava, na mesma transação, a entrada de abertura do histórico, sem status anterior.

## Consequências

- A criação de vínculo esconde as mitigações já vinculadas ao risco escolhido.
- Todo vínculo nasce com histórico, o que interage com a 0006.

## Implementação

Action `CreateLink`; `RecordStatusChange::open()`; função pura `availableMitigations()` em `resources/js/lib/link-options.ts`.

## Referências

- Casos de Uso, UC005 (R-5, R-6 e pós-condição); Especificação, RF09.
