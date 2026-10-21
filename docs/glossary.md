# Glossário do Framework RME

Fonte única da terminologia do projeto, para o artigo, a interface, a documentação e o protocolo do painel de especialistas. Cada termo traz o nome no código, quando existe, e a fonte da definição: uma decisão desta pasta ou uma referência externa. A terminologia foi fixada pela decisão [0028](decisions/0028-glossario-e-terminologia.md).

Quando o código ainda não reflete a definição, a entrada indica a diferença em **Situação no código**.

## 1. Sistema de IA

- **Sistema de IA** (`AiSystem`): sistema projetado que gera saídas, como conteúdo, previsões, recomendações ou decisões, para um conjunto de objetivos definidos por pessoas (ISO/IEC 22989, 3.1.4). É o objeto que o framework acompanha: todo o ciclo parte de um sistema cadastrado, ao qual se ligam riscos, vínculos, evidências, eventos e mudanças.
- **Faixa de risco** (`category`, enum `AiSystemCategory`): classificação do sistema segundo o EU AI Act. Define a intensidade do monitoramento, pelo intervalo de revisão (0016 e 0017). Valores:
    - **Inaceitável:** prática proibida (art. 5). O sistema pode ser cadastrado e ter vínculos planejados, mas nunca é considerado em operação: não há verificação nem revisão periódica (0017).
    - **Alto:** sistema de alto risco (art. 6 e Anexo III).
    - **Limitado:** sistema sujeito a obrigações de transparência (art. 50).
    - **Mínimo:** demais sistemas.
    - O termo "categoria" fica reservado às mitigações (seção 3).
- **Origem do sistema** (`source_type`, enum `SystemSourceType`): a relação da organização com o sistema, que indica quanto controle ela tem sobre ele. Os valores seguem os papéis da ISO/IEC 22989 (5.19), em que a mesma organização pode acumular papéis, e as formas de obter componentes previstas no projeto do sistema (6.2.3: desenvolver, comprar pronto ou usar código aberto).
    - **Interno:** a organização é a produtora do sistema: projeta, desenvolve, testa e implanta.
    - **Terceiros:** a organização é cliente de um provedor, que fornece o sistema como produto ou serviço.
    - **Código aberto:** a organização incorpora ou adapta um modelo ou software de código aberto.
    - **Híbrido:** a organização combina componente próprio com componente de terceiros ou de código aberto, como um modelo de fornecedor ajustado com dados próprios.
- **Domínio de aplicação** (`application_domain`): área em que o sistema é usado, como saúde ou crédito. Campo livre e opcional, sem efeito na classificação (0016).
- **Perfil de risco:** conjunto dos subdomínios dos riscos cadastrados para o sistema. Define os eventos esperados (0016).

## 2. Risco

- **Risco** (`Risk`): possibilidade de dano associada a um sistema de IA, com nome único no sistema. Na ISO/IEC 22989 (3.5.11, a partir da ISO 31000), risco é o efeito da incerteza sobre objetivos, que pode ser positivo ou negativo; o framework trata só do efeito negativo, o dano. Depois do primeiro vínculo, o sistema do risco não muda (0003).
- **Subdomínio de risco** (`risk_subdomain_id`): classificação do risco num dos 24 subdomínios do MIT AI Risk Repository, agrupados em 7 domínios; o domínio é derivado (0012). Também classifica eventos adversos, mudanças do sistema e os riscos-alvo das mitigações.
- **Fase do ciclo de vida** (`lifecycle_phase`, enum `LifecyclePhase`): fase do sistema, segundo as oito fases da ISO/IEC 22989 (6.2). No risco, é a fase em que ele foi identificado; no vínculo, a fase em que a mitigação é aplicada; na reavaliação, a fase em que a causa se originou. A norma não prescreve um modelo único: as fases podem se repetir ao longo da vida do sistema, como projeto e desenvolvimento e implantação a cada correção ou atualização (6.1). Valores:
    - **Concepção:** a decisão de transformar uma ideia num sistema, com objetivos, requisitos, gestão de riscos, custos, recursos e análise de viabilidade (6.2.2).
    - **Projeto e desenvolvimento:** a criação do sistema, com abordagem, arquitetura, código, dados de treinamento e implementação dos controles do plano de tratamento de riscos (6.2.3). Inclui a coleta e a preparação dos dados.
    - **Verificação e validação:** a confirmação de que o sistema funciona conforme os requisitos e atende aos objetivos, com testes, aceite e revisão dos resultados frente aos riscos (6.2.4).
    - **Implantação:** a instalação, liberação ou configuração do sistema no ambiente de operação (6.2.5).
    - **Operação e monitoramento:** o sistema em funcionamento e disponível para uso, com monitoramento, reparos, atualizações e suporte (6.2.6).
    - **Validação contínua:** extensão da operação para sistemas com aprendizado contínuo, em que o treinamento incremental acontece em produção e o funcionamento é conferido continuamente com dados de teste (6.2.7 e 3.1.9). Só se aplica a esses sistemas.
    - **Reavaliação do sistema:** a avaliação dos resultados da operação frente aos objetivos e aos riscos, que pode levar ao refinamento de objetivos e requisitos (6.2.8). Não confundir com a reavaliação do vínculo (seção 6).
    - **Retirada:** o fim do sistema, por descomissionamento e descarte, inclusive dos dados, ou por substituição do sistema ou de componentes (6.2.9).
