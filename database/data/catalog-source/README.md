# Fonte do catálogo de mitigações (C2)

O catálogo carregado pela aplicação, `database/data/mitigation-catalog.json`,
é gerado a partir destes arquivos (decisão 0024):

- `catalog.csv`: a folha **Catálogo** da planilha de trabalho do grupo,
  exportada em CSV, sem alterações. Preserva os dados brutos da anotação dupla
  (subdomínios dos anotadores A e B, concordância e consenso), base do OE1.
- `meta.json`: versão da base de Saeri et al., data de acesso e critério de
  curadoria. Editável.

Para gerar o catálogo:

```bash
docker compose exec app php artisan mitigations:import-catalog
```

Só entram as linhas com situação "Concluída"; as demais são listadas. O
resultado passa pelas mesmas validações do catálogo, e nada é gravado enquanto
houver problema: todos são relatados de uma vez, com a linha do CSV. Depois,
`php artisan migrate:fresh --seed` carrega o catálogo novo.

## Concordância entre anotadores (OE1)

A medida de concordância da decisão 0025 é calculada a partir das colunas
"Subdomínios: anotador A" e "Subdomínios: anotador B" deste `catalog.csv`:

```bash
docker compose exec app php artisan mitigations:agreement
```

Entram as linhas com as duas colunas preenchidas, qualquer que seja a situação.
Sem opções, as linhas com só um anotador ficam de fora e são listadas, o que
serve para acompanhar a anotação em andamento.

**O resultado do OE1 relatado no trabalho deve vir de uma execução estrita:**

```bash
docker compose exec app php artisan mitigations:agreement --strict
```

Com `--strict`, qualquer linha com só um anotador é erro, relatado com a linha,
e nada é gravado. O `oe1-agreement.json` registra se o cálculo foi estrito
(`strict`) e quantas linhas ficaram de fora (`left_out`); o relatado deve ter
`"strict": true`.

O resultado aparece em tabelas e é gravado em `oe1-agreement.json`: alfa de
Krippendorff com a distância MASI, intervalo de confiança de 95% por
reamostragem (semente fixa), situação em relação ao mínimo (0,667) e à meta
(0,800), e AC1 de Gwet, g de Guilford e concordância positiva por subdomínio.
O mesmo CSV gera sempre o mesmo arquivo. A anotação segue o guia em
`docs/catalog-annotation-guide.md`.

Os dados vêm do MIT AI Risk Initiative (airisk.mit.edu), sob CC BY 4.0. Os
nomes foram traduzidos e as descrições traduzidas e adaptadas pelo grupo.
