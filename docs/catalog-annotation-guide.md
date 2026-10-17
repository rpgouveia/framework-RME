# Guia de anotação dos subdomínios de risco do catálogo

> **Versão revisada após a medição do OE1.** As 40 mitigações do catálogo foram anotadas com a versão anterior deste guia, sem a seção "Revisão após a medição do OE1". Essa seção atende ao item 5 da decisão 0025: registra os subdomínios em que os anotadores mais divergiram e esclarece como marcá-los. Ela orienta anotações futuras e não foi aplicada às mitigações já anotadas.

Este guia orienta a anotação dupla do catálogo de mitigações (decisões 0024 e 0025). Cada mitigação recebe dois anotadores, de membros diferentes, que indicam de forma independente os subdomínios de risco do MIT AI Risk Repository que ela trata. A concordância entre os dois é a medida do OE1.

## Regra central

Marque **apenas os subdomínios cujo risco a mitigação reduz diretamente**, e não os que ela toca de passagem.

Pergunte: se esta mitigação for aplicada como descrita, qual risco fica menor, seja por ficar menos provável, seja por causar menos dano? Marque esse subdomínio. Um subdomínio que a mitigação só ajuda de forma indireta, que depende de outras ações para ser reduzido ou que aparece apenas como contexto na descrição não é marcado.

Exemplo: o registro das decisões de um sistema reduz diretamente a falta de transparência (7.4). Ele também pode ajudar, mais tarde, a investigar um caso de discriminação (1.1), mas não reduz esse risco por si só; por isso, marca-se 7.4, e não 1.1.

Em geral, uma mitigação trata um ou dois subdomínios. Se você marcou muitos, revise cada um pela regra central.

## Revisão após a medição do OE1

### O que a medição mostrou

O alfa de Krippendorff ficou em 0,527, com intervalo de confiança de 95% de 0,389 a 0,646, abaixo do mínimo de 0,667 (decisão 0025). Dois padrões explicam a maior parte das divergências:

- **Marcação além da regra central.** O anotador A marcou, em média, 3,05 subdomínios por mitigação, e o anotador B, 3,6 (122 e 144 marcações no total), quando a regra central espera, em geral, um ou dois. Na maioria das divergências, um anotador acrescentou subdomínios ao conjunto do outro.
- **Confusão entre a categoria da mitigação e o risco, e entre subdomínios vizinhos.** Mitigações de gestão de riscos, testes ou documentação receberam subdomínios ligados ao tipo de ação, e não ao risco reduzido.

O consenso seguiu o mesmo padrão: em 14 das 24 mitigações com divergência, registrou a união dos dois conjuntos, e 28 das 40 mitigações ficaram com três ou mais subdomínios.

### Subdomínios com mais divergência

Foram considerados problemáticos os subdomínios com concordância positiva abaixo de 0,70 e ao menos duas marcações divergentes, ou com ao menos cinco marcações divergentes. A decisão 0025 não fixou esse critério; ele foi definido depois da medição e serve apenas para escolher onde esclarecer o guia, sem alterar a medida.

| Subdomínio                                                 | Em comum | Só A | Só B | Concordância positiva |
| ---------------------------------------------------------- | -------- | ---- | ---- | --------------------- |
| 3.1 Informação falsa ou enganosa                           | 4        | 1    | 4    | 0,615                 |
| 3.2 Poluição do ecossistema de informação                  | 3        | 2    | 2    | 0,600                 |
| 4.1 Desinformação, vigilância e influência em larga escala | 7        | 1    | 4    | 0,737                 |
| 5.1 Dependência excessiva e uso inseguro                   | 1        | 1    | 3    | 0,333                 |
| 6.5 Falha de governança                                    | 23       | 0    | 8    | 0,852                 |
| 7.1 IA perseguindo objetivos próprios                      | 1        | 2    | 0    | 0,500                 |
| 7.2 IA com capacidades perigosas                           | 1        | 1    | 5    | 0,250                 |
| 7.3 Falta de capacidade ou robustez                        | 20       | 2    | 5    | 0,851                 |
| 7.4 Falta de transparência ou interpretabilidade           | 14       | 1    | 5    | 0,824                 |

