# 0003. Sistema do risco travado depois do primeiro vínculo

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

A edição de risco permitia trocar o sistema de IA. Um risco com vínculos pertence à cadeia de rastreabilidade do seu sistema, e o relatório de rastreabilidade é exportado por sistema.

## Decisão

O sistema de IA de um risco pode ser alterado enquanto o risco não tiver vínculos. Depois do primeiro vínculo, **inclusive cancelado**, o sistema fica fixo.

## Consequências

- Um risco cadastrado no sistema errado pode ser corrigido logo; um risco que já foi tratado não muda de contexto.
- Vínculos cancelados também contam, porque são permanentes (0006).

## Implementação

`UpdateRiskRequest` recusa um `ai_system_id` diferente quando há vínculos; na edição, o campo vira texto somente leitura e o formulário deixa de enviá-lo.

## Referências

- Decisão 0006.
