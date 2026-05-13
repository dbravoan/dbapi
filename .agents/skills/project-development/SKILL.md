# dbapi Project Development Guide

A comprehensive guide for developing features in this Laravel 13 DDD/CQRS multitenant API using the dba-ddd-skeleton framework.

---

## Quick Overview

**Stack:**
- Laravel 13 with PHP 8.3
- MySQL 8.4 via Sail Docker
- DDD (Domain-Driven Design) + CQRS (Command Query Responsibility Segregation)
- Multi-tenancy: Table-per-tenant with dynamic prefixing
- API-first: RESTful JSON with OpenAPI/Swagger documentation
- OAuth 2.0 authentication via Passport

**Architecture:**
- `src/` → Domain logic organized by module (Blogging, Forms, Language, Pages, etc.)
- Each module has: Domain, Application (CQRS handlers), Infrastructure (repos, controllers)
- `app/` → Framework glue: Models, Providers, HTTP middleware, Console commands
- `routes/api.php` → API route definitions with module gates

**Key Modules:**
- `blog` → Posts (translatable), Categories, Tags
- `pages` → Static pages with block editor (translatable)
- `languages` → Language definitions for translatable content
- `forms` → Dynamic form definitions + submissions with anti-spam
- `todolist` → Task management

---

## Project Structure

```
src/
├── Blogging/           # Blog posts, categories, tags
│   ├── Post/          # Post aggregate root
│   │   ├── Domain/    # Post entity, events, repository interface
│   │   ├── Application/  # Commands (Create, Update), Queries (Find, Search)
│   │   └── Infrastructure/  # Eloquent repo implementation, controllers
│   ├── Category/      # Similar structure
│   ├── Tag/
│   └── Infrastructure/Module/  # BlogModuleProvisioner (table creation)
├── Language/          # Language definitions (ISO 639-1 codes)
│   └── Language/
│       ├── Domain/
│       ├── Application/
│       └── Infrastructure/
├── PageManagement/    # Pages with block editor content
│   └── Page/
│       ├── Domain/
│       ├── Application/
│       └── Infrastructure/
├── Forms/             # Dynamic form definitions
│   ├── Form/
│   │   ├── Domain/
│   │   ├── Application/
│   │   └── Infrastructure/
│   └── Infrastructure/Module/  # FormsModuleProvisioner, SpamProtection
├── Identity/          # Users (cross-module)
├── TodoList/          # Tasks
└── Shared/            # Shared value objects, traits

app/
├── Http/
│   ├── Controllers/Controller.php  # Base controller with OpenAPI traits
│   ├── Kernel.php  # Middleware pipeline
│   ├── Middleware/  # identify_tenant, api.version, tenant, etc.
├── Models/  # Eloquent models (BlogPost, Post Translation, Form, etc.)
├── Providers/
│   ├── RepositoryServiceProvider  # Bind domain repos to Eloquent
│   ├── DomainServiceProvider  # Register CQRS handlers
│   └── others
├── Jobs/SendToCrmJob.php  # Async queue job for form submissions
└── Console/Commands/dba:tenant:*  # Tenant management

config/
├── api.php  # API configuration, routes versioning, default language
├── auth.php  # Passport/Sanctum settings
└── others

database/
├── migrations/  # Global migrations + tenant-specific provisioning
└── factories/

routes/api.php  # All API routes with module gates

public/index.php  # Entry point
```

---

## Architecture Patterns

### DDD (Domain-Driven Design)

Each module (Blogging, Forms, Pages) is organized in three layers:

#### 1. **Domain Layer** (`Domain/`)

Contains business logic and rules:
- **Entities** (e.g., `Post`, `Form`) — Value objects with identity
- **Value Objects** (e.g., `PostId`, `FormField`) — Immutable, identity-less objects
- **Aggregate Roots** (e.g., `Post extends AggregateRoot`) — Cluster entities and VOs that must be kept consistent
- **Domain Events** (e.g., `PostCreated`) — Recorded on aggregate root via `recordEvent()`
- **Repository Interface** (e.g., `PostRepository`) — Abstract persistence contract

