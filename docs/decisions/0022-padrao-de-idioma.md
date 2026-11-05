# 0022. Padrão de idioma da interface

- **Situação:** Aceita; implementada
- **Registrada em:** 2026-10-12

## Contexto

As telas do domínio foram escritas com o texto diretamente em português, enquanto o backend já usava o mecanismo de tradução do Laravel, com chaves em inglês. O commit 6383a1d introduziu no frontend uma função `t()` que lê o mesmo arquivo de idioma do backend e a aplicou à barra lateral, ao cabeçalho, às telas de autenticação e configurações e à página inicial. O projeto passou a ter duas formas de tratar idioma, e o idioma da barra lateral estava pendente.

## Decisão

1. **Idioma:** a interface é em português do Brasil, com o locale `pt_BR`.
2. **Mecanismo único:** todo texto visível passa por tradução, com a chave em inglês e o texto em `lang/pt_BR.json`. O arquivo é a fonte única de textos para o backend (`__()`) e para o frontend (`t()`).
3. **Sem biblioteca de internacionalização no frontend:** mantém-se a solução própria (`resources/js/lib/i18n.ts` e o hook `useTranslations`), que lê o arquivo do Laravel por uma prop compartilhada. Uma biblioteca como react-i18next exigiria um segundo dicionário ou uma ponte para o do Laravel, e configuração adicional para a renderização no servidor, sem benefício para uma interface de um único idioma. Plurais, quando necessários, seguem a sintaxe do Laravel numa função própria.
4. **Rótulos de enums:** têm uma única fonte, no arquivo de idioma; o frontend não mantém uma cópia própria dos textos.
5. **Migração:** todo código novo usa o mecanismo único desde já. As telas existentes migram numa etapa dedicada, sem alterar a redação dos textos.

- **Adendo (2026-11-05), relatórios exportados:** a regra do item 2 alcança os três relatórios em CSV (rastreabilidade, eventos adversos e mudanças do sistema). Eles são lidos por pessoas numa planilha, e o formato já foi escolhido para isso: o separador ponto e vírgula e a marca de ordem de bytes servem ao Excel em português. Cabeçalhos, valores de enums e as frases montadas nas células compostas passam por `__()`, com os mesmos rótulos da interface (item 4). O relatório em JSON fica fora da regra: é formato de troca entre sistemas, e suas chaves e valores de enums são identificadores estáveis em inglês, independentes do idioma da interface; o glossário indica, ao lado de cada valor, o código correspondente. Nos dois formatos, o conteúdo cadastrado (nomes, descrições, justificativas e os nomes traduzidos das taxonomias) sai como foi registrado. Os nomes dos arquivos não mudam, porque os relatórios das reconstruções os citam. Motivo: na revisão dos relatórios da reconstrução da Optum, as células compostas misturavam o texto do grupo em português com códigos e conectivos em inglês, como em "adjust by Analista de dados clínicos (reversal: adverse_event; cause: identified, ...)".

## Consequências

- Resolve a pendência sobre o idioma da barra lateral.
- Todo ambiente precisa de `APP_LOCALE=pt_BR` no `.env`; o `.env.example` só é copiado quando o `.env` não existe.
- Uma chave sem tradução aparece em inglês, sem erro. A etapa de migração deve criar uma verificação automática de chaves faltantes.

## Implementação

Mecanismo introduzido no commit 6383a1d (`HandleInertiaRequests`, `lib/i18n.ts`, `hooks/use-translations.ts`). Telas do domínio migradas em 2026-10-12.

