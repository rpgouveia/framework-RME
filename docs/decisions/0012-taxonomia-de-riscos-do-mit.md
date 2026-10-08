# 0012. Riscos classificados pela taxonomia do MIT AI Risk Repository

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

As categorias de risco eram próprias do projeto, sem âncora na literatura. Saeri et al. apontam o mapeamento entre mitigações e riscos como próximo passo e remetem à taxonomia de domínios do MIT AI Risk Repository.

## Decisão

- O risco é classificado em um **subdomínio** do MIT AI Risk Repository (7 domínios, 24 subdomínios, versão registrada no arquivo); o domínio é derivado.
- Cada mitigação do catálogo declara **um ou mais subdomínios-alvo**.
- Na criação de vínculo, as mitigações cujo alvo inclui o subdomínio do risco aparecem como **recomendadas**; as demais continuam disponíveis (R-8).

## Consequências

- Atende ao critério de sucesso de que toda mitigação do catálogo declara risco-alvo.
- A mesma taxonomia passou a classificar os eventos adversos (0015).

## Implementação

`database/data/taxonomies/mit-ai-risk-domains.json`; relação entre mitigação e subdomínio; recomendação em `link-options.ts`.

## Referências

- SLATTERY et al. (2026), _Patterns_; SAERI et al. (2025).