Os valores vêm de `database/data/catalog-source/oe1-agreement.json`.

### Esclarecimentos

1. **A categoria não define o subdomínio.** A categoria de Saeri et al. descreve o tipo de ação (gestão de riscos, testes, documentação); o subdomínio descreve o risco reduzido. Uma mitigação de gestão de riscos não recebe 6.5 por ser de gestão, nem uma de documentação recebe 7.4 por documentar.
2. **6.5 Falha de governança.** Marque quando o objeto direto da mitigação é a própria estrutura de supervisão: políticas, papéis e responsabilidades, processos de gestão de riscos, prestação de contas e relato a autoridades. Não marque em mitigação técnica ou operacional apenas porque ela faz parte de uma boa governança, como testes, filtros de conteúdo ou monitoramento.
3. **7.1, 7.2 e 7.3.** A falha comum de desempenho ou de robustez é 7.3. Marque 7.2 só quando a mitigação limita ou avalia capacidades que podem causar dano em massa, como ataque cibernético, desenvolvimento de armas, persuasão em larga escala ou autorreplicação. Marque 7.1 só quando ela trata de comportamento do sistema em conflito com os objetivos de quem o projetou ou o usa. Na dúvida entre falha comum e capacidade perigosa, marque 7.3.
4. **Domínio 4 (agentes maliciosos e uso indevido).** Marque 4.1, 4.2 ou 4.3 só quando a mitigação dificulta o uso deliberado do sistema por agentes mal-intencionados. Medir o desempenho, documentar o sistema ou testar sua validade não reduz diretamente a desinformação nem a vigilância em larga escala.
5. **3.1 e 3.2.** Marque 3.1 quando a mitigação reduz a produção ou a propagação de informação incorreta pelo sistema, como a avaliação da veracidade das respostas. Marque 3.2 só quando ela atua no efeito agregado sobre o ecossistema de informação, como a identificação da origem de conteúdo gerado por IA em larga escala; reduzir erros individuais não basta.
6. **5.1 Dependência excessiva e uso inseguro.** Marque quando a mitigação atua sobre a confiança ou a dependência do usuário em relação ao sistema, como avisos sobre antropomorfização ou instruções sobre os limites de uso. Testes de veracidade ou de segurança não reduzem 5.1 diretamente.
7. **7.4 Falta de transparência ou interpretabilidade.** Marque quando a mitigação torna o funcionamento ou as decisões do sistema compreensíveis para quem o supervisiona ou é afetado por ele. Documentos internos de avaliação de risco não tornam o sistema mais interpretável.
8. **Consenso.** O consenso não é a união dos dois conjuntos. Depois da discussão, cada subdomínio entra apenas se os dois anotadores concordarem que a mitigação reduz diretamente aquele risco.

## Independência

- O anotador B não vê a coluna do anotador A. A coluna do outro anotador fica oculta ou é preenchida em cópia separada até os dois terminarem.
- Nenhuma ferramenta de IA é usada para sugerir ou conferir subdomínios. A IA pode ter apoiado a triagem e a tradução (decisão 0024), mas a anotação dos subdomínios é só humana.
- Os anotadores não conversam sobre as mitigações que estão anotando até registrarem as suas respostas. As dúvidas vão para a coluna "Observações", sem consultar o outro anotador.
- Depois que os dois terminam, as divergências são discutidas e o consenso é registrado em "Subdomínios: consenso". As colunas dos anotadores não são alteradas depois disso: elas são os dados da medida.

## Formato

- Use os códigos de nível 2 da lista abaixo (por exemplo, `2.2`), nunca só o domínio (`2`) nem o nome.
- Separe os códigos por vírgula e espaço, em ordem crescente: `1.1, 7.3`.
- Toda mitigação recebe ao menos um subdomínio.

O comando `php artisan mitigations:agreement` recusa célula sem código e código que não seja um subdomínio da lista, informando a linha.

## Calibração

Antes da anotação, o grupo faz uma rodada de calibração com mitigações elegíveis que **não** estão entre as 40 do catálogo, para alinhar a leitura da regra central e das definições. As mitigações medidas não são usadas na calibração (decisão 0025, item 7). Ajustes deste guia decorrentes da calibração são feitos antes de a anotação começar.

