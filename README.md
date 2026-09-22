# Framework RME

A web application for managing the risks of AI systems: it keeps a registry of
the AI systems an organization runs, the risks identified for each one, the
adverse events they actually caused, the mitigations that address those risks,
who is accountable for them, and the evidence proving they are in place.

Applied research project — PUCPR.

## Domain model

```mermaid
erDiagram
    AI_SYSTEM     ||--o{ RISK           : "is exposed to"
    AI_SYSTEM     ||--o{ ADVERSE_EVENT  : "suffers"
    RISK          ||--o{ LINK           : "is addressed by"
    MITIGATION    ||--o{ LINK           : "is applied through"
    OWNER         ||--o{ LINK           : "is accountable for"
    LINK          ||--o{ STATUS_HISTORY : "changes through"
    LINK          ||--o{ EVIDENCE       : "is proven by"
    OWNER         ||--o{ STATUS_HISTORY : "records"
    ADVERSE_EVENT ||--o{ STATUS_HISTORY : "triggers"
```

| Entity          | Table              | What it holds                                                                                         |
| --------------- | ------------------ | ----------------------------------------------------------------------------------------------------- |
| `AiSystem`      | `ai_systems`       | An AI system in the portfolio: name, source type, category, registration date.                        |
| `Risk`          | `risks`            | A risk identified for one system: description, category, lifecycle phase, uncertainty level.          |
| `AdverseEvent`  | `adverse_events`   | Something that went wrong on a system in production: type, description, date.                         |
| `Mitigation`    | `mitigations`      | The reusable catalogue entry: measure, SAERI category, target risk, expected evidence, cost, source.  |
| `Owner`         | `owners`           | The organizational role accountable for a mitigation, and its area.                                   |
| `Link`          | `links`            | **The core entity.** Applies one mitigation to one risk under one owner, with cost, dates and status. |
| `StatusHistory` | `status_histories` | The audit trail of status changes on a link, and what triggered each one.                             |
| `Evidence`      | `evidence`         | An artifact proving a link is in place.                                                               |

Every classification field is a PHP backed enum in [`app/Enums`](app/Enums),
cast on the model and validated with `Rule::enum()`. The columns are plain
strings, so changing an enum needs no migration.

Cost is one of those enums: `CostLevel` (low/medium/high) backs a mitigation's
`suggested_cost` and both cost columns of a link, so an estimate and the cost
actually observed stay comparable. It is deliberately qualitative — if the team
later needs real currency amounts, add a numeric column next to it rather than
widening the scale.

> **Before building on them:** the enum values are a first proposal and several
> carry a `@todo`. `SaeriCategory` and `AdverseEventType` in particular are
> placeholders — replace their cases with the real taxonomy from the source
> paper. The TypeScript mirror of every enum lives in
> [`resources/js/types/models.ts`](resources/js/types/models.ts) and must be
> updated alongside the PHP one.

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

- PHP 8.5 with the `pdo_pgsql` extension
- Composer 2
- Node 22+
- Docker (for the PostgreSQL container)

## Quick start

```bash
git clone <repo-url> framework-RME
cd framework-RME

cp .env.example .env       # the DB_* defaults already match compose.yml
docker compose up -d       # starts PostgreSQL 18 on :5432

composer setup             # install + key:generate + migrate + npm install + npm run build
php artisan db:seed        # sample portfolio to develop against

composer dev               # http://localhost:8000
```

Sign in with the seeded account:

| Email              | Password   |
| ------------------ | ---------- |
| `test@example.com` | `password` |

## Database

`compose.yml` runs a single PostgreSQL 18 service. Its credentials match the
`DB_*` values in `.env.example`, so no configuration is needed:

|                 |                                                  |
| --------------- | ------------------------------------------------ |
| Host / port     | `127.0.0.1:5432`                                 |
| Database        | `laravel`                                        |
| User / password | `root` / `root`                                  |
| Volume          | `postgres_data` (survives `docker compose down`) |

```bash
docker compose up -d                  # start
docker compose down                   # stop, keeping the data
docker compose down -v                # stop and wipe the volume

php artisan migrate                   # apply new migrations
php artisan migrate:fresh --seed      # drop everything and reseed
docker compose exec postgres psql -U root -d laravel   # a psql shell
```

Tests never touch this database — `phpunit.xml` points them at an in-memory
SQLite connection.

## Development

```bash
composer dev
```

This runs four processes together (see `php artisan dev:list`):

