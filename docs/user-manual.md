# User Manual — DB API

> Short, clear guide for API consumers.

---

## Base URL

```
http://{host}/api/{tenant}/{version}/
```

Example: `http://localhost/api/acme/v1/posts`

## Authentication

All **write** endpoints require a Bearer token.

Obtain one via Passport (separate auth flow), then include:

```
Authorization: Bearer eyJ0eXAiOiJKV1Qi...
```

Read endpoints are public (no token needed).

## Standard response format

### Success

```json
{
  "success": true,
  "data": { ... },
  "message": "Post created successfully"
}
```

### Error

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

## HTTP status codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 202 | Accepted (async) |
| 400 | Bad request (invalid tenant format, unsupported version) |
| 401 | Unauthenticated (missing/invalid token) |
| 403 | Forbidden (module not enabled for tenant) |
| 404 | Not found (tenant/entity) |
| 422 | Validation error (check `errors` field) |
| 429 | Too many requests (rate limited) |

## Multitenancy

Every request targets a tenant. The tenant ID is the first URL segment:

```
/acme/v1/posts         → tenant "acme"
/other-company/v1/forms → tenant "other-company"
```

Tenant IDs:
- 3–63 characters
- Lowercase letters, numbers, hyphens, underscores
- Cannot start/end with `-` or `_`

## Module gating

Some endpoints require the tenant to have a module enabled. If disabled:

```json
{
  "success": false,
  "message": "Module 'blog' is not enabled for this tenant"
}
```

Enable via CLI: `php artisan dba:tenant:enable-module {app_id} {module}`.

## Available endpoints

### Blog posts

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/posts` | No | List/search posts |
| `GET` | `/posts/{id}` | No | Get post by ID (opt. `?lang=es`) |
| `POST` | `/posts` | Yes | Create post (supports SEO + OG + tags) |
| `PUT` | `/posts/{id}` | Yes | Update post translation |

### Categories

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/categories` | No | List/search categories |
| `GET` | `/categories/{id}` | No | Get category by ID |
| `POST` | `/categories` | Yes | Create category |
| `PUT` | `/categories/{id}` | Yes | Rename category |

### Tags

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/tags` | No | List/search tags |
| `GET` | `/tags/{id}` | No | Get tag by ID |
| `POST` | `/tags` | Yes | Create tag (slug auto-generated) |
| `PUT` | `/tags/{id}` | Yes | Rename tag |

### Forms

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/forms/{key}/submit` | No | Submit form (honeypot anti-spam) |
| `GET` | `/forms/{id}` | Yes | Get form definition |
| `POST` | `/forms` | Yes | Create form definition |

Form submission includes a hidden `honeypot` field. Fill it → submission rejected (bot detected).

### Users

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/users/{id}` | No | Get user |
| `GET` | `/user` | Yes | Get current authenticated user |
| `POST` | `/users` | Yes | Create user |
| `PUT` | `/users/{id}` | Yes | Rename user |

### Languages

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/languages` | No | List all active languages |
| `GET` | `/languages/{id}` | No | Get language by ID |
| `POST` | `/languages` | Yes | Register new language (ISO 639-1) |

### Pages

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/pages/{id}` | No | Get page by ID (opt. `?lang=es`) |
| `POST` | `/pages` | Yes | Create page with block editor content |
| `PUT` | `/pages/{id}` | Yes | Update page translation |

### Tasks

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/tasks/{id}` | No | Get task by ID |
| `POST` | `/tasks` | Yes | Create task |
| `PUT` | `/tasks/{id}` | Yes | Update task |

## Quick examples

```bash
# Get all posts
curl http://localhost/api/acme/v1/posts

# Get a post in Spanish
curl http://localhost/api/acme/v1/posts/550e...0000?lang=es

# Create a post (authenticated)
curl -X POST http://localhost/api/acme/v1/posts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"id":"550e...0000","title":"Hello","content":"World","language":"en"}'

# Submit a form (with honeypot)
curl -X POST http://localhost/api/acme/v1/forms/contact/submit \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","honeypot":""}'
```

## Rate limiting

- 60 requests per minute per IP (configurable).
- Form submissions have additional rate limits.
- Hit limit → HTTP 429.

## API versions

Supported versions: `v1` (default).

Configure per-tenant via env:

```
API_TENANT_SUPPORTED_VERSIONS='{"acme":["v1"],"legacy":["v1","v2"]}'
```

## Error troubleshooting

| Symptom | Likely cause |
|---------|-------------|
| 400 "Invalid tenant" | Wrong tenant format (see rules above) |
| 400 "Unsupported version" | Using `v2` but tenant only supports `v1` |
| 401 | Missing `Authorization: Bearer` header |
| 403 "Module not enabled" | Tenant missing the required module |
| 404 | Wrong tenant ID or entity ID |
| 422 | Required field missing or wrong type |

## Swagger UI

Interactive docs: `http://localhost/api/documentation`

Full request/response schemas, try-it-out, and auth setup available there.
