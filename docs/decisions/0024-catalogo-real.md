# 0024. Catálogo real de mitigações (C2)

- **Situação:** Aceita; em execução (preenchimento pelo grupo)
- **Registrada em:** 2026-10-12

## Contexto

O catálogo de mitigações do protótipo é fictício (decisão 0010). Antes de construir o catálogo real, a fonte foi conferida na página oficial do projeto (airisk.mit.edu/blog/mapping-ai-risk-mitigations), com três achados.

1. **A base é pública, mas não mapeia mitigações para riscos.** A AI Risk Mitigation Database está disponível no Airtable. Cada mitigação tem identificador, nome (literal ou construído pelos autores) e descrição, e é classificada nas 23 subcategorias da taxonomia. Os próprios autores registram que muitas mitigações não especificam o risco que tratam e apontam o mapeamento entre mitigações e riscos como direção futura. Uma fonte secundária consultada antes afirmava que a base já trazia esse mapeamento; a página oficial a desmente. Os subdomínios de risco de cada mitigação são, portanto, contribuição do grupo, como já previa a decisão 0011.
2. **O recorte do catálogo responde a uma lacuna reconhecida pelos autores.** Os documentos da base focam organizações que desenvolvem e implantam os sistemas mais avançados, e os autores citam compradores e usuários de IA, como grandes organizações, entre os atores pouco cobertos. Selecionar mitigações aplicáveis a quem implanta e opera sistemas de IA é adaptar a base a esse ator.
3. **Os dados estão sob licença CC BY 4.0**, que exige dar o crédito e indicar as alterações. Traduzir e adaptar nomes e descrições é uma alteração.

Há ainda uma consequência para o OE1. A subcategoria de Saeri de cada mitigação já vem classificada na base; copiá-la não é classificação do grupo. O que o grupo classifica é o mapeamento de cada mitigação para os subdomínios de risco do MIT.

## Decisão

1. **Critério de inclusão**, com quatro condições obrigatórias:
    - **Ator:** aplicável a uma organização que implanta ou opera sistemas de IA, e não exclusiva de quem desenvolve modelos de fronteira;
    - **Acionável:** ação concreta, e não condição organizacional;
    - **Verificável:** é possível dizer que evidência comprovaria sua aplicação;
    - **Classificada:** tem subcategoria na taxonomia (as não classificadas ficam de fora).
2. **Redundância:** quando várias entradas descrevem a mesma ação, escolhe-se uma representativa, mantendo o identificador dela. Entradas não são fundidas, para preservar a rastreabilidade até a base.
3. **Tamanho e cobertura:** 40 mitigações, 10 por membro do grupo, com as subcategorias aplicáveis representadas, cada domínio de risco do MIT coberto por ao menos três mitigações e prioridade para os subdomínios dos incidentes reconstruídos no OE5.
4. **Triagem registrada:** cada entrada examinada é marcada como incluída ou excluída, com o motivo. O uso de IA como apoio é permitido, com validação humana integral, registrado por entrada e declarado no método, porque Saeri et al. relatam que assistentes de IA omitiram, fundiram e inventaram mitigações durante a extração.
5. **Rubricas das estimativas:**
    - **Custo:** quatro dimensões (tempo para implantar, pessoas e competências, ferramentas e serviços, impacto na operação), com níveis descritos; se as dimensões divergirem, vale o nível mais alto. Fundamento: avaliação qualitativa com níveis definidos por critérios explícitos (NIST SP 800-30 Rev. 1).
    - **Incerteza:** combinação da evidência (limitada, média, robusta) com a concordância entre as fontes (baixa, média, alta), por matriz, seguindo a abordagem do IPCC (Mastrandrea et al., 2010). A incerteza se refere às estimativas do grupo, e a fonte da estimativa funciona como o relato rastreável dessa avaliação.
6. **Medida do OE1:** a concordância entre anotadores é medida sobre o mapeamento de cada mitigação para os subdomínios de risco do MIT. Cada mitigação tem dois anotadores de membros diferentes, que anotam de forma independente; as divergências são discutidas e o consenso é registrado. A medida deve ser adequada a classificações com múltiplos rótulos.
7. **Ferramenta:** o trabalho é feito numa planilha compartilhada, com triagem, catálogo, rubrica e acompanhamento automático da cobertura. A folha do catálogo é exportada e convertida no arquivo do catálogo por um comando que aplica as validações do sistema.
8. **Licença:** os dados do MIT AI Risk Initiative são usados sob CC BY 4.0, com crédito no README e nos metadados do catálogo e a indicação de que nomes e descrições foram traduzidos e adaptados (adendo à 0023).

## Consequências

- O resultado do OE1 sai da própria construção do catálogo e responde a uma questão que os autores da base deixam em aberto.
- O critério de curadoria pode ser defendido no texto como adaptação da base a um ator que a literatura reconhece como pouco coberto.
- O catálogo real substitui o fictício; seeders e testes não devem depender de nomes de mitigações fictícias.

## Implementação

Planilha de trabalho `Catalogo_C2_Planilha_de_Trabalho.xlsx`, mantida pelo grupo.

- **Item 7 (importação):** o comando `php artisan mitigations:import-catalog` converte a folha **Catálogo**, exportada em CSV, em `database/data/mitigation-catalog.json`. O CSV fica versionado em `database/data/catalog-source/catalog.csv`, sem alterações, preservando os dados brutos da anotação dupla, base do OE1; os metadados editáveis (versão da base, data de acesso e critério de curadoria) ficam em `database/data/catalog-source/meta.json`. Só entram as linhas com situação "Concluída"; as demais são relatadas. O resultado passa pelas mesmas validações do catálogo, com os problemas relatados de uma vez pela linha do CSV, e nada é gravado enquanto houver algum. A geração é determinística.
- **Item 8 (licença):** os metadados gerados trazem o crédito ao MIT AI Risk Initiative sob CC BY 4.0 e a indicação das alterações, e marcam o catálogo como real (`fictional: false`).
- **Consequência (independência do catálogo fictício):** seeders e testes não dependem de nomes nem de identificadores das mitigações fictícias. Os seeders escolhem mitigações do catálogo carregado por posição ou sorteio, e os testes criam as suas pela factory.
- **Restrição da validação:** a chave do documento de origem de cada mitigação precisa estar entre os 13 documentos registrados na taxonomia de Saeri et al. (`database/data/taxonomies/saeri-mitigation-taxonomy.json`), os mesmos da lista da planilha.

## Referências

- SAERI, A. K. et al. Mapping AI Risk Mitigations: Evidence Scan and Preliminary AI Risk Mitigation Taxonomy. MIT FutureTech, 2025. Base e taxonomia em airisk.mit.edu (CC BY 4.0).
- MASTRANDREA, M. D. et al. Guidance Note for Lead Authors of the IPCC Fifth Assessment Report on Consistent Treatment of Uncertainties. IPCC, 2010.
- NIST. Guide for Conducting Risk Assessments. SP 800-30 Rev. 1, 2012.
- Decisões 0010, 0011, 0012 e 0023.
