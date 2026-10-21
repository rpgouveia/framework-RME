# Framework RME

A web application for managing the risks of AI systems. It keeps a registry of
the AI systems an organization runs, the risks identified for each one, the
mitigations applied to those risks, who is accountable for each, and the
evidence proving they are in place. It then keeps that claim honest over time:
review deadlines, adverse events and changes to a system take verified
mitigations back to reassessment, and every step lands in an append-only audit
trail exported as a traceability report.

An applied research project at PUCPR.

## Domain model

```mermaid
erDiagram
    AI_SYSTEM      ||--o{ RISK           : "is exposed to"
    AI_SYSTEM      ||--o{ ADVERSE_EVENT  : "suffers"
    AI_SYSTEM      ||--o{ SYSTEM_CHANGE  : "goes through"
    RISK           ||--o{ LINK           : "is addressed by"
    MITIGATION     ||--o{ LINK           : "is applied through"
    OWNER          ||--o{ LINK           : "is accountable for"
    LINK           ||--o{ STATUS_HISTORY : "changes through"
    LINK           ||--o{ EVIDENCE       : "is proven by"
    LINK           ||--o{ REASSESSMENT   : "is reassessed in"
    LINK           |o--o| LINK           : "replaces"
    LINK           |o--o{ ADVERSE_EVENT  : "intercepts"
    ADVERSE_EVENT  ||--o{ STATUS_HISTORY : "reverts"
    SYSTEM_CHANGE  ||--o{ STATUS_HISTORY : "reverts"
    STATUS_HISTORY ||--o| REASSESSMENT   : "is concluded by"
    OWNER          |o--o{ STATUS_HISTORY : "records"
    OWNER          ||--o{ REASSESSMENT   : "concludes"
    TAXONOMY       ||--o{ TAXONOMY_TERM  : "lists"
    TAXONOMY_TERM  ||--o{ RISK           : "classifies"
    TAXONOMY_TERM  ||--o{ MITIGATION     : "classifies"
    TAXONOMY_TERM  }o--o{ MITIGATION     : "is targeted by"
    TAXONOMY_TERM  }o--o{ ADVERSE_EVENT  : "classifies"
    TAXONOMY_TERM  }o--o{ SYSTEM_CHANGE  : "is reached by"
```

| Entity             | Table                | What it holds                                                                                                                                          |
| ------------------ | -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `AiSystem`         | `ai_systems`         | An AI system in the portfolio: name, application domain, source type, EU AI Act tier, registration date.                                               |
| `Risk`             | `risks`              | A risk identified for one system: name, description, MIT risk subdomain, lifecycle phase, uncertainty level.                                           |
| `Mitigation`       | `mitigations`        | A read-only catalogue entry: measure, Saeri et al. subcategory, target risk subdomains, expected evidence, suggested cost and its uncertainty, source. |
| `Owner`            | `owners`             | The organizational role accountable for a mitigation, and its area. Deactivated rather than deleted once in use.                                       |
| `Link`             | `links`              | **The core entity.** Applies one mitigation to one risk under one owner, with a progress status and a verification status.                             |
| `Evidence`         | `evidence`           | An artifact proving a link is in place, optionally with the cost actually observed. Append-only.                                                       |
| `StatusHistory`    | `status_histories`   | The append-only audit trail of a link: every change of progress or verification, with its origin, author or trigger.                                   |
| `AdverseEvent`     | `adverse_events`     | An incident or a near miss on a system, classified by MIT risk subdomains; a near miss may name the link that intercepted it. Append-only.             |
| `SystemChange`     | `system_changes`     | A new model version or a data change of a system, optionally naming the risk subdomains it reaches. Append-only.                                       |
| `Reassessment`     | `reassessments`      | The conclusion of one reversal: outcome (maintain, adjust, replace, close), owner, justification, cause analysis and changes made.                     |
| `Taxonomy`         | `taxonomies`         | A versioned reference taxonomy (MIT AI risk domains, Saeri et al. mitigations), with its citation; `TaxonomyTerm` holds its entries.                   |
| `ReferenceDataset` | `reference_datasets` | The metadata of a reference file as it was loaded, such as the catalogue's, so the app describes the data it actually holds.                           |