```php
// src/Blogging/Post/Domain/Post.php
class Post extends AggregateRoot
{
    private PostId $id;
    private array $translations = [];  // keyed by language_code
    private Status $status;

    public static function create(PostId $id, Status $status, array $translations): self {
        $post = new self();
        $post->id = $id;
        $post->status = $status;
        $post->translations = $translations;
        $post->recordEvent(new PostCreated($id, $status));
        return $post;
    }
}
```

#### 2. **Application Layer** (`Application/`)

Implements CQRS: Commands (write) + Queries (read)

**Commands** handle state changes:
```php
// src/Blogging/Post/Application/Create/CreatePostCommand.php
class CreatePostCommand implements Command
{
    public function __construct(
        readonly string $id,
        readonly string $title,
        readonly string $languageCode,
        ...
    ) {}
}

// src/Blogging/Post/Application/Create/CreatePostCommandHandler.php
class CreatePostCommandHandler implements CommandHandler
{
    public function __invoke(CreatePostCommand $command): void {
        $post = Post::create(...);
        $this->repository->save($post);
        // Domain events automatically published by bus
    }
}
```

**Queries** fetch data:
```php
// src/Blogging/Post/Application/Find/FindPostQuery.php
class FindPostQuery implements Query
{
    public function __construct(readonly PostId $id, readonly string $language) {}
}

// Handler returns Response DTO
class FindPostQueryHandler implements QueryHandler
{
    public function __invoke(FindPostQuery $query): ?PostResponse {
        $post = $this->repository->search($query->id, $query->language);
        return $post ? PostResponse::from($post) : null;
    }
}
```

**Response DTOs** encapsulate read models:
```php
class PostResponse implements Response
{
    public function toArray(): array { return [...]; }
}
```

#### 3. **Infrastructure Layer** (`Infrastructure/`)

Concrete implementations:

- **Repository** — Persists/retrieves domain entities
- **Controllers** — HTTP endpoints; dispatch commands/queries, handle responses
- **Provisioners** — Create tenant-specific tables on module enable

```php
// src/Blogging/Post/Infrastructure/Persistence/EloquentPostRepository.php
class EloquentPostRepository implements PostRepository
{
    public function save(Post $post): void {
        // Save to blog_posts + blog_post_translations tables
    }

    public function search(PostId $id, string $language): ?Post {
        // Join, hydrate aggregate root
    }
}

// src/Blogging/Post/Infrastructure/Controller/CreatePostController.php
class CreatePostController extends ApiController
{
    #[OA\Post(path: "/{tenant}/{version}/posts", ...)]
    public function __invoke(Request $request): JsonResponse {
        $validated = $request->validate([...]);
        $this->bus->dispatch(new CreatePostCommand(...));
        return $this->sendResponse(null, 'Post created', 201);
    }
}
```

### CQRS (Command Query Responsibility Segregation)

- **Commands** = write operations → routed to CommandBus → dispatches to handler → publishes domain events
- **Queries** = read operations → routed to QueryBus → dispatches to handler → returns Response DTO

Handlers are auto-discovered by scanning `Application/Create/*Handler.php` and `Application/Find/*Handler.php` patterns.

### Multi-Tenancy (Table-per-Tenant)

- Central `tenants` table stores: `app_id`, `name`, `type`, `enabled_modules` (JSON), `status`
- Tenant context set via middleware → `config(['database.tenant.app_id' => 'my_blog'])`
- All Eloquent models override `getTable()` to prefix tenant name
- Example: `Post::all()` queries `app_my_blog_posts` table

Tenant-aware middleware pipeline:
1. `identify_tenant` — Extract tenant from URL path, validate it exists
2. `api.version` — Extract API version (default v1), validate against tenant
3. `tenant` — Set `config(['database.tenant.app_id'])` for this request

### Translatable Pattern (Intermediate Table)

Entities like Posts and Pages support multiple languages:

