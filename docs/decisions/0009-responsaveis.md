# 0009. Responsáveis: unicidade, trava e desativação

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

O responsável é um papel organizacional (R-2), identificado por cargo e área, sem nome de pessoa. O histórico guarda o id do responsável, então editar cargo ou área mudaria retroativamente o autor dos registros.

## Decisão

- O par **cargo e área é único**, sem diferenciar maiúsculas e minúsculas.
- Cargo e área ficam **travados** depois que o responsável é usado em vínculo ou histórico.
- Responsáveis usados podem ser **desativados**, desde que não respondam por vínculos não cancelados; podem ser **reativados** a qualquer momento.
- Responsáveis inativos não são oferecidos nem aceitos em novos vínculos, mudanças de status ou trocas de responsável; o atual de um vínculo continua visível, marcado como inativo.

## Consequências

- Mudança organizacional se registra com um responsável novo e reatribuição dos vínculos ativos.
- O cadastro de um par igual ao de um inativo é recusado com orientação para reativá-lo.

## Implementação

Coluna `deactivated_at`; escopo `active()`; rotas dedicadas de desativação e reativação.

## Referências

- UC005 (R-2).
