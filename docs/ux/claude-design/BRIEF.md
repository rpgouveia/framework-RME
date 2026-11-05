# Brief de UX do Framework RME para o Claude Design

O briefing para redesenhar a interface do Framework RME no Claude Design:
melhorar a UX das telas, aplicar o visual do IBM Carbon sobre os componentes
que o projeto já tem e adicionar tooltips que expliquem os termos do domínio.

A terminologia vem de [`docs/glossary.md`](../../glossary.md), a fonte única
fixada pela decisão [0028](../../decisions/0028-glossario-e-terminologia.md).
Este brief não redefine nenhum termo: quando houver divergência, vale o
glossário.

## Como usar

1. **Anexe ao Claude Design:**
    - este `BRIEF.md`;
    - `docs/glossary.md`;
    - `screen-map.md` (a referência tela a tela, com cada campo e estado);
    - os screenshots de `screenshots/` das telas que for trabalhar;
    - o código, se ele tiver acesso ao repositório: `resources/css/app.css` e
      `resources/js/components/ui` são o ponto de partida do design system.
2. **Comece pelo design system**, não pelas telas: os tokens do Carbon
   aplicados aos componentes do shadcn (seção [Design system](#design-system)).
3. **Depois, uma tela por vez**, na ordem da seção [Telas](#telas). Peça que
   cada tela use só os componentes do design system.
4. **Revise cada resultado contra a seção
   [O que não inventar](#o-que-não-inventar)** antes de seguir.

## O produto em um minuto

App web de gestão de riscos de sistemas de IA, protótipo de pesquisa aplicada
da PUCPR. Usado por equipes de governança, risco e compliance. Interface
inteira em português do Brasil.

O ciclo: a organização cadastra seus **sistemas de IA**, classificados por
**faixa de risco** do EU AI Act. Em cada sistema registra os **riscos**,
classificados nos subdomínios do MIT AI Risk Repository. A cada risco aplica
**mitigações** de um catálogo fixo de 40 entradas, criando **vínculos**: um
vínculo liga um risco a uma mitigação sob um **responsável** (um papel, não uma
pessoa). Um vínculo tem duas dimensões: **progresso** da implementação e
**verificação** (declarada ou verificada por evidência). Prazos de revisão,
**eventos adversos** e **mudanças do sistema** revertem vínculos verificados
para declarada, e cada reversão pede uma **reavaliação**. Evidências, histórico,
eventos, mudanças e reavaliações são permanentes: não se editam nem se excluem.
A tela inicial é o **Acompanhamento**, que mostra o que está pendente.

## Termos que mais dão errado

As definições completas estão no glossário; estes são os pontos em que a IA de
design já errou ou tende a errar.

| Termo                       | Certo                                                                                                                                                                                    | Errado                                                                    | Glossário |
| --------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------- | --------- |
| **Custo**                   | Nível qualitativo (Baixo, Médio, Alto) do **esforço** de implementação, pela rubrica de 4 dimensões: tempo, pessoas e competências, ferramentas e serviços, impacto na operação.         | Valor em dinheiro, R$, OPEX, orçamento, horas, "custo previsto vs real".  | 3         |
| **Faixa de risco**          | A classificação do sistema no EU AI Act: Inaceitável, Alto, Limitado, Mínimo.                                                                                                            | "Categoria" (reservado à categoria de Saeri das mitigações).              | 1         |
| **Acompanhamento**          | A tela inicial.                                                                                                                                                                          | "Painel", "Dashboard", "Painel de controle".                              | 8         |
| **Progresso**               | Cinco valores: Planejado, Em andamento, Implementado, Suspenso, Cancelado.                                                                                                               | "Em monitoramento" (foi removido), percentual de conclusão, "fase 4/5".   | 4         |
| **Verificação**             | Declarada ou Verificada. Verificada exige evidência registrada depois da última reversão.                                                                                                | "Aprovada", "Validada", "Vencida", "Auditada".                            | 4         |
| **Incerteza**               | Grau de confiança numa avaliação. **Alta incerteza = confiança baixa.**                                                                                                                  | Gravidade, severidade, prioridade. Não use vermelho como se fosse perigo. | 2         |
| **Fase do ciclo de vida**   | As 8 fases da ISO/IEC 22989: Concepção, Projeto e desenvolvimento, Verificação e validação, Implantação, Operação e monitoramento, Validação contínua, Reavaliação do sistema, Retirada. | "Monitoramento", "Coleta de dados", "Descomissionamento".                 | 2         |
| **Reavaliação**             | A decisão sobre um vínculo revertido (Manter, Ajustar, Substituir, Encerrar).                                                                                                            | Confundir com a fase "Reavaliação do sistema".                            | 6 e 2     |
| **Responsável**             | Papel organizacional: cargo e área.                                                                                                                                                      | Nome de pessoa, avatar com foto, "Dr. Fulano".                            | 4         |
| **Evento adverso**          | Incidente (causou dano) ou Quase-incidente (poderia ter causado).                                                                                                                        | "Quase-acidente", "alerta", "ticket".                                     | 7         |
| **Painel de especialistas** | Avaliação externa do framework (pesquisa), fora do app.                                                                                                                                  | Usar "painel" sozinho para qualquer coisa.                                | 8         |

## O que não inventar

O redesign anterior, feito no Google Stitch, ficou bonito mas inventou boa parte
do conteúdo. **Todo número, status, campo, ação e texto de um mockup tem que
existir no app** (veja `screen-map.md` e o glossário). O que for proposta nova
precisa vir marcado como proposta, e nunca pode ser dado de domínio inventado.

### Números e indicadores

O app só mostra **contagens e datas**. Não existem:

- percentuais de conformidade ou de maturidade ("Conformidade PUCPR 98%");
- score de risco, severidade numérica, "risco residual", "impacto -62%";
- percentual ou etapa de conclusão de um vínculo ("85% concluído", "fase 4/5");
- telemetria, métricas de modelo, limiares, sensores, SLA;
- gráficos de tendência ou histórico temporal (só há as barras de progresso do
  Acompanhamento).

### Dinheiro

Custo é **esforço**, nunca dinheiro. Nada de moeda, R$, OPEX, CAPEX, orçamento,
horas ou "custo previsto vs real" em valores. Os três custos (sugerido,
estimado, observado) são sempre Baixo, Médio ou Alto, na mesma escala.

### Pessoas, papéis e processos

- Não há comitês, pareceres, pautas, deliberações, auditorias, aprovações nem
  fluxo de aprovação. Quem verifica, reverte ou reavalia é um **responsável**
  (cargo e área), escolhido num campo. Um cargo pode se chamar "Auditor
  Interno", mas é só o nome do papel, não um processo de auditoria.
- Não há atribuição a pessoas, nomes de pessoas nos registros, fotos, @menções,
  comentários, chat ou notificações (sem sino).
- Não há perfis de usuário nem permissões: qualquer usuário logado faz tudo.
- O usuário logado só aparece no menu do rodapé da barra lateral (nome, e-mail,
  Configurações, Sair).

### Integridade e segurança fictícias

Os registros são permanentes **por regra de negócio**, não por criptografia.
Nada de hash, checksum, SHA-256, ledger, blockchain, "trilha criptográfica",
"integridade verificada" ou "registro imutável assinado".

### Dados e campos que não existem

- **Evidência é só texto:** tipo, descrição (até 255 caracteres) e custo
  observado opcional. Não há upload, anexo, arquivo, tamanho, download nem
  pré-visualização.
- **Não há códigos de registro** como "VNC-2024-064", "SYS-091" ou
  "RME-MIT-008". Os registros aparecem pelo nome; vínculos, como
  "Risco → Mitigação". A única exceção é o "Identificador na base" de Saeri, no
  detalhe da mitigação.
- **Não há integrações** (Prometheus, OpenTelemetry, repositórios, SSO), versão
  do app ("v2.0") nem ambiente ("Sincronizado com RME Core").
- **Normas citadas são só as do projeto:** EU AI Act, MIT AI Risk Repository,
  taxonomia de Saeri et al. e ISO/IEC 22989. Não há checagem de conformidade
  com ISO 42001, NIST AI RMF nem nenhuma outra.

### Status e valores fora das listas

Use só os valores do glossário. Não existem "Em monitoramento", "Vencido" como
status de verificação, "Aprovado", "Em análise", "Mitigado", níveis de
severidade, "Urgência" nem "Prioridade".

### Textos institucionais

Não invente slogans, selos ou afirmações sobre o produto ("Governança de Riscos
e Maturidade", "Conformidade com EU AI Act", "Ready", "Certificado"). Alguns
desses textos já entraram nas telas de entrada e de cadastro pelo redesign do
Stitch e precisam de revisão do grupo.

### Ações que não existem

- Editar ou excluir evidências, eventos adversos, mudanças do sistema,
  reavaliações ou entradas do histórico.
- Excluir vínculos: eles só são cancelados.
- Criar ou editar mitigações: o catálogo é somente de consulta.
- Editar cargo e área de um responsável já usado: ele só é desativado.

## O que pode propor

O redesign deve melhorar a UX, então pode e deve propor o que não muda dado nem
regra:

- reorganizar, agrupar, dividir formulários em seções ou passos;
- abas, filtros, busca e ordenação sobre os campos que já existem;
- ações em linha que levem a telas que já existem (ex.: "Reavaliar" direto na
  lista de vínculos revertidos de um evento);
- um bloco de "próxima ação" que escolha entre as ações que o vínculo já tem
  (Verificar, Renovar verificação, Reavaliar, Registrar evidência), mostrando o
  motivo quando a ação está bloqueada;
- textos de ajuda e tooltips **tirados do glossário**;
- modais de confirmação e prévias de impacto (já existem em várias telas);
- estados vazios com chamada para ação;
- versões mobile.

Ideias do redesign do Stitch que valem aproveitar, sem o conteúdo inventado:
o bloco de próxima ação no topo do vínculo; a criação de vínculo em passos, com
a mitigação escolhida numa tabela com busca e o detalhe ao lado; e os botões
"Reavaliar" em linha na fila do Acompanhamento.

## Regras do domínio que a interface precisa mostrar

- **Duas dimensões de status:** todo vínculo mostra progresso **e**
  verificação, com rótulo em cada uma.
- **Registros permanentes pedem confirmação:** evento adverso, evidência,
  mudança do sistema e reavaliação abrem um modal antes de gravar, avisando que
  o registro não pode ser editado. Cancelar vínculo e reclassificar sistema para
  Inaceitável hoje gravam sem modal; o redesign deve acrescentar a confirmação.
- **Prévia de reversão:** evento adverso, mudança do sistema e reclassificação
  para Inaceitável mostram quantos vínculos verificados voltarão a declarada, e
  quais, antes de confirmar.
- **Faixa inaceitável:** o sistema nunca está em operação; seus vínculos não
  podem ser verificados nem têm revisão periódica e servem para planejar a
  descontinuação. Sempre com alerta em vermelho.
- **Data de revisão:** é o último dia válido da verificação ("Vence hoje" no
  próprio dia); vínculos declarados não têm data de revisão.
- **Ações bloqueadas explicam o motivo** (ex.: "Registre uma evidência antes de
  verificar o vínculo.").

## Design system

**Base: shadcn/ui, que o projeto já usa, com os tokens do IBM Carbon.** Nada de
segunda biblioteca de componentes (como `@carbon/react`): o visual do Carbon
entra pelo tema, e todas as telas mudam juntas.

- **Tokens** em `resources/css/app.css`, no lugar do tema neutro padrão:
    - cor principal IBM Blue `#0f62fe`, só em ação primária, link e foco;
    - texto `#161616`, secundário `#525252`, terciário `#8c8c8c`;
    - superfícies `#ffffff` e `#f4f4f4`, linhas `#e0e0e0`;
    - cantos 0px (`--radius: 0`);
    - IBM Plex Sans, corpo com `letter-spacing: 0.16px`, títulos grandes com
      peso 300;
    - campos com fundo `#f4f4f4`, linha embaixo e sublinhado azul de 2px no foco;
    - profundidade por linha fina e troca de superfície, sem sombra;
    - semânticas do Carbon: verde (verificada, em dia), amarelo (pendente),
      vermelho (erro, faixa inaceitável), azul (informação);
    - **tema escuro** com o Gray 100 do Carbon. O app já tem tema claro e
      escuro, e os dois precisam funcionar.
- **Componentes que já existem** em `resources/js/components/ui`: alert, avatar,
  badge, breadcrumb, button, card, checkbox, collapsible, dialog, dropdown-menu,
  input, input-otp, label, navigation-menu, pagination, select, separator,
  sheet, sidebar, skeleton, sonner, spinner, table, textarea, toggle,
  toggle-group, tooltip.
- **Componentes a adicionar pelo shadcn** (sem criar do zero): `popover` (para o
  toggletip), `radio-group` (desfechos da reavaliação), `tabs` (detalhe do
  sistema), `command` (busca de mitigação e de subdomínio).
- **Nada de cor fixa nas páginas.** As telas de login, cadastro e
  recuperação de senha e a página inicial foram refeitas com cores em
  hexadecimal e a fonte carregada na própria página. Isso quebra o tema escuro
  e precisa voltar para os tokens.

O `DESIGN.md` do Carbon usado antes descreve o site de marketing da IBM; para
tabelas, tags, notificações, modais e tooltips, siga os componentes de produto
do [Carbon](https://carbondesignsystem.com/components/overview/components/).

## Tooltips

**Dois padrões:**

- **Definition tooltip:** termo com sublinhado pontilhado que abre ao passar o
  mouse ou focar com o teclado. Para frases curtas. O app já tem um, em
  `components/review-date.tsx`.
- **Toggletip:** ícone de informação ao lado do rótulo, que abre no clique e
  funciona no toque. Para explicações longas, listas ou link para mais detalhes.
  Precisa do `popover`.

**Os textos vêm do glossário.** A tabela abaixo condensa cada definição para
caber num tooltip e indica a seção de onde saiu; se o glossário mudar, a tabela
acompanha. Os códigos `G#` são os mesmos usados em `screen-map.md`.

| #   | Termo na tela                                                                                                                                    | Texto do tooltip                                                                                                                                                                                                                                | Padrão     | Glossário |
| --- | ------------------------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------- | --------- |
| G1  | "Faixa de risco" (Inaceitável, Alto, Limitado, Mínimo)                                                                                           | A classificação do sistema no EU AI Act. Define o intervalo de revisão dos vínculos verificados: 90 dias para Alto, 180 para Limitado e 365 para Mínimo.                                                                                        | Toggletip  | 1; 5      |
| G2  | "Inaceitável", "Sistema na faixa inaceitável do EU AI Act"                                                                                       | Prática proibida pelo EU AI Act. O sistema pode ser cadastrado e ter vínculos planejados, mas nunca é considerado em operação: não há verificação nem revisão periódica.                                                                        | Toggletip  | 1         |
| G3  | "Origem" do sistema                                                                                                                              | A relação da organização com o sistema. Interno: ela o produz. Terceiros: é cliente de um provedor. Código aberto: incorpora ou adapta software aberto. Híbrido: combina componente próprio com de terceiros ou aberto.                         | Toggletip  | 1         |
| G4  | "Domínio de aplicação"                                                                                                                           | Área em que o sistema é usado, como saúde ou crédito. Não altera nenhuma classificação.                                                                                                                                                         | Definition | 1         |
| G5  | "Domínio de risco", "Subdomínio de risco (MIT)", códigos como "1.1"                                                                              | Classificação do risco num dos 24 subdomínios do MIT AI Risk Repository, agrupados em 7 domínios. Também classifica eventos adversos, mudanças do sistema e os riscos-alvo das mitigações.                                                      | Toggletip  | 2         |
| G6  | "Fase do ciclo de vida"                                                                                                                          | Fase do sistema segundo a ISO/IEC 22989. No risco, a fase em que ele foi identificado; no vínculo, a fase em que a mitigação é aplicada; na reavaliação, a fase em que a causa se originou.                                                     | Toggletip  | 2         |
| G7  | "Nível de incerteza", "Incerteza" (Baixa, Média, Alta)                                                                                           | Grau de confiança numa avaliação: incerteza alta significa confiança baixa. No risco, a confiança de que ele existe no sistema; na mitigação, a confiança nas estimativas do catálogo.                                                          | Toggletip  | 2         |
| G8  | "Vínculo"                                                                                                                                        | A aplicação de uma mitigação a um risco de um sistema, sob um responsável. O par risco e mitigação é único, e o vínculo não se exclui: se cancela.                                                                                              | Definition | 4         |
| G9  | "Declarada" / "Verificada"                                                                                                                       | Declarada: a organização afirma a mitigação, sem comprovação vigente. Verificada: comprovada por evidência registrada depois da última reversão, com um responsável que assina.                                                                 | Definition | 4         |
| G10 | "Progresso": Planejado, Em andamento, Implementado, Suspenso, Cancelado                                                                          | O andamento da implementação, independente da verificação. Planejado: não iniciada. Em andamento: iniciada. Implementado: concluída e em operação. Suspenso: parada, com intenção de retomar. Cancelado: vínculo encerrado.                     | Toggletip  | 4         |
| G11 | "Aguardando verificação"                                                                                                                         | Vínculo declarado sem reversão pendente: aguarda a primeira verificação ou foi reavaliado sem verificar.                                                                                                                                        | Definition | 6         |
| G12 | "Aguardando reavaliação"                                                                                                                         | Vínculo declarado cuja última reversão ainda não tem reavaliação concluída.                                                                                                                                                                     | Definition | 6         |
| G13 | "Reversão", "Reverter verificação", "Vínculos revertidos"                                                                                        | A volta de um vínculo verificado a declarado, automática (por um gatilho) ou manual (com motivo e responsável). Para verificar de novo, é preciso evidência nova.                                                                               | Definition | 5         |
| G14 | Origem da reversão: Vencimento da revisão, Evento adverso, Reclassificação do sistema, Nova versão do modelo, Alteração na base de dados, Manual | O que provocou a reversão. Registros automáticos não têm responsável; os manuais têm.                                                                                                                                                           | Toggletip  | 5; 6      |
| G15 | "Registrado pelo sistema (origem: …)"                                                                                                            | Mudança automática, sem autor humano: a origem indica o gatilho.                                                                                                                                                                                | Definition | 5         |
| G16 | "Próxima revisão", "Vence hoje", "Em dia", "Não se aplica"                                                                                       | O último dia válido da verificação: a data da verificação mais o intervalo da faixa de risco. Vínculos declarados não têm data de revisão.                                                                                                      | Definition | 5         |
| G17 | "Renovar verificação"                                                                                                                            | Nova verificação de um vínculo ainda verificado, antes do vencimento, com evidência gravada depois da última verificação. Recomeça a contagem.                                                                                                  | Definition | 5         |
| G18 | "Custo sugerido", "Custo estimado", "Custo observado"                                                                                            | Nível do esforço de implementação, não só financeiro: tempo, pessoas, ferramentas e impacto na operação; vale o nível mais alto. Sugerido: o típico, do catálogo. Estimado: o previsto para este vínculo. Observado: o efetivamente empregado.  | Toggletip  | 3         |
| G19 | "Fonte da estimativa"                                                                                                                            | O relato rastreável da evidência e da concordância que sustentam as estimativas da mitigação. É separado da fonte da mitigação.                                                                                                                 | Definition | 3         |
| G20 | "Categoria", "Subcategoria" (Saeri et al.)                                                                                                       | Classificação da mitigação pela taxonomia de Saeri et al. (2025), em 4 categorias: governança e supervisão; técnico e segurança; processos operacionais; transparência e responsabilização.                                                     | Toggletip  | 3         |
| G21 | "Catálogo de mitigações", "(C2)"                                                                                                                 | As 40 mitigações curadas pelo grupo a partir da base de Saeri et al. Recomendação: tirar o "(C2)" da interface, que é jargão da pesquisa.                                                                                                       | —          | 3         |
| G22 | "Recomendadas para este risco", "Risco-alvo"                                                                                                     | As mitigações cujo risco-alvo inclui o subdomínio do risco aparecem como recomendadas; as demais continuam disponíveis.                                                                                                                         | Definition | 3         |
| G23 | "Evidência esperada"                                                                                                                             | O que demonstraria que a mitigação foi aplicada. Útil como referência ao registrar uma evidência.                                                                                                                                               | Definition | 3         |
| G25 | "Evento adverso"                                                                                                                                 | Ocorrência num sistema de IA em operação, decorrente de seu funcionamento, uso ou falha, que causou ou poderia plausivelmente ter causado dano. Basta a suspeita fundamentada para registrar.                                                   | Toggletip  | 7         |
| G26 | "Incidente" / "Quase-incidente"                                                                                                                  | Incidente: a ocorrência causou dano. Quase-incidente: poderia ter causado e não causou, inclusive quando barrada por uma mitigação.                                                                                                             | Definition | 7         |
| G27 | "Subdomínios de risco materializados"                                                                                                            | Os subdomínios do MIT em que o evento se encaixa. Só os vínculos dos riscos nesses subdomínios são revertidos.                                                                                                                                  | Definition | 6; 7      |
| G28 | "Vínculo que interceptou"                                                                                                                        | Num quase-incidente, o vínculo cuja mitigação barrou a ocorrência. Ele não é revertido, porque o evento mostra que funcionou.                                                                                                                   | Definition | 7         |
| G29 | "Data de detecção"                                                                                                                               | Quando o evento foi percebido. O intervalo desde a ocorrência mede a demora do monitoramento.                                                                                                                                                   | Definition | 7         |
| G30 | "Esperados para este sistema"                                                                                                                    | Os subdomínios dos riscos já cadastrados no sistema, o seu perfil de risco. Aparecem primeiro; os demais continuam disponíveis.                                                                                                                 | Definition | 1         |
| G31 | "Riscos não mapeados"                                                                                                                            | Subdomínio informado num evento ou numa mudança em que o sistema não tem risco cadastrado. O alerta some quando o risco é cadastrado.                                                                                                           | Toggletip  | 6         |
| G32 | "Mudança do sistema", "Nova versão do modelo", "Alteração na base de dados"                                                                      | Registro permanente de uma nova versão do modelo ou de uma alteração na base de dados, que reverte os vínculos verificados do sistema.                                                                                                          | Definition | 7         |
| G33 | "Subdomínios de risco afetados (opcional)"                                                                                                       | Com subdomínios informados, só os vínculos desses riscos são revertidos. Sem nenhum, todos os vínculos verificados do sistema são revertidos.                                                                                                   | Toggletip  | 6; 7      |
| G34 | "Desfecho": Manter, Ajustar, Substituir, Encerrar                                                                                                | Manter: verifica no mesmo ato, com evidência posterior à reversão. Ajustar: altera responsável, custo estimado, fase ou progresso. Substituir: cancela e leva à criação de outro vínculo para o mesmo risco. Encerrar: cancela, sem substituto. | Toggletip  | 6         |
| G35 | "Análise de causa": apurada, não apurada, não se aplica                                                                                          | Apurada: com a causa e a fase de origem. Não apurada: por escolha explícita. Não se aplica: quando a reversão não decorre de dano, como no vencimento ou na mudança do sistema.                                                                 | Toggletip  | 6         |
| G36 | "Verificar no mesmo ato"                                                                                                                         | Opcional no Ajustar: verifica o vínculo junto com a reavaliação, com evidência posterior à reversão.                                                                                                                                            | Definition | 6         |
| G37 | "Substituição", "Substituído por"                                                                                                                | O vínculo novo registra qual vínculo substitui.                                                                                                                                                                                                 | Definition | 6         |
| G38 | "Responsável", "Quem verifica / reverte / reavalia"                                                                                              | Papel organizacional, identificado por cargo e área, e não por pessoa. Responde pelo vínculo e assina verificações, reversões manuais e reavaliações.                                                                                           | Definition | 4         |
| G39 | "Desativar", "Inativo"                                                                                                                           | Responsáveis já usados não são excluídos nem editados: são desativados e deixam de ser oferecidos nos formulários.                                                                                                                              | Toggletip  | 4 (0009)  |
| G40 | "Histórico de status"                                                                                                                            | Registro permanente de toda mudança de progresso ou de verificação do vínculo. Cada registro altera uma única dimensão.                                                                                                                         | Definition | 5         |
| G41 | "Evidências que embasaram a verificação"                                                                                                         | As evidências registradas até a verificação, e depois da última reversão, que sustentam aquela verificação.                                                                                                                                     | Definition | 4; 5      |
| G42 | "Relatório de rastreabilidade"                                                                                                                   | Exportação, por sistema, de riscos, mitigações, responsáveis, evidências, histórico, reavaliações, eventos adversos e mudanças, em CSV e JSON.                                                                                                  | Toggletip  | 8         |
| G43 | "O registro é permanente"                                                                                                                        | Evidências, histórico, eventos, mudanças e reavaliações não se editam nem se excluem: um registro errado se corrige com outro.                                                                                                                  | Definition | 5; 7      |
| G45 | "Reclassificação do sistema"                                                                                                                     | Mudar o sistema para a faixa Inaceitável reverte todos os seus vínculos verificados.                                                                                                                                                            | Definition | 6         |
| G46 | "Reavaliação do sistema" (fase do ciclo de vida)                                                                                                 | A avaliação dos resultados da operação frente aos objetivos e aos riscos. Não confundir com a reavaliação do vínculo.                                                                                                                           | Definition | 2         |
| G47 | "Tipo" da evidência                                                                                                                              | Documento: estabelece como a mitigação deve funcionar. Relatório: resultado de análise. Log de auditoria: registro automático. Resultado de teste. Certificação: atestado de terceiro. Ata de reunião: registro de decisão.                     | Toggletip  | 5         |

Os códigos G24 (catálogo de demonstração, que não aparece mais) e G44 (o
código "RF07" no texto de ajuda do custo observado, que deve sair da interface)
não têm tooltip.

**Rubrica de custo:** o toggletip de G18 nos formulários de vínculo e de
evidência pode mostrar a tabela da rubrica (seção 3 do glossário), como texto
de apoio previsto na decisão 0028 (tarefa de UX #95).

## Telas

Em ordem de prioridade. Os nomes de arquivo são os de `screenshots/`; o
detalhe de cada tela está em `screen-map.md`.

| #   | Tela                                      | Screenshots                                                                                                         | O que resolver                                                                                                                                                                                                                   |
| --- | ----------------------------------------- | ------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Design system e padrão de tooltip         | `01-auth-login` (o estilo Carbon que já existe)                                                                     | Tokens claro e escuro, botões, campos, tags de status, tabela, alerta, modal, definition tooltip e toggletip.                                                                                                                    |
| 2   | Acompanhamento                            | `10-acompanhamento`, `dark-10-acompanhamento`, `mobile-10-acompanhamento`                                           | Tela longa (cerca de 9 blocos) e com números repetidos; falta hierarquia do que fazer primeiro; os 6 contadores por origem ficam numa grade quebrada (4 + 2); os atalhos levam a listas sem filtro.                              |
| 3   | Detalhe do vínculo                        | `63-links-show-verified`, `64-…-awaiting-reassessment`, `65-…-awaiting-verification`, `66-links-show-verify-dialog` | 6 a 7 cartões sem próxima ação clara; o motivo de bloqueio fica em texto miúdo; as duas dimensões de status aparecem sem rótulo; "Cancelar vínculo" sem confirmação; evidências e histórico aparecem só como números.            |
| 4   | Lista de vínculos                         | `60-links-index`, `61-links-index-awaiting-reassessment`, `mobile-60-links-index`                                   | Faltam busca, filtro por sistema e filtro "Sem evidência"; tags de status sem rótulo; densidade.                                                                                                                                 |
| 5   | Criar vínculo                             | `62-links-create`                                                                                                   | São três controles para escolher uma mitigação, e o select só mostra nomes; falta dizer que risco e mitigação não mudam depois; o custo estimado precisa da rubrica.                                                             |
| 6   | Reavaliar vínculo                         | `75-reassessments-create`                                                                                           | Seções condicionais sem estrutura fixa; a opção indisponível parece só desbotada; os rádios são nativos.                                                                                                                         |
| 7   | Registrar evento adverso                  | `41-adverse-events-create`                                                                                          | O formulário mais longo, sem seções; seletor de subdomínios enorme; o "Vínculo que interceptou" aparece longe da natureza; falta a definição de evento adverso.                                                                  |
| 8   | Eventos adversos (lista e detalhe)        | `40-adverse-events-index`, `42-adverse-events-show`                                                                 | 7 chips de domínio que quebram linha; sem filtro por sistema; "Mudanças disparadas" ambíguo; vínculos pendentes sem botão "Reavaliar".                                                                                           |
| 9   | Sistemas de IA                            | `20`–`27`                                                                                                           | Faixa de risco sem explicação do impacto; reclassificar para Inaceitável sem modal; os eventos do sistema não aparecem no detalhe; 4 downloads com o mesmo peso; a lista de mudanças só é alcançada pelo breadcrumb.             |
| 10  | Riscos                                    | `30`–`33`                                                                                                           | Sem filtros; a incerteza "Alta" em vermelho parece gravidade; o formulário não separa identificação de classificação; as definições dos subdomínios estão em inglês.                                                             |
| 11  | Catálogo de mitigações                    | `50-mitigations-index`, `51-mitigations-show`                                                                       | Jargão "(C2)"; procedência acadêmica com o mesmo peso da informação prática; falta "Criar vínculo com esta mitigação" e filtro por risco-alvo.                                                                                   |
| 12  | Evidências, histórico e mudança de status | `68`–`73`                                                                                                           | O custo observado diz "custo real (RF07)"; a evidência esperada da mitigação não aparece no formulário; cancelar vínculo sem modal; "Voltar" e "Cancelar vínculo" lado a lado.                                                   |
| 13  | Responsáveis                              | `80`–`82`                                                                                                           | Não explica por que um responsável usado não pode ser editado nem excluído; reatribuir vínculos é feito um a um.                                                                                                                 |
| 14  | Entrada e configurações                   | `00`–`03`, `90`–`92`                                                                                                | Login, cadastro e recuperação de senha já têm estilo Carbon, mas com cores fixas; a página inicial tem textos institucionais a revisar; em Configurações, "Sistema" (tema do sistema operacional) se confunde com sistema de IA. |

Os dados dos screenshots são o portfólio de demonstração dos seeds: os nomes
de sistemas, riscos e responsáveis são fictícios, mas realistas, e podem ser
usados nos mockups.

## O que devolver

O design é implementado neste repositório, com React, Tailwind v4 e shadcn/ui.
Para cada tela:

1. o protótipo ou as imagens (claro, escuro e mobile, quando couber);
2. o que mudou e por quê, em poucas linhas;
3. os componentes usados, pelos nomes de `components/ui` e dos que serão
   adicionados;
4. onde fica cada tooltip, pelo código `G#`;
5. a lista do que for **proposta nova** (filtro, ação em linha, texto de ajuda),
   para o grupo aprovar antes da implementação.
