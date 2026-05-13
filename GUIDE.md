# Developer Guide — dbapi

Complete guide for launching the project with Sail, setting up the database, creating tenants, and testing with curl / Postman.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [First-time setup](#2-first-time-setup)
3. [Start and stop Sail](#3-start-and-stop-sail)
4. [Database setup](#4-database-setup)
5. [Create your first tenant](#5-create-your-first-tenant)
6. [Tenant lifecycle commands](#6-tenant-lifecycle-commands)
7. [Available modules](#7-available-modules)
8. [Translatable modules](#8-translatable-modules)
9. [Obtain an API token (Passport)](#9-obtain-an-api-token-passport)
10. [Testing with Swagger UI](#10-testing-with-swagger-ui)
11. [Testing with curl](#11-testing-with-curl)
12. [Testing with Postman](#12-testing-with-postman)
13. [Run the test suite](#13-run-the-test-suite)
14. [Troubleshooting](#14-troubleshooting)

---

## 1. Prerequisites

| Tool | Minimum version |
|------|----------------|
| Docker Desktop | 4.x |
| Docker Compose | v2 |
| PHP (for composer only, not for running code) | any |
| Composer | 2.x |

> All PHP execution happens inside Docker (Sail). Your host PHP version does not matter for running the app.

---

## 2. First-time setup

```bash
# 1. Clone the project
git clone <repo-url> dbapi
cd dbapi

# 2. Copy environment file
cp .env.example .env

# 3. Edit .env — set DB credentials (defaults already work with Sail)
#    DB_HOST=mysql        (use 'mysql' inside Sail, not 127.0.0.1)
#    DB_DATABASE=dbapi
#    DB_USERNAME=sail
#    DB_PASSWORD=password
#    DB_PORT=3306

# 4. Install PHP dependencies (uses your host composer just to bootstrap Sail)
composer install --ignore-platform-reqs

# 5. Build the Sail image
./vendor/bin/sail build --no-cache

# 6. Generate app key
./vendor/bin/sail artisan key:generate

# 7. Install Passport keys (one-time)
./vendor/bin/sail artisan passport:keys
```

### Recommended: alias Sail

```bash
# Add to ~/.bashrc or ~/.zshrc
alias sail='[ -f sail ] && sh sail || ./vendor/bin/sail'
```

After that you can use `sail` instead of `./vendor/bin/sail`.

---

## 3. Start and stop Sail

```bash
# Start in background (detached)
sail up -d

# View logs
sail logs -f

# Stop
sail down

# Stop + remove volumes (wipes DB data)
sail down -v
```

The app is available at: **http://localhost** (or the port set in `APP_PORT`).

---

## 4. Database setup

```bash
# Run all global migrations (creates the tenants table + Passport tables)
sail artisan migrate

# Verify migrations ran
sail artisan migrate:status
```

Expected tables after migration:

- `tenants` — global tenant registry
- `oauth_clients`, `oauth_access_tokens`, etc. — Passport
- `personal_access_tokens` — Sanctum (not used but migrated)
- `failed_jobs`, `password_resets`

---

## 5. Create your first tenant

```bash
# Blog-only tenant
sail artisan dba:tenant:create my_blog \
    --name="My Blog" \
    --type=blog \
    --modules=blog

# TodoList-only tenant
sail artisan dba:tenant:create my_todos \
    --name="My Todos" \
    --type=todolist \
    --modules=todolist

# Full-featured tenant (all modules)
sail artisan dba:tenant:create demo_app \
    --name="Demo App" \
    --type=blog \
    --modules=blog,pages,languages,forms,todolist

# Blog + translatable pages with multilingual support
sail artisan dba:tenant:create acme_company \
    --name="Acme Company" \
    --type=blog \
    --modules=blog,pages,languages,forms

# Tenant with custom allowed versions (overrides global config)
sail artisan dba:tenant:create enterprise_co \
    --name="Enterprise Co" \
    --type=blog \
    --modules=blog \
    --versions=v1,v2
```

What this does:
1. Inserts a row into `tenants`
2. Creates all required DB tables for each requested module:
   - `blog` → `{tenant}_users`, `{tenant}_categories`, `{tenant}_tags`, `{tenant}_posts`, `{tenant}_post_translations`, `{tenant}_post_tag`
   - `pages` → `{tenant}_pages`, `{tenant}_page_translations`
   - `languages` → `{tenant}_languages`
   - `forms` → `{tenant}_forms`, `{tenant}_form_submissions`
   - `todolist` → `{tenant}_tasks`

---

## 6. Tenant lifecycle commands

```bash
# List all tenants
sail artisan dba:tenant:list

# List only active tenants
sail artisan dba:tenant:list --status=active

# Show full details for one tenant
sail artisan dba:tenant:show my_blog

# Enable a module (provisions tables if needed)
sail artisan dba:tenant:enable-module my_blog todolist

# Disable a module (soft — keeps tables)
sail artisan dba:tenant:disable-module my_blog todolist

# Suspend a tenant (done via tinker or direct DB, no command yet — by design)
# UPDATE tenants SET status='suspended' WHERE app_id='bad_tenant';
```

---

## 7. Available modules

Each tenant can enable/disable modules independently. Available modules:

| Module | Purpose | Tables created | Key features |
|--------|---------|-----------------|--------------|
| `blog` | Blog posts, categories, tags | posts, post_translations, categories, tags, post_tag | Multi-language support, SEO fields per language |
| `pages` | Static pages with block editor | pages, page_translations | Rich content editing, translatable, SEO fields |
| `languages` | Language definitions | languages | ISO 639-1 codes, set default language for tenant |
| `forms` | Dynamic form definitions & submissions | forms, form_submissions | Anti-spam, honeypot field, submission throttling, webhook forwarding |
| `todolist` | Task management | tasks | Priority levels, status tracking |
| `identity` | User management (cross-module) | users | Available on all tenants, not module-gated |

### Enable a module on existing tenant

```bash
# Enable forms on my_blog tenant
sail artisan dba:tenant:enable-module my_blog forms

# Enable multiple modules at once
sail artisan dba:tenant:enable-module my_blog pages,languages,forms
```

### Disable a module (soft delete — keeps tables)

```bash
sail artisan dba:tenant:disable-module my_blog pages
```

---

## 8. Translatable modules

The `blog`, `pages`, and `forms` modules support multi-language content. Each entity has translations stored in an intermediate table per language.

### How it works

When you create a post or page, you specify a `language_code` (ISO 639-1, e.g., "en", "es", "fr"). The entity is stored with:

- **Core fields** in the main table (e.g., `posts`): id, status, created_at, updated_at
- **Translatable fields** in the translation table (e.g., `post_translations`):
  - `post_id`, `language_code` (unique constraint)
  - `slug`, `title`, `content`
  - SEO metadata: `seo_title`, `seo_description`, `seo_keywords`, `canonical_url`
  - Open Graph fields: `og_title`, `og_description`, `og_image`
  - Structured data: `structured_data` (JSON-LD)

### Example: Create a blog post in English

```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "title": "Hello World",
  "slug": "hello-world",
  "content": {
    "rows": [
      {
        "layout": "1",
        "columns": [
          {
            "blocks": [
              {
                "type": "rich_text",
                "content": "<p>This is my first post.</p>"
              }
            ]
          }
        ]
      }
    ]
  },
  "language_code": "en",
  "seo_title": "Hello World | My Blog",
  "seo_description": "My first blog post",
  "seo_keywords": "hello, world, first",
  "category_id": "550e8400-e29b-41d4-a716-446655440001",
  "tag_names": ["php", "laravel"]
}
```

### Example: Query a post by language

```bash
# Get post in English
curl "http://localhost/my_blog/v1/posts/550e8400-e29b-41d4-a716-446655440000?language=en"

# Get post in Spanish (if translation exists; falls back to first translation if not)
curl "http://localhost/my_blog/v1/posts/550e8400-e29b-41d4-a716-446655440000?language=es"
```

### Example: Create a page with translations

```bash
curl -X POST http://localhost/acme_company/v1/pages \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "660e8400-e29b-41d4-a716-446655440000",
    "status": "published",
    "language_code": "en",
    "slug": "about-us",
    "title": "About Us",
    "content": {
      "rows": [
        {
          "layout": "1",
          "columns": [
            {
              "blocks": [
                {
                  "type": "rich_text",
                  "content": "<p>We are a company...</p>"
                }
              ]
            }
          ]
        }
      ]
    },
    "seo_title": "About Us - Acme",
    "seo_description": "Learn more about Acme Company"
  }'
```

---

## 9. Obtain an API token (Passport)

### 9a. Create a Passport personal access client (one-time)

```bash
sail artisan passport:client --personal
# Note the client ID and secret that are printed
```

### 9b. Create a personal access token via tinker

```bash
sail artisan tinker
```

```php
// Inside tinker — use the tenant's user table
config(['database.tenant.app_id' => 'my_blog']);

$user = App\Models\User::create([
    'name'     => 'Admin',
    'email'    => 'admin@example.com',
    'password' => bcrypt('secret'),
]);

$token = $user->createToken('dev-token')->accessToken;
echo $token;
```

Copy the printed token — you'll use it as `Bearer <token>` in headers.

---

## 10. Testing with Swagger UI

The interactive API documentation is served at:

```
http://localhost/api/documentation
```

The raw OpenAPI JSON spec is available at:

```
http://localhost/docs
```

### Step 1 — Obtain a Bearer token

Follow [section 9](#9-obtain-an-api-token-passport) to generate a Passport personal access token.
Copy the token string — it looks like a long alphanumeric string, **not** a JWT.

### Step 2 — Authorise in Swagger UI

1. Open `http://localhost/api/documentation`
2. Click the **Authorize 🔓** button (top-right)
3. In the `bearerAuth (http, Bearer)` field, paste your token and click **Authorize**
4. Click **Close** — the padlock icon will turn closed (🔒)

All endpoints marked with 🔒 in the UI will now send `Authorization: Bearer <token>` automatically.

### Step 3 — Fill path parameters

Every endpoint requires two path parameters that identify the tenant and API version:

| Parameter | Description | Example |
|-----------|-------------|---------|
| `tenant`  | Your tenant's `app_id` (set when created) | `my_blog` |
| `version` | API version | `v1` |

When you click **Try it out** on any endpoint, fill these fields before executing.

### Step 4 — Execute a request

1. Click on an endpoint (e.g. `POST /{tenant}/{version}/posts`)
2. Click **Try it out**
3. Fill in `tenant` and `version`
4. Edit the request body example if needed
5. Click **Execute**
6. View the response below (status code + JSON body)

### Endpoint overview

| Method | Path | Auth | Module |
|--------|------|------|--------|
| `POST` | `/{tenant}/{version}/posts` | 🔒 Bearer | blog |
| `GET` | `/{tenant}/{version}/posts/{id}` | public | blog |
| `PUT` | `/{tenant}/{version}/posts/{id}` | 🔒 Bearer | blog |
| `POST` | `/{tenant}/{version}/categories` | 🔒 Bearer | blog |
| `GET` | `/{tenant}/{version}/categories/{id}` | public | blog |
| `PUT` | `/{tenant}/{version}/categories/{id}` | 🔒 Bearer | blog |
| `POST` | `/{tenant}/{version}/tags` | 🔒 Bearer | blog |
| `GET` | `/{tenant}/{version}/tags/{id}` | public | blog |
| `PUT` | `/{tenant}/{version}/tags/{id}` | 🔒 Bearer | blog |
| `POST` | `/{tenant}/{version}/languages` | 🔒 Bearer | languages |
| `GET` | `/{tenant}/{version}/languages` | public | languages |
| `GET` | `/{tenant}/{version}/languages/{id}` | public | languages |
| `POST` | `/{tenant}/{version}/pages` | 🔒 Bearer | pages |
| `GET` | `/{tenant}/{version}/pages/{id}` | public | pages |
| `PUT` | `/{tenant}/{version}/pages/{id}` | 🔒 Bearer | pages |
| `POST` | `/{tenant}/{version}/forms` | 🔒 Bearer | forms |
| `GET` | `/{tenant}/{version}/forms/{id}` | 🔒 Bearer | forms |
| `POST` | `/{tenant}/{version}/forms/{key}/submit` | public | forms |
| `POST` | `/{tenant}/{version}/users` | 🔒 Bearer | identity |
| `GET` | `/{tenant}/{version}/users/{id}` | public | identity |
| `PUT` | `/{tenant}/{version}/users/{id}` | 🔒 Bearer | identity |
| `POST` | `/{tenant}/{version}/tasks` | 🔒 Bearer | todolist |
| `GET` | `/{tenant}/{version}/tasks/{id}` | public | todolist |
| `PUT` | `/{tenant}/{version}/tasks/{id}` | 🔒 Bearer | todolist |

### Common response envelope

All responses follow this structure:

```json
{
  "success": true,
  "data": { ... },
  "message": "Operation completed successfully"
}
```

Error responses:

```json
{
  "success": false,
  "message": "Not Found"
}
```

### Regenerate the docs after code changes

```bash
sail artisan l5-swagger:generate
```

---

## 11. Testing with curl

Replace `my_blog` with your tenant's `app_id` and `<TOKEN>` with the token from step 7.

### Health check (no auth required)

```bash
curl http://localhost/up
```

### Find a post (public — no auth)

```bash
curl http://localhost/my_blog/v1/posts/550e8400-e29b-41d4-a716-446655440000
```

### Create a category (auth required)

```bash
curl -X POST http://localhost/my_blog/v1/categories \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "550e8400-e29b-41d4-a716-446655440001",
    "name": "Technology",
    "slug": "technology"
  }'
```

### Create a blog post (auth required)

```bash
curl -X POST http://localhost/my_blog/v1/posts \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Hello World",
    "content": "This is my first post.",
    "language": "en",
    "category_id": "550e8400-e29b-41d4-a716-446655440001",
    "tag_names": ["php", "laravel"]
  }'
```

### Create a blog post (auth required)

```bash
curl -X POST http://localhost/my_blog/v1/posts \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Hello World",
    "slug": "hello-world",
    "content": {
      "rows": [{
        "layout": "1",
        "columns": [{
          "blocks": [{
            "type": "rich_text",
            "content": "<p>This is my first post.</p>"
          }]
        }]
      }]
    },
    "language_code": "en",
    "seo_title": "Hello World | My Blog",
    "seo_description": "My first blog post",
    "seo_keywords": "hello, world",
    "category_id": "550e8400-e29b-41d4-a716-446655440001",
    "tag_names": ["php", "laravel"]
  }'
```

### Register a language (auth required)

```bash
curl -X POST http://localhost/acme_company/v1/languages \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "770e8400-e29b-41d4-a716-446655440001",
    "code": "es",
    "name": "Spanish",
    "native_name": "Español",
    "is_default": false,
    "is_active": true
  }'
```

### List available languages (public)

```bash
curl http://localhost/acme_company/v1/languages
# Returns array of registered languages for tenant
```

### Create a page with translations (auth required)

```bash
curl -X POST http://localhost/acme_company/v1/pages \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "660e8400-e29b-41d4-a716-446655440000",
    "status": "published",
    "language_code": "en",
    "slug": "about-us",
    "title": "About Us",
    "content": {
      "rows": [{
        "layout": "1",
        "columns": [{
          "blocks": [{
            "type": "rich_text",
            "content": "<p>We are a company...</p>"
          }]
        }]
      }]
    },
    "seo_title": "About Us - Acme",
    "seo_description": "Learn more about Acme Company",
    "seo_keywords": "about, company"
  }'
```

### Get a page (public)

```bash
curl "http://localhost/acme_company/v1/pages/660e8400-e29b-41d4-a716-446655440000?language=en"
# If language param omitted, returns first translation
```

### Create a form definition (auth required)

```bash
curl -X POST http://localhost/acme_company/v1/forms \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Contact Us",
    "key": "contact-us",
    "recipient_email": "contact@acme.com",
    "active": true,
    "fields": [
      {
        "name": "name",
        "label": "Full Name",
        "type": "text",
        "required": true
      },
      {
        "name": "email",
        "label": "Email Address",
        "type": "email",
        "required": true
      },
      {
        "name": "message",
        "label": "Message",
        "type": "textarea",
        "required": true
      },
      {
        "name": "honeypot",
        "label": "Leave blank",
        "type": "text",
        "required": false
      }
    ]
  }'
```

### Submit a form (public, no auth)

```bash
curl -X POST http://localhost/acme_company/v1/forms/contact-us/submit \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "message": "I have a question...",
    "honeypot": ""
  }'
# Returns 201 on success (anti-spam checks pass)
# Returns 422 on validation failure or if honeypot is filled
# Rate limited to 5 submissions per 60s per IP per form
```

### Get a form definition (auth required)

```bash
curl -H "Authorization: Bearer <TOKEN>" \
  http://localhost/acme_company/v1/forms/123
# Returns form schema with field definitions
```

### Create a task (todolist module, auth required)

```bash
curl -X POST http://localhost/my_todos/v1/tasks \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "660e8400-e29b-41d4-a716-446655440000",
    "title": "Buy groceries",
    "status": "pending",
    "priority": "medium",
    "description": "Milk, eggs, bread"
  }'
```

### Get a task (public read)

```bash
curl http://localhost/my_todos/v1/tasks/660e8400-e29b-41d4-a716-446655440000
```

### Update a task (auth required)

```bash
curl -X PUT http://localhost/my_todos/v1/tasks/660e8400-e29b-41d4-a716-446655440000 \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Buy groceries",
    "status": "done",
    "priority": "medium",
    "description": "Done!"
  }'
```

### Test tenant not found → 404

```bash
curl http://localhost/ghost_tenant/v1/posts/some-id
# → {"success":false,"message":"Not Found"}
```

### Test suspended tenant → 403

```bash
# Suspend a tenant via tinker
sail artisan tinker --execute="App\Models\Tenant::where('app_id','my_blog')->update(['status'=>'suspended']);"

curl http://localhost/my_blog/v1/posts/some-id
# → {"success":false,"message":"Tenant account is suspended"}

# Restore it
sail artisan tinker --execute="App\Models\Tenant::where('app_id','my_blog')->update(['status'=>'active']);"
```

### Test module not enabled → 403

```bash
# Try to access pages on a blog-only tenant (if pages not enabled)
curl http://localhost/my_blog/v1/pages/some-id
# → {"success":false,"message":"Module 'pages' is not enabled for this tenant"}

# Enable the module first
sail artisan dba:tenant:enable-module my_blog pages

# Now the same request will work (if page exists)
curl http://localhost/my_blog/v1/pages/some-id
```

---

## 12. Testing with Postman

### Import the collection

Create a new Postman Collection with the following variables:

| Variable   | Value |
|------------|-------|
| `base_url` | `http://localhost` |
| `tenant`   | `my_blog` |
| `version`  | `v1` |
| `token`    | `<your-passport-token>` |

### Folder structure

```
dbapi API
├── Blog
│   ├── POST   {{base_url}}/{{tenant}}/{{version}}/posts
│   ├── GET    {{base_url}}/{{tenant}}/{{version}}/posts/:id
│   ├── PUT    {{base_url}}/{{tenant}}/{{version}}/posts/:id
│   ├── POST   {{base_url}}/{{tenant}}/{{version}}/categories
│   ├── GET    {{base_url}}/{{tenant}}/{{version}}/categories/:id
│   ├── POST   {{base_url}}/{{tenant}}/{{version}}/tags
│   └── GET    {{base_url}}/{{tenant}}/{{version}}/tags/:id
├── Languages
│   ├── GET    {{base_url}}/{{tenant}}/{{version}}/languages
│   ├── GET    {{base_url}}/{{tenant}}/{{version}}/languages/:id
│   └── POST   {{base_url}}/{{tenant}}/{{version}}/languages
├── Pages
│   ├── GET    {{base_url}}/{{tenant}}/{{version}}/pages/:id
│   ├── POST   {{base_url}}/{{tenant}}/{{version}}/pages
│   └── PUT    {{base_url}}/{{tenant}}/{{version}}/pages/:id
├── Forms
│   ├── GET    {{base_url}}/{{tenant}}/{{version}}/forms/:id
│   ├── POST   {{base_url}}/{{tenant}}/{{version}}/forms
│   └── POST   {{base_url}}/{{tenant}}/{{version}}/forms/:key/submit
├── TodoList
│   ├── POST   {{base_url}}/{{tenant}}/{{version}}/tasks
│   ├── GET    {{base_url}}/{{tenant}}/{{version}}/tasks/:id
│   └── PUT    {{base_url}}/{{tenant}}/{{version}}/tasks/:id
└── Identity
    ├── POST   {{base_url}}/{{tenant}}/{{version}}/users
    └── GET    {{base_url}}/{{tenant}}/{{version}}/users/:id
```

### Auth header

For all write endpoints, add to the **Authorization** tab:
- Type: `Bearer Token`
- Token: `{{token}}`

### Sample request bodies

**Create post:**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "title": "Hello World",
  "slug": "hello-world",
  "content": {
    "rows": [{
      "layout": "1",
      "columns": [{
        "blocks": [{
          "type": "rich_text",
          "content": "<p>This is my first post.</p>"
        }]
      }]
    }]
  },
  "language_code": "en",
  "seo_title": "Hello World",
  "seo_description": "My first post",
  "category_id": null,
  "tag_names": ["php", "laravel"]
}
```

**Create language:**
```json
{
  "id": "770e8400-e29b-41d4-a716-446655440001",
  "code": "es",
  "name": "Spanish",
  "native_name": "Español",
  "is_default": false,
  "is_active": true
}
```

**Create page:**
```json
{
  "id": "660e8400-e29b-41d4-a716-446655440000",
  "status": "published",
  "language_code": "en",
  "slug": "about-us",
  "title": "About Us",
  "content": {
    "rows": [{
      "layout": "1",
      "columns": [{
        "blocks": [{
          "type": "rich_text",
          "content": "<p>Company info...</p>"
        }]
      }]
    }]
  },
  "seo_title": "About Us",
  "seo_description": "Learn about our company"
}
```

**Create form:**
```json
{
  "name": "Contact Form",
  "key": "contact",
  "recipient_email": "admin@company.com",
  "active": true,
  "fields": [
    {"name": "name", "label": "Name", "type": "text", "required": true},
    {"name": "email", "label": "Email", "type": "email", "required": true},
    {"name": "message", "label": "Message", "type": "textarea", "required": true},
    {"name": "honeypot", "label": "Leave blank", "type": "text", "required": false}
  ]
}
```

**Submit form:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "message": "Hello, I have a question",
  "honeypot": ""
}
```

**Create task:**
```json
{
  "id": "660e8400-e29b-41d4-a716-446655440000",
  "title": "Buy groceries",
  "status": "pending",
  "priority": "medium",
  "description": "Milk, eggs, bread"
}
```

**Update task:**
```json
{
  "title": "Buy groceries",
  "status": "done",
  "priority": "medium",
  "description": null
}
```

### Expected status codes

| Scenario | Code |
|----------|------|
| Success (read) | 200 |
| Created | 201 |
| Validation error | 422 |
| Unauthenticated | 401 |
| Module not enabled / suspended | 403 |
| Unknown tenant / not found | 404 |
| Invalid tenant format | 400 |
| Unsupported version | 400 |

---

## 13. Run the test suite

```bash
# All tests (requires Sail running + DB)
sail artisan test

# Specific suite
sail artisan test --testsuite=Feature
sail artisan test --testsuite=Unit

# Single file
sail artisan test tests/Feature/TenantMiddlewareTest.php

# With coverage (requires Xdebug in Sail image)
sail artisan test --coverage

# Parallel (faster)
sail artisan test --parallel
```

Tests that mock `TenantResolver` do **not** require a DB connection and run fast.
Tests that exercise actual DB operations require `sail artisan migrate --env=testing` first.

---

## 14. Troubleshooting

### Sail won't start

```bash
# Make sure Docker is running, then:
sail down -v
sail up -d
```

### "Class not found" after adding new files

```bash
sail composer dump-autoload
```

### "No application encryption key has been specified"

```bash
sail artisan key:generate
```

### Passport keys missing

```bash
sail artisan passport:keys
```

### Migration already ran but tables are missing

```bash
sail artisan migrate:fresh   # WARNING: drops all data
# or
sail artisan migrate:status  # check which migrations are pending
```

### Tenant tables not created

Make sure you ran `dba:tenant:create` or `dba:tenant:enable-module` — tenant tables are **not** created by standard migrations; they are provisioned per-tenant on demand.

### Unknown column errors on `{tenant}_posts`

Run provision again (it is idempotent — it only adds missing columns):

```bash
sail artisan dba:tenant:enable-module my_blog blog
```
