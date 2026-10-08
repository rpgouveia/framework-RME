# 0021. Mudanças do sistema como gatilho de reavaliação

- **Situação:** Aceita; implementada na etapa 4d
- **Registrada em:** 2026-10-08

## Contexto

O RF05 lista quatro gatilhos explícitos de reavaliação: evento adverso, vencimento do prazo, nova versão do modelo e alteração relevante na base de dados. Os dois primeiros foram implementados na etapa 4b (0019); os dois últimos não tinham entidade, campo nem tela. Deixá-los fora enfraqueceria o atendimento ao requisito justamente onde o framework se diferencia.

## Decisão

1. **Registro manual de mudança do sistema:** entidade somente de inclusão, ligada ao sistema de IA, com tipo (nova versão do modelo ou alteração na base de dados), descrição obrigatória, data da mudança (não futura) e, opcionalmente, os subdomínios de risco afetados. A relevância da mudança fica declarada pelo próprio ato de registrar; o registro substitui, no protótipo, uma integração com os processos de desenvolvimento.
2. **Efeito:** o registro reverte, na mesma transação, os vínculos verificados e não cancelados do sistema, pelo caminho único de gravação (0007), com origem própria para cada tipo de mudança.
    - **Com subdomínios informados:** reversão dirigida, como no evento adverso (0019): só os vínculos cujo risco está em algum dos subdomínios.
    - **Sem subdomínios informados:** reversão de **todos** os vínculos verificados do sistema. As evidências foram produzidas sobre o estado anterior do sistema; sem análise do alcance da mudança, não há base para afirmar que alguma prova continua valendo.
3. **Confirmação:** antes de gravar, a tela informa quantos vínculos serão revertidos e lembra que informar os subdomínios afetados limita a reversão aos riscos realmente envolvidos.
4. **Risco não mapeado:** subdomínio informado numa mudança, sem risco cadastrado para o sistema, entra no alerta de risco não mapeado do painel (0019), identificado como vindo de mudança do sistema.
5. **Reavaliação:** os vínculos revertidos seguem a reavaliação da 0020. A análise de causa não se aplica a essas reversões, como no vencimento: não há dano cuja causa apurar.
6. **Faixa inaceitável:** o registro é permitido, para documentação; como esses sistemas não têm vínculos verificados, não há reversão.

## Consequências

- Os quatro gatilhos do RF05 passam a existir no protótipo.
- Incentivo: registrar uma mudança sem análise gera mais trabalho do que registrá-la com os subdomínios afetados, o que premia a equipe que estuda o impacto da mudança. O risco oposto, de a equipe deixar de registrar mudanças para evitar retrabalho, é de natureza organizacional e deve constar como limitação do protótipo.

## Implementação

Implementada na etapa 4d (2026-10-08).

- **Item 1 (registro):** tabela `system_changes`, somente de inclusão, com tipo (enum `SystemChangeType`: `model_version`, `data_change`), descrição, data da mudança e os subdomínios afetados na tabela `system_change_risk_subdomains`. Rotas `ai-systems.system-changes` restritas a `index`, `create`, `store` e `show`, aninhadas no sistema com `shallow`. A data futura é recusada pela validação (`before_or_equal:today`).
- **Item 2 (efeito):** a Action `RecordSystemChange` grava a mudança e, na mesma transação, reverte por `RecordStatusChange::revert()` os vínculos verificados e não cancelados do sistema: só os dos subdomínios informados, ou todos, se nenhum for informado (`RecordSystemChange::linksToRevert()`). Cada tipo tem sua origem em `ChangeOrigin` (`model_version`, `data_change`); a entrada de reversão fica sem responsável, com motivo automático citando o tipo e a data ("Nova versão do modelo de 11/05/2026.") e aponta para a mudança (`status_histories.system_change_id`).
- **Item 3 (confirmação):** a função pura `systemChangeReassessment()` (`resources/js/lib/system-change-reassessment.ts`) calcula, na tela, os vínculos que serão revertidos, a partir dos vínculos verificados enviados pelo controlador; o servidor refaz o cálculo ao gravar. O diálogo de confirmação lista os vínculos e, sem subdomínio informado, explica que todos os verificados serão revertidos e que informar os subdomínios limita a reversão.
- **Item 4 (risco não mapeado):** `MonitoringProtocol::unmappedRisks()` junta os subdomínios de eventos adversos e de mudanças do sistema sem risco cadastrado, agrupados por sistema e subdomínio, com as origens de cada caso (`sources`: `adverse_event`, `system_change`) e o registro mais recente de cada uma. O painel mostra a origem e leva ao evento ou à mudança.
- **Item 5 (reavaliação):** `RecordReassessment::causeApplies()` só pede análise de causa para evento adverso e reversão manual; nas reversões por mudança do sistema, a causa fica `not_applicable`. O destaque "Aguardando reavaliação" do vínculo e o contexto da reavaliação mostram a mudança, com link.
- **Item 6 (faixa inaceitável):** a Action grava a mudança e não reverte nada; o diálogo avisa disso.
- **Telas:** card "Mudanças do sistema" no detalhe do sistema, com a lista e o botão "Registrar mudança"; listagem paginada por sistema; cadastro com o seletor de subdomínios agrupado por domínio e com o perfil de risco do sistema primeiro (componente `RiskSubdomainPicker`, extraído do cadastro de evento adverso e compartilhado com ele); detalhe com os vínculos revertidos e o andamento da reavaliação ("2 de 4 reavaliados"). O painel e os chips da listagem de vínculos incluem as duas origens novas.
- **Relatório:** seção `system_changes` no JSON (tipo, descrição, data, subdomínios e vínculos revertidos); nas mudanças de verificação de cada vínculo, `system_change_id`. Exportação CSV à parte (`ai-systems/{id}/system-changes.csv`, `CompileTraceabilityReport::systemChangeRows()`), uma linha por mudança, inclusive as que não reverteram vínculos, com a coluna `unmapped_subdomains_at_export`, como no CSV de eventos adversos.
- **Seeders:** o `SystemChangeSeeder` cria, num sistema próprio ("Triagem automática de chamados"), uma alteração na base de dados com subdomínios (reversão dirigida, incluindo um subdomínio sem risco cadastrado, que aparece no alerta) e uma nova versão do modelo sem subdomínios (reverte os verificados restantes); e uma mudança num sistema da faixa inaceitável, sem reversão. Tudo pelas Actions, com viagem no tempo.

## Referências

- Especificação, RF05; Prototipação, Tela 5 (gatilhos de mudança na base de dados e nova versão do modelo).
- Decisões 0007, 0018, 0019 e 0020.
- Qualidade de dados invisível até a auditoria ou o incidente: WANG et al. (2026).
