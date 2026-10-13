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

Os dados vêm do MIT AI Risk Initiative (airisk.mit.edu), sob CC BY 4.0. Os
nomes foram traduzidos e as descrições traduzidas e adaptadas pelo grupo.
