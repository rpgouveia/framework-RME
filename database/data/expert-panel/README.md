# Painel de especialistas (OE3 e OE6)

Dados do painel de especialistas da decisão 0027, executado conforme o
protocolo em `docs/expert-panel-protocol.md`. **O painel não foi executado
neste trabalho:** por enquanto, só o banco de itens está versionado.

- `items.csv`: o banco de itens, com as colunas `id`, `bloco`, `objetivo`
  (`OE3`, `OE6` ou os dois, separados por ponto e vírgula), `enunciado` e
  `fundamento`. Depois da primeira rodada, passa a conter a redação submetida
  à segunda (decisão 0027, item 8). Editável pelo grupo.
- `round-2-responses.csv`: a exportação anonimizada do formulário da segunda
  rodada, sem alterações além da anonimização. Ainda não existe.
- `oe6-cvr.json`: o resultado do cálculo, gerado pelo comando abaixo. Ainda não
  existe.

## Formato do CSV de respostas

O formato é o da Seção 6.2 do protocolo:

- uma coluna `Respondente`, com o código de cada especialista (E01, E02 e
  assim por diante), sem repetição;
- uma coluna por item, cujo cabeçalho começa pelo `id` entre colchetes, como
  `[DEF-C1] A ocorrência é observada...`; o resto do cabeçalho é ignorado;
- respostas dos itens `Essencial`, `Útil, mas não essencial` ou
  `Desnecessário` (maiúsculas e espaços nas pontas não importam);
- uma coluna por faixa, começando por `[INT-ALTO]`, `[INT-LIMITADO]` e
  `[INT-MINIMO]`, com um número inteiro positivo de dias.

Colunas sem colchetes no início, como o carimbo de data e hora e os
comentários, são ignoradas. Vírgula ou ponto e vírgula, com ou sem BOM, como o
formulário ou a planilha exportarem.

## Cálculo

```bash
docker compose exec app php artisan expert-panel:cvr
```

Por padrão, lê `round-2-responses.csv` e `items.csv` desta pasta e grava
`oe6-cvr.json`; as opções `--csv`, `--items` e `--output` indicam outros
arquivos.

Como todas as perguntas do formulário são obrigatórias, o cálculo é sempre
estrito: coluna de item ou de intervalo ausente ou repetida, identificador
desconhecido entre colchetes, respondente vazio ou repetido, resposta fora das
três categorias ou vazia, intervalo que não seja inteiro positivo e painel com
menos de 5 ou mais de 60 respondentes são erros. Todos são relatados de uma
vez, com a linha do CSV, e nada é gravado enquanto houver erro.

O resultado aparece em tabelas e é gravado em `oe6-cvr.json`: o valor crítico
para o N do painel (Ayre e Scally, 2014), o CVR de cada item e se é mantido, o
CVI por bloco e geral, sobre todos os itens e sobre os mantidos, os critérios
do OE3 (todos os itens com `OE3` mantidos) e do OE6 (todos os itens mantidos)
e, por faixa, a mediana e os quartis dos intervalos sugeridos, com a indicação
de que o intervalo do protocolo de monitoramento está ou não entre os quartis.
O mesmo CSV gera sempre o mesmo arquivo.

## Arquivo gerado

`oe6-cvr.json` é gerado pelo comando acima e **não deve ser editado nem
formatado à mão**. A decisão 0027 depende de que o arquivo versionado seja
idêntico ao que o comando produz a partir do CSV de respostas; por isso ele
fica fora do formatador do projeto (`fmt.ignorePatterns` em `vite.config.ts`).
Para mudá-lo, corrija o CSV e rode o comando de novo.
