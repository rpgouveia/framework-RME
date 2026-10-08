# 0019. Gatilhos de reavaliação e eventos adversos

- **Situação:** Aceita; implementada na etapa 4b
- **Registrada em:** 2026-10-08

## Contexto

Com a etapa 4a, a verificação só mudava por ação manual. O RF05 exige reavaliação acionada por gatilhos explícitos, e a decisão 0018 definiu que a reversão para declarada é a própria sinalização. Faltava definir quando cada gatilho age, sobre quais vínculos, e como o evento adverso é registrado.

## Decisão

1. **Vencimento:** a data de revisão é o último dia válido da verificação. A reversão ocorre na primeira execução diária posterior a ela, com origem "vencimento", sem responsável e com motivo automático que informa a data vencida. No próprio dia, o vínculo aparece como "vence hoje".
2. **Renovação antes do vencimento:** um vínculo verificado pode ser renovado se houver evidência gravada depois da última verificação, com as mesmas regras da verificação (responsável ativo escolhido, sistema operável, vínculo não cancelado). A renovação gera registro próprio no histórico e recomeça a contagem.
3. **Evento adverso, reavaliação dirigida:** ao registrar o evento, voltam a declarados, na mesma transação, os vínculos verificados do sistema cujo risco está em algum dos subdomínios do evento. A confirmação do registro informa quantos vínculos serão revertidos. Eventos registrados com atraso revertem os vínculos verificados no momento do registro, independentemente da data da última verificação; a reavaliação julga se ela já considerava o ocorrido.
4. **Risco não mapeado:** evento num subdomínio em que o sistema não tem risco cadastrado gera um alerta no painel, com atalho para cadastrar o risco já com sistema e subdomínio preenchidos. O alerta é calculado e desaparece quando um risco daquele subdomínio é cadastrado para o sistema.
5. **Natureza do evento:** campo obrigatório, incidente ou quase-incidente. Ambos disparam a reavaliação dirigida.
6. **Evento interceptado por mitigação:** num quase-incidente, campo opcional com o vínculo cuja mitigação interceptou a ocorrência, do mesmo sistema. Esse vínculo não é revertido, porque o evento demonstra que ele funcionou; o detalhe do evento sugere registrar uma evidência nele, para que o funcionamento seja comprovado. Os demais vínculos do subdomínio são revertidos normalmente.
7. **Gravidade:** não há, por ora. Todo evento dispara a reavaliação dirigida. A questão será reavaliada após o MVP.
8. **Data de detecção:** campo opcional, entre a data de ocorrência e hoje. O intervalo entre as duas mede a demora do monitoramento em perceber os problemas.
9. **Reclassificação para a faixa inaceitável:** reverte todos os vínculos verificados do sistema na mesma transação da edição, com origem "reclassificação"; o formulário avisa antes de salvar quantos serão revertidos.
10. **Registro de eventos não restrito:** qualquer sistema pode registrar eventos. A prototipação (Tela 4) restringia aos sistemas em operação, definidos como os que têm ao menos um vínculo verificado; essa regra impediria novos eventos depois que um evento revertesse todos os vínculos do sistema, e impediria eventos em sistemas já em produção ainda sem verificação. Um campo explícito de situação operacional do sistema será reavaliado após o MVP.
11. **Agendador e painel:** serviço de agendamento no `compose.yml`; o comando `links:flag-due-for-review` passa a reverter de fato, de forma idempotente. No painel, o indicador de revisões vencidas, que ficaria quase sempre zerado, dá lugar a "aguardando reavaliação" separado por origem (vencimento, evento adverso, reclassificação, manual).

## Consequências

- Todas as reversões automáticas passam pelo caminho único de gravação (0007), com a origem da 0018.
- Fora do ambiente de desenvolvimento, os gatilhos de vencimento dependem de agendamento no servidor, o que deve constar da especificação.
- Diverge da prototipação na Tela 4 (item 10), com motivo registrado.

## Implementação

Implementada na etapa 4b (2026-10-08). Todas as reversões passam por `RecordStatusChange::revert()` (0007), sem responsável, com a origem da 0018 e motivo automático; o vínculo volta a declarado e `next_review_date` fica nula.

