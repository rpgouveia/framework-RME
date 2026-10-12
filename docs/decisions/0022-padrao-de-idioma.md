# 0022. Padrão de idioma da interface

- **Situação:** Aceita; implementação pendente
- **Registrada em:** 2026-10-09

## Contexto

As telas do domínio foram escritas com o texto diretamente em português, enquanto o backend já usava o mecanismo de tradução do Laravel, com chaves em inglês. O commit 6383a1d introduziu no frontend uma função `t()` que lê o mesmo arquivo de idioma do backend e a aplicou à barra lateral, ao cabeçalho, às telas de autenticação e configurações e à página inicial. O projeto passou a ter duas formas de tratar idioma, e o idioma da barra lateral estava pendente.

## Decisão

1. **Idioma:** a interface é em português do Brasil, com o locale `pt_BR`.
2. **Mecanismo único:** todo texto visível passa por tradução, com a chave em inglês e o texto em `lang/pt_BR.json`. O arquivo é a fonte única de textos para o backend (`__()`) e para o frontend (`t()`).
3. **Sem biblioteca de internacionalização no frontend:** mantém-se a solução própria (`resources/js/lib/i18n.ts` e o hook `useTranslations`), que lê o arquivo do Laravel por uma prop compartilhada. Uma biblioteca como react-i18next exigiria um segundo dicionário ou uma ponte para o do Laravel, e configuração adicional para a renderização no servidor, sem benefício para uma interface de um único idioma. Plurais, quando necessários, seguem a sintaxe do Laravel numa função própria.
4. **Rótulos de enums:** têm uma única fonte, no arquivo de idioma; o frontend não mantém uma cópia própria dos textos.
5. **Migração:** todo código novo usa o mecanismo único desde já. As telas existentes migram numa etapa dedicada, sem alterar a redação dos textos.

## Consequências

- Resolve a pendência sobre o idioma da barra lateral.
- Todo ambiente precisa de `APP_LOCALE=pt_BR` no `.env`; o `.env.example` só é copiado quando o `.env` não existe.
- Uma chave sem tradução aparece em inglês, sem erro. A etapa de migração deve criar uma verificação automática de chaves faltantes.

## Implementação

Mecanismo introduzido no commit 6383a1d (`HandleInertiaRequests`, `lib/i18n.ts`, `hooks/use-translations.ts`). Migração das telas do domínio pendente.

## Referências

- Commit 6383a1d; decisão 0023.