| Process  | Command                                     |
| -------- | ------------------------------------------- |
| `server` | `php artisan serve` → http://localhost:8000 |
| `vite`   | `npm run dev` (HMR)                         |
| `queue`  | `php artisan queue:listen`                  |
| `logs`   | `php artisan pail`                          |

Run `npm run dev` on its own if you only need the asset server.

> **`Unable to locate file in Vite manifest`** means the frontend has not been
> built: run `npm run dev` (or `npm run build`) and reload. The same error
> naming a page under `resources/js/pages/` means that page component does not
> exist yet — see below.

## How a feature is wired

Take AI systems as the example; every entity follows the same shape.

```
routes/web.php                          Route::resource('ai-systems', AiSystemController::class)
 └─ app/Http/Controllers/AiSystemController.php
     ├─ app/Policies/AiSystemPolicy.php          Gate::authorize(...)
     ├─ app/Http/Requests/StoreAiSystemRequest.php
     │   └─ app/Concerns/AiSystemValidationRules.php   rules shared by store + update
     ├─ app/Models/AiSystem.php                  #[Fillable], casts, relationships
     └─ Inertia::render('ai-systems/index')
         └─ resources/js/pages/ai-systems/index.tsx
```

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

### Pages still to build

Every page under `resources/js/pages/` for the domain entities is currently a
**placeholder**: it renders the props the controller sent and a TODO note.
Pick one, delete the `<ScaffoldPlaceholder>` and build the real UI — the
backend behind it is done and tested.

| Section        | Page components                             |
| -------------- | ------------------------------------------- |
| AI systems     | `ai-systems/{index,create,show,edit}`       |
| Risks          | `risks/{index,create,show,edit}`            |
| Adverse events | `adverse-events/{index,create,show,edit}`   |
| Mitigations    | `mitigations/{index,create,show,edit}`      |
| Owners         | `owners/{index,create,show,edit}`           |
| Links          | `links/{index,create,show,edit}`            |
| Evidence       | `evidence/{index,create,show,edit}`         |
| Status history | `status-histories/{index,create,show,edit}` |

Evidence and status history are nested under a link
(`/links/{link}/evidence`), because neither exists on its own. Adverse events
hang off an AI system the same way risks do, so they are a top level resource
with the system picked on the form. Run
`php artisan route:list --except-vendor` for the full map.

## Testing

```bash
php artisan test --compact                       # the whole suite
php artisan test --compact tests/Feature/LinkTest.php
vendor/bin/pest --filter="a link can be created"
```

Each entity has a feature test in `tests/Feature/` covering the guest
redirect, the listing, creation, validation failures, updates and deletion.
CI runs the same suite against its own PostgreSQL service
(`.github/workflows/tests.yml`).

## Code quality

```bash
composer lint          # Pint, fixes formatting
composer types:check   # PHPStan level 7 over app/, database/, routes/
npm run check:fix      # lint + format the frontend
npm run types:check    # tsc --noEmit
composer ci:check      # everything CI runs
```

A husky `pre-commit` hook runs `lint-staged` and `composer lint`, so formatting
is fixed before a commit lands.

Commit messages follow the conventional style already used in the history:
`feat:`, `fix:`, `ci:`, `style:`, `chore:`.

## Project structure

```
app/
├── Concerns/          validation rule traits + the enum options helper
├── Enums/             every classification used by the domain
├── Http/
│   ├── Controllers/   one resource controller per entity
│   └── Requests/      Store*/Update* form requests
├── Models/            the eight domain models
└── Policies/          one policy per entity

database/
├── factories/         factories with states (implemented, dueForReview, ...)
├── migrations/        the schema
└── seeders/           one seeder per entity, chained by DatabaseSeeder

resources/js/
├── components/        shared React components
├── pages/             one directory per entity, mapped to Inertia::render()
├── routes/            generated by Wayfinder — do not edit
└── types/models.ts    TypeScript mirror of the models and enums
```

## Not implemented yet

Two capabilities from the solution architecture have no code behind them:

- **Traceability report** — exporting a system's full risk → mitigation →
  evidence chain.
- **Periodic reassessment** — a scheduled job acting on `links.next_review_date`
  (the `dueForReview()` factory state already produces overdue links to develop
  against).

Adverse events are recorded and can be named as the trigger of a status change,
but nothing reacts to one yet: registering an event does not reopen or reassess
the links covering the risk it materialized. That rule is still to be defined.
