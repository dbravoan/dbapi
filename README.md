# DB API

Laravel 13 multitenant REST API with **DDD/CQRS** architecture, table-per-tenant isolation, and a full Swagger UI.

## Quick start

```bash
cp .env.example .env
php artisan key:generate
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan dba:tenant:provision app_demo
```

| URL | What |
|-----|------|
| [http://localhost/](http://localhost/) | Landing page |
| [http://localhost/docs](http://localhost/docs) | Documentation hub (all guides) |
| [http://localhost/api/documentation](http://localhost/api/documentation) | Swagger UI (interactive API docs) |
| [http://localhost/api/swagger-docs](http://localhost/api/swagger-docs) | OpenAPI 3.0 raw JSON |

## Stack

| Layer | Technology |
|-------|------------|
| PHP | 8.3+ |
| Framework | Laravel 13 |
| Database | MySQL 8.4 (via Sail) |
| Auth | Laravel Passport (Bearer tokens) |
| DDD/CQRS | `dbravoan/dba-ddd-skeleton` |
| OpenAPI | L5 Swagger (`darkaonline/l5-swagger`) |
| Tests | PHPUnit 11 + Mockery |

## Architecture

**3 patterns layered together:**

1. **DDD** — Code organised by bounded context (`Blogging`, `Identity`, `Forms`, `Language`, `PageManagement`, `TodoList`) and inside each context by aggregate (`Post`, `User`, `Form`, …). Every aggregate has `Domain → Application → Infrastructure`. Inner layers never depend on outer layers.

2. **CQRS** — Writes go through a `CommandBus`, reads through a `QueryBus`. Handlers are single-method (`__invoke`) classes. Domain events are recorded on the aggregate and dispatched by the bus.

3. **Table-per-tenant** — The URL segment `/{tenant}/{version}/...` resolves to a tenant whose `app_id` prefixes all Eloquent table names at runtime (e.g. `acme_posts`, `acme_users`).

See `docs/architecture.md` for full details.

## Project structure

```
├── app/                    Laravel glue
│   ├── Console/Commands/   Tenant management (CLI)
│   ├── Http/Middleware/    identify_tenant, require.module, api.version, tenant
│   ├── Models/             Eloquent models (thin persistence, no business logic)
│   └── Providers/          Repository bindings + handler registrations
├── src/                    DDD modules
│   ├── Blogging/           Posts (translatable), Categories, Tags
│   ├── Forms/              Dynamic forms + submissions + anti-spam
│   ├── Identity/           Users (cross-module, no gate)
│   ├── Language/           ISO 639-1 language catalog
│   ├── PageManagement/     Pages (translatable, block editor)
│   ├── Shared/             TenantContext, TenantResolver, BlockEditorValidator
│   └── TodoList/           Tasks (reference module)
├── routes/api.php          All HTTP routes
├── config/l5-swagger.php   Swagger UI configuration
└── .agents/                AI agent harness
    ├── agents/             leader / implementer / reviewer definitions
    ├── skills/             Domain-specific skill files
    └── scripts/            commit.sh, push.sh, human-gate.sh
```

## API routes

All routes live under `/{tenant}/{version}/` with middleware pipeline `identify_tenant → api.version → tenant`. Writes marked 🔒 require `auth:api` (Bearer token).

### Blogging (`require.module:blog`)

| Method | Path | Access |
|--------|------|--------|
| `GET` | `/posts` | Public |
| `GET` | `/posts/{id}` | Public |
| `GET` | `/categories` | Public |
| `GET` | `/categories/{id}` | Public |
| `GET` | `/tags` | Public |
| `GET` | `/tags/{id}` | Public |
| `POST` | `/posts` | 🔒 Authenticated |
| `PUT` | `/posts/{id}` | 🔒 Authenticated |
| `POST` | `/categories` | 🔒 Authenticated |
| `PUT` | `/categories/{id}` | 🔒 Authenticated |
| `POST` | `/tags` | 🔒 Authenticated |
| `PUT` | `/tags/{id}` | 🔒 Authenticated |

### Forms (`require.module:forms`)

| Method | Path | Access |
|--------|------|--------|
| `POST` | `/forms/{key}/submit` | Public (anti-spam + rate-limited) |
| `GET` | `/forms/{id}` | 🔒 Authenticated |
| `POST` | `/forms` | 🔒 Authenticated |

### Identity (no module gate — always available)

| Method | Path | Access |
|--------|------|--------|
| `GET` | `/users/{id}` | Public |
| `GET` | `/user` | 🔒 Authenticated (current user) |
| `POST` | `/users` | 🔒 Authenticated |
| `PUT` | `/users/{id}` | 🔒 Authenticated |

### Languages (`require.module:languages`)

| Method | Path | Access |
|--------|------|--------|
| `GET` | `/languages` | Public |
| `GET` | `/languages/{id}` | Public |
| `POST` | `/languages` | 🔒 Authenticated |

### Pages (`require.module:pages`)

| Method | Path | Access |
|--------|------|--------|
| `GET` | `/pages/{id}` | Public |
| `POST` | `/pages` | 🔒 Authenticated |
| `PUT` | `/pages/{id}` | 🔒 Authenticated |

### TodoList (`require.module:todolist`)

| Method | Path | Access |
|--------|------|--------|
| `GET` | `/tasks/{id}` | Public |
| `POST` | `/tasks` | 🔒 Authenticated |
| `PUT` | `/tasks/{id}` | 🔒 Authenticated |

## Request example

```bash
curl -X POST http://localhost/app_demo/v1/posts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "title": "My First Post",
    "content": "Hello world!",
    "language": "en",
    "category_id": null,
    "tag_names": ["php", "laravel"]
  }'
```

## Tenant format

- 3 to 63 chars
- Lowercase letters and numbers
- Internal `-` or `_` allowed
- Cannot start or end with `-` or `_`

## API versioning

Configured via environment:

```env
API_SUPPORTED_VERSIONS=v1
API_TENANT_SUPPORTED_VERSIONS='{"app_demo":["v1"],"legacy_co":["v1","v2"]}'
```

Resolution order: (1) tenant DB `allowed_versions` → (2) tenant config map → (3) global fallback.

## Testing

```bash
# Run all tests (115+ tests, 215+ assertions)
php artisan test

# Inside Sail
./vendor/bin/sail artisan test

# Static analysis (PHPStan level 5)
composer stan

# Lint (configurable)
composer lint
```

## Swagger / OpenAPI

```bash
# Regenerate OpenAPI docs from PHP attributes
php artisan l5-swagger:generate
```

| Endpoint | Description |
|----------|-------------|
| `/api/documentation` | Swagger UI (interactive, try-it-out) |
| `/api/swagger-docs` | Raw OpenAPI 3.0 JSON |

Annotations live on each controller (`#[OA\Get]`, `#[OA\Post]`, etc.) and globally in `src/OpenApi.php` (shared schemas: SuccessResponse, ErrorResponse, Post, Category, Tag, Form, User, Language, Page, Task).

## CLI commands

| Command | Purpose |
|---------|---------|
| `dba:tenant:create` | Create a new tenant with optional module provisioning |
| `dba:tenant:provision` | Create tables for an existing tenant |
| `dba:tenant:list` | List all tenants |
| `dba:tenant:show` | Show tenant details |
| `dba:tenant:enable-module` | Enable a module and provision its tables |
| `dba:tenant:disable-module` | Disable a module (optionally drop tables) |

## Documentation

| Document | What it covers |
|----------|---------------|
| [`/docs`](http://localhost/docs) | Documentation hub (browser) |
| `docs/user-manual.md` | API consumer quick-start |
| `docs/development.md` | GitHub Flow, adding features, conventions, deploy |
| `docs/architecture.md` | DDD layering, CQRS, multitenancy |
| `docs/conventions.md` | Coding standards, naming, file layout |
| `docs/verification.md` | How to prove a feature works |

## Harness scripts

| Script | Purpose |
|--------|---------|
| `.agents/scripts/commit.sh` | Stage + test + commit (agents only, never to master) |
| `.agents/scripts/push.sh` | Push branch to origin (protected branch guard) |
| `.agents/scripts/human-gate.sh` | CI/CD gate: master/main merges require human |

## License

MIT