A link has **two status dimensions**: its progress (`planned`,`in_progress`,
`implemented`, `suspended`, `cancelled`) and its verification
(`declared` or `verified`). A link is only _verified_ on evidence recorded
after its last verification change, and a verified link carries a review
date. Links are never deleted: they end by cancellation, and a replacement
points at the link it replaces.

Every classification field is a PHP backed enum in [`app/Enums`](app/Enums),
cast on the model and validated with `Rule::enum()`. The columns are plain
strings, so changing an enum needs no migration. The TypeScript mirror of every
enum lives in [`resources/js/types/models.ts`](resources/js/types/models.ts) and
must be updated alongside the PHP one. Its pt-BR labels live in
`lang/pt_BR.json`, keyed by enum and case (`"Cost Level: High": "Alto"`), and
reach the screens through `useEnumLabels()` (see [Translations](#translations));
a new enum also joins the list shared by `HandleInertiaRequests` and the
`EnumLabels` type in `resources/js/lib/enum-labels.ts`.

Cost is one of those enums: `CostLevel` (low/medium/high) backs a mitigation's
`suggested_cost`, a link's `estimated_cost` and the cost observed on evidence,
so an estimate and the actual cost stay comparable. It is deliberately
qualitative: if the team later needs real currency amounts, add a numeric
column next to it rather than widening the scale. Each level follows the
rubric of decision 0024, which applies to all three costs (0028).

### Reference data

The taxonomies, the mitigation catalogue and the monitoring protocol are
versioned JSON files under [`database/data`](database/data), loaded by the
seeders and validated on load (`app/Support`):

| File                                        | What it holds                                                                                        |
| ------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| `taxonomies/mit-ai-risk-domains.json`       | The MIT AI Risk Repository domain taxonomy (7 domains, 24 subdomains), classifying risks and events. |
| `taxonomies/saeri-mitigation-taxonomy.json` | The preliminary mitigation taxonomy of Saeri et al., classifying the catalogue.                      |
| `mitigation-catalog.json`                   | The mitigation catalogue: 40 entries curated by the group (decision 0024).                           |
| `protocols/c3-monitoring-protocol.json`     | The C3 monitoring protocol: review interval per EU AI Act tier and the dashboard windows.            |
| `catalog-source/`                           | Where the real catalogue comes from: the group's spreadsheet export (`catalog.csv`) and `meta.json`. |
| `expert-panel/`                             | The item bank of the expert panel (`items.csv`) and, once the panel runs, its responses and result.  |

The catalogue file is generated from `catalog-source/`: the "Catálogo" sheet
exported as CSV, kept as is with the raw double annotation, and the editable
metadata. Only finished rows go in, and nothing is written while any row fails
the catalogue's validation:

```bash
docker compose exec app php artisan mitigations:import-catalog
```

The same CSV holds the raw double annotation behind OE1. The agreement between
the two annotators (decision 0025: Krippendorff's alpha with the MASI distance,
a seeded bootstrap interval and per subdomain measures) is computed from it,
shown as tables and written to `catalog-source/oe1-agreement.json`. The
annotators follow [`docs/catalog-annotation-guide.md`](docs/catalog-annotation-guide.md),
in Portuguese:

```bash
docker compose exec app php artisan mitigations:agreement
```

The measured alpha (0.527) fell short of the 0.667 minimum. As decision 0025
sets, the result is reported as is, without annotating again, and the guide was
revised afterwards for future annotation: its section on the OE1 measurement
records where the annotators diverged and how to mark those subdomains. The
spreadsheet the group annotated in is kept as `docs/catalog-annotation.xlsx`.

### Expert panel

The expert panel that would evaluate the framework's content validity (OE6) and
validate the adverse event definition (OE3) was planned but not run within this
project, for lack of time (decision 0027). Everything it needs is ready for a
future run, in Portuguese:

| File                                                             | What it holds                                                                   |
| ---------------------------------------------------------------- | ------------------------------------------------------------------------------- |
| [`docs/expert-panel-protocol.md`](docs/expert-panel-protocol.md) | The procedure: profiles, recruitment, consent, the two rounds and the analysis. |
| `database/data/expert-panel/items.csv`                           | The 21 items the experts rate, each tied to the decision behind it.             |
| `docs/form-expert-panel-OE6.xlsx`                                | The questionnaire, a sheet to collect the second round and a cross-check of it. |

The second round's answers, exported as
`database/data/expert-panel/round-2-responses.csv`, give Lawshe's content
validity ratio per item, the critical value for the panel size (Ayre and
Scally's exact binomial method), whether the OE3 and OE6 criteria are met, the
content validity index and the review intervals the experts suggest. The result
is shown as tables and written to
`database/data/expert-panel/oe6-cvr.json`:

```bash
docker compose exec app php artisan expert-panel:cvr
```

### Using the app

[`docs/tutorial.md`](docs/tutorial.md) is a step-by-step guide to the app, in
Portuguese: from registering a system, its risks and mitigations to reassessing
a link after an adverse event, plus a 15-minute tour of the sample data.

### Domain decisions

The business rules are recorded, in Portuguese, in
[`docs/decisions`](docs/decisions/README.md), one numbered decision per file.
Read the matching decision before changing a rule, and check
[`PENDENTES.md`](docs/decisions/PENDENTES.md) before implementing anything
not yet decided there.

The project's terminology is fixed in [`docs/glossary.md`](docs/glossary.md),
in Portuguese: each term with its name in the code and the decision or
reference that defines it. Use it for labels, documentation and the article.

## Stack

|          |                                                                                |
| -------- | ------------------------------------------------------------------------------ |
| Backend  | Laravel 13 · PHP 8.5                                                           |
| Frontend | React 19 · Inertia v3 · TypeScript · Tailwind v4 · shadcn/ui                   |
| Database | PostgreSQL 18 (Docker)                                                         |
| Auth     | Laravel Fortify (login, registration, password reset, email verification, 2FA) |
| Tests    | Pest 5                                                                         |
| Tooling  | Vite (vite-plus) · Wayfinder · Pint · PHPStan (larastan)                       |

The frontend talks to the backend through **Inertia**, not a REST API:
controllers return `Inertia::render('page/path', [...props])` and the React page
receives those props directly. There is no `/api` layer and no client-side
router.

## Requirements

Docker is the only requirement: the whole stack runs in containers.

To run the app on the host instead (`composer dev`), you also need PHP 8.5 with
the `pdo_pgsql` extension, Composer 2 and Node 22+; Docker then only serves the
database.

## Quick start

```bash
git clone <repo-url> framework-RME
cd framework-RME

cp .env.example .env       # the DB_* defaults already match compose.yml
docker compose up -d       # database + app + vite + queue + scheduler + logs
```

The app is on http://localhost:8000 and the Vite dev server on
http://localhost:5173. The first `up` builds the image, installs both
dependency trees and seeds a sample portfolio to develop against, so it takes
a few minutes; later ones start in seconds.

Sign in with one of the seeded accounts:

| Email              | Password   |
| ------------------ | ---------- |
| `test@example.com` | `password` |
| `teste@teste.com`  | `teste123` |

## Database

The `postgres` service in `compose.yml` runs PostgreSQL 18. Its credentials
match the `DB_*` values in `.env.example`, so no configuration is needed:

|                 |                                                  |
| --------------- | ------------------------------------------------ |
| Host / port     | `127.0.0.1:5432`                                 |
| Database        | `laravel`                                        |
| User / password | `root` / `root`                                  |
| Volume          | `postgres_data` (survives `docker compose down`) |

```bash
docker compose up -d postgres         # the database on its own
docker compose down                   # stop, keeping the data
docker compose down -v                # stop and wipe the volume (and node_modules)

docker compose exec app php artisan migrate               # apply new migrations
docker compose exec app php artisan migrate:fresh --seed  # drop everything and reseed
docker compose exec postgres psql -U root -d laravel      # a psql shell
```

Drop `docker compose exec app` from those Artisan calls when you work on the
host; the container and the host share the same database.

Tests never touch this database: `phpunit.xml` points them at an in-memory
SQLite connection.

## Development

### In containers

`docker compose up -d` runs the processes of `composer dev`, each as its own
service:

| Service     | Command                                     | What it is                                              |
| ----------- | ------------------------------------------- | ------------------------------------------------------- |
| `postgres`  | none                                        | PostgreSQL 18 on :5432                                  |
| `setup`     | `docker/setup.sh`                           | one-shot: dependencies, app key, migrations, first seed |
| `app`       | `php artisan serve` → http://localhost:8000 | `composer dev` › server                                 |
| `vite`      | `npm run dev` → http://localhost:5173       | `composer dev` › vite (HMR + SSR)                       |
| `queue`     | `php artisan queue:listen`                  | `composer dev` › queue (the worker)                     |
| `scheduler` | `php artisan schedule:work`                 | the schedule of `routes/console.php`, every minute      |
| `logs`      | `php artisan pail`                          | `composer dev` › logs                                   |

```bash
docker compose up -d                  # start everything
docker compose logs -f app vite       # follow a few services
docker compose logs -f logs           # the pail stream
docker compose exec app bash          # a shell with php, composer, node and npm
docker compose down                   # stop everything
docker compose up -d --build          # rebuild after editing docker/Dockerfile
```

Everything runs off the bind-mounted working tree, so edits on the host apply
immediately. Vite polls for changes because bind mounts do not forward
filesystem events.

Worth knowing:

- **`queue` is the worker**: `queue:listen` reloads the code on every job, so
  a changed job class takes effect without restarting the container. Swap it
  for `queue:work` if you want the production behaviour instead.
- **`scheduler` runs the schedule**: `schedule:work` checks `routes/console.php`
  every minute and runs what is due, such as `links:flag-due-for-review` at
  07:00. It only lives while the containers are up; see
  [Running the schedule](#running-the-schedule) for production.
- **`node_modules` is a named volume**, not the host directory: the container
  installs the Linux builds of Rollup, Tailwind Oxide and lightningcss without
  touching the macOS ones. `docker compose down -v` wipes it, and the next `up`
  reinstalls.
- **`setup` runs on every `up`** and is idempotent: it installs what is
  missing, migrates, and seeds only when the database holds no users, so your
  data survives a restart.
- **Both the containers and `composer dev` bind :8000 and :5173**, so run one
  or the other, not both.

### On the host

```bash
docker compose up -d postgres
composer setup                        # install + key:generate + migrate + npm install + npm run build
composer dev
```

This runs the same four processes in one terminal (see `php artisan dev:list`).
Run `npm run dev` on its own if you only need the asset server.

> **`Unable to locate file in Vite manifest`** means the frontend has not been
> built: run `npm run dev` (or `npm run build`) and reload. The same error
> naming a page under `resources/js/pages/` means that page component does not
> exist yet; see below.

## How a feature is wired

Take AI systems as the example; every entity follows the same shape.

```
routes/web.php                          Route::resource('ai-systems', AiSystemController::class)
 └─ app/Http/Controllers/AiSystemController.php
     ├─ app/Policies/AiSystemPolicy.php          Gate::authorize(...)
     ├─ app/Http/Requests/StoreAiSystemRequest.php
     │   └─ app/Concerns/AiSystemValidationRules.php   rules shared by store + update
     ├─ app/Actions/UpdateAiSystem.php           writes with side effects
     ├─ app/Models/AiSystem.php                  #[Fillable], casts, relationships
     └─ Inertia::render('ai-systems/index')
         └─ resources/js/pages/ai-systems/index.tsx
```

- **Writes with side effects go through an Action** in `app/Actions`, inside a
  transaction. In particular, `RecordStatusChange` is the **single write path**
  for a link's status: opening, progress changes, verification and reversal all
  pass through it, so the history and the rules cannot be bypassed (decision
  0007). The other actions (`RecordAdverseEvent`, `RecordSystemChange`,
  `RecordReassessment`, `UpdateAiSystem`, `RevertOverdueLinks`) call it.
- **Append-only records** (status history, evidence, adverse events, system
  changes, reassessments) expose no `edit`, `update` or `destroy` routes. Those
  that belong to another record are nested under it with `shallow()`, such as
  `/links/{link}/reassessments/create` and `/reassessments/{reassessment}`.
- **Validation rules live in a trait** under `app/Concerns`, shared by the
  `Store*` and `Update*` requests, following the existing
  `ProfileValidationRules` convention.
- **Authorization** goes through the policies. They are deliberately permissive
  right now: any authenticated, verified user may do anything. Add roles there,
  not in the controllers.
- **Frontend routes** come from Wayfinder. Import them instead of hardcoding
  URLs; they regenerate on every `npm run dev` / `npm run build`:

    ```tsx
    import { index, show } from '@/routes/ai-systems';

    <Link href={show(aiSystem.id)}>{aiSystem.name}</Link>;
    ```

- **The interface is in pt-BR** (locale `pt_BR`), while code, routes and enum
  values are in English. Texts are keyed by their English string and
  translated in `lang/pt_BR.json`, read by the backend (`__()`) and by the
  frontend (`t()` from `useTranslations`); validation messages and field names
  are in `lang/pt_BR/validation.php`. See [Translations](#translations).

Run `php artisan route:list --except-vendor` for the full route map.

### Screens

| Section        | What it does                                                                                                                                                                             |
| -------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Dashboard      | Portfolio totals, risks without links, links without evidence, links awaiting verification or reassessment (by origin), reviews coming due, recent adverse events, risks not yet mapped. |
| AI systems     | The portfolio; each system shows its risks, system changes, and the traceability report downloads.                                                                                       |
| Risks          | Risks per system, classified by MIT subdomain, with their links.                                                                                                                         |
| Mitigations    | The read-only catalogue, with its Saeri et al. classification and source.                                                                                                                |
| Owners         | Accountable roles; deactivated instead of deleted once used.                                                                                                                             |
| Links          | Filterable list by progress, verification and reversal origin; each link has its timeline, evidence and reassessments.                                                                   |
| Adverse events | Recording an incident or near miss, with a preview of the links it will revert.                                                                                                          |
| System changes | Recording a model version or data change on a system, with a preview of the links it will revert.                                                                                        |
| Reassessments  | Concluding a reversal with an outcome, cause analysis and adjustments.                                                                                                                   |

## Testing

```bash
php artisan test --compact                       # the whole suite
php artisan test --compact tests/Feature/LinkTest.php
vendor/bin/pest --filter="a link can be created"
```

Prefix them with `docker compose exec app` to run them in the container; the
same goes for the quality commands below.

Each entity has a feature test in `tests/Feature/` covering the guest
redirect, the listing, creation, validation failures and, where allowed,
updates and deletion. The rules of each decision have their own files
(`LinkVerificationTest`, `ReassessmentTest`, `SystemChangeTest`, ...), and a
seeder test checks that the sample data tells every story through the actions.

Locally the suite runs on an in-memory SQLite database (`phpunit.xml`); CI runs
it against its own PostgreSQL service (`.github/workflows/tests.yml`). Some
rules are database CHECK constraints, so run it on PostgreSQL too before
pushing a schema change:

```bash
docker compose exec postgres createdb -U root testing     # once
docker compose exec -e DB_CONNECTION=pgsql -e DB_HOST=postgres -e DB_DATABASE=testing \
    app php artisan test --compact
```

## Code quality

```bash
composer lint          # Pint, fixes formatting
composer types:check   # PHPStan level 7 over app/, database/, routes/
npm run check:fix      # lint + format the frontend
npm run types:check    # tsc --noEmit
npm run test:unit      # Vitest: the frontend's pure functions, such as lib/i18n
composer ci:check      # everything CI runs
```

A husky `pre-commit` hook runs `lint-staged` and `composer lint`, so formatting
is fixed before a commit lands.

Commit messages follow the conventional style already used in the history:
`feat:`, `fix:`, `docs:`, `ci:`, `style:`, `chore:`.

## Translations

Every visible text is keyed by its English string, with the Portuguese in
`lang/pt_BR.json` (decision 0022). In React, `useTranslations()` gives:

```tsx
const { t, tc } = useTranslations();

t('Reason: :reason', { reason }); // :name placeholders
tc(':count link|:count links', count); // Laravel's choice syntax
interpolateNodes(t('Replaced by :link.'), { link: <Link>…</Link> }); // an element in a sentence
```

A page's `layout` callback runs outside the component: it takes
`translations` from the page props and uses `translator(translations)`.
`FrontendTranslationKeysTest` fails when a key looked up through `t()` or `tc()`
is missing from `lang/pt_BR.json`, which would otherwise show the English key,
and the lint rule `react/jsx-no-literals` refuses text written straight into
JSX (attributes such as `placeholder` and `aria-label` still need review).

Enum labels come from the same file, keyed by enum and case, such as
`"Uncertainty Level: High": "Alta"`. The backend resolves them (`label()`) and
shares them with every page: `useEnumLabels()` gives
`enumLabels.UncertaintyLevel.high`, and a layout callback reads `enumLabels`
from the page props. `EnumLabelTest` fails when a case has no translation.

The default Laravel and Fortify translations come from
[Laravel Lang](https://laravel-lang.com) (`laravel-lang/common`, a dev
dependency); the project's own lines live in the same files. Every environment
needs `APP_LOCALE=pt_BR` in `.env`. To update the defaults:

```bash
docker compose exec app php artisan lang:add pt_BR   # never lang:reset: it deletes the project's lines
docker compose exec app composer lint                # the publisher aligns the arrows, Pint undoes it
git diff lang/
docker compose exec app php artisan test
```

`lang:add` overwrites every key Laravel Lang publishes. Where the project keeps
its own wording instead, such as the domain's `Close` or the validation messages
of the domain forms, the text is listed in
[`tests/Fixtures/pinned-translations.php`](tests/Fixtures/pinned-translations.php):
`PinnedTranslationsTest` fails until an overwritten text is restored, and also
when a new departure from Laravel Lang is left out of the list (decision 0023).

New translations follow the vocabulary of the interface:

| Use                     | For                                                                                          | Not                    |
| ----------------------- | -------------------------------------------------------------------------------------------- | ---------------------- |
| ativar, desativar       | turning a feature on or off, such as 2FA                                                     | habilitar, desabilitar |
| excluir                 | removing                                                                                     | deletar                |
| digite                  | asking for input                                                                             | insira                 |
| e-mail                  | the address and the message                                                                  | email                  |
| cadastrar               | the domain entities that are edited and listed: systems, risks, owners                       |                        |
| registrar               | what is append only: evidence, adverse events, system changes, reassessments, status history | cadastrar              |
| criar conta             | the user's own account                                                                       | cadastrar              |
| entrar, sair            | starting and ending the session                                                              | fazer login, login     |
| acompanhamento          | the home screen                                                                              | painel                 |
| faixa de risco          | a system's EU AI Act tier                                                                    | categoria              |
| painel de especialistas | the expert panel of decision 0027, always in full                                            | painel                 |

## Project structure

```
app/
├── Actions/           the write paths: RecordStatusChange and the actions built on it
├── Concerns/          validation rule traits + the enum options helper
├── Console/Commands/  links:flag-due-for-review, mitigations:import-catalog, mitigations:agreement,
│                      expert-panel:cvr
├── Enums/             every classification used by the domain
├── Http/
│   ├── Controllers/   one controller per entity, plus the dashboard and the report
│   └── Requests/      Store*/Update* form requests
├── Models/            the domain models
├── Policies/          one policy per entity
└── Support/           reference data loaders (taxonomies, catalogue, monitoring protocol) and the
                       OE1 and OE6 measures (annotation agreement, content validity)

database/
├── data/              versioned reference files (taxonomies, catalogue, protocol, panel items)
├── factories/         factories with states (implemented, highRisk, inSubdomain, ...)
├── migrations/        the schema
└── seeders/           one seeder per entity, chained by DatabaseSeeder

docs/                  in Portuguese
├── decisions/         the domain decision records
├── tutorial.md        a step-by-step guide to the app
├── catalog-annotation-guide.md, catalog-annotation.xlsx
│                      the OE1 annotation guide and the group's annotation spreadsheet
└── expert-panel-protocol.md, form-expert-panel-OE6.xlsx
                       the OE6 panel protocol and its questionnaire

lang/                  pt_BR translations: Laravel Lang defaults + the project's lines

resources/js/
├── components/        shared React components
├── lib/               labels, formatting and the pure preview functions
├── pages/             one directory per entity, mapped to Inertia::render()
├── routes/            generated by Wayfinder; do not edit
└── types/models.ts    TypeScript mirror of the models and enums

docker/
├── Dockerfile         the dev image: PHP 8.5 + Node 22 + Composer
└── setup.sh           what the `setup` service runs on every `up`
compose.yml            every service of the development stack
```

The seeders build the sample portfolio **through the actions**, travelling in
time, so the data follows the same rules as the app: links verified on
evidence, reversed by each trigger, reassessed with each outcome.

## Verification and reassessment

A link's `next_review_date` is set when the link is verified: the verification
date plus the review interval of the EU AI Act tier of the risk's system, as the
C3 protocol file `database/data/protocols/c3-monitoring-protocol.json` sets it
(high 90 days, limited 180, minimal 365). A declared link has no review date,
and a system in the unacceptable tier never operates, so its links cannot be
verified: they serve to plan its discontinuation. The rules are recorded in
decisions 0017 and 0018.

### Triggers

Five triggers take a verified link back to declared, awaiting reassessment.
Each reversal is recorded in the status history with its origin and, when
automatic, no author:

- **Review due:** `links:flag-due-for-review` reverts the links past their
  review date. The date is the last valid day: a link due today is shown as
  "Vence hoje" and reverted from the next day. Running it twice on the same day
  changes nothing more.
- **Adverse event:** recording an event reverts, in the same transaction, the
  verified links of the system whose risk is in one of the event's subdomains,
  except the link that intercepted a near miss.
- **Reclassification:** editing a system into the unacceptable tier reverts
  all its verified links.
- **New model version** and **data change:** recording a system change
  reverts the verified links of the system whose risk is in one of the
  subdomains it names, or **all** of them when it names none.
- **Manual:** a user may revert a verified link by hand, with a reason.

An event or a change naming a subdomain the system has no risk for shows on the
dashboard as a risk not yet mapped, with a shortcut to register it.

### Reassessment

Each reversal is concluded by one reassessment, with an owner and a justification:

| Outcome      | Effect                                                                                                  |
| ------------ | ------------------------------------------------------------------------------------------------------- |
| **Maintain** | Verifies the link again, on evidence recorded after the reversal.                                       |
| **Adjust**   | Changes the owner, estimated cost, lifecycle phase or progress; verifying in the same step is optional. |
| **Replace**  | Cancels the link and opens the form for its replacement, of the same risk.                              |
| **Close**    | Cancels the link.                                                                                       |

A reversal from an adverse event or a manual one asks for a cause analysis
(identified, with the cause and its lifecycle phase, or not identified); a
review due, a reclassification or a system change does not. Maintain and
replace are unavailable for a system in the unacceptable tier.

### Traceability report

Each AI system page offers four downloads:

| Download             | Route                                | Content                                                                          |
| -------------------- | ------------------------------------ | -------------------------------------------------------------------------------- |
| JSON                 | `ai-systems/{id}/report.json`        | The full chain: risks, links, evidence, history, reassessments, events, changes. |
| CSV                  | `ai-systems/{id}/report.csv`         | One row per link.                                                                |
| Adverse events (CSV) | `ai-systems/{id}/adverse-events.csv` | One row per event, including those that reverted nothing.                        |
| System changes (CSV) | `ai-systems/{id}/system-changes.csv` | One row per change, including those that reverted nothing.                       |

The CSV files are UTF-8 with a BOM and `;` as the delimiter, so they open
directly in a pt-BR spreadsheet, and cells that look like formulas are
neutralised. Every file carries the version of the monitoring protocol it was
produced under.

### Running the schedule

The schedule runs `links:flag-due-for-review` daily at 07:00. In development
the `scheduler` service runs the schedule while the containers are up; outside
them, run `php artisan schedule:work`, or trigger the command by hand:

```bash
php artisan links:flag-due-for-review
```

In production nothing runs the schedule by itself: the server needs a cron
entry that calls `php artisan schedule:run` every minute (or a process manager
keeping `schedule:work` alive). Without it, links past their review date are
never reverted.

## Credits

The default Laravel and Fortify translations in `lang/pt_BR` come from
[Laravel Lang](https://github.com/Laravel-Lang/lang), under the MIT license,
Copyright (c) Laravel Lang Team.

The mitigation and risk taxonomies and the mitigation database come from the
[MIT AI Risk Initiative](https://airisk.mit.edu), with data under
[CC BY 4.0](https://creativecommons.org/licenses/by/4.0/): Saeri et al. (2025),
_Mapping AI Risk Mitigations: Evidence Scan and Preliminary AI Risk Mitigation
Taxonomy_, and Slattery et al. (2026), _A meta-review, database, and taxonomy of
risks from artificial intelligence_. The names were translated, and the
catalogue descriptions translated and adapted, by the group.