- **Itens 1, 2 e 5 (mecanismo único e migração):** as páginas e os componentes de `resources/js` usam `t()`, com os textos no `lang/pt_BR.json` e a mesma redação de antes. A única exceção é a correção autorizada na página inicial: o vínculo tem "revisão periódica após a verificação", e não "data de revisão" (0017 e 0018). Os breadcrumbs, definidos no `layout` de cada página fora do componente, usam `translator(translations)` com as props da página. Frases com um link no meio usam `interpolateNodes`, que põe o elemento no lugar do marcador `:nome`.
- **Item 3 (plurais):** `tc()`, com a sintaxe de escolha do Laravel (`singular|plural`, `{0}`, `[2,*]`) e a regra do Laravel para pt_BR, testada em `resources/js/lib/i18n.test.ts` (Vitest, `npm run test:unit`, incluído no CI).
- **Item 4 (rótulos de enums):** a chave do rótulo nomeia o enum e o caso, como `Uncertainty Level: High` (`EnumOptions::labelKey()`), porque o mesmo nome de caso se lê de forma diferente em cada enum ("Alta", "Alto", "Em monitoramento", "Monitoramento"). O backend resolve os rótulos pelo arquivo de idioma e os compartilha uma vez com todas as páginas (prop `enumLabels`, hook `useEnumLabels()`); o `resources/js/lib/labels.ts` guarda só as classes de cor. O `EnumLabelTest` exige tradução para todos os casos de todos os enums. Como efeito, a mensagem de transição de status recusada, que mostrava o status em inglês, passou ao português; e `Close` deixou de colidir com o Laravel Lang e saiu da lista de textos fixados.
- **Texto literal em JSX:** a regra `react/jsx-no-literals` do oxlint, no `vite.config.ts`, recusa texto escrito diretamente em JSX, inclusive em expressões. Ficam de fora os atributos, em sua maioria classes e identificadores, e os símbolos "→", "·", "—", "/" e o nome "MIT AI Risk Repository".
- **Verificação de chaves faltantes:** o `FrontendTranslationKeysTest` falha quando uma chave usada em `t()` ou `tc()` não existe no `lang/pt_BR.json`. Ao ser criado, ele encontrou oito chaves das telas de autenticação e de configurações removidas por engano na implementação da 0023; elas foram restauradas com o texto do Laravel Lang.
- **Colisões com o Laravel Lang:** `Changes`, `Next`, `Record` e `Search` mantêm o texto das telas e entram na lista de textos fixados (0023, adendo).
- **Adendo de 2026-11-05 (relatórios exportados):** os três CSV passam por `__()`. As colunas continuam como identificadores em inglês nas constantes de `CompileTraceabilityReport` e saem traduzidas por `csvHeader()`, com a chave `Report Column: ` seguida do identificador (por exemplo, `Report Column: system_category`, "Faixa de risco"); o prefixo as separa das demais chaves, e os rótulos seguem o glossário. Os valores de enums, também dentro das células compostas, saem pelo `label()`: `rows()` recebe o relatório em códigos e converte cada um com `tryFrom()`. As frases das células compostas (mudanças de progresso e de verificação, evidências, reavaliações e eventos) são chaves com marcadores `:nome`, como `:date: :outcome by :owner (reversal: :origin; cause: :cause) :justification`, traduzida para "…, por :owner (reversão: :origin; causa: :cause) …". Nas mudanças de verificação, o motivo só entra na célula quando é manual: o das reversões automáticas é gerado a partir da origem, que a célula já mostra com o número do evento ou da mudança, e traz a data em DD/MM/AAAA; o JSON mantém todos. Nenhuma chave nova colide com o Laravel Lang, e a lista de textos fixados não mudou. O `TraceabilityReportTest` exige tradução para toda coluna dos três CSV, sem repetir nome no mesmo arquivo, e confere um vínculo com histórico completo (progresso, verificação, reversões por evento, por mudança e manual com motivo, e reavaliações): no CSV, rótulos e frases em português, sem nenhum código nem conectivo em inglês; no JSON, os códigos em inglês. Ficaram de fora, como prevê o adendo, o JSON, as datas (ISO 8601), o separador, a marca de ordem de bytes, a neutralização de fórmulas, os nomes dos arquivos e o conteúdo cadastrado. Os relatórios exportados antes da mudança continuam no formato anterior.
- **Vocabulário:** o glossário da seção _Translations_ do README distingue "cadastrar" (entidades que se editam e se listam), "registrar" (o que é somente de inclusão) e "criar conta" (a conta do usuário). Os textos das telas de conta passaram a "entrar" em vez de "fazer login", "digitando" em vez de "inserindo" e "ativação" em vez de "habilitação", e os rótulos do campo de senha ("Mostrar senha", "Ocultar senha"), antes em inglês, passaram por `t()`.

## Referências

- Commit 6383a1d; decisão 0023.
