# 0007. Histórico de status somente de inclusão

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

O histórico sustenta a auditoria (RF09, Tela 7). As rotas permitiam editar e excluir registros, e o status anterior era enviado pelo cliente.

## Decisão

- Registros do histórico **não se editam nem se excluem**.
- O **status anterior** é lido pelo servidor do vínculo no momento da mudança; valor enviado pelo cliente é ignorado.
- Toda gravação de histórico passa por **um único caminho**, que confere a transição (0006) e atualiza o vínculo na mesma transação.

## Consequências

- Mudanças automáticas e de verificação, previstas para a etapa 4, devem usar o mesmo caminho.

## Implementação

Rota `status-histories` com `only(['index', 'create', 'store', 'show'])`; Action `RecordStatusChange` (`open` e `handle`).

## Referências

- Especificação, RF09 e Tela 7.
