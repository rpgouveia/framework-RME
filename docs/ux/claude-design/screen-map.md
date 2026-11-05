# Screen map: Framework RME

The screen-by-screen reference behind [`BRIEF.md`](BRIEF.md). It maps every
screen of the app as it is on `main` after decision 0028: route, layout,
blocks, fields with their pt-BR labels, actions, empty and conditional states,
and the friction found while mapping. It comes from reading
`resources/js/pages`, the shared components, the controllers and
`lang/pt_BR.json`.

- **Terms:** `docs/glossary.md` is the single source (decision 0028). Where
  this map and the glossary disagree, the glossary wins.
- **Tooltip codes:** the `[G#]` references below point to the tooltip table in
  [`BRIEF.md`](BRIEF.md#tooltips), which takes its copy from the glossary.
- **Screenshots:** `screenshots/`, named after the screens here.

---

## 0. Global shell, tokens, components

### Layout resolution (`resources/js/app.tsx`)

- `welcome`: no layout. Restyled in Carbon by hand (commit ab07265): hex colors and the IBM Plex `<link>` live in the page, not in the theme tokens.
- `auth/*`: `AuthLayout`. Login, register and forgot-password bypass it and render their own Carbon-style page with hard-coded hex colors and the IBM Plex `<link>` (commits ab07265, dcaace6, e8cc5f9); the other auth pages still use `auth/auth-simple-layout.tsx`. It is a centered column (max-w-sm) with the logo icon linking to `/`, an `h1` title and a muted description, both set by each page through `setLayoutProps`.
- `settings/*`: `[AppLayout, SettingsLayout]`.
- Everything else: `AppLayout`, which is `app/app-sidebar-layout.tsx` (the sidebar shell). `AppHeader` and `app-header-layout.tsx` exist but are **not used**. They are leftovers from the starter kit: links to the Laravel GitHub repo and docs, plus a search button that does nothing.
- App-wide wrappers: `TooltipProvider delayDuration={0}` and a Sonner `<Toaster/>`. Server flash `toast` messages are shown through `use-flash-toast`.
- Page `<title>` is `"{title} - Framework RME"` (`APP_NAME="Framework RME"`, `APP_LOCALE=pt_BR`).
- Breadcrumbs: each page sets a static `Page.layout = (props) => ({ breadcrumbs })`. Settings pages use `setLayoutProps`.

### Sidebar (`app-sidebar.tsx`, `nav-main.tsx`)

- shadcn `Sidebar`, `collapsible="icon"`, `variant="inset"`.
- Header: `AppLogo` (logo icon plus the app name "Framework RME"), links to the Dashboard.
- Group label: **"Plataforma"**. Items, in order:
    1. Acompanhamento (LayoutGrid) → `/dashboard`
    2. Sistemas de IA (Bot) → `/ai-systems`
    3. Riscos (ShieldAlert) → `/risks`
    4. Eventos adversos (TriangleAlert) → `/adverse-events`
    5. Mitigações (ShieldCheck) → `/mitigations` (the page itself is titled "Catálogo de mitigações")
    6. Vínculos (Link2) → `/links`
    7. Responsáveis (Users) → `/owners`
- There are no sub-groups. Evidence, history, reassessments and system changes have no nav entry; they are reached from a link or a system.
- When collapsed, each item shows a tooltip with its title (`SidebarMenuButton tooltip`).

### Sidebar header bar (`app-sidebar-header.tsx`)

A 64px bar with `SidebarTrigger` and `Breadcrumbs`. The last crumb is plain text; the others are links.

### User menu (`nav-user.tsx`, `user-menu-content.tsx`)

- Sits in the sidebar footer. Shows avatar initials plus the name, with a ChevronsUpDown icon.
- Dropdown: the user's name and e-mail, a separator, **"Configurações"** (→ `/settings/profile`), a separator, **"Sair"** (logout).

### Settings layout (`layouts/settings/layout.tsx`)

- Heading "Configurações", with "Gerencie seu perfil e configurações de conta".
- Left vertical nav (ghost buttons, active one gets `bg-muted`): **Perfil**, **Segurança**, **Aparência**.
- Content sits in a `max-w-xl` column.

### Design tokens (`resources/css/app.css`)

- Stock shadcn "neutral" theme in OKLCH. No brand color: `--primary` is near-black (0.205) in light mode and near-white (0.985) in dark mode.
- Light mode: background white; foreground 0.145; secondary, muted and accent 0.97; muted-foreground 0.556; border and input 0.922; ring 0.87; destructive `oklch(0.577 0.245 27.325)`.
- Dark mode (`.dark` class; `@custom-variant dark (&:is(.dark *))`): background 0.145; secondary, muted, accent and border 0.269; muted-foreground 0.708; destructive 0.396/0.141/25.7; ring 0.439. The sidebar uses its own set (0.985 light, 0.205 dark).
- Chart-1..5 palettes are defined for both modes but **unused**. The dashboard bars use `bg-primary/70`.
- `--radius: 0.625rem`; lg = radius, md = radius − 2px, sm = radius − 4px.
- Font: `'Instrument Sans', ui-sans-serif, system-ui…`, loaded through Laravel's `@fonts` directive in `app.blade.php`.
- Dark mode is set by the `appearance` cookie or localStorage (`light | dark | system`). An inline script in `app.blade.php` applies it before paint.
- Semantic colors hard-coded outside the tokens (`lib/labels.ts`, `unacceptable-tier-alert.tsx`):
    - **Amber** for pending / warning (amber-100/900; pending card `border-amber-300 bg-amber-50/60`).
    - **Red** for the unacceptable tier (`border-red-300 bg-red-50 text-red-900`, dark `red-950/40`; noted as WCAG AA ≥9:1). Also used for "Alto" uncertainty and for incidents.
    - **Emerald** for Verificada, Implementado and "EM DIA".
    - **Sky** for "Em andamento" and the replacement banner.
        - **Secondary gray** for Planejado, Limitado, Mínimo and Baixa.
    - **Outlined** for Cancelado, Declarada and Quase-incidente.
    - The EU AI Act "Inaceitável" badge uses `bg-destructive text-white`.

### shadcn components in `resources/js/components/ui`

alert (default/destructive), avatar, badge (default/secondary/destructive/outline), breadcrumb, button (default/destructive/outline/secondary/ghost/link; sizes default/sm/lg/icon), card, checkbox, collapsible, dialog, dropdown-menu, icon, input, input-otp, label, navigation-menu, pagination, placeholder-pattern, select, separator, sheet, sidebar, skeleton, sonner, spinner, table, textarea, toggle, toggle-group, tooltip.

- **Not present:** RadioGroup (the reassessment form uses native `<input type="radio">`), Popover, HoverCard, Tabs, Combobox/Command, Accordion.

### Existing Tooltip usage and `title=` attributes

- `components/review-date.tsx` is the only domain tooltip. When there is no review date it shows a dotted-underline label, focusable, `cursor-help`:
    - "Não se aplica" with tooltip "O sistema está na faixa inaceitável do EU AI Act e não pode operar, então o vínculo não tem revisão periódica."
    - "Aguardando verificação" with tooltip "A revisão periódica começa na primeira verificação do vínculo, com o intervalo da faixa do sistema."
- `nav-main.tsx`: sidebar item tooltips (only when collapsed).
- `app-header.tsx`: tooltips on the Repository and Documentation icons. This header is unused.
- `title=` HTML attribute: only `risk-subdomain-badges.tsx` (`title={code name}` on truncated subdomain badges, `max-w-56`). Other `title=` hits are `<Head title>`, `Heading title` and `DeleteDialog title` props, which are not tooltips.

### Shared components (behavior relevant to design)

- **DeleteDialog**: trigger is "Excluir" (destructive) unless overridden. Dialog has title and description, then "Cancelar" (secondary) and "Excluir" (destructive submit).
- **FilterLink**: server-side filter chip. Active = default/primary; inactive = outline; the "subtle" second level is secondary when active and ghost when inactive.
- **PaginationLinks**: "Anterior" / "Próxima" plus page numbers. Shown only when `last_page > 1`. Every list paginates at 15.
- **LinkStatusBadges**: progress badge plus verification badge, side by side.
- **StatusTransition** shows what a history entry changed:
    - "Renovação da verificação:" followed by a Verificada badge;
    - "Verificação:" with old badge → new badge;
    - "Abertura" → status (the first entry);
    - old status badge → new status badge.
- **EntryAuthor**: "Registrado pelo sistema" with "Origem: {origin}" below, or the owner's role with the area below.
- **ObservedCost**: the cost level plus the link "Informado na evidência de {date}", or "Não informado".
- **WaitingDays**: "hoje", "1 dia" or "{n} dias".
- **UnacceptableTierAlert**: red Alert titled "Sistema na faixa inaceitável do EU AI Act".
    - Long text: "A faixa corresponde a práticas proibidas pelo EU AI Act. O sistema não é considerado em operação, e seus vínculos não têm revisão periódica: servem para planejar a descontinuação."
    - Short text: "O sistema não pode operar: este vínculo não terá revisão periódica e serve para planejar a descontinuação."
    - Anchor id `faixa-inaceitavel`.
- **FictionalCatalogAlert**: default Alert with a flask icon, "Catálogo de demonstração" / "O catálogo atual contém dados fictícios de demonstração…". It renders only when `catalog.fictional`. **The loaded catalog has `"fictional": false`, so it does not show today.**
- **RiskSubdomainPicker**: a fieldset of checkboxes for the MIT subdomains, described under adverse-events/create.
- **LinkVerificationCard**: described under links/show.
- **SystemChangesTable**: described under ai-systems/show.
- Clickable rows (`rowLink`): most list tables open the record on a row click; inner links and buttons still work on their own.

---

## 2. Public / entry

### Welcome: `/` → `welcome`

- **Layout:** none (a standalone page, max-w-5xl). **Breadcrumbs:** none.
- **Purpose:** landing page explaining the framework.
- **Blocks, in order:**
    1. Top bar: logo and app name (links to the Dashboard if logged in, otherwise to login). Right side: "Acompanhamento" (primary sm) when logged in; otherwise "Entrar" (ghost sm) and "Cadastrar-se" (outline sm).
    2. Hero: large logo, `h1` "Gestão de riscos de sistemas de IA", and two muted paragraphs ("Mantenha um registro dos sistemas de IA…" and "Vencimentos de revisão, eventos adversos e mudanças em um sistema levam as mitigações verificadas de volta à reavaliação…").
    3. Hero buttons: "Abrir o painel" (primary) when logged in; otherwise "Criar uma conta" (primary) and "Entrar" (outline).
    4. Section "O que o framework mantém" (uppercase eyebrow): a 3-column grid of 6 icon cards (Sistemas de IA, Riscos, Mitigações, Vínculos, Eventos adversos, Rastreabilidade), each with a pt-BR summary.
    5. Footer: "Projeto de pesquisa aplicada — PUCPR."
- **Jargon:** EU AI Act [G1], MIT AI Risk Repository [G5], Saeri et al. [G20], "nível de incerteza" [G7], "quase-incidentes" [G26], "trilha de auditoria somente de acréscimo" [G43].
- **Friction:** the cards are not clickable. Jargon-heavy for a first visit, with no explanations.

### Auth screens (`auth/*`, AuthSimpleLayout, no breadcrumbs)

- **Login**, `/login` → `auth/login`.
    - Title "Entre na sua conta", description "Digite seu e-mail e senha abaixo para entrar".
    - Optional status message.
    - Fields: Endereço de e-mail (email, required); Senha (password, required), with a "Esqueceu sua senha?" link in the label row; checkbox "Lembrar-me".
    - Primary button "Entrar". Footer: "Não tem uma conta? Cadastrar-se".
- **Register**, `/register` → `auth/register`.
    - Title "Criar uma conta", description "Digite seus dados abaixo para criar sua conta".
    - Fields: Nome (text, required, placeholder "Nome completo"); Endereço de e-mail (email, required); Senha (required); Confirmar senha (required).
    - Primary button "Criar conta". Footer: "Já tem uma conta? Entrar".
- **Forgot password**, `/forgot-password` → `auth/forgot-password`.
    - Title "Esqueceu a senha", description "Digite seu e-mail para receber um link de redefinição de senha".
    - Field: Endereço de e-mail (email). Button "Enviar link de redefinição de senha". Footer: "Ou volte para entrar".
- **Reset password**, `/reset-password/{token}` → `auth/reset-password`.
    - Title "Redefinir senha", description "Digite sua nova senha abaixo".
    - Fields: E-mail (read-only); Senha; Confirmar senha. Button "Redefinir senha".
- **Confirm password**, `/user/confirm-password` → `auth/confirm-password`.
    - Required before `/settings/security`.
    - Title "Confirmar senha", description "Esta é uma área segura do aplicativo. Confirme sua senha antes de continuar."
    - Field: Senha. Button "Confirmar senha".
- **Two-factor challenge** → `auth/two-factor-challenge`.
    - Default state: title "Código de autenticação", description "Digite o código de autenticação fornecido pelo seu aplicativo autenticador.", 6-digit OTP input.
    - Recovery state: title "Código de recuperação", description "Confirme o acesso à sua conta digitando um dos seus códigos de recuperação de emergência.", text input "Digite o código de recuperação".
    - Button "Continuar". Toggle link "ou você pode entrar usando um código de recuperação / de autenticação".
- **Verify email** → `auth/verify-email`.
    - Title "Verificação de e-mail", description "Verifique seu endereço de e-mail clicando no link que acabamos de enviar para você."
    - Button "Reenviar o e-mail de verificação" and link "Sair".
- **Friction:** none domain-specific. This is the stock starter kit.

---

## 3. Acompanhamento (Dashboard)

### `/dashboard` → `dashboard` (DashboardController, `__invoke`)

- **Layout:** AppLayout. **Breadcrumbs:** Acompanhamento.
- **Purpose:** show where the traceability chain is still incomplete.
- **Props:** totals; unlinkedRisks; linksWithoutEvidence; recentEvents (30-day window); reviews (14-day window); verification counts by origin plus the 5 longest waiting; unmappedRisks; systems; unacceptableSystems. The windows come from `c3-monitoring-protocol.json`. Each short list shows 5 items.
- **Heading:** "Acompanhamento", with "Onde a cadeia de rastreabilidade ainda está incompleta."
- **Empty state** (0 systems): a dashed box "Comece pelo sistema de IA" with the text "O ciclo do framework parte de um sistema de IA cadastrado… Cadastre o primeiro sistema para o painel começar a mostrar o que falta em cada etapa." and the primary button "Cadastrar sistema de IA".
- **Overview blocks, in order:**
    1. **Unacceptable alert** (only when such systems exist). Red alert titled ":count sistema(s) na faixa inaceitável do EU AI Act", with "Práticas proibidas: esses sistemas não são considerados em operação e ficam fora dos indicadores de revisão. Seus vínculos servem para planejar a descontinuação." and a list of linked system names.
    2. **Totals row** (inline `dl`, muted): Sistemas de IA · Riscos · Vínculos · Mitigações no catálogo · Responsáveis ativos.
    3. **Three pending cards** (3 columns; a card turns amber when its count is above 0). Each shows a big number, a sentence and a "→" link:
        - "Riscos sem vínculo", "Riscos identificados sem nenhuma mitigação aplicada.", "Ver riscos →" (goes to `/risks`, **unfiltered**).
        - "Vínculos sem evidência", "Mitigações aplicadas sem nada que comprove a execução.", "Ver vínculos →" (goes to `/links`, **unfiltered**).
        - "Eventos nos últimos 30 dias", "Problemas recentes nos sistemas em operação.", "Ver eventos →".
    4. **Card "Verificação dos vínculos"**, description "Vínculos cancelados ficam de fora. A primeira verificação também deixa de fora os sistemas na faixa inaceitável, que não podem ser verificados."
        - Three clickable tiles linking to filtered `/links`: "Aguardando verificação" (amber when above 0) with "Declarados à espera da prova: nunca verificados ou reavaliados sem verificação."; "Aguardando reavaliação" (amber) with "Revertidos para declarados, à espera de uma decisão."; "Verificados" with "Comprovados por evidência e em revisão periódica."
        - Sub-label "Aguardando reavaliação, pela origem da última reversão": 6 origin chips (Vencimento da revisão, Evento adverso, Reclassificação do sistema, Nova versão do modelo, Alteração na base de dados, Manual), each with a count and a link to the filtered list. The grid is 4 columns for 6 chips.
        - "Esperando reavaliação há mais tempo" (only when non-empty): a list of "Risco → Mitigação" links, each with "{origin} · {n dias}".
    5. **Card "Riscos não mapeados"**, description "Subdomínios citados por eventos adversos ou por mudanças do sistema em que o sistema não tem risco cadastrado: um risco que ainda não foi identificado."
        - Each row: "{code} {name}"; the linked system name; "Evento adverso: :count evento(s), o mais recente em {date link}" and/or "Mudança do sistema: :count mudança(s), a mais recente em {date link}"; an outline sm button "Cadastrar risco" (prefills system and subdomain).
        - Empty: "Todos os eventos estão em subdomínios com risco cadastrado no sistema."
    6. **2×2 grid of list cards:**
        - "Riscos sem vínculo" ("Os mais antigos primeiro."). Rows: risk link, system name, outline "Criar vínculo". Empty: "Todos os riscos têm ao menos um vínculo."
        - "Próximas revisões" ("Vencem hoje ou nos próximos 14 dias. A data é o último dia válido: no dia seguinte, o vínculo volta a declarado."). Rows: link label, "Revisão em {ReviewDate}", verification badge, and a badge "VENCE HOJE" (amber) or "EM DIA" (emerald). Empty: "Nenhuma revisão prevista para os próximos 14 dias."
        - "Vínculos sem evidência" ("Os mais antigos primeiro."). Rows: link label, "Criado em {date}", verification badge, outline "Registrar evidência". Empty: "Todos os vínculos têm ao menos uma evidência."
        - "Eventos recentes" ("Últimos 30 dias, do mais recente ao mais antigo."). Rows: system name (links to the event), subdomain badges, date. Empty: "Nenhum evento adverso nos últimos 30 dias."
    7. **Card "Vínculos por status"** ("Progresso das mitigações aplicadas; cancelados ficam de fora."): 5 rows (Planejado, Em andamento, Implementado, Suspenso, Cancelado is left out), each a status badge, a bar and a count.
    8. **Card "Por sistema de IA"**: table with columns Sistema (name link, domain sub-line, "Inaceitável" badge when applicable) | Riscos | Sem vínculo* | Vínculos | Aguardando reavaliação* | Eventos (30 dias)*. Columns marked * are amber and bold when above 0.
- **Jargon:** G1, G2, G9, G11, G12, G14, G16 ("VENCE HOJE", "EM DIA", "último dia válido"), G13 ("volta a declarado"), G31, G5 (subdomain codes), G10 (status bars).
- **Existing tooltips:** the ReviewDate tooltip only, and only when a date is null (rare here).
- **Friction:**
    - Very dense: about 9 stacked blocks, three of them duplicate counts (pending cards, then the same lists below).
    - The "Riscos sem vínculo" and "Vínculos sem evidência" CTAs lead to unfiltered lists; no matching filter exists on those lists.
    - Six origin chips in a 4-column grid leave a ragged second row.
    - "VENCE HOJE" and "EM DIA" are all-caps badges unlike the rest of the UI.
    - The number "Verificados" sits next to amber pending tiles with no visual hierarchy.
    - The chart color tokens are unused.

---

## 4. Sistemas de IA

### List: `/ai-systems` → `ai-systems/index` (AiSystemController@index)

- **Layout:** AppLayout. **Breadcrumbs:** Sistemas de IA.
- **Purpose:** the portfolio list.
- **Props:** `aiSystems`, paginated, with `risks_count`, latest first.
- **Blocks:**
    - Heading "Sistemas de IA" (**no description**; the welcome page has one: "O portfólio de sistemas, com seu domínio de aplicação, origem e faixa de risco no EU AI Act.").
    - Primary button "Cadastrar sistema de IA".
    - Table (row click → show): **Nome** (link, with the application domain in a muted sub-line) | **Origem** (outline badge) | **Faixa de risco** (colored badge) | **Cadastro** (date) | **Riscos** (right-aligned) | sr-only "Ações" column with the muted text link "Editar".
    - Pagination.
- **Empty state:** dashed box "Nenhum sistema de IA foi cadastrado ainda." with the primary button "Cadastrar sistema de IA".
- **Jargon:** "Faixa de risco" [G1], Origem [G3].
- **Friction:** no search or filter by category; no indication of systems with pending items; inline "Editar" inside a clickable row.

### Create: `/ai-systems/create` → `ai-systems/create` (form partial `ai-systems/form.tsx`)

- **Layout:** AppLayout. **Breadcrumbs:** Sistemas de IA (only; no "Cadastrar" crumb).
- **Purpose:** register a system and classify it under the EU AI Act.
- **Heading:** "Cadastrar sistema de IA", with "Adicione um sistema de IA ao portfólio e classifique-o conforme o EU AI Act."
- **Form** (max-w-xl, single column):
    1. **Nome**: text, required, max 255, placeholder "Ex.: Modelo de score de crédito".
    2. **Domínio de aplicação (opcional)**: text, max 255, placeholder "Ex.: Atendimento ao cliente", help "Onde o sistema é usado, para contextualizá-lo. Não altera nenhuma classificação."
    3. **Origem**: select, required, placeholder "Selecione a origem". Options: Interno, Terceiros, Código aberto, Híbrido.
    4. **Faixa de risco**: select, required, placeholder "Selecione a faixa de risco (EU AI Act)". Options in order: **Inaceitável**, Alto, Limitado, Mínimo.
    5. **Data de cadastro**: date, required.
- **Actions:** "Cadastrar" (primary) and "Cancelar" (ghost → list). Success toast "Sistema de IA cadastrado." and redirect to the system page.
- **Jargon:** G1, G2, G3, G4.
- **Friction:**
    - "Faixa de risco" has no help text explaining tiers or review intervals; the impact of the choice is invisible.
    - "Inaceitável" is the first option in the select.
    - "Origem" options are unexplained.

### Edit: `/ai-systems/{id}/edit` → `ai-systems/edit`

- **Breadcrumbs:** Sistemas de IA (only).
- **Heading:** "Editar sistema de IA", with the system name as description.
- Same form; submit "Salvar"; Cancelar returns to the **list**, not to the system.
- **Conditional:** when the user picks Inaceitável on a system that is not already unacceptable and has verified links, an inline red `role="alert"` box shows: "A faixa inaceitável corresponde a práticas proibidas: ao salvar, :count vínculo(s) verificado(s) deste sistema voltará(ão) a declarado(s), e o sistema deixa de ser considerado em operação."
- **Prop:** `verifiedLinksCount`.
- **Jargon:** G45, G13.
- **Friction:** a destructive side-effect hides behind a plain "Salvar" with no confirmation dialog, unlike every other permanent action. Cancel goes to the list.

### Detail: `/ai-systems/{id}` → `ai-systems/show`

- **Breadcrumbs:** Sistemas de IA › {name}.
- **Purpose:** the system hub: its risks, changes and exports.
- **Props:** `aiSystem`, with `risks` (subdomain.parent, links_count) and `system_changes` (subdomains, reversals_count).
- **Blocks:**
    1. **Header:** `h1` name, category badge, application domain (muted). Buttons: "Registrar evento adverso" (outline, prefills the system), "Editar" (outline), "Excluir" (destructive, **only when the system has no risks**). Delete opens "Excluir este sistema de IA?" / "O sistema ":name" será removido do portfólio. Esta ação não pode ser desfeita."
    2. **UnacceptableTierAlert** (long text, id `faixa-inaceitavel`) when the category is Inaceitável.
    3. **Card "Detalhes":** Origem, Faixa de risco, Cadastro, Riscos (count).
    4. **Card "Riscos"** with "Cadastrar risco" (primary sm, prefills the system). Table: Nome (link) | Subdomínio de risco (code+name, with the domain in a sub-line) | Fase do ciclo de vida | Incerteza (**plain text**, not a badge) | Vínculos. Rows are not clickable. Empty: "Nenhum risco foi mapeado para este sistema ainda."
    5. **Card "Mudanças do sistema"**, description "Novas versões do modelo e alterações na base de dados levam os vínculos verificados à reavaliação.", with "Registrar mudança" (primary sm). SystemChangesTable (row click): Data (link) | Tipo | Subdomínios afetados (codes, or "Todos (nenhum informado)") | Vínculos revertidos. Empty: "Nenhuma mudança foi registrada para este sistema."
    6. **Card "Relatório de rastreabilidade"**, description "O arquivo traz a cadeia completa de cada risco deste sistema… inclusive os que não reverteram vínculos." Four outline download anchors: "Baixar CSV", "Baixar JSON", "Baixar eventos adversos (CSV)", "Baixar mudanças do sistema (CSV)".
- **Jargon:** G1, G2, G5, G6, G7, G32, G33, G13, G42.
- **Friction:**
    - The system's adverse events are not listed here; there is only a button to create one.
    - Three header buttons are all outline, so there is no clear primary; the primary buttons sit inside cards.
    - "Excluir" silently disappears when risks exist, with no explanation.
    - Four equal-weight download buttons; "CSV" vs "JSON" is unexplained for non-technical users.
    - There is no link to the paginated system-changes list; it is reachable only through breadcrumbs.

### System changes list: `/ai-systems/{id}/system-changes` → `system-changes/index` (SystemChangeController@index)

- **Breadcrumbs:** Sistemas de IA › {system} › Mudanças.
- **Purpose:** paginated changes of one system.
- **Heading:** "Mudanças do sistema", with "{system}. Cada mudança fica registrada e não pode ser editada nem excluída."
- Primary button "Registrar mudança". SystemChangesTable (as above) with pagination.
- **Empty:** dashed "Nenhuma mudança foi registrada para este sistema."
- **Friction:** orphan page; no UI element links here except breadcrumbs.

### Record system change: `/ai-systems/{id}/system-changes/create` → `system-changes/create`

- **Breadcrumbs:** Sistemas de IA › {system} › Registrar mudança.
- **Purpose:** record a model or data change, which reverts verified links.
- **Props:** `aiSystem`, types, riskDomains (all MIT subdomains), expected (the system's risk profile), verifiedLinks.
- **Heading:** "Registrar mudança do sistema", with "{system}. Uma nova versão do modelo ou uma alteração na base de dados leva os vínculos verificados à reavaliação."
- **Form** (max-w-xl):
    1. **Tipo de mudança**: select, required, placeholder "Selecione o tipo". Options: Nova versão do modelo, Alteração na base de dados.
    2. **Descrição**: textarea, required, max 2000, placeholder "Ex.: Troca do modelo de classificação pela versão 2.0 do fornecedor."
    3. **Data da mudança**: date, required, max today, help "Não pode ser uma data futura."
    4. **Subdomínios de risco afetados (opcional)**: RiskSubdomainPicker (checkboxes), help "Informe os riscos que a mudança pode alcançar: só os vínculos deles serão revertidos. Sem nenhum, todos os vínculos verificados do sistema são revertidos. Taxonomia de domínios do MIT AI Risk Repository."
    5. Default Alert "O registro é permanente": "Mudanças do sistema não podem ser editadas nem excluídas. O registro devolve a declarados os vínculos verificados que a mudança alcança, aguardando reavaliação."
- **Actions:** "Registrar mudança" (primary, type=button, validates and then opens the dialog) and "Cancelar" (ghost → system).
- **Confirmation dialog** "Registrar esta mudança do sistema?" / "Depois de registrada, a mudança não pode ser editada nem excluída." plus a reversal preview:
    - Unacceptable system: "O sistema está na faixa inaceitável: a mudança fica registrada, sem reverter vínculos."
    - No subdomain chosen: "Nenhum subdomínio foi informado: todos os vínculos verificados deste sistema serão revertidos. Informar os subdomínios afetados limita a reversão aos riscos realmente envolvidos."
    - Then either "Nenhum vínculo verificado será revertido." or an amber box ":count vínculo(s) verificado(s) voltará(ão) a declarado(s), aguardando reavaliação:" with a list "Risco → Mitigação (código)".
    - Buttons: "Voltar" (secondary) and "Registrar" (primary).
- **Jargon:** G32, G33, G5, G30, G13, G12, G43, G2.
- **Friction:**
    - The consequential choice (subdomains) comes after the description and date, and its impact is visible only in the dialog.
    - The picker is a long scrolling box (max-h 32rem) with English definitions.
    - "(opcional)" understates a choice that decides between reverting everything and a targeted reversal.

### System change detail: `/system-changes/{id}` → `system-changes/show`

- **Breadcrumbs:** Sistemas de IA › {system} › Mudanças › {type}.
- **Header:** `h1` with the type label (e.g. "Nova versão do modelo"); system link (muted). Muted note: "Mudanças do sistema não podem ser editadas nem excluídas: elas registram o que mudou e explicam as reversões que causaram."
- **Card "Detalhes":** Tipo, Data da mudança, Descrição, and "Subdomínios de risco afetados". The last is either a list with an amber "Sem risco cadastrado neste sistema." note and a "Cadastrar risco" link per unmapped subdomain, or "Nenhum informado: a mudança reverteu todos os vínculos verificados do sistema."
- **Card "Vínculos revertidos pela mudança":** description "Ao registrar a mudança, os vínculos verificados que ela alcança voltaram a declarados. Cada um é reavaliado separadamente."
    - Progress line ":reassessed de :count reavaliado(s)".
    - Rows: link label, then either an outcome badge with "em {date}" (→ reassessment) or amber "Aguardando reavaliação".
    - Empty: "Nenhum vínculo verificado foi revertido por esta mudança."
- **No actions** in the header; it is append-only.
- **Jargon:** G31, G13, G12, G34.
- **Friction:** a pending row has no "Reavaliar" button; the user must open the link first.

---

## 5. Riscos

### List: `/risks` → `risks/index` (RiskController@index)

- **Breadcrumbs:** Riscos.
- **Purpose:** all risks across systems.
- **Props:** paginated risks with aiSystem, subdomain.parent and links_count, latest first.
- **Heading:** "Riscos" (no description). Primary button "Cadastrar risco".
- **Table** (row click):
    - **Nome** (link, with a truncated one-line description below);
    - **Sistema** (link);
    - **Subdomínio de risco** (code+name, with the domain below);
    - **Fase do ciclo de vida**;
    - **Incerteza** (badge: Baixa gray / Média amber / Alta red);
    - **Vínculos**;
    - an Actions column with "Editar" (muted link) and "Excluir" (red text button, only when links = 0) → dialog "Excluir este risco?" / "O risco ":name" será removido. Esta ação não pode ser desfeita."
- **Empty:** "Nenhum risco foi cadastrado ainda." with the button "Cadastrar risco".
- **Jargon:** G5, G6, G7.
- **Friction:**
    - Seven columns; no filters (system, domain, "sem vínculo") even though the dashboard sends users here for "Riscos sem vínculo".
    - "Incerteza" colors (Alta = red) read like severity, but this is uncertainty about the assessment.

### Create: `/risks/create[?ai_system=&subdomain=]` → `risks/create` (partial `risks/form.tsx`)

- **Breadcrumbs:** Riscos (only).
- **Heading:** "Cadastrar risco", with "Registre um risco identificado em um sistema de IA."
- **Props:** aiSystems, riskDomains (with descriptions), lifecyclePhases, uncertaintyLevels.
- **Empty precondition** (no systems): dashed box "Todo risco pertence a um sistema de IA, e nenhum sistema foi cadastrado ainda. Cadastre um sistema antes de registrar seus riscos." with the button "Cadastrar sistema de IA".
- **Form** (max-w-xl, single column):
    1. **Sistema de IA**: select, required, placeholder "Selecione o sistema". Prefilled from the query.
    2. **Nome**: text, required, max 255, placeholder "Ex.: Viés de seleção", help "O tipo de risco, em poucas palavras. É o que aparece nas listas e pode se repetir em outros sistemas."
    3. **Descrição**: textarea, required, max 2000, placeholder "Ex.: O modelo apresenta desempenho inferior para grupos sub-representados nos dados de treino, o que pode levar a decisões discriminatórias.", help "Como esse risco se manifesta neste sistema: o contexto, o mecanismo e quem pode ser afetado." Live counter "n/2000".
    4. **Domínio de risco**: select (not submitted, it only narrows the next field), placeholder "Selecione o domínio". Options are "1 …" through "7 …". Help: "A classificação padronizada do risco, pela taxonomia do MIT AI Risk Repository (Slattery et al.). Liga o risco às mitigações recomendadas e aos eventos adversos."
    5. **Subdomínio de risco**: select, required, disabled until a domain is chosen. Placeholder "Selecione primeiro o domínio" / "Selecione o subdomínio". When chosen, shows the source definition **in English** (`lang="en"`, left border quote).
    6. **Fase do ciclo de vida**: select, required, placeholder "Selecione a fase". The 8 ISO/IEC 22989 phases: Concepção, Projeto e desenvolvimento, Verificação e validação, Implantação, Operação e monitoramento, Validação contínua, Reavaliação do sistema, Retirada.
    7. **Nível de incerteza**: select, required, placeholder "Selecione o nível" (Baixa / Média / Alta).
- **Actions:** "Cadastrar" (primary) and "Cancelar" (ghost → list). Toast "Risco cadastrado."
- **Jargon:** G5 (the help exists but cites "Slattery et al."), G6, G7.
- **Friction:**
    - Seven ungrouped fields. Classification fields (domain, subdomain, phase, uncertainty) are not visually separated from identity fields (system, name, description).
    - Subdomain definitions are in English.
    - Phase and uncertainty have no help text.

### Edit: `/risks/{id}/edit` → `risks/edit`

- **Breadcrumbs:** Riscos (only). **Heading:** "Editar risco", with the risk name.
- Same form; submit "Salvar"; Cancelar → the risk.
- **Conditional (links > 0):** the system field becomes read-only text: label "Sistema de IA", the name, and "Não pode ser alterado: o risco tem :count vínculo(s) na cadeia de rastreabilidade deste sistema."

### Detail: `/risks/{id}` → `risks/show`

- **Breadcrumbs:** Riscos › {name}.
- **Header:** `h1` name. Buttons: "Editar" (outline) and "Excluir" (destructive, only with no links; same dialog as the list).
- **Card "Detalhes":** Descrição (pre-line); Domínio de risco / Subdomínio de risco (with the English definition below); Sistema de IA (link) / Fase do ciclo de vida / Incerteza (badge).
- **Card "Vínculos"** with "Criar vínculo" (primary sm, `?risk=`).
    - Table (row click): Mitigação | Responsável (role, with area below) | Status (two badges) | Custo estimado | Próxima revisão (ReviewDate, with tooltip when no date).
    - Empty: "Este risco foi identificado, mas ainda não tem nenhuma mitigação associada. Crie um vínculo para definir como ele será tratado e quem responde por isso."
- **Jargon:** G5, G6, G7, G8, G9, G10, G16, G18.
- **Friction:** the empty-links state (the most important to-do) is plain muted text rather than a highlighted call to action.

---

## 6. Eventos adversos

### List: `/adverse-events[?nature=&domain=]` → `adverse-events/index` (AdverseEventController@index)

- **Breadcrumbs:** Eventos adversos.
- **Purpose:** the event log, newest first.
- **Props:** adverseEvents (aiSystem, subdomains, status_histories_count), filters, natures, riskDomains.
- **Heading:** "Eventos adversos", with "O que deu errado nos sistemas em operação, do mais recente ao mais antigo." Primary button "Registrar evento adverso".
- **Filters:**
    - Row 1 (FilterLink): "Todas as naturezas" · Incidente · Quase-incidente.
    - Row 2 (subtle): "Todos os domínios" · "1 …" … "7 …" (7 domain chips).
- **Table** (row click): **Natureza** (badge: Incidente red, Quase-incidente outlined) | **Subdomínios de risco** (truncated badges with a `title`) | **Sistema** (link) | **Ocorrência** (date) | **Descrição** (truncated link) | **Mudanças disparadas** (count, right).
- **Empty states:**
    - With a filter: "Nenhum evento adverso neste filtro materializa riscos do domínio :domain." or "Nenhum evento adverso neste filtro.", with the outline button "Ver todos os eventos".
    - Without a filter: "Nenhum evento adverso foi registrado." with the button "Registrar evento adverso".
- **Jargon:** G25, G26, G27, G5. "Mudanças disparadas" is a count of status-history entries citing the event, and that meaning is unclear.
- **Friction:**
    - There are 7 domain chips with long labels; they wrap onto several lines.
    - No system filter.
    - "Mudanças disparadas" is ambiguous: it is not the number of reverted links.

### Register: `/adverse-events/create[?ai_system=]` → `adverse-events/create`

- **Breadcrumbs:** Eventos adversos › Registrar.
- **Purpose:** record an incident or near miss. This reverts matching verified links.
- **Props:** aiSystems, riskDomains, expectedRiskSubdomainsBySystem, natures, linksBySystem.
- **Heading:** "Registrar evento adverso", with "Registre algo que deu errado em um sistema de IA em operação."
- **Empty precondition:** muted text "Nenhum sistema de IA foi cadastrado ainda. Todo evento adverso acontece em um sistema." (no CTA button).
- **Form** (max-w-xl, single column):
    1. **Sistema afetado**: select, required, placeholder "Selecione o sistema". Items read "Nome · domínio". Prefill via `?ai_system`.
    2. **Natureza**: select, required, sm:w-64, placeholder "Selecione a natureza". Options: Incidente, Quase-incidente. Help: "Um quase-incidente foi percebido antes de causar dano. Os dois disparam a reavaliação dos vínculos."
    3. **Subdomínios de risco materializados**: RiskSubdomainPicker, required (at least one, checked on the client).
        - Help: "Selecione os riscos que a ocorrência materializa; um evento pode tocar mais de um. Taxonomia de domínios do MIT AI Risk Repository."
        - Before a system is chosen: dashed "Escolha o sistema primeiro."
        - "Selecionados: 1.1, 2.3" line.
        - A scroll box (max-h 32rem) containing:
            - section "Esperados para este sistema" (each checkbox shows code+name, "{domain} · :count risco(s) cadastrado(s)" and the English definition); or the line "Este sistema ainda não tem riscos cadastrados: todos os subdomínios aparecem abaixo, por domínio.";
            - section "Outros subdomínios" ("Um evento fora dos riscos cadastrados pode indicar um risco ainda não identificado."), grouped by domain headers.
        - Error: "Selecione ao menos um subdomínio de risco que a ocorrência materializa."
    4. **Descrição**: textarea, required, max 2000, placeholder "Ex.: O modelo recusou todas as solicitações de clientes acima de 60 anos durante dois dias."
    5. **Data de ocorrência**: date, required, max today, help "Não pode ser uma data futura."
    6. **Data de detecção (opcional)**: date, min = occurrence, max today, help "Quando o monitoramento percebeu a ocorrência, entre a data de ocorrência e hoje. O intervalo mede a demora em detectar."
    7. **Vínculo que interceptou (opcional)**: only when Natureza = Quase-incidente and a system is chosen. Select with "Nenhum" plus eligible links "Risco → Mitigação (código)". Disabled when there are none. Help: either "O vínculo cuja mitigação barrou a ocorrência. Ele não é revertido, porque o evento mostra que funcionou." or "Nenhum vínculo deste sistema trata os subdomínios selecionados."
    8. Default Alert (triangle) "O registro é permanente": "Eventos adversos não podem ser editados nem excluídos. O registro devolve a declarados os vínculos verificados do sistema cujo risco está em algum dos subdomínios do evento."
- **Actions:** "Registrar evento" (primary, type=button, opens the dialog) and "Cancelar" (ghost → list).
- **Dialog** "Registrar este evento adverso?":
    - Text: "Confira o sistema, os subdomínios de risco (:subdomains) e a data: depois de registrado, o evento não pode ser editado nem excluído."
    - Preview: "Nenhum vínculo verificado deste sistema será revertido." or an amber box ":count vínculo(s) verificado(s) voltará(ão) a declarado(s), aguardando reavaliação:" with a list.
    - Buttons: "Voltar" (secondary) and "Registrar" (primary).
- **Toast:** "Evento adverso registrado."
- **Jargon:** G25, G26, G27, G30, G31, G28, G29, G13, G12, G43, G5.
- **Friction:**
    - The longest form in the app, with no section grouping.
    - The picker is a very long checklist with English definitions.
    - The interceptor field appears conditionally at the bottom, far from the Natureza choice that triggers it.
    - No definition of what qualifies as an adverse event (0026 notes the definition text is not yet shown on the form).
    - The empty precondition has no CTA.

### Detail: `/adverse-events/{id}` → `adverse-events/show`

- **Breadcrumbs:** Eventos adversos › "Evento adverso: {subdomain}". The crumb uses "code name" for one subdomain, or a list of codes.
- **Props:** adverseEvent (with reversals and reassessments, intercepting_link, status_histories), detectionDelayDays, unmappedSubdomainCodes.
- **Header:** `h1` "Evento adverso: …", nature badge; system link · domain. Muted note: "Eventos adversos não podem ser editados nem excluídos: eles registram o que aconteceu e explicam as mudanças de status que disparam."
- **Card "Detalhes":**
    - Natureza; Data de ocorrência; Data de detecção (date plus "Detectado no mesmo dia / 1 dia depois / :count dias depois", or "Não informada").
    - "Subdomínios de risco materializados": a list (code name, with domain), plus an amber "Sem risco cadastrado neste sistema." and a "Cadastrar risco" link per unmapped subdomain.
    - Descrição.
- **Card "Vínculo que interceptou"** (only when set): description "A mitigação deste vínculo barrou a ocorrência, por isso ele não foi revertido. Registre uma evidência nele para comprovar o funcionamento." Shows the link label and "Registrar evidência" (primary sm).
- **Card "Vínculos revertidos pelo evento":** description "Ao registrar o evento, os vínculos verificados do sistema cujo risco está em algum dos subdomínios voltaram a declarados. Cada um é reavaliado separadamente."
    - Progress line ":reassessed de :count reavaliado(s)".
    - Rows: link label, then an outcome badge with "em {date}", or amber "Aguardando reavaliação".
    - Empty: "Nenhum vínculo verificado foi revertido por este evento."
- **Card "Outras mudanças que citam o evento"** (only when present): table Vínculo | Transição (StatusTransition) | Responsável (EntryAuthor) | Data.
- **No header actions** (append-only).
- **Jargon:** G26, G27, G29, G28, G31, G13, G12, G34, G15.
- **Friction:** a pending reverted link has no direct "Reavaliar" button.

---

## 7. Mitigações (catálogo)

### List: `/mitigations[?category=&subcategory=&q=]` → `mitigations/index` (MitigationController@index)

- **Breadcrumbs:** Catálogo de mitigações.
- **Purpose:** the read-only catalog (C2).
- **Props:** paginated mitigations (subcategory.parent, links_count), filters, categories (Saeri tree), catalog.fictional.
- **Heading:** "Catálogo de mitigações", with "O catálogo curado (C2), organizado pela taxonomia de Saeri et al. (2025): 4 categorias e 23 subcategorias. Toda mitigação aplicada a um risco vem daqui."
- FictionalCatalogAlert when fictional (currently off).
- **Filter bar:**
    - Category chips: "Todas" plus 4 categories (code name).
    - On the right, a search form: input placeholder "Buscar pelo nome" (aria "Buscar pelo nome em português ou pelo nome original") and the outline button "Buscar".
    - Inside a category, a second row of subtle chips: "Todas de :category" plus subcategories.
- **Table** (row click): **Nome** (link, with the original English name below, truncated) | **Subcategoria** (outline badge) | **Custo sugerido** | **Incerteza** (badge) | **Vínculos**.
- **Below the table** (when filtered): ":count mitigação(ões) encontrada(s)." with the link "Limpar filtros".
- **Empty states:**
    - "O catálogo está vazio. Ele é carregado a partir do arquivo de dados do projeto, pelo seeder."
    - "Nenhuma mitigação com ":q" no nome [na categoria/subcategoria …]."
    - "Nenhuma mitigação [na categoria …]."
    - Each with the link "Ver o catálogo inteiro".
- **Actions:** none to create (read-only by design).
- **Jargon:** G20, G21, G18 (Custo sugerido), G7, G23.
- **Friction:**
    - The sidebar label "Mitigações" differs from the page title.
    - "(C2)" and "Saeri et al. (2025)" in the heading are research jargon.
    - The empty-state copy mentions "seeder", a developer term.
    - No filter by MIT risk subdomain treated, even though that is how mitigations relate to risks.

### Detail: `/mitigations/{id}` → `mitigations/show`

- **Breadcrumbs:** Catálogo de mitigações › {name}.
- **Props:** mitigation (subcategory.parent, targetRiskSubdomains, links), sourceDocument, taxonomy and riskTaxonomy (citation, version, url), catalog.
- **Header:** `h1` name with a subcategory outline badge. Muted: "Entrada do catálogo curado (C2). Mitigações não são cadastradas nem editadas pela aplicação."
- **Card "Descrição".**
- **Two columns:**
    - **Card "Taxonomia de Saeri et al."**, description "Classificação e rastreio até a fonte. {version link}." Fields: Categoria (pt name plus the English original); Subcategoria (pt name, English original, English description); Nome original (English); Documento de origem ("title (author, year)"); Identificador na base (`code`).
    - **Card "Contribuição do framework (C2)"**, description "Saeri et al. não avaliam risco tratado, custo nem eficácia: estes campos são do grupo, com a fonte que os fundamenta. O custo real de cada aplicação fica no vínculo." Fields: "Subdomínios de risco tratados" (a list, plus the footnote "Taxonomia de domínios do MIT AI Risk Repository (version)" with an external link); "Risco-alvo sugerido"; "Evidência esperada"; "Custo sugerido"; "Incerteza" (badge); "Fonte da estimativa".
- **Card "Vínculos que aplicam esta mitigação":** table Vínculo | Responsável | Status (row click). Empty: "Esta mitigação ainda não foi aplicada a nenhum risco."
- **No action** to apply this mitigation to a risk (no "Criar vínculo" from here).
- **Jargon:** G20, G21, G23, G22, G19, G18, G7, G5. "Evidência esperada" is useful but appears only here.
- **Friction:**
    - Mixed pt/en content.
    - The provenance-heavy layout puts research metadata on equal footing with practical info.
    - No "Criar vínculo com esta mitigação" shortcut.

---

## 8. Vínculos (core)

### List: `/links[?verification=&origin=]` → `links/index` (LinkController@index)

- **Breadcrumbs:** Vínculos.
- **Purpose:** every risk→mitigation link, ordered by next review (no-date links last).
- **Props:** links (risk.aiSystem, mitigation, owner, lastReversal, counts), filters.
- **Heading:** "Vínculos", with "Mitigações aplicadas a cada risco, ordenadas pela próxima revisão." Primary button "Criar vínculo".
- **Filters:**
    - Row 1: "Todos" · "Aguardando verificação" · "Aguardando reavaliação" · "Verificados".
    - With "Aguardando reavaliação", a subtle row 2: "Todas as origens" plus the 6 origins.
    - With any filter, a muted explanation line:
        - Verificados: "Vínculos verificados que não foram cancelados."
        - Aguardando reavaliação: "Vínculos revertidos cuja última reversão ainda não foi reavaliada, dos que esperam há mais tempo. Os de sistemas na faixa inaceitável aparecem aqui para planejar a descontinuação."
        - Aguardando verificação: "Vínculos declarados à espera da prova: os que aguardam a primeira verificação e os reavaliados ainda sem verificação. Ficam de fora os cancelados e os de sistemas na faixa inaceitável, que não podem ser verificados."
- **Table** (row click): **Risco** (link, with the system name below) | **Mitigação** (link) | **Responsável** (role / area) | **Status** (progress plus verification badges) | **Próxima revisão** (ReviewDate), or **Aguardando há** (WaitingDays) in the reassessment filter | **Custo estimado**.
- **Empty states:**
    - With a filter: "Nenhum vínculo aguarda verificação." / "Nenhum vínculo aguarda reavaliação." / "Nenhum vínculo está verificado.", with the outline button "Ver todos os vínculos".
    - Without a filter: "Nenhum vínculo foi criado ainda." with the button "Criar vínculo".
- **Jargon:** G8, G9, G10, G11, G12, G14, G16, G18, G2.
- **Friction:**
    - No "sem evidência" filter, though the dashboard card sends users here.
    - No search or system filter.
    - Two badges per row plus links in three cells; dense.
    - The "Verificados" chip vs the "Verificada" badge are inconsistent forms.

### Create: `/links/create[?risk=&replaces=]` → `links/create`

- **Breadcrumbs:** Vínculos › Criar vínculo.
- **Purpose:** apply a catalog mitigation to a risk under an owner.
- **Props:** risks (with system category, domain/subdomain, linked_mitigation_ids), mitigations (category, subcategory, cost, uncertainty, estimate source, targets), owners (active only), saeriCategories, lifecyclePhases, costLevels, reviewIntervals, replacing.
- **Heading:** "Criar vínculo", with "Associe uma mitigação do catálogo a um risco já cadastrado."
- **Precondition state:** "Para criar um vínculo é preciso ter :items." where items are "um risco", "uma mitigação no catálogo" and/or "um responsável", with primary buttons "Cadastrar risco" / "Ver o catálogo de mitigações" / "Cadastrar responsável".
- **Form** (max-w-xl): 0. **Replacement banner** (sky; only when `replacing` matches the chosen risk): "Substituição do vínculo :risk → :mitigation" / "Ele foi cancelado na reavaliação. Escolha outra mitigação para o mesmo risco." (hidden `replaces_link_id`).
    1. **Risco**: select, required, placeholder "Selecione o risco", grouped by system (SelectLabel = system name). Below it: "Subdomínio de risco (MIT): :subdomain, em :domain". When the system is unacceptable, a short UnacceptableTierAlert.
    2. **Mitigação**, in three parts:
        - ToggleGroup (outline sm) of Saeri categories: "Todas" plus 4 (aria "Filtrar por categoria da taxonomia de Saeri et al.").
        - When a category is chosen, a subcategory select: "Todas as subcategorias de :category" plus a list.
        - The mitigation select, required, placeholder "Selecione uma mitigação do catálogo", grouped "Recomendadas para este risco" / "Outras do catálogo" (or "Catálogo" before a risk is chosen).
        - Messages: "Nenhuma mitigação deste filtro trata o subdomínio :code; todas continuam disponíveis." / "Este risco já está vinculado a todas as mitigações do catálogo." / "Nenhuma mitigação disponível neste filtro."
        - When chosen, a muted info panel: Subcategoria (Saeri et al.); Subdomínios de risco tratados (MIT) (the risk's own subdomain in bold); Custo sugerido; Incerteza (badge); Fonte da estimativa.
    3. **Responsável**: select, required, placeholder "Selecione o papel responsável". Items read "Cargo (Área)".
    4. Two columns: **Fase do ciclo de vida** (select, required, "Selecione a fase") and **Custo estimado** (select, required, "Selecione o custo"; auto-filled from the mitigation's suggested cost).
    5. **Review notice** (muted text), one of:
        - before a risk: "O vínculo nasce Planejado e Declarado. A revisão periódica começa na primeira verificação, com o intervalo da faixa do sistema do risco escolhido.";
        - with a tier: "… a próxima revisão será :days dias depois dela, porque o sistema é de risco :tier.";
        - unacceptable: "O vínculo nasce Planejado e Declarado e não terá revisão periódica: … Ele serve para planejar a descontinuação."
- **Actions:** "Salvar" (primary, disabled until a risk is chosen) and "Cancelar" (ghost → list). Toast "Vínculo criado com sucesso."
- **Jargon:** G5, G20, G22, G18, G19, G7, G38, G6, G9/G10 (via "nasce Planejado e Declarado"), G1, G2, G37.
- **Friction:**
    - Three controls to pick one mitigation (toggle, subcategory select, mitigation select), each Radix Select showing names only. Descriptions are not visible while choosing.
    - The submit is "Salvar" while the page title is "Criar vínculo".
    - The tutorial claims this screen shows the fictional-catalog warning; it does not.
    - No hint that the pair cannot be changed later (tutorial: "não dá para trocar o risco nem a mitigação").

### Edit: `/links/{id}/edit` → `links/edit`

- **Breadcrumbs:** Vínculos › {Risco → Mitigação} › Editar.
- **Heading:** "Editar vínculo", with the link label.
- **Read-only card "Identificação"** (max-w-xl): Risco; Mitigação; Status (badges plus the link "Alterar pelo histórico"); Data de criação; Custo observado (ObservedCost); Próxima revisão (ReviewDate).
- **Form:**
    1. **Responsável**: select, required, "Selecione o responsável". Active owners plus the current one, marked "(inativo)" if retired.
    2. **Fase do ciclo de vida**: select, required.
    3. **Custo estimado**: select, required, alone in a 2-column grid.
- **Actions:** "Salvar" (primary) and "Cancelar" (ghost → link). Toast "Vínculo atualizado."
- **Jargon:** G18, G16, G39 ("inativo"), G10.
- **Friction:** a half-empty 2-column grid. Why most fields are locked is not explained (only "Alterar pelo histórico").

### Detail: `/links/{id}[?verify=1]` → `links/show`

- **Breadcrumbs:** Vínculos › {Risco → Mitigação}.
- **Purpose:** the link hub: details, verification, reassessments, evidence, history.
- **Props:** link (many relations), verification {problem, owners}, awaitingReassessment.
- **Blocks:**
    1. **Header:**
        - `h1` "{Risco link} → {Mitigação link}" (the arrow has aria "mitigado por"); below it, status badges and the system link.
        - Buttons: "Editar" (outline); "Reavaliar" (primary, only when awaiting reassessment); then either "Reativar vínculo" (primary, when cancelled → status form with `new_status=planned`) or "Cancelar vínculo" (outline with red text → status form with `new_status=cancelled`).
    2. **Card "Detalhes":** Responsável (role / area), Fase do ciclo de vida, Custo estimado, Custo observado, Data de criação, Próxima revisão (ReviewDate).
    3. **UnacceptableTierAlert** (long text) when the system is unacceptable.
    4. **LinkVerificationCard**, titled "Verificação" with a verification badge; description "A verificação comprova, com evidência, que a mitigação está aplicada. A revisão periódica começa na verificação."
        - **Reversal banner** (amber; only when declared and a reversal exists). Headline, one of: "Reavaliado ({outcome em date link}): aguardando verificação"; "{Sistema na faixa inaceitável link}: planejar a descontinuação"; or "Aguardando reavaliação". Lines: "Verificação revertida em :date (origem: :origin)."; "Motivo: …"; "Mudança do sistema: {tipo de data link}"; "Evento adverso: {códigos de data link}".
        - `dl`: "Última verificação" (date or "Nunca verificado") and "Verificado por" (EntryAuthor or "—").
        - When declared: "Verificar" (primary, disabled when `problem` is set, with the reason as muted text below; e.g. "Registre uma evidência antes de verificar o vínculo.").
        - When verified: "Renovar verificação" (primary, may be disabled with a reason) and "Reverter verificação" (outline).
        - **Verify dialog**: title "Verificar este vínculo?" or "Renovar a verificação deste vínculo?". Description "A revisão periódica passa a contar a partir de hoje, com o intervalo da faixa do sistema." (or the renewal text) plus "O registro fica no histórico." Field **Quem verifica**: select, required, "Selecione o papel responsável", preselected with the link's owner. Buttons "Voltar" (secondary) and "Verificar" / "Renovar" (primary). It auto-opens with `?verify=1`.
        - **Revert dialog**: title "Reverter a verificação?". Description "O vínculo volta a declarado e sai da revisão periódica. Para verificá-lo de novo, será preciso registrar uma evidência nova." Fields **Quem reverte** (select, required) and **Motivo** (textarea, required, max 255, placeholder "Ex.: A auditoria encontrou o controle desativado em produção."). Buttons "Voltar" and "Reverter" (destructive).
    5. **Card "Substituição"** (only when relevant): "Este vínculo substitui {link}, cancelado numa reavaliação." / "Este vínculo foi substituído por {link}."
    6. **Card "Reavaliações"** with "Reavaliar" (primary sm, when pending).
        - Rows: "{Desfecho} em {date}" (link), then muted "Reversão por {origem} · {cargo}".
        - Empty: "A última reversão aguarda reavaliação." or "Este vínculo ainda não foi reavaliado."
    7. **Two CountCards:**
        - "Evidências", "Documentos e registros que comprovam a aplicação da mitigação.", a big count, "Registrar evidência" (ghost) and "Ver evidências" (outline).
        - "Histórico de status", "Cada mudança de status e de verificação do vínculo, com quem a registrou e quando.", a count, "Registrar mudança" (ghost) and "Ver histórico" (outline).
- **Jargon:** almost the whole glossary: G8–G18, G2, G13, G14, G15, G34, G37, G38, G40.
- **Friction:**
    - Six or seven stacked cards. The two status dimensions appear in the header, Detalhes and the verification card.
    - "Reavaliar" can appear twice.
    - The most important call to action depends on state (Verificar / Renovar / Reavaliar / Registrar evidência), and nothing presents it as "next step".
    - "Cancelar vínculo" is an outline button styled red that navigates to a form, not a dialog.
    - "Reverter verificação" is outline in the card but its confirm is destructive.
    - The disabled-button reason is small muted text.
    - Evidence and history are only counts; recent items are not shown inline.
    - There is no link to the paginated reassessments list.

### Evidence list: `/links/{id}/evidence` → `evidence/index` (EvidenceController@index)

- **Breadcrumbs:** Vínculos › {link} › Evidências.
- **Heading:** "Evidências", with the link label as description. Primary button "Registrar evidência".
- **Conditional default Alert** (when the link is declared and verifiable): "Este vínculo pode ser verificado" / "O vínculo está declarado e já tem a evidência que a verificação exige.", with the primary sm button "Verificar vínculo" (→ `links/{id}?verify=1`).
- **Table** (row click): Tipo (link) | Descrição (truncated) | Custo observado (or "—") | Registrada em.
- **Empty:** "Nenhuma evidência foi registrada para este vínculo. Sem evidência, não há como comprovar que a mitigação foi aplicada." with "Registrar evidência".
- **Jargon:** G18, G9.

### Register evidence: `/links/{id}/evidence/create` → `evidence/create`

- **Breadcrumbs:** Vínculos › {link} › Evidências › Registrar.
- **Heading:** "Registrar evidência", with "{link}. A data de registro é definida automaticamente."
- **Form:**
    1. **Tipo**: select, required, "Selecione o tipo". Options: Documento, Relatório, Log de auditoria, Resultado de teste, Certificação, Ata de reunião.
    2. **Descrição**: textarea, required, **max 255**, placeholder "Ex.: Relatório de auditoria de equidade do 1º trimestre, assinado pelo comitê."
    3. **Custo observado (opcional)**: select, sm:w-64. Options: "Não informado", Baixo, Médio, Alto. Help: "O custo real da aplicação até agora (RF07). O informado na evidência mais recente passa a ser o custo observado do vínculo."
    4. Alert (lock icon) "Evidências são permanentes": "Depois de registrada, a evidência não pode ser editada nem excluída, porque sustenta a verificação do vínculo. Um engano se corrige registrando outra evidência."
- **Actions:** "Registrar" (primary, opens the dialog) and "Cancelar" (ghost → evidence list).
- **Dialog** "Registrar esta evidência?" / "Confira o tipo e a descrição: depois de registrada, a evidência não pode ser editada nem excluída." Buttons "Voltar" and "Registrar".
- **Toast:** "Evidência registrada." Redirects to the evidence list.
- **Jargon:** G18, G44 ("RF07"), G43.
- **Friction:**
    - No file upload or URL field; evidence is text only.
    - The mitigation's "Evidência esperada" is not shown here, where it would help most.
    - Max 255 characters for the description is tight.

### Evidence detail: `/evidence/{id}` → `evidence/show`

- **Breadcrumbs:** Vínculos › {link} › Evidências › {Tipo}.
- **Header:** `h1` type label; link label (muted link). Button "Registrar nova evidência" (outline). Muted note: "Evidências não podem ser editadas nem excluídas. Para corrigir esta, registre uma nova."
- **Card "Detalhes":** Tipo, Registrada em, Custo observado ("Não informado"), Descrição.

### Status history (timeline): `/links/{id}/status-histories` → `status-histories/index`

- **Breadcrumbs:** Vínculos › {link} › Histórico.
- **Props:** link, and entries: a mixed timeline of changes and reassessments, newest first, paginated at 15.
- **Heading:** "Histórico de status", with "{link}. Cada mudança fica registrada e não pode ser editada nem excluída." Primary button "Registrar mudança".
- **Vertical timeline** (left border, dots: filled primary dot for reassessment, hollow for a change):
    - Change entry: date (link) plus StatusTransition; "Registrado pelo sistema (origem: …)" or "Registrado por :role (:area)"; the reason text; "Evento adverso: {códigos} em {data}".
    - Reassessment entry: date (link), "Reavaliação:" with an outcome badge, "Reavaliado por :role (:area)", the justification.
- **No empty state** (the opening entry always exists).
- **Jargon:** G40, G15, G9, G10, G17, G34.
- **Friction:** dates are the only clickable element; the origin is shown only as text.

### Record status change: `/links/{id}/status-histories/create[?new_status=]` → `status-histories/create`

- **Breadcrumbs:** Vínculos › {link} › Histórico › Registrar mudança.
- **Props:** link, statuses (allowed transitions only), owners (active), adverseEvents (**all events, every system**).
- **Heading:** "Registrar mudança de status", with the link label.
- **Form:**
    1. "Status atual": read-only badges.
    2. **Novo status**: select, required, "Selecione o novo status". From Cancelado, only Planejado is offered.
    3. **Motivo**: textarea, max 255. Optional, but **required when cancelling** (the "(opcional)" suffix disappears). Placeholder "O que motivou a mudança." or "Explique por que o vínculo está sendo cancelado."
    4. Two columns: **Registrado por** (select, required, default = the link's owner, "Selecione o responsável") and **Data da mudança** (date, required, default today, no max).
    5. **Evento adverso relacionado (opcional)**: select. "Nenhum", or items "códigos · sistema · data".
- **Actions:** "Registrar mudança" (primary), which becomes "Cancelar vínculo" (**destructive**) when Novo status = Cancelado; "Voltar" (ghost → link).
- **Toast:** "Mudança de status registrada." Redirects to the timeline.
- **Jargon:** G10, G38, G25.
- **Friction:**
    - The secondary button is "Voltar" here but "Cancelar" elsewhere, which collides with "Cancelar vínculo".
    - Cancelling a link has no confirmation dialog, unlike other permanent records.
    - The related-event list is unfiltered by system.
    - No explanation of what each progress status means.

### Status entry detail: `/status-histories/{id}` → `status-histories/show`

- **Breadcrumbs:** Vínculos › {link} › Histórico › {date}.
- **Header:** `h1` "Renovação da verificação", "Mudança de verificação" or "Mudança de status", plus StatusTransition.
- **Card "Detalhes":** Vínculo (link), Data da mudança, Registrado por (EntryAuthor). Then either "Evidências que embasaram a verificação" (a list of "{Tipo} de {data}" links with descriptions, or "Nenhuma evidência encontrada"), or "Motivo" (text or "Não informado").
- **Card "Evento adverso relacionado"** (when present): Subdomínios de risco (list), Ocorrência, Sistema de IA, Descrição. No link to the event page.
- **Jargon:** G41, G15, G17.
- **Friction:** the related event card does not link to the event.

### Reassessments list: `/links/{id}/reassessments` → `reassessments/index` (ReassessmentController@index)

- **Breadcrumbs:** Vínculos › {link} › Reavaliações.
- **Heading:** "Reavaliações", with "{link}. Cada reavaliação conclui uma reversão e não pode ser editada nem excluída."
- **Table** (row click): Data (link) | Desfecho (outline badge) | Reversão (origin label) | Causa (Apurada / Não apurada / Não se aplica) | Quem reavaliou (role).
- **Empty:** dashed "Este vínculo ainda não foi reavaliado."
- **No actions.**
- **Friction:** orphan page, reachable only through the breadcrumb from a reassessment detail.

### Reassess: `/links/{id}/reassessments/create` → `reassessments/create`

- **Breadcrumbs:** Vínculos › {link} › Reavaliar.
- If there is no pending reversal, the server redirects to the link with an error toast.
- **Props:** link, reversal, outcomes (each with `problem`), causeApplies, verificationProblem, owners, costLevels, lifecyclePhases, statuses (no Cancelado), causeStatuses.
- **Heading:** "Reavaliar vínculo", with the link label.
- **Blocks:**
    1. Short UnacceptableTierAlert when the system is unacceptable.
    2. **Card "Reversão a reavaliar"** (max-w-3xl): Origem, Data, Status do vínculo (badges); "Motivo"; "Mudança do sistema" link; "Evento adverso" link.
    3. **Form** (max-w-3xl):
        - **Desfecho**: fieldset of 4 bordered radio cards, required. Each shows the label, a muted effect sentence and, when disabled, amber "Indisponível: :problem".
            - Manter: "A mitigação continua adequada. O vínculo é verificado agora, com a evidência registrada depois da reversão."
            - Ajustar: "Muda responsável, custo estimado, fase ou status de progresso. Verificar agora é opcional; sem verificação, o vínculo passa a aguardar verificação." For unacceptable systems: "Registre o plano de descontinuação, levando o vínculo à fase Retirada."
            - Substituir: "Cancela este vínculo e leva à criação de outro, com outra mitigação para o mesmo risco."
            - Encerrar: "Cancela este vínculo, sem substituto."
            - Error: "Escolha o desfecho da reavaliação."
        - **Quem reavalia**: select, required, "Selecione o papel responsável", preselected with the link's owner.
        - **Justificativa**: textarea, required, max 2000, placeholder "Ex.: A mitigação estava ativa; o evento veio de um caso fora do escopo dela."
        - **Fieldset "Análise de causa"** (only when the reversal came from an adverse event or was manual): radios, required, "Causa apurada" / "Causa não apurada". When apurada:
            - **Causa apurada**: textarea, required, max 2000, placeholder "Ex.: Entrada fora do domínio previsto, que o filtro não cobria.";
            - **Fase do ciclo de vida em que a causa se originou**: select, required.
        - **Fieldset "Ajustes no vínculo"** (only when Ajustar): "Deixe "Sem mudança" no que não muda. O antes e o depois de cada mudança ficam registrados na reavaliação."
            - 2×2 selects, each defaulting to "Sem mudança ({current})": Responsável, Custo estimado, Fase do ciclo de vida, Status de progresso.
            - Checkbox **Verificar no mesmo ato**: disabled when not verifiable, with help or the reason "Use quando a mudança já está aplicada e a evidência registrada depois da reversão a comprova. Sem verificar, o vínculo passa a aguardar verificação."
- **Actions:** "Registrar reavaliação" (primary, opens the dialog) and "Cancelar" (ghost → link).
- **Dialog** "Registrar esta reavaliação?": "Desfecho: :outcome. :effect A reavaliação é permanente: não pode ser editada nem excluída." Buttons "Voltar" and "Registrar".
- **Toast:** "Reavaliação registrada." Replace redirects to links/create with "Crie o vínculo que o substitui."
- **Jargon:** G34, G35, G36, G14, G13, G10, G18, G6, G2, G37.
- **Friction:**
    - A long conditional form, where the fieldsets appear in order of selection rather than in a fixed grouping.
    - Native radios (no shadcn RadioGroup).
    - "Causa não apurada" vs the enum label "Não apurada" vs "Não se aplica" is inconsistent wording.
    - Unavailable outcomes are shown disabled, with the reason in amber text.

### Reassessment detail: `/reassessments/{id}` → `reassessments/show`

- **Breadcrumbs:** Vínculos › {link} › Reavaliações › Reavaliação.
- **Header:** `h1` "Reavaliação" with an outcome badge; link label (muted link). Muted: "Reavaliações não podem ser editadas nem excluídas: elas registram o que foi concluído, por quem e por quê."
- **Cards:**
    - "Decisão": Desfecho, Data, Quem reavaliou (role / area), Justificativa.
    - "Análise de causa": Situação (Apurada / Não apurada / Não se aplica). When identified: Fase de origem and Causa apurada. When not applicable: "Uma reversão por vencimento ou por reclassificação do sistema não tem causa a apurar."
    - "Reversão reavaliada": Origem, Data (link to the history entry), Registrada por (EntryAuthor), Motivo, Mudança do sistema / Evento adverso links.
    - "Mudanças aplicadas" (only for Ajustar): table Campo | Antes | Depois, or "Nenhum campo do vínculo foi alterado."
    - "Verificação e substituição": "O vínculo foi verificado no mesmo ato, {em date link}." or "Sem verificação no mesmo ato[: o vínculo passou a aguardar verificação]."; for Substituir, "Substituído por {link}." or "O vínculo substituto ainda não foi criado."
- **Friction:**
    - The not-applicable cause text mentions only review due and reclassification, but system changes are also not-applicable (0021).
    - When the replacing link has not been created, there is no button to create it.

---

## 9. Responsáveis

### List: `/owners` → `owners/index` (OwnerController@index)

- **Breadcrumbs:** Responsáveis.
- **Props:** paginated owners with links_count and status_histories_count; active first.
- **Heading:** "Responsáveis", with "Papéis organizacionais que respondem pelos vínculos." Primary button "Cadastrar responsável".
- **Table** (row click): **Cargo** (link plus an outline "Inativo" badge) | **Área** | **Vínculos** | actions: "Excluir" (red text, only when never used) → "Excluir este responsável?" / "O responsável ":role · :area" será removido. Esta ação não pode ser desfeita."
- **Empty:** "Nenhum responsável foi cadastrado ainda. Todo vínculo precisa de um papel que responda por ele." with "Cadastrar responsável".
- **Jargon:** G38, G39.
- **Friction:**
    - The sidebar says "Responsáveis" but the column says "Cargo"; the concept "papel organizacional" is not explained in the list.
    - No Edit action in the list (unlike other lists).

### Create: `/owners/create` → `owners/create` (partial `owners/form.tsx`)

- **Breadcrumbs:** Responsáveis › Cadastrar.
- **Heading:** "Cadastrar responsável", with "Registre um papel organizacional que possa responder por vínculos."
- **Fields:**
    1. **Cargo**: text, required, max 255, placeholder "Ex.: Gestor de Risco de IA", help "Um papel organizacional, nunca o nome de uma pessoa."
    2. **Área**: text, required, max 255, placeholder "Ex.: Segurança da Informação".
- **Actions:** "Cadastrar" (primary) and "Cancelar" (ghost). Toast "Responsável cadastrado."

### Edit: `/owners/{id}/edit` → `owners/edit`

- **Breadcrumbs:** Responsáveis › {cargo} › Editar.
- **Heading:** "Editar responsável", with "{cargo} · {área}".
- **Locked state** (used owner): card "Cargo e área travados" with Cargo, Área, Vínculos and "Registros no histórico"; the text "O cargo e a área não podem ser alterados depois que o responsável foi usado na cadeia de rastreabilidade. Cadastre um novo responsável e reatribua os vínculos ativos."; outline button "Voltar ao responsável".
- **Otherwise:** the same form, "Salvar" and "Cancelar".

### Detail: `/owners/{id}` → `owners/show`

- **Breadcrumbs:** Responsáveis › {cargo}.
- **Header:** `h1` cargo plus an "Inativo" badge; area, or ":area · desativado em :date".
- **Header actions:**
    - Never used: "Editar" (outline) and "Excluir" (destructive dialog).
    - Used and active: "Desativar" (outline) → dialog "Desativar este responsável?". If it still has active links: "Este responsável ainda responde por :count vínculo(s) ativo(s). Reatribua-o(s) antes de desativar." with "Entendi". Otherwise: "Ele deixa de ser oferecido na criação de vínculos e no registro de mudanças de status. O histórico continua mostrando o que ele registrou, e ele pode ser reativado depois." with "Cancelar" (secondary) and "Desativar" (primary).
    - Inactive: "Reativar" (primary, submits directly).
- **Card "Vínculos pelos quais responde":** table Vínculo | Status | Próxima revisão (row click). Empty: "Este responsável ainda não responde por nenhum vínculo. Ele pode ser escolhido ao criar um vínculo[, depois de reativado]."
- **Jargon:** G39, G38, G9, G10, G16.
- **Friction:**
    - Reassigning links requires editing each link one at a time; there is no bulk reassign, though the dialog asks for it.
    - A used owner has no Edit button, and nothing explains why.

---

## 10. Configurações (Settings)

All use `[AppLayout, SettingsLayout]`. The breadcrumb is set with `setLayoutProps`.

- **Perfil**: `/settings/profile` → `settings/profile` (ProfileController@edit).
    - Breadcrumb "Configurações de perfil".
    - Small heading "Perfil", with "Atualize seu nome e endereço de e-mail".
    - Fields: Nome (text, required, placeholder "Nome completo"); Endereço de e-mail (email, required).
    - Conditional: "Seu endereço de e-mail não foi verificado. Clique aqui para reenviar o e-mail de verificação.", plus a green success line.
    - Button "Salvar" (primary).
    - **DeleteUser** block: small heading "Excluir conta" / "Exclua sua conta e todos os seus recursos"; a warning box "Atenção" / "Prossiga com cautela, esta ação não pode ser desfeita."; destructive "Excluir conta" → dialog "Tem certeza de que deseja excluir sua conta?" with the explanation, a Senha field, "Cancelar" (secondary) and "Excluir conta" (destructive).
- **Segurança**: `/settings/security` → `settings/security`. Requires password confirmation first.
    - Breadcrumb "Configurações de segurança".
    - Small heading "Atualizar senha", with "Verifique se sua conta está usando uma senha longa e aleatória para permanecer segura".
    - Fields: Senha atual, Nova senha, Confirmar senha (password inputs with show/hide). Button "Salvar".
    - **ManageTwoFactor**: "Autenticação de dois fatores" / "Gerencie suas configurações de autenticação de dois fatores". Buttons "Ativar 2FA", "Continuar configuração" or "Desativar 2FA" (destructive), plus the setup modal (QR code and OTP) and recovery codes ("Ver códigos de recuperação", "Gerar novos códigos").
- **Aparência**: `/settings/appearance` → `settings/appearance`.
    - Breadcrumb "Configurações de aparência".
    - Small heading "Configurações de aparência", with "Atualize as configurações de aparência para sua conta".
    - Segmented control: **Claro** (Sun) / **Escuro** (Moon) / **Sistema** (Monitor).
- **Friction:**
    - "Sistema" (the OS theme) reuses the word for an AI system.
    - The Settings breadcrumb is a single crumb, without a "Configurações" parent.

---

## 11. Cross-cutting UX friction (factual summary)

0. **Cost reads as money:** no field says that cost is the effort of implementing (decision 0028), and the observed-cost help says "O custo real da aplicação até agora (RF07)". The 0024 rubric (time, people, tools, operational impact) is not shown anywhere; 0028 says it belongs as help text on the link and evidence forms (UX task #95).
1. **Internal codes in UI copy:** "(C2)" (catalog heading, mitigation detail), "RF07" (evidence cost help), "Slattery et al." and "Saeri et al. (2025)" in form help, and "seeder" in the empty catalog state.
2. **Terminology drift:**
    - Resolved by 0028: the EU AI Act classification is "Faixa de risco" everywhere, and "Categoria" is only the Saeri category of a mitigation. Check that alerts and help texts follow.
    - "Origem" means both the system source type and the reversal origin.
    - "Responsável" / "Cargo" / "Papel responsável" / "Quem verifica" / "Registrado por" all name the owner role.
    - "Cadastrar" vs "Registrar" are used for similar actions.
    - Sidebar "Mitigações" vs page "Catálogo de mitigações".
    - Chip "Verificados" vs badge "Verificada".
    - "Causa não apurada" vs "Não apurada".
3. **The two status dimensions** (progress and verification) are always shown as two adjacent badges with no labels. Only ReviewDate has a tooltip.
4. **English definitions** of MIT subdomains and Saeri subcategories are shown verbatim (`lang="en"`) in forms and details. This is a known open item in `docs/decisions/PENDENTES.md`.
5. **Acompanhamento CTAs go to unfiltered lists:** "Riscos sem vínculo" → `/risks`, "Vínculos sem evidência" → `/links`. Neither list has a matching filter. The Risks and AI systems lists have no filters or search at all.
6. **Orphan pages:** `system-changes/index` and `reassessments/index` are reachable only through breadcrumbs.
7. **Inconsistent confirmations:** adverse event, evidence, system change and reassessment use a confirm dialog. Cancelling a link (status form) and reclassifying a system to Inaceitável (edit form) save directly, with only inline warnings.
8. **Destructive styling is inconsistent:** "Cancelar vínculo" is outline with red text (and becomes a destructive button on the status form); "Reverter verificação" is outline; table "Excluir" actions are red text links.
9. **Long, ungrouped forms:** risk (7 fields), adverse event (8 blocks including a 32rem scroll picker) and reassessment (conditional fieldsets). Link creation needs three controls to pick one mitigation, and options show no descriptions while choosing.
10. **Next-step guidance on the link page depends on state** (Verificar / Renovar / Reavaliar / Registrar evidência). Disabled buttons explain why only in small muted text. Event and change detail pages list pending reassessments with no direct action.
11. **Breadcrumbs are inconsistent:** AI system and risk create/edit pages show only the list crumb; owners and links include "Cadastrar" / "Editar".
12. **Docs drift:** `docs/tutorial.md` calls the link page box "Verificação dos vínculos" (on the link page it is titled "Verificação"; that name belongs to the Acompanhamento card).
13. **Unused starter-kit artifacts:** `app-header.tsx` (Laravel repo/docs links, an inert search button) and `nav-footer.tsx` are not rendered; chart color tokens are defined but unused.
