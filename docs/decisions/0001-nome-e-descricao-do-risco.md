# 0001. Nome e descrição do risco separados

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

O risco tinha só a descrição, limitada a 255 caracteres, enquanto os campos de texto da mitigação aceitavam 2000. A descrição acumulava dois papéis: identificar o risco e caracterizá-lo. Na interface, a coluna de descrição ocupava metade da tabela e ainda truncava, e o título do detalhe e o breadcrumb eram parágrafos cortados.

## Decisão

- O risco tem **nome** (até 255 caracteres), que identifica o tipo de risco, como "Prompt injection".
- O risco tem **descrição** (até 2000 caracteres, coluna `text`), que caracteriza como o risco se manifesta naquele sistema.

## Consequências

- O nome é usado como rótulo em selects, títulos, breadcrumbs e no relatório.
- O mesmo nome pode se repetir em sistemas diferentes, o que permite comparar sistemas (ver 0002).

## Implementação

Migration original de `risks`, `RiskValidationRules`, factory, telas de `risks/` e relatório de rastreabilidade.

## Referências

- Modelo lógico (ERD) do grupo, entidade Risk.