- **Incerteza** (`uncertainty_level`, enum `UncertaintyLevel`): grau de confiança numa avaliação, em três níveis (baixa, média, alta). Incerteza alta significa confiança baixa. O nível resulta da matriz da rubrica (seção 3), que combina a evidência disponível (limitada, média, robusta) com a concordância entre as fontes (baixa, média, alta), na abordagem do IPCC (Mastrandrea et al., 2010). O conceito é um só; muda o objeto avaliado:
    - **No risco:** confiança em que o risco existe no sistema e na forma como se materializaria. Evidência limitada: hipótese levantada pela equipe, sem ocorrência conhecida. Média: incidentes em sistemas semelhantes, ou norma ou guia que aponte o risco. Robusta: ocorrência no próprio sistema, resultado de teste ou de auditoria.
    - **Na mitigação:** confiança nas estimativas do catálogo (risco-alvo, evidência esperada e custo), e não na mitigação em si (0024).
    - Não confundir com a incerteza da definição de risco da ISO/IEC 22989 (3.5.11), que é a incerteza sobre os próprios eventos e consequências. A incerteza do framework é sobre a avaliação feita pela organização.

## 3. Mitigação e catálogo

- **Mitigação** (`Mitigation`): medida que reduz um ou mais riscos de sistemas de IA. Entrada somente de consulta do catálogo (0010).
- **Catálogo de mitigações (C2):** as 40 mitigações curadas pelo grupo a partir da base de Saeri et al. (2025), com as estimativas do framework (0024).
- **Categoria e subcategoria de Saeri** (`saeri_subcategory_id`): classificação da mitigação pela taxonomia de Saeri et al. (2025). A mitigação recebe a subcategoria; a categoria é derivada (0011). As quatro categorias são governança e supervisão; técnico e segurança; processos operacionais; transparência e responsabilização.
- **Risco-alvo** (`targetRiskSubdomains`): subdomínios de risco que a mitigação reduz diretamente, segundo a regra central do guia de anotação (0025). Na criação de vínculo, orientam as mitigações recomendadas (0012).
- **Evidência esperada** (`expected_evidence`): o que demonstraria que a mitigação foi aplicada.
- **Fonte da estimativa** (`estimate_source`): relato rastreável da avaliação da evidência e da concordância que sustenta as estimativas, separado da fonte da mitigação (0011).
- **Rubrica de custo e incerteza:** critérios explícitos para cada nível de custo e de incerteza, definidos na 0024, item 5, para que dois avaliadores cheguem ao mesmo nível pela mesma mitigação.
- **Custo** (enum `CostLevel`): nível qualitativo (baixo, médio, alto) do esforço de implementação de uma mitigação, não só financeiro. É avaliado pelas quatro dimensões da rubrica; se elas divergirem, vale o nível mais alto:

    | Dimensão               | Baixo                                             | Médio                                                                 | Alto                                                                 |
    | ---------------------- | ------------------------------------------------- | --------------------------------------------------------------------- | -------------------------------------------------------------------- |
    | Tempo para implantar   | Até cerca de duas semanas                         | De algumas semanas a cerca de três meses                              | Mais de três meses                                                   |
    | Pessoas e competências | A própria equipe do sistema, sem competência nova | Outra área ou competência específica (jurídico, segurança, dados)     | Equipe dedicada, contratação ou especialista externo                 |
    | Ferramentas e serviços | Nenhuma ferramenta ou serviço novo pago           | Ferramenta ou serviço de custo moderado, ou ampliação de um existente | Infraestrutura, ferramenta ou serviço dedicado, ou auditoria externa |
    | Impacto na operação    | Não altera processos de outras áreas              | Altera um processo existente                                          | Muda a arquitetura do sistema ou processos de várias áreas           |

    Os três custos usam a mesma rubrica e mudam só a referência, o que os torna comparáveis:
    - **Custo sugerido** (`mitigations.suggested_cost`): esforço típico de uma organização que implanta a mitigação pela primeira vez. Estimado no catálogo.
    - **Custo estimado** (`links.estimated_cost`): esforço previsto por esta organização para este vínculo, antes de implementar. Editável no vínculo (0005).
    - **Custo observado** (`evidence.observed_cost`): esforço efetivamente empregado, avaliado ao registrar uma evidência. Opcional; o do vínculo é o da evidência mais recente que o informou (0018, item 9).