- **Main table** (e.g., `posts`): `id`, `status`, `created_at`, `updated_at`
- **Translation table** (e.g., `post_translations`):
  - `post_id` + `language_code` (unique constraint)
  - Translatable fields: `slug`, `title`, `content`
  - SEO fields: `seo_title`, `seo_description`, `seo_keywords`, `canonical_url`
  - OG fields: `og_title`, `og_description`, `og_image`
  - `structured_data` (JSON-LD)

Repository joins tables transparently:
```php
$post = $repository->search($postId, 'en');  // Returns Post with English translation
```

See the `translatable-modules` skill for detailed implementation patterns.

---

## Adding a New Module

### Step 1: Create Directory Structure

```bash
mkdir -p src/YourModule/YourEntity/{Domain,Application,Infrastructure/{Persistence,Controller}}
```

### Step 2: Define Domain Layer

```php
// src/YourModule/YourEntity/Domain/YourEntity.php
declare(strict_types=1);
namespace Dbapi\YourModule\YourEntity\Domain;

use Dba\DddSkeleton\Shared\Domain\AggregateRoot;

class YourEntity extends AggregateRoot
{
    private YourEntityId $id;
    private string $name;
    private Status $status;

    public static function create(YourEntityId $id, string $name, Status $status): self {
        $entity = new self();
        $entity->id = $id;
        $entity->name = $name;
        $entity->status = $status;
        $entity->recordEvent(new YourEntityCreated($id, $name));
        return $entity;
    }

    // Domain methods...
    public function changeName(string $name): void {
        if ($this->name === $name) return;
        $this->name = $name;
        $this->recordEvent(new YourEntityRenamed($this->id, $name));
    }
}
```

```php
// src/YourModule/YourEntity/Domain/YourEntityRepository.php
interface YourEntityRepository
{
    public function save(YourEntity $entity): void;
    public function search(YourEntityId $id): ?YourEntity;
    public function findAll(): array;
}
```

### Step 3: Define Application Layer

```php
// src/YourModule/YourEntity/Application/Create/CreateYourEntityCommand.php
class CreateYourEntityCommand implements Command
{
    public function __construct(
        readonly string $id,
        readonly string $name,
        readonly string $status,
    ) {}
}

// src/YourModule/YourEntity/Application/Create/CreateYourEntityCommandHandler.php
class CreateYourEntityCommandHandler implements CommandHandler
{
    public function __construct(private readonly YourEntityRepository $repository) {}

    public function __invoke(CreateYourEntityCommand $command): void {
        $entity = YourEntity::create(
            YourEntityId::from($command->id),
            $command->name,
            Status::from($command->status),
        );
        $this->repository->save($entity);
    }
}
```

### Step 4: Define Infrastructure Layer

```php
// src/YourModule/YourEntity/Infrastructure/Persistence/EloquentYourEntityRepository.php
class EloquentYourEntityRepository implements YourEntityRepository
{
    public function save(YourEntity $entity): void {
        YourEntityModel::updateOrCreate(
            ['id' => $entity->id->value()],
            ['name' => $entity->name, 'status' => $entity->status->value()],
        );
    }

    public function search(YourEntityId $id): ?YourEntity {
        $model = YourEntityModel::find($id->value());
        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(YourEntityModel $model): YourEntity {
        return YourEntity::create(
            YourEntityId::from($model->id),
            $model->name,
            Status::from($model->status),
        );
    }
}

// src/YourModule/YourEntity/Infrastructure/Controller/CreateYourEntityController.php
class CreateYourEntityController extends ApiController
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Post(path: "/{tenant}/{version}/your-entities", tags: ["YourEntity"])]
    public function __invoke(Request $request): JsonResponse {
        $validated = $request->validate([
            'id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $this->bus->dispatch(new CreateYourEntityCommand(
            $validated['id'],
            $validated['name'],
            $validated['status'],
        ));

        return $this->sendResponse(null, 'Entity created successfully', 201);
    }
}
```

### Step 5: Create Eloquent Model

```php
// app/Models/YourEntity.php
class YourEntity extends Model
{
    protected $fillable = ['name', 'status'];

    protected function getTable(): string {
        return config('database.tenant.app_id') . '_your_entities';
    }
}
```

