# Development Guide

Complete reference for developing with DB API.

---

## 1. GitHub Flow (branch strategy)

```
master ──●────────────────────●──
          \                  /
feature/   ●──●──●──●──●──●──
          (commits)      (PR)
```

### Rules

| Step | Who | Action |
|------|-----|--------|
| 1 | Agent/Human | Branch from `master`: `git checkout -b feature/123-slug` |
| 2 | Agent | Make changes, commit via `.agents/scripts/commit.sh "message"` |
| 3 | Agent | Push via `.agents/scripts/push.sh` |
| 4 | Human | Create PR on GitHub: `gh pr create --fill` |
| 5 | Human | Review + approve PR |
| 6 | Human | **Only human** merges to `master` (protected branch) |
| 7 | Human | Delete feature branch |

- **Never commit directly to `master` or `main`.**
- Branch naming: `feature/<id>-<kebab-description>` (e.g. `feature/12-add-comments`).
- One feature = one branch. No scope mixing.

### Commit messages

Use **imperative present tense**:

```
Add Comment aggregate with CRUD operations
Fix Post slug generation when title has special chars
Rename CategorysResponse to CategoriesResponse
```

Format: `<verb> <what> [why]`

---

## 2. How to add a new feature

### 2.1 Pre-work

```bash
./init.sh                    # Must exit 0
php artisan test             # Must be green
```

Read these files first:
- `docs/architecture.md` — understand DDD layering
- `docs/conventions.md` — coding standards
- `feature_list.json` — pick a `pending` feature

### 2.2 Implementation steps

1. **Domain layer** (`src/{Context}/{Agg}/Domain/`)
   - Create `AggregateRoot` subclass with private constructor + `create()` factory
   - Create Value Objects (extend `Uuid`, `StringValueObject`, etc.)
   - Create `*CreatedDomainEvent` (recorded inside `create()`)
   - Create `*Repository` interface
   - Write domain test at `src/{Context}/{Agg}/Tests/Domain/`

2. **Application layer** (`src/{Context}/{Agg}/Application/`)
   - Create `Create*Command` (immutable DTO)
   - Create `Create*CommandHandler` (depends on repository interface only)
   - Create response DTO at `Application/Response/*Response.php`
   - Repeat for `Find/`, `Update/`, `SearchByCriteria/` as needed

3. **Infrastructure layer** (`src/{Context}/{Agg}/Infrastructure/`)
   - Create `Controller/` (invokable, extends `ApiController`, injects `CommandBus`/`QueryBus`)
   - Add `#[OA\*]` annotations for Swagger
   - Create `Persistence/Eloquent*Repository.php` (implements domain interface)
   - Create `Module/*ModuleProvisioner.php` if new tables are needed

4. **Wiring**
   - Bind repository in `app/Providers/RepositoryServiceProvider.php`
   - Register handlers in `app/Providers/DomainServiceProvider.php`
   - Add routes in `routes/api.php` under correct gate
   - Add Provisioner to `ProvisionTenantCommand` if applicable

5. **Tests**
   - Feature test at `tests/Feature/*RouteTest.php` (mock buses + tenant resolver)
   - Cover: 200/201, 401, 403 (module gate), 404, 422

6. **Verify**
   ```bash
   php artisan test
   php artisan l5-swagger:generate
   ./init.sh
   ```

---

## 3. Code conventions summary

| Rule | Standard |
|------|----------|
| PHP version | 8.3+ |
| Strict types | `declare(strict_types=1)` on every file |
| Classes | `final` unless inheriting |
| Value Objects | `final readonly` (extend skeleton base) |
| Controllers | `final`, invokable, extend `ApiController` |
| Response envelope | `success`, `data`, `message` via `sendResponse()`/`sendError()` |
| Namespaces | `Dbapi\` in `src/`, `App\` in `app/` |
| Indentation | 4 spaces, no tabs |
| No debug | No `dd()`, `dump()`, `Log::debug`, TODOs without feature ref |

Full details at `docs/conventions.md`.

---

## 4. Module provisioning

Each module with per-tenant tables has a provisioner extending `ModuleProvisioner`:

```
src/{Context}/Infrastructure/Module/{Name}ModuleProvisioner.php
```

The provisioner:
- Creates tables with `{app_id}_` prefix
- Is idempotent (checks `Schema::hasTable`/`hasColumn`)
- Is called by `dba:tenant:enable-module` and `dba:tenant:provision`

### Current modules and tables

| Module | Tables | Gate key |
|--------|--------|----------|
| Blog | `_users`, `_categories`, `_tags`, `_posts`, `_post_translations`, `_post_tag` | `blog` |
| Forms | `_forms`, `_form_submissions` | `forms` |
| Languages | `_languages` | `languages` |
| Pages | `_pages`, `_page_translations` | `pages` |
| TodoList | `_tasks` | `todolist` |

---

## 5. Deployment

### Requirements

- PHP 8.3+
- MySQL 8.4+
- Composer 2.x
- Laravel Passport (OAuth)

### Deploy steps

```bash
# 1. Production dependencies
composer install --no-dev --optimize-autoloader

