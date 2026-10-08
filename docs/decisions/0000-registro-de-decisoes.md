# 0000. Registro de decisões do projeto

- **Situação:** Aceita
- **Registrada em:** 2026-10-08

## Contexto

As regras de domínio do Framework RME foram decididas pelo grupo ao longo do desenvolvimento, em conversas e documentos fora do repositório. Quem implementa uma mudança (inclusive assistentes de código) não tinha como consultá-las, e cada prompt precisava reconstruí-las, o que abre espaço para erro.

## Decisão

Cada decisão de domínio fica registrada em um arquivo Markdown nesta pasta, numerado em sequência, com contexto, decisão, consequências, implementação e referências. As decisões ainda em aberto ficam em `PENDENTES.md`. Uma decisão nunca é apagada: se for revista, o arquivo antigo recebe a situação **Substituída** e aponta para o novo.

## Consequências

- Antes de alterar uma regra de negócio, leia a decisão correspondente.
- Uma mudança que contrarie uma decisão aceita exige nova decisão do grupo, registrada aqui.
- Itens de `PENDENTES.md` não devem ser implementados por iniciativa própria.

## Implementação

Esta pasta. Os registros 0001 a 0017 foram escritos retroativamente em 2026-10-08, a partir das discussões do grupo e do documento _Protocolo de monitoramento pós-implantação (C3): registro da discussão e propostas_.

## Referências

- Documento do C3 (versão 5) e documento de planejamento do painel do OE6, mantidos pelo grupo fora do repositório.
