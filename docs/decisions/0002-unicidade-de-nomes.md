# 0002. Unicidade de nomes de mitigações e riscos

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

O catálogo semeado tinha duas mitigações diferentes com o mesmo nome, o que fez a criação de vínculo parecer mostrar uma mitigação duplicada e não esconder a já vinculada. O nome significa coisas diferentes em cada entidade.

## Decisão

- **Mitigação:** nome único no catálogo inteiro, sem diferenciar maiúsculas e minúsculas. Um catálogo com homônimos é ambíguo por definição.
- **Risco:** nome único dentro do mesmo sistema de IA, sem diferenciar maiúsculas e minúsculas. O mesmo nome em sistemas diferentes é permitido e desejável, porque o nome identifica um tipo de risco que se repete entre sistemas.

## Consequências

- Validação e índice único no banco seguem a mesma semântica (`lower(...)`).
- Ao mudar o sistema de um risco sem vínculos, a unicidade é conferida no sistema novo.

## Implementação

Regra `UniqueNameIgnoringCase`; índices `mitigations_name_unique` e `risks_ai_system_id_name_unique`; trait `PicksUnusedNames` nas factories.

## Referências

- Decisões 0001 e 0003.
