# 0023. Licença das traduções e de dependências de terceiros

- **Situação:** Aceita; implementada
- **Registrada em:** 2026-10-09

## Contexto

O projeto declara licença MIT no `composer.json`. O commit 6383a1d adicionou o pacote `lucascudo/laravel-pt-br-localization`, licenciado como GPL-3.0-or-later, e copiou arquivos dele para `lang/pt_BR/`. Esses arquivos continuam sob GPL, o que torna inexata a declaração de que o projeto é MIT. O Laravel Lang oferece as mesmas traduções sob licença MIT, com suporte ao Laravel 13, ao português do Brasil e às telas do Fortify.

## Decisão

1. **Fonte das traduções padrão:** as traduções padrão do Laravel e do Fortify vêm do Laravel Lang (`laravel-lang/common`), instalado como dependência de desenvolvimento. O pacote anterior é removido, e nenhum arquivo derivado dele permanece no repositório.
2. **Créditos:** o README registra que as traduções padrão vêm do Laravel Lang, sob licença MIT, cumprindo a exigência de manter o aviso de copyright.
3. **Preservação do domínio:** atualizações das traduções padrão não podem alterar as traduções do domínio no `lang/pt_BR.json`.
4. **Dependências futuras:** dependências e arquivos de terceiros devem ter licença compatível com a MIT do projeto (por exemplo, MIT, BSD, Apache-2.0 ou ISC). Licenças copyleft, como GPL e AGPL, exigem decisão do grupo registrada nesta pasta.

- **Adendo (2026-10-12), alcance do item 3:** a preservação cobre também os textos fixados pelo projeto, isto é, as chaves em que o projeto mantém redação própria, diferente da do Laravel Lang (por exemplo, "Encerrar" para `Close` e as mensagens das regras de validação usadas pelos formulários do domínio). Esses textos ficam numa lista única, protegida por teste: uma atualização que altere algum deles falha nos testes até que seja restaurado.

## Consequências

- O projeto passa a ser integralmente distribuível sob MIT.
- A verificação de licença entra na revisão de qualquer nova dependência.

## Implementação

Implementada em 2026-10-12.

- **Item 1 (fonte):** `lucascudo/laravel-pt-br-localization` removido; `laravel-lang/common` (`^6.8`) em `require-dev`. As traduções foram publicadas com `php artisan lang:add pt_BR`, que gravou `lang/pt_BR.json` e `lang/pt_BR/` (`auth`, `pagination`, `passwords`, `validation`, `actions` e `http-statuses`). O conteúdo do pacote anterior que o Laravel Lang não substituiu foi removido: 146 chaves do `pt_BR.json`, 11 atributos genéricos do `validation.php` e a chave `password` do `passwords.php`. Os arquivos de `lang/pt_BR/` contêm só o Laravel Lang mais os atributos de validação do domínio e as mensagens fixadas.
- **Telas do starter kit React:** o Laravel Lang não publica os textos dessas telas (login, cadastro, configurações e barra lateral), porque o kit React não usa tradução. As chaves que elas usam foram copiadas das traduções pt_BR do próprio Laravel Lang (`starter-kits` e `lang`, MIT), com redação do projeto onde indicado na revisão; uma atualização não as altera, pois não fazem parte da publicação.
- **Item 2 (créditos):** seção _Credits_ do README.
- **Item 3 e adendo (preservação):** o `lang:add` sobrescreve toda chave que o Laravel Lang publica; as chaves só do projeto ficam intactas. Os textos fixados estão em `tests/Fixtures/pinned-translations.php`, e o `PinnedTranslationsTest` verifica que cada um mantém a redação do projeto e que todo texto do projeto diferente de todas as traduções do Laravel Lang para a mesma chave está na lista. As mensagens das sete regras de validação usadas pelo domínio voltaram ao texto do projeto anterior ao commit 6383a1d.
- **Procedimento de atualização** (seção _Translations_ do README): `php artisan lang:add pt_BR`, nunca `lang:reset`, que apaga as chaves do projeto; `composer lint`, porque o publicador alinha as setas dos arquivos PHP; `git diff lang/`; e os testes.

## Referências

- Laravel Lang: https://laravel-lang.com e https://github.com/Laravel-Lang/lang (licença MIT).
- Decisão 0022.
