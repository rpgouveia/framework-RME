# 0010. Catálogo de mitigações somente de consulta

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

O UC004 é "Consultar catálogo de mitigações", e a R-8 determina que mitigações venham só do catálogo curado. O catálogo é o componente C2, produto da pesquisa.

## Decisão

- A interface só **lista e detalha** mitigações; não cria, edita nem exclui.
- O conteúdo é carregado de um **arquivo versionado** preenchido pelo grupo, validado por inteiro na carga, com todos os erros relatados de uma vez.
- O arquivo tem metadados, incluindo a indicação de dados **fictícios**, que a interface exibe como aviso.

## Consequências

- O catálogo atual é fictício; o conteúdo real depende da curadoria do grupo (ver `PENDENTES.md`).

## Implementação

`database/data/mitigation-catalog.json`; `MitigationCatalog` e `InvalidMitigationCatalog`; rota `mitigations` com `only(['index', 'show'])`.

## Referências

- Casos de Uso, UC004 e R-8 do UC005; decisão 0011.