### Step 6: Create Migration

```php
// database/migrations/2026_05_06_000001_create_your_entities_table.php
class CreateYourEntitiesTable extends Migration
{
    public function up(): void {
        Schema::create('{tenant}_your_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->enum('status', ['draft', 'published']);
            $table->timestamps();
        });
    }
}
```

### Step 7: Bind Repository in RepositoryServiceProvider

```php
// app/Providers/RepositoryServiceProvider.php
$this->app->bind(
    \Dbapi\YourModule\YourEntity\Domain\YourEntityRepository::class,
    \Dbapi\YourModule\YourEntity\Infrastructure\Persistence\EloquentYourEntityRepository::class,
);
```

### Step 8: Register Routes

```php
// routes/api.php
Route::middleware('require.module:your_module')->group(function () {
    Route::post('/your-entities', CreateYourEntityController::class);
    Route::get('/your-entities/{id}', FindYourEntityController::class);
});
```

---

## Making an Entity Translatable

See the dedicated `translatable-modules` skill for step-by-step instructions on implementing the intermediate table pattern.

**Quick reference:**
1. Create main table (id, status, timestamps)
2. Create translation table (entity_id, language_code, translatable_fields, SEO fields)
3. Unique constraint: (entity_id, language_code)
4. Update repository to join + hydrate
5. Accept `language_code` parameter in Create/Update commands
6. Add language validation in repositories

---

## Testing

### Unit Tests

Test domain logic in isolation:

```php
// tests/Unit/Blogging/Post/Domain/PostTest.php
class PostTest extends TestCase
{
    public function test_can_create_post(): void {
        $post = Post::create(
            PostId::from('550e8400-e29b-41d4-a716-446655440000'),
            Status::PUBLISHED,
            ['en' => [...]]
        );

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals(Status::PUBLISHED, $post->status());
        $this->assertCount(1, $post->events());
    }
}
```

### Feature Tests

Test API endpoints (integration tests):

```php
// tests/Feature/CreatePostTest.php
class CreatePostTest extends TestCase
{
    public function test_authenticated_user_can_create_post(): void {
        $tenant = Tenant::factory()->create(['app_id' => 'test_blog']);
        $token = $this->actingAs(User::factory()->for($tenant)->create())->token;

        $response = $this->postJson('/test_blog/v1/posts', [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'Hello',
            'language_code' => 'en',
            ...
        ], ['Authorization' => "Bearer $token"]);

        $response->assertStatus(201);
        $this->assertDatabaseHas(
            config('database.tenant.app_id') . '_posts',
            ['id' => '550e8400-e29b-41d4-a716-446655440000']
        );
    }
}
```

### Running Tests

```bash
# All tests
sail artisan test

# Specific file
sail artisan test tests/Feature/CreatePostTest.php

# With coverage (requires Xdebug)
sail artisan test --coverage

# Parallel (faster)
sail artisan test --parallel
```

---

## Debugging with Sail

### View logs in real-time

```bash
sail logs -f
sail logs -f laravel  # Laravel application logs only
```

### Access MySQL from CLI

```bash
sail mysql  # MySQL client
# or
sail shell  # Interactive bash inside container
```

### Tinker (REPL)

```bash
sail artisan tinker

# Inside tinker:
config(['database.tenant.app_id' => 'my_blog']);
$post = \App\Models\BlogPost::first();
$post->translations()->get();
```

### Dump & Die

```php
dd($variable);  // Dump and halt execution
dump($variable);  // Dump, continue
```

---

## Common Commands

### Project Setup

```bash
# Install dependencies
composer install --ignore-platform-reqs

# Build Sail image
sail build --no-cache

# Run migrations
sail artisan migrate

# Publish Passport keys
sail artisan passport:keys
```

### Tenant Management

```bash
# Create tenant
sail artisan dba:tenant:create my_blog --name="My Blog" --modules=blog,pages,languages,forms

# List tenants
sail artisan dba:tenant:list

# Show tenant details
sail artisan dba:tenant:show my_blog

# Enable module on tenant
sail artisan dba:tenant:enable-module my_blog forms

# Disable module
sail artisan dba:tenant:disable-module my_blog forms
```