- **Item 1 (vencimento):** a Action `RevertOverdueLinks` reverte os vínculos do escopo `Link::dueForReview()`, que passou a ser monitorável com `next_review_date` anterior a hoje: a data é o último dia válido. O motivo informa a data vencida. O comando `links:flag-due-for-review` usa a Action e é idempotente, porque um vínculo revertido deixa de ser monitorável. A interface mostra "Vence hoje" (`ReviewDate`).
- **Item 2 (renovação):** `RecordStatusChange::verify()` aceita vínculo já verificado e grava o registro verificada → verificada, exibido como "Renovação da verificação". A regra de evidência ficou unificada em `verificationProblem()`: conta a evidência gravada depois da última mudança de verificação (reversão, verificação ou renovação), e qualquer uma se nunca houve mudança. `StatusHistory::supportingEvidence()` usa a mesma fronteira. O CHECK de uma dimensão por registro já aceitava o caso nos dois bancos, sem ajuste. O card de verificação tem o botão "Renovar verificação", desabilitado com o motivo.
- **Itens 3 e 6 (reavaliação dirigida e interceptador):** a Action `RecordAdverseEvent` grava o evento e, na mesma transação, reverte os vínculos `verified()` do sistema com risco nos subdomínios do evento, exceto o interceptador (`linksToRevert()`). A lista é recalculada na transação; o formulário a antecipa no diálogo de confirmação com a função pura `eventReassessment()` em `resources/js/lib/event-reassessment.ts`. `AdverseEvent::reversals()` lista as reversões no detalhe do evento.
- **Item 4 (risco não mapeado):** `MonitoringProtocol::unmappedRisks()` calcula, sem armazenar, os pares de sistema e subdomínio de eventos sem risco cadastrado, com contagem e evento mais recente; `unmappedSubdomainCodes()` marca os subdomínios no detalhe do evento. O painel tem o cartão "Riscos não mapeados" com atalho para `risks/create?ai_system=&subdomain=`, que pré-preenche sistema, domínio e subdomínio.
- **Itens 5, 6 e 8 (campos do evento):** `adverse_events.nature` (enum `AdverseEventNature`), `detected_at` e `intercepting_link_id`. A migration de eventos passou a rodar depois da de vínculos, por causa da chave estrangeira. A validação aceita o interceptador só em quase-incidente, do mesmo sistema, não cancelado e com risco em subdomínio do evento; a detecção, entre a ocorrência e hoje. A listagem filtra por natureza.
- **Item 9 (reclassificação):** a Action `UpdateAiSystem` reverte, na mesma transação, os vínculos verificados do sistema que vai para a faixa inaceitável; a edição recebe `verifiedLinksCount` e avisa antes de salvar. Os vínculos revertidos assim ficam em "Aguardando reavaliação" (escopo `awaitingReassessment()` sem a exclusão de sistemas inaceitáveis), embora não possam ser verificados de novo.
- **Item 10:** o registro de eventos continua aberto a qualquer sistema.
- **Item 11 (agendador e painel):** serviço `scheduler` no `compose.yml` (`php artisan schedule:work`); o README explica que, em produção, é preciso um agendamento no servidor. O painel perdeu o indicador de revisões vencidas e ganhou "Aguardando reavaliação" por origem (escopo `revertedBy()`), com link para a listagem filtrada (`?verification=awaiting_reassessment&origin=`); as próximas revisões incluem as que vencem hoje.
- **Relatório:** a origem de cada mudança de verificação (`verification.changes` no JSON, coluna `verification_changes` no CSV). O relatório não exporta eventos.
- **Seeders:** eventos antigos pela Action, nos subdomínios dos riscos de cada sistema (`AdverseEventSeeder`); histórias de vínculo com renovação, vencimento hoje e reversão por vencimento (`LinkSeeder`); e o `ReassessmentSeeder`, com incidente, quase-incidente interceptado, risco não mapeado e sistema reclassificado depois de ter vínculos verificados.

## Referências

- Especificação, RF04, RF05 e RF09; Prototipação, Telas 4 e 5; decisões 0007, 0013, 0015, 0016, 0017 e 0018.
- Quase-incidentes: SAERI et al. (2025), subcategoria 4.3, que inclui explicitamente quase-incidentes; OCDE (2024), perigo de IA; OLISAELOKA et al. (2026), monitoramento de eventos potenciais no Woebot.
- Evento interceptado: OLISAELOKA et al. (2026), intervenções humanas no Therabot contadas como eventos adversos.
- Data de detecção: HE, PAN e CHU (2026), dimensão de detecção.
- Escalada de riscos de curto prazo: SUBASRI et al. (2025), em sentido geral, sem tratar de quase-incidentes.