## 4. Vínculo

- **Vínculo** (`Link`): aplicação de uma mitigação a um risco de um sistema, sob um responsável. O par risco e mitigação é único (0004), e o vínculo é permanente: não se exclui, se cancela (0006).
- **Responsável** (`Owner`): papel organizacional, identificado por cargo e área, e não por pessoa (0009). Responde pelo vínculo e assina verificações, reversões manuais e reavaliações.
- **Progresso** (`status`, enum `LinkStatus`): andamento da implementação da mitigação. É uma das duas dimensões do vínculo, independente da verificação (0013). Valores, por critério de entrada:
    - **Planejado:** vínculo criado, implementação não iniciada. É o status inicial (0005).
    - **Em andamento:** implementação iniciada e não concluída.
    - **Implementado:** implementação concluída; mitigação em operação.
    - **Suspenso:** mitigação interrompida temporariamente, com intenção de retomar.
    - **Cancelado:** vínculo encerrado. Sai da revisão periódica, não pode ser verificado nem reavaliado e só pode voltar a planejado.
    - O acompanhamento de uma mitigação em operação não é um status de progresso: é representado pela verificação e pelo ciclo de revisão (vínculo monitorável, seção 5).
- **Verificação** (`verification_status`, enum `VerificationStatus`): se a implementação está comprovada por evidência. É a segunda dimensão do vínculo (0013).
    - **Declarada:** a organização afirma a mitigação, sem comprovação vigente. É o estado inicial e o estado depois de toda reversão.
    - **Verificada:** comprovada por evidência registrada depois da última reversão (no primeiro ciclo, qualquer evidência), com um responsável que assina a verificação (0013 e 0018).
    - O termo segue a ISO/IEC 22989 (3.5.17), em que verificação é a confirmação, por evidência objetiva, de que requisitos especificados foram atendidos. No framework, o requisito é a implementação da mitigação no vínculo.

## 5. Evidência e histórico

- **Evidência** (`Evidence`): registro do que comprova a implementação ou o funcionamento de uma mitigação num vínculo. Somente de inclusão; um registro errado se corrige com outro (0008).
- **Tipo de evidência** (`type`, enum `EvidenceType`): a natureza do registro, pelo que ele comprova.
    - **Documento:** política, procedimento ou especificação que estabelece como a mitigação deve funcionar.
    - **Relatório:** resultado de uma análise ou avaliação.
    - **Log de auditoria:** registro automático produzido pelo sistema.
    - **Resultado de teste:** saída de um teste executado.
    - **Certificação:** atestado emitido por terceiro.
    - **Ata de reunião:** registro de uma decisão tomada em reunião.
- **Histórico de status** (`StatusHistory`): registro somente de inclusão de toda mudança de progresso ou de verificação do vínculo. Cada registro altera uma única dimensão (0007 e 0013).
- **Origem do registro** (`origin`, enum `ChangeOrigin`): o que provocou um registro do histórico: manual, vencimento da revisão, evento adverso, reclassificação do sistema, nova versão do modelo ou alteração na base de dados. Registros automáticos não têm responsável; os manuais têm (0018, item 5).
- **Reversão:** volta de um vínculo verificado a declarado. Pode ser automática, por um gatilho de reavaliação, ou manual, com motivo e responsável. Exige evidência nova para verificar de novo (0018).
- **Renovação:** nova verificação de um vínculo ainda verificado, antes do vencimento, com evidência gravada depois da última verificação. Recomeça a contagem (0019, item 2).
- **Data de revisão** (`next_review_date`): último dia válido da verificação: data da verificação mais o intervalo da faixa. Vínculos declarados não têm data de revisão (0017 e 0018).
- **Intervalo de revisão:** prazo de validade da verificação, por faixa de risco: 90 dias para alto, 180 para limitado e 365 para mínimo (0017).
- **Vínculo monitorável:** vínculo não cancelado, verificado, com data de revisão e de sistema fora da faixa inaceitável. É a regra única da revisão periódica (0017).

## 6. Monitoramento e reavaliação

