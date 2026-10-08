# 0014. Eventos adversos somente de inclusão

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

O evento registra algo que aconteceu e, na reavaliação por gatilho, disparará mudanças nos vínculos do sistema. Alterar sistema ou data depois deixaria essas mudanças sem explicação.

## Decisão

- Eventos adversos **não se editam nem se excluem**.
- A data de ocorrência não pode ser futura.
- O registro pede confirmação antes de gravar.

## Consequências

- Um evento registrado por engano permanece; por isso a confirmação pesa mais aqui do que na evidência.

## Implementação

Rota `adverse-events` com `only(['index', 'create', 'store', 'show'])`.

## Referências

- Especificação, Tela 4; UC008.