## Os 24 subdomínios

Lista gerada a partir do arquivo de taxonomia do projeto (`database/data/taxonomies/mit-ai-risk-domains.json`). Os nomes estão em português; entre parênteses, o nome original. As descrições são as originais de Slattery et al. (2026), Tabela 2.

### 1. Discriminação e toxicidade

- **1.1 Discriminação injusta e representação distorcida** (Unfair discrimination and misrepresentation). Unequal treatment of individuals or groups by AI, often based on race, gender, or other sensitive characteristics, resulting in unfair outcomes and unfair representation of those groups.
- **1.2 Exposição a conteúdo tóxico** (Exposure to toxic content). AI that exposes users to harmful, abusive, unsafe or inappropriate content. May involve providing advice or encouraging action. Examples of toxic content include hate speech, violence, extremism, illegal acts, or child sexual abuse material, as well as content that violates community norms such as profanity, inflammatory political speech, or pornography.
- **1.3 Desempenho desigual entre grupos** (Unequal performance across groups). Accuracy and effectiveness of AI decisions and actions is dependent on group membership, where decisions in AI system design and biased training data lead to unequal outcomes, reduced benefits, increased effort, and alienation of users.

### 2. Privacidade e segurança

- **2.1 Comprometimento da privacidade por obtenção, vazamento ou inferência de informações sensíveis** (Compromise of privacy by obtaining, leaking, or correctly inferring sensitive information). AI systems that memorize and leak sensitive personal data or infer private information about individuals without their consent. Unexpected or unauthorized sharing of data and information can compromise user expectation of privacy, assist identity theft, or cause loss of confidential intellectual property.
- **2.2 Vulnerabilidades de segurança e ataques a sistemas de IA** (AI system security vulnerabilities and attacks). Vulnerabilities that can be exploited in AI systems, software development toolchains, and hardware, resulting in unauthorized access, data and privacy breaches, or system manipulation causing unsafe outputs or behavior.

### 3. Informação incorreta

- **3.1 Informação falsa ou enganosa** (False or misleading information). AI systems that inadvertently generate or spread incorrect or deceptive information, which can lead to inaccurate beliefs in users and undermine their autonomy. Humans that make decisions based on false beliefs can experience physical, emotional, or material harms.
- **3.2 Poluição do ecossistema de informação e perda de uma realidade comum** (Pollution of information ecosystem and loss of consensus reality). Highly personalized AI-generated misinformation that creates “filter bubbles” where individuals only see what matches their existing beliefs, undermining shared reality and weakening social cohesion and political processes.

### 4. Agentes maliciosos e uso indevido

- **4.1 Desinformação, vigilância e influência em larga escala** (Disinformation, surveillance, and influence at scale). Using AI systems to conduct large-scale disinformation campaigns, malicious surveillance, or targeted and sophisticated automated censorship and propaganda, with the aim of manipulating political processes, public opinion, and behavior.
- **4.2 Ciberataques, desenvolvimento ou uso de armas e danos em massa** (Cyberattacks, weapon development or use, and mass harm). Using AI systems to develop cyber weapons (e.g., by coding cheaper, more effective malware), develop new or enhance existing weapons (e.g., Lethal Autonomous Weapons or chemical, biological, radiological, nuclear, and high-yield explosives), or use weapons to cause mass harm.
- **4.3 Fraudes, golpes e manipulação direcionada** (Fraud, scams, and targeted manipulation). Using AI systems to gain a personal advantage over others such as through cheating, fraud, scams, blackmail, or targeted manipulation of beliefs or behavior. Examples include AI-facilitated plagiarism for research or education, impersonating a trusted or fake individual for illegitimate financial benefit, or creating humiliating or sexual imagery.

### 5. Interação humano-computador

