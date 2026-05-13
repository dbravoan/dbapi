# Architecture — what "doing a good job" means in `dbapi`

> Read this **before** you implement anything. If a doc rule conflicts with
> something you saw in the codebase, the doc wins; report the drift in your
> impl report so we can decide whether to update the doc or refactor the code.

---

## 1. Mental model

`dbapi` is a **Laravel 13 multitenant REST API** that uses three architectural
patterns simultaneously:

1. **DDD (Domain-Driven Design)** — code is organised by *bounded context*
   (`Blogging`, `Identity`, `Forms`, …) and inside each context, by
   *aggregate* (`Post`, `User`, …). Every aggregate has three layers:
   `Domain → Application → Infrastructure`. Layers below cannot know about
   layers above.
2. **CQRS (Command Query Responsibility Segregation)** — writes go through a
   `CommandBus`, reads through a `QueryBus`. Handlers are one-method classes
   (`__invoke`). Domain events are recorded on the aggregate and dispatched
   by the bus.
3. **Table-per-tenant multitenancy** — the URL segment `{tenant}` resolves to
   a tenant whose `app_id` is used to prefix every Eloquent table at runtime.

The `dbravoan/dba-ddd-skeleton` package provides the base classes
(`AggregateRoot`, `Uuid`, `StringValueObject`, `Command`, `Query`,
`CommandBus`, `QueryBus`, `EloquentRepository`, `ApiController`, `Criteria`,
…) under the **`Dba\DddSkeleton\…`** namespace. Use them; do not reimplement.

## 2. Bounded contexts

| Context | Purpose | Module key for `require.module` middleware |
|---|---|---|
| `Blogging` | Posts (translatable), Categories, Tags | `blog` |
| `Identity` | Users (cross-cutting — no module gate; always available, see §5) | — |
| `Forms` | Dynamic form definitions, submissions, anti-spam, async CRM | `forms` |
| `Language` | Language catalog for translations | `languages` |
| `PageManagement` | Translatable pages with block-editor content | `pages` |
| `TodoList` | Tasks (reference / simplest module) | `todolist` |
| `Shared` | Cross-cutting infra (`TenantContext`, `TenantResolver`, validators, base `ModuleProvisioner`) | — |

Whether to add a new context vs. add an aggregate to an existing context is
a design decision. Rule of thumb: **a new context only if there is a
business invariant that crosses no existing context**. Otherwise add an
aggregate.

## 3. Layer responsibilities

### Domain (`src/{Context}/{Agg}/Domain/`)

- Pure PHP. No Laravel, no Eloquent, no HTTP, no `Carbon`, no `Log`.
- Aggregates extend `Dba\DddSkeleton\Shared\Domain\Aggregate\AggregateRoot`.
- Factory `create(...)` builds the aggregate **and** records the
  `…CreatedDomainEvent` via `$this->record(...)`. State changes that matter
  to the business also record events.
- Value Objects (`final readonly`) extend one of:
  `StringValueObject`, `Uuid`, `IntValueObject`, `FloatValueObject`,
  `BoolValueObject`, `DateTimeValueObject`, `EmailValueObject`,
  `UrlValueObject`, `MoneyValueObject`.
- Enum-like VOs carry a `private const ALLOWED = [...]` and assert in the
  constructor (see `TaskStatus`, `TaskPriority`).
- Repository **interfaces** live in `Domain/`. Implementations don't.

### Application (`src/{Context}/{Agg}/Application/`)

- One sub-folder per use case: `Create/`, `Update/`, `Delete/`, `Find/`,
  `SearchByCriteria/`, `Submit/`, `FindAll/`, …
- Each use case has a `Command` (or `Query`) DTO + its `Handler`.
- The handler depends **only** on the repository interface from `Domain/`.
- Read handlers return a `Response` DTO from `Application/Response/`. The
  DTO has a `toArray()` for the controller.
- No Eloquent, no `Request`, no validation rules here. Application orchestrates,
  it does not parse HTTP.

### Infrastructure (`src/{Context}/{Agg}/Infrastructure/`)

- `Controller/` — invokable controllers extending
  `Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController`. They:
  1. Validate the `Request`.
  2. Dispatch to `CommandBus` or `QueryBus`.
  3. Return via `$this->sendResponse(...)` / `$this->sendError(...)`.
- `Persistence/` — Eloquent repositories extending `EloquentRepository`
  and implementing the Domain interface. `toPrimitives()` / `fromPrimitives()`
  is the wire between aggregate and Eloquent model.
- `Module/` — optional, for `ModuleProvisioner` implementations that create
  per-tenant tables when a module is enabled.

## 4. Wiring

Three Laravel providers do the gluing:

- **`app/Providers/RepositoryServiceProvider.php`** — every
  `RepositoryInterface → EloquentRepository` binding. New aggregates must be
  added here.
- **`app/Providers/DomainServiceProvider.php`** — every command and query
  handler is registered here so `LaravelCommandBus` / `LaravelQueryBus` can
  discover them via the `dba_ddd.command_handler` / `dba_ddd.query_handler`
  service tags. New handlers must be added here.
- **`app/Providers/EventServiceProvider.php`** — domain event subscribers
  if/when we add cross-aggregate reactions.

### Identity — cross-cutting context (no module gate)

`Identity/User` is deliberately **not** behind a `require.module` gate.
Users are foundational to authentication (`auth:api` via Passport), and every
tenant — regardless of which business modules it has enabled — needs to
resolve users for token verification, ownership checks, and audit trails.
Gating User endpoints behind a module would create a circular dependency
(a module must be enabled before its creator can authenticate). The current
behaviour (User routes are always available) is the correct design.

## 5. Multitenancy contract

- Every route lives under `/{tenant}/{version}/...`.
- The middleware pipeline is `identify_tenant → api.version → tenant`. The
  first resolves the tenant via `TenantResolverInterface`; the third sets
  `config(['database.tenant.app_id' => $appId])`.
- Eloquent models override `getTable()` to compute the table name from that
  config value. **Never** hard-code a table name.
- Module gating uses the `require.module:<key>` middleware, which checks the
  tenant's `enabled_modules` JSON column.

## 6. Translatable aggregates

Posts and Pages are translatable. The pattern uses an intermediate table
(`*_translations`) keyed by `(entity_id, language_code)`. See the
`translatable-modules` skill. When adding a translatable aggregate, the
**domain** must model translations as part of the aggregate; the **repository**
joins and hydrates them. Controllers accept `language_code` as a query/body
parameter and pass it down.

## 7. What "good work" looks like in this repo

A change is "good" if all of the following are true:

1. It respects the three layers strictly — no layer skips.
2. It uses `Dba\DddSkeleton\…` base classes — no reimplementation, no
   hallucinated `Dbravoan\DbaSkeletonDdd\…` namespace.
3. The corresponding provider(s) are updated (repository binding, handler
   registration).
4. Routes are exposed in `routes/api.php` under the right module gate and
   auth middleware.
5. At least one Domain unit test (under `src/{Ctx}/{Agg}/Tests/Domain/`) or
   Feature test (under `tests/Feature/`) covers the new behaviour.
6. OpenAPI attributes (`#[OA\…]`) on the controller are present and
   accurate (Swagger UI at `/documentation` must still render cleanly).
7. `./init.sh` exits 0.

## 8. What is out of scope

- Front-end concerns (no Blade, no Vue, no Inertia).
- Schema migrations that touch the central `tenants` table — those are rare
  and require a separate, explicit feature.
- Direct SQL — go through Eloquent or `Criteria`, never `DB::raw` business logic.
- New language adapters in `Shared/Infrastructure/BlockEditor/` — design first.
