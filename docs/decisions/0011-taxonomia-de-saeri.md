# 0011. Taxonomia de Saeri et al. como dado versionado e catálogo rastreável

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

O catálogo usava apenas as 4 categorias de Saeri et al. (2025), sem as 23 subcategorias, sem rastreabilidade até a base original e com um único campo de fonte misturando a origem da mitigação e a das estimativas. O RNF05 pede listas extensíveis.

## Decisão

- Taxonomias são **dados versionados** no banco, carregados de arquivos com citação, versão e data.
- Cada mitigação tem **subcategoria** de Saeri (a categoria é derivada), **nome original**, **identificador na base de Saeri** e **documento de origem**.
- A **fonte da estimativa** (custo, incerteza, evidência esperada) é separada da fonte da mitigação, conforme o RNF03.
- O detalhe separa o que vem de Saeri da **contribuição do framework (C2)**.

## Consequências

- O preenchimento do catálogo real exige consultar a base de Saeri para cada entrada.
- A classificação em 23 subcategorias torna a avaliação de concordância entre anotadores mais significativa.

## Implementação

`database/data/taxonomies/saeri-mitigation-taxonomy.json`; estrutura genérica de termos de taxonomia.

## Referências

- SAERI et al. (2025); Especificação, RNF03 e RNF05.
