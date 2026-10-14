# 0026. Definição operacional de evento adverso

- **Situação:** Aceita
- **Registrada em:** 2026-10-12

## Contexto

A definição de evento adverso é a base do protocolo de monitoramento (C3) e do OE3, e seus critérios serão avaliados pelo painel de especialistas (OE6). As decisões 0015 e 0019 já tinham fixado a classificação por subdomínio, a natureza do evento (incidente ou quase-incidente) e o tratamento do evento interceptado por mitigação. Restavam três pontos: quem pode sofrer o dano, o que conta como operação e o posicionamento da definição em relação à literatura.

As referências divergem sobre o primeiro ponto. A OCDE (2024) define incidente de IA pelo dano a pessoas ou grupos, a infraestrutura crítica, a direitos e às obrigações legais que os protegem, e à propriedade, a comunidades ou ao meio ambiente. O EU AI Act segue a mesma lista para o incidente grave. O NIST AI RMF (2023) separa três destinatários: pessoas, organização e ecossistema.

## Decisão

1. **Destinatários do dano:** os três do NIST AI RMF. O dano pode recair sobre pessoas ou grupos, sobre a organização que opera o sistema (operações do negócio, segurança, perdas financeiras e reputação) ou sobre o ecossistema, incluindo o meio ambiente. O risco de registrar oscilações comuns do negócio é contido pelo critério de origem (C2): só conta o dano que decorre do funcionamento, do uso ou de falha do sistema de IA.
2. **Operação:** conta como operação todo uso real cujos efeitos alcançam pessoas ou bens reais, inclusive piloto, implantação em etapas e uso interno por funcionários. Ocorrências em testes, avaliações ou exercícios controlados são resultados de teste. Um exercício de red teaming autorizado contra o sistema em produção é teste, mas, se expuser dados reais, o vazamento é evento adverso.
3. **Posicionamento:** a definição é apresentada como adaptação da definição de incidente da OCDE (2024), da qual adota o critério no efeito, a origem no desenvolvimento, uso ou mau funcionamento, e a distinção entre incidente e perigo. Amplia os destinatários com o NIST AI RMF (2023); incorpora o dano sem defeito técnico de Rismani et al. (2023), os quase-incidentes da subcategoria 4.3 de Saeri et al. (2025), a lacuna e as práticas relatadas por Olisaeloka et al. (2026) e o limiar de registro da ICH E2A. Em relação ao EU AI Act, o incidente grave é um subconjunto do evento adverso: todo incidente grave é evento adverso, mas não o contrário, e o framework não substitui a notificação legal.
4. **Critérios:** a definição tem seis critérios. Mudam o C1 (operação, item 2) e o C3 (destinatários, item 1); C2, C4, C5 e C6 permanecem. Redação sintética:

   > Evento adverso é toda ocorrência observada em um sistema de IA em operação, com efeitos sobre pessoas ou bens reais, decorrente de seu funcionamento, de seu uso ou de falha, que causou dano (incidente) ou poderia plausivelmente tê-lo causado (quase-incidente) a pessoas ou grupos, à organização que o opera ou ao ecossistema, e que pode ser associada a ao menos um subdomínio de risco da taxonomia adotada. O registro não exige prova de causalidade: basta a suspeita fundamentada, sendo a relação causal apurada na reavaliação do vínculo. A ocorrência interceptada por uma mitigação antes de causar dano é registrada como quase-incidente.

5. **Itens do painel:** para o painel de especialistas, o C3 é desdobrado em dois itens, o efeito (C3a: dano ocorrido ou plausível) e os destinatários (C3b), totalizando sete itens. Num painel pequeno, um único especialista em desacordo pode reprovar um item, e a separação faz uma eventual reprovação apontar exatamente o que foi rejeitado.

## Consequências

- A definição sai de PENDENTES.md e fica pronta para o painel.
- Fundamentação corrigida: Subasri et al. (2025) deixa de fundamentar o quase-incidente, papel que passa à subcategoria 4.3 de Saeri et al. (2025).
- O texto do trabalho deve declarar que a generalização a partir de Olisaeloka et al. (2026), cujo escopo é IA generativa em saúde mental, é do framework.
- O texto público da página inicial do protótipo sobre eventos adversos pode ser revisto à luz da definição.

## Implementação

Não há mudança de código: a definição orienta o registro e a avaliação. O documento do C3 (versão 6) traz os critérios, o posicionamento e os itens do painel nas Seções 5.7 e 5.9 a 5.11. A inclusão do texto da definição no arquivo versionado do protocolo, prevista na decisão 0017, fica para quando o grupo quiser exibi-la na tela de registro de evento.

## Referências

- OECD. Defining AI incidents and related terms. OECD Artificial Intelligence Papers, n. 16. Paris: OECD Publishing, 2024. DOI: 10.1787/d1a8d965-en.
- NIST. Artificial Intelligence Risk Management Framework (AI RMF 1.0). NIST AI 100-1, 2023. DOI: 10.6028/NIST.AI.100-1.
- UNIÃO EUROPEIA. Regulamento (UE) 2024/1689 (EU AI Act), art. 3, ponto 49, e art. 73.
- RISMANI, S. et al. (2023); SAERI, A. K. et al. (2025); OLISAELOKA, L. et al. (2026); ICH E2A; SCHUETT, J. et al. (2023).
- Documento do C3, versão 6, Seções 5.3, 5.7 e 5.9 a 5.11; decisões 0015, 0017 e 0019.