### API Documentation

```bash
# Regenerate Swagger docs
sail artisan l5-swagger:generate

# View routes
sail artisan route:list
sail artisan route:list --path=posts
```

### Code Generation

```bash
# Clear cached autoload
sail composer dump-autoload

# Cache config
sail artisan config:cache

# Cache routes
sail artisan route:cache
```

### Development

```bash
# Watch for changes (not available by default, use Vite if needed)
npm run dev

# Code quality
./vendor/bin/phpstan analyse src/ --level 9

# Fix code style
./vendor/bin/php-cs-fixer fix src/
```

---

## Contribution Guidelines

### Before Starting

1. **Read the architecture docs:** Review `GUIDE.md` and existing module implementations
2. **Check the skill:** Read relevant skills (dba-ddd-skeleton, translatable-modules, etc.)
3. **Create a feature branch:** `git checkout -b feature/your-feature`

### While Coding

1. **Follow DDD structure:** Domain → Application → Infrastructure (one layer per file, one concern per class)
2. **Write PHPStan Level 9 code:** Use strict types, type hints, declare all properties
3. **Add OpenAPI attributes:** Document endpoints with `#[OA\Get]`, `#[OA\Post]`, etc.
4. **Test your code:** Unit tests for domain logic, feature tests for API endpoints
5. **Keep commits atomic:** One feature per commit; write descriptive messages
6. **Update GUIDE.md:** Add curl examples if adding new endpoints

### Before Submitting

1. **Run tests:** `sail artisan test`
2. **Check code quality:** `./vendor/bin/phpstan analyse src/ --level 9`
3. **Lint code:** `./vendor/bin/php-cs-fixer fix src/`
4. **Regenerate docs:** `sail artisan l5-swagger:generate`
5. **Test in Swagger UI:** Navigate to `http://localhost/api/documentation`

### Common Mistakes to Avoid

- ❌ Mixing layers: Don't put repository calls in controllers; dispatch commands instead
- ❌ Skipping domain events: Always record events in aggregate root factory methods
- ❌ Hardcoding tenant ID: Use `config('database.tenant.app_id')` in models
- ❌ Forgetting module gates: Add `require.module:module_name` middleware to routes
- ❌ Not testing translations: Include `language_code` parameter in tests
- ❌ Missing OpenAPI docs: Controllers need `#[OA\*]` attributes for Swagger generation

---

## Useful Links

- **DDD Skeleton Docs:** See `dba-ddd-skeleton` skill for package-specific guidance
- **Translatable Modules:** See `translatable-modules` skill for multi-language patterns
- **Forms System:** See `forms-system` skill for dynamic forms, anti-spam, async jobs
- **Laravel Docs:** https://laravel.com/docs/13
- **OpenAPI/Swagger:** https://swagger.io/specification/

---

## FAQ

**Q: How do I add a new language to a tenant?**
A: POST to `/{tenant}/v1/languages` with ISO 639-1 code (e.g., "es", "fr"). See GUIDE.md section 7-8 for examples.

**Q: Can a post exist without translations?**
A: No. Posts must have at least one translation (the language specified at creation). However, you can query a post in a different language, which falls back to the first translation if that language's translation doesn't exist.

**Q: How do forms prevent spam?**
A: Two mechanisms: honeypot field (bots fill hidden fields) + rate limiting (5 submissions per 60s per IP per form). See `forms-system` skill.

**Q: Where do I find the database schema?**
A: Migrations in `database/migrations/`. Each tenant's tables created by provisioners on module enable.

**Q: Can I run tests without Docker?**
A: No. Tests require a MySQL database, which Sail provides. However, you can mock repository dependencies in unit tests.

**Q: How is the OpenAPI spec generated?**
A: From PHP attributes (e.g., `#[OA\Post(...)]`) on controller methods. Run `sail artisan l5-swagger:generate` to update `storage/api-docs/openapi.json`.