# 2. Environment
cp .env.production .env
php artisan key:generate

# 3. Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Migrate central DB
php artisan migrate

# 5. Passport keys
php artisan passport:keys

# 6. Tenant provisioning (repeat per tenant)
php artisan dba:tenant:provision tenant_id

# 7. Queue worker (if using async CRM forwarding)
php artisan queue:work &
```

### Docker (Sail)

`compose.yaml` includes `laravel.test` (PHP 8.4) and `mysql:8.4`.

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

---

## 6. Response envelope

Every endpoint returns:

```json
{
  "success": true,
  "data": { ... },
  "message": "Operation completed successfully"
}
```

Errors:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

---

## 7. Middleware pipeline

Request path: `/{tenant}/{version}/posts/{id}`

```
identify_tenant    → Resolves tenant from URL, validates status (403 if suspended)
api.version        → Validates version against allowed list (400 if unsupported)
tenant             → Ensures tenant context is set (400 if missing)
require.module:X   → Checks tenant has module X enabled (403 if not)
auth:api           → Authenticates via Bearer token (401 if missing)
Controller         → Validates request, dispatches bus, returns response
```

---

## 8. Testing guide

### Test types

| Type | Location | What |
|------|----------|------|
| Domain unit | `src/{Ctx}/{Agg}/Tests/Domain/` | Aggregate + VO behaviour, no Laravel |
| Infrastructure | `src/{Ctx}/{Agg}/Tests/Infrastructure/` | Repository query logic |
| Feature | `tests/Feature/` | Full HTTP route via mocked buses |
| Unit | `tests/Unit/` | Shared logic (e.g. BlockEditorValidator) |

### Feature test template

See `tests/Feature/TaskRouteTest.php` for reference. Pattern:

1. `mockResolver()` — replaces `TenantResolverInterface` with mock
2. `makeTenant()` — builds `Tenant` stub (set `enabled_modules` for gate tests)
3. Mock `CommandBus::dispatch()` and/or `QueryBus::ask()` in each test
4. One test per behaviour (`_returns_404_when_not_found`, etc.)

### Coverage requirements

| Changed | Must add |
|---------|----------|
| Value Object | Unit test (valid value passes, invalid throws) |
| Aggregate | Domain unit test |
| Controller/Route | Feature test (200 + 401 + 403 + 404 + 422) |
| Repository binding | Feature test (proved by bus path) |

---

## 9. Swagger / OpenAPI

Docs live at `/api/documentation`.

### Regenerate

```bash
php artisan l5-swagger:generate
```

### Annotation locations

- **Per-controller**: `#[OA\Get]`, `#[OA\Post]`, etc. on `__invoke`
- **Global**: `src/OpenApi.php` — info, tags, security scheme, shared schemas (SuccessResponse, ErrorResponse, Post, User, etc.)

### Adding new schemas

Add `#[OA\Schema(...)]` attributes to `src/OpenApi.php` for any new response DTO.

---

## 10. CLI reference

| Command | Arguments | Description |
|---------|-----------|-------------|
| `dba:tenant:create` | `app_id`, `--name=`, `--type=`, `--modules=`, `--versions=` | Create tenant + provision modules |
| `dba:tenant:provision` | `app_id` | Create all tables for a tenant |
| `dba:tenant:list` | `--status=` | List tenants |
| `dba:tenant:show` | `app_id` | Show tenant details |
| `dba:tenant:enable-module` | `app_id`, `module` | Enable + provision module |
| `dba:tenant:disable-module` | `app_id`, `module`, `--drop-tables` | Disable module |

---

## 11. Composer scripts

| Script | Command |
|--------|---------|
| `composer test` | `php artisan test` |
| `composer stan` | `phpstan analyse src/ --level 5` |

---

## 12. Agent harness

This repo uses AI agents (via OpenCode) for development. See:

- `AGENTS.md` — Navigation map
- `OPENCODE.md` — Role definitions
- `CHECKPOINTS.md` — Session end-state checks
- `.agents/scripts/commit.sh` — Agent-safe commit (guards against master)
- `.agents/scripts/push.sh` — Agent-safe push
- `.agents/scripts/human-gate.sh` — CI/CD gate

### Agent workflow

```
leader → dispatches implementer → writes code + tests
leader → dispatches reviewer     → validates against docs
leader → flips feature to done   → only after reviewer approves
```
