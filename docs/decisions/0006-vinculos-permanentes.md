# 0006. Vínculos permanentes, encerrados por cancelamento

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

Todo vínculo nasce com histórico (0004) e o histórico é somente de inclusão (0007), então a exclusão de vínculos ou ficaria impossível ou exigiria apagar a trilha de auditoria.

## Decisão

- Vínculos **não são excluídos**; são encerrados pelo status `cancelled`, pelo fluxo de histórico.
- O motivo é **obrigatório** ao cancelar.
- A partir de `cancelled`, a **única transição** permitida é a reativação para `planned`. As demais transições continuam livres até a definição de uma matriz completa.

## Consequências

- O escopo `dueForReview` exclui vínculos cancelados.
- Riscos, mitigações, responsáveis e sistemas com vínculos também nunca são excluídos.
- A reativação preserva a trilha única do par, que a R-6 impediria de recriar.

## Implementação

Rota `links` com `except('destroy')`; `LinkStatus::canTransitionTo()`; atalhos "Cancelar vínculo" e "Reativar vínculo" no detalhe.

## Referências

- Decisões 0004 e 0007.