- **5.1 Dependência excessiva e uso inseguro** (Overreliance and unsafe use). Anthropomorphizing, trusting, or relying on AI systems by users, leading to emotional or material dependence and to inappropriate relationships with or expectations of AI systems. Trust can be exploited by malicious actors (e.g., to harvest information or enable manipulation), or result in harm from inappropriate use of AI in critical situations (e.g., medical emergency). Over reliance on AI systems can compromise autonomy and weaken social ties.
- **5.2 Perda de agência e autonomia humanas** (Loss of human agency and autonomy). Delegating by humans of key decisions to AI systems, or AI systems that make decisions that diminish human control and autonomy, potentially leading to humans feeling disempowered, losing the ability to shape a fulfilling life trajectory, or becoming cognitively enfeebled.

### 6. Danos socioeconômicos e ambientais

- **6.1 Centralização de poder e distribuição injusta de benefícios** (Power centralization and unfair distribution of benefits). AI-driven concentration of power and resources within certain entities or groups, especially those with access to or ownership of powerful AI systems, leading to inequitable distribution of benefits and increased societal inequality.
- **6.2 Aumento da desigualdade e piora na qualidade do emprego** (Increased inequality and decline in employment quality). Social and economic inequalities caused by widespread use of AI, such as by automating jobs, reducing the quality of employment, or producing exploitative dependencies between workers and their employers.
- **6.3 Desvalorização econômica e cultural do esforço humano** (Economic and cultural devaluation of human effort). AI systems capable of creating economic or cultural value, including through reproduction of human innovation or creativity (e.g., art, music, writing, coding, invention), destabilizing economic and social systems that rely on human effort. The ubiquity of AI-generated content may lead to reduced appreciation for human skills, disruption of creative and knowledge-based industries, and homogenization of cultural experiences.
- **6.4 Dinâmicas competitivas** (Competitive dynamics). Competition by AI developers or state-like actors in an AI “race” by rapidly developing, deploying, and applying AI systems to maximize strategic or economic advantage, increasing the risk they release unsafe and error-prone systems.
- **6.5 Falha de governança** (Governance failure). Inadequate regulatory frameworks and oversight mechanisms that fail to keep pace with AI development, leading to ineffective governance and the inability to manage AI risks appropriately.
- **6.6 Danos ambientais** (Environmental harm). The development and operation of AI systems that cause environmental harm, such as through energy consumption of data centers or the materials and carbon footprints associated with AI hardware.

### 7. Segurança, falhas e limitações de sistemas de IA

- **7.1 IA perseguindo objetivos próprios em conflito com objetivos ou valores humanos** (AI pursuing its own goals in conflict with human goals or values). AI systems that act in conflict with ethical standards or human goals or values, especially the goals of designers or users. These misaligned behaviors may be introduced by humans during design and development, such as through reward hacking and goal misgeneralisation, and may result in AI using dangerous capabilities such as manipulation, deception, or situational awareness to seek power, self-proliferate, or achieve other goals.
- **7.2 IA com capacidades perigosas** (AI possessing dangerous capabilities). AI systems that develop, access, or are provided with capabilities that increase their potential to cause mass harm through deception, weapons development and acquisition, persuasion and manipulation, political strategy, cyber-offense, AI development, situational awareness, and self-proliferation. These capabilities may cause mass harm due to malicious human actors, misaligned AI systems, or failure in the AI system.
- **7.3 Falta de capacidade ou robustez** (Lack of capability or robustness). AI systems that fail to perform reliably or effectively under varying conditions, exposing them to errors and failures that can have significant consequences, especially in critical applications or areas that require moral reasoning.
- **7.4 Falta de transparência ou interpretabilidade** (Lack of transparency or interpretability). Challenges in understanding or explaining the decision-making processes of AI systems, which can lead to mistrust, difficulty in enforcing compliance standards or holding relevant actors accountable for harms, and the inability to identify and correct errors.
- **7.5 Bem-estar e direitos da IA** (AI welfare and rights). Ethical considerations regarding the treatment of potentially sentient AI entities, including discussions around their potential rights and welfare, particularly as AI systems become more advanced and autonomous.
- **7.6 Riscos multiagentes** (Multi-agent risks). Risks from multi-agent interactions due to incentives (which can lead to conflict or collusion) and/or the structure of multi-agent systems, which can create cascading failures, selection pressures, new security vulnerabilities, and a lack of shared information and trust.