- **Gatilho de reavaliação:** fato que reverte vínculos verificados: vencimento da revisão, evento adverso, reclassificação do sistema para a faixa inaceitável, mudança do sistema ou decisão manual (0019 e 0021).
- **Reversão dirigida:** reversão limitada aos vínculos cujo risco está nos subdomínios informados num evento adverso ou numa mudança do sistema (0019, item 3, e 0021).
- **Risco não mapeado:** subdomínio informado num evento ou numa mudança sem risco cadastrado para o sistema. Gera um alerta na tela de acompanhamento (0019, item 4, e 0021, item 4).
- **Aguardando reavaliação:** vínculo declarado cuja última reversão ainda não tem reavaliação concluída (0020, item 4).
- **Aguardando verificação:** vínculo declarado sem reversão pendente, que aguarda a primeira verificação ou foi reavaliado sem verificar (0020, item 4).
- **Reavaliação** (`Reassessment`): decisão registrada sobre um vínculo revertido, com desfecho, responsável, justificativa e, quando couber, análise de causa. Somente de inclusão (0020). Não confundir com a fase de reavaliação do sistema (seção 2).
- **Desfecho** (`outcome`, enum `ReassessmentOutcome`) (0020, item 3):
    - **Manter:** conclui a reavaliação e verifica o vínculo no mesmo ato, com evidência posterior à reversão.
    - **Ajustar:** altera responsável, custo estimado, fase ou progresso e registra o que mudou; a verificação no mesmo ato é opcional.
    - **Substituir:** cancela o vínculo e leva à criação de outro para o mesmo risco, que registra qual substitui.
    - **Encerrar:** cancela o vínculo, sem substituto.
- **Análise de causa** (`cause_status`, enum `CauseStatus`): **apurada**, com a causa e a fase de origem; **não apurada**, por escolha explícita; ou **não se aplica**, quando a reversão não decorre de dano, como no vencimento ou na mudança do sistema (0020, item 6, e 0021, item 5).

## 7. Eventos e mudanças do sistema

- **Evento adverso** (`AdverseEvent`): ocorrência observada num sistema de IA em operação, decorrente de seu funcionamento, uso ou falha, que causou ou poderia plausivelmente ter causado dano a pessoas, à organização ou ao ecossistema, associável a ao menos um subdomínio da taxonomia. Basta a suspeita fundamentada para registrar. Somente de inclusão. Definição operacional completa, com os critérios C1 a C6, na decisão 0026.
- **Natureza do evento** (`nature`, enum `AdverseEventNature`):
    - **Incidente:** a ocorrência causou dano.
    - **Quase-incidente:** a ocorrência poderia plausivelmente ter causado dano e não causou, inclusive quando barrada por uma mitigação.
- **Vínculo interceptador** (`intercepting_link_id`): num quase-incidente, o vínculo cuja mitigação barrou a ocorrência. Não é revertido, porque o evento demonstra que ele funcionou (0019, item 6).
- **Data de ocorrência e data de detecção** (`occurrence_date`, `detected_at`): quando o evento ocorreu e quando foi percebido. O intervalo entre as duas mede a demora do monitoramento (0019, item 8).
- **Mudança do sistema** (`SystemChange`): registro de uma nova versão do modelo ou de uma alteração na base de dados. Somente de inclusão. Reverte os vínculos verificados do sistema, de forma dirigida quando os subdomínios afetados são informados (0021).

## 8. Instrumentos e telas

- **Tela de acompanhamento:** tela inicial do protótipo, com as pendências de verificação e de reavaliação, os alertas de risco não mapeado e os vencimentos.
- **Relatório de rastreabilidade:** exportação, por sistema, de riscos, mitigações, responsáveis, evidências, histórico, reavaliações, eventos adversos e mudanças do sistema, em CSV e JSON.
- **Painel de especialistas:** avaliação do artefato por especialistas externos, com o Content Validity Ratio, planejada no OE6 e não executada neste trabalho (0027). O termo "painel" sem complemento não é usado.

## Referências

- UNIÃO EUROPEIA. Regulamento (UE) 2024/1689 (EU AI Act), art. 5, art. 6, Anexo III e art. 50.
- ISO; IEC. ISO/IEC 22989:2022: Information technology. Artificial intelligence. Artificial intelligence concepts and terminology. Genebra: ISO, 2022. Itens 3.1.4, 3.1.9, 3.5.11, 3.5.17, 5.19 e 6.
- MASTRANDREA, M. D. et al. Guidance Note for Lead Authors of the IPCC Fifth Assessment Report on Consistent Treatment of Uncertainties. IPCC, 2010.
- NATIONAL INSTITUTE OF STANDARDS AND TECHNOLOGY. Guide for Conducting Risk Assessments. NIST SP 800-30 Rev. 1. Gaithersburg: NIST, 2012.
- SAERI, A. K. et al. Mapping AI Risk Mitigations: Evidence Scan and Preliminary AI Risk Mitigation Taxonomy. MIT FutureTech, 2025. Base e taxonomia em airisk.mit.edu (CC BY 4.0).
- SLATTERY, P. et al. (2026), _Patterns_: MIT AI Risk Repository, conforme a decisão 0012.
