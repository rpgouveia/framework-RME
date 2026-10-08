# 0008. Evidências somente de inclusão, com data automática

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

A verificação de um vínculo (RF03) depende de evidência, e a reverificação após uma reversão exigirá evidência posterior a ela (0013). Evidências editáveis ou com data digitada permitiriam alterar a prova retroativamente.

## Decisão

- Evidências **não se editam nem se excluem**. Um registro errado se corrige registrando outro.
- A **data de registro** é definida pelo servidor.

## Consequências

- A criação mostra aviso de permanência e pede confirmação antes de gravar.

## Implementação

Rota `links.evidence` com `only(['index', 'create', 'store', 'show'])`; data gravada com `today()` no `store`.

## Referências

- Especificação, RF03 e Tela 3 (data automática); decisão 0013.
