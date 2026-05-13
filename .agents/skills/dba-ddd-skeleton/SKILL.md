---
name: dba-ddd-skeleton
description: >
  Expert guide for developing features in this Laravel DDD/CQRS multitenant API that uses the
  dbravoan/dba-ddd-skeleton package. Use when: creating a new aggregate, adding commands/queries,
  implementing repository interfaces, writing domain events, registering handlers, binding repositories,
  extending ValueObjects, working with Criteria pattern, adding controllers, setting up module provisioning,
  or understanding the Domain/Application/Infrastructure layer structure in src/.
argument-hint: 'Describe the feature or aggregate to build (e.g. "Create Product aggregate in Inventory domain")'
---

# dba-ddd-skeleton — Architecture & Development Guide

This project is a **Laravel 13 + DDD/CQRS multitenant REST API** built on top of
`dbravoan/dba-ddd-skeleton`. Every feature lives in `src/` under a `Domain/Context/Aggregate`
folder tree with three strict layers: **Domain → Application → Infrastructure**.

---

## Quick-Reference Card

| Concern | Location | Base class / Interface |
|---|---|---|
| Entity / Aggregate Root | `src/{Domain}/{Agg}/Domain/{Agg}.php` | `AggregateRoot` |
| Value Object — string | `src/{Domain}/{Agg}/Domain/{Agg}Prop.php` | `StringValueObject` |
| Value Object — UUID | `src/{Domain}/{Agg}/Domain/{Agg}Id.php` | `Uuid` |
| Value Object — enum-like | `src/{Domain}/{Agg}/Domain/{Agg}Status.php` | `StringValueObject` (custom ALLOWED const) |
| Domain Event | `src/{Domain}/{Agg}/Domain/{Agg}CreatedDomainEvent.php` | `DomainEvent` |
| Repository interface | `src/{Domain}/{Agg}/Domain/{Agg}Repository.php` | plain `interface` |
| Command DTO | `src/{Domain}/{Agg}/Application/Create/Create{Agg}Command.php` | `implements Command` |
| Command Handler | `src/{Domain}/{Agg}/Application/Create/Create{Agg}CommandHandler.php` | `implements CommandHandler` |
| Query DTO | `src/{Domain}/{Agg}/Application/Find/Find{Agg}Query.php` | `implements Query` |
| Query Handler | `src/{Domain}/{Agg}/Application/Find/Find{Agg}QueryHandler.php` | `implements QueryHandler` |
| Response DTO | `src/{Domain}/{Agg}/Application/Response/{Agg}Response.php` | `implements Response` |
| Eloquent Repository | `src/{Domain}/{Agg}/Infrastructure/Persistence/Eloquent{Agg}Repository.php` | `extends EloquentRepository implements {Agg}Repository` |
| Controller (write) | `src/{Domain}/{Agg}/Infrastructure/Controller/Create{Agg}Controller.php` | `extends ApiController` (invokable) |
| Controller (read) | `src/{Domain}/{Agg}/Infrastructure/Controller/Find{Agg}Controller.php` | `extends ApiController` (invokable) |
| Eloquent Model | `app/Models/{Domain}{Agg}.php` | `extends Model` |
| Repository binding | `app/Providers/RepositoryServiceProvider.php` | `$this->app->bind(...)` |
| Handler registration | `app/Providers/DomainServiceProvider.php` | `$this->commandHandlers / $this->queryHandlers` |
| Routes | `routes/api.php` | `/{tenant}/v1/{resource}` |

---

## 1. Domain Layer

### 1.1 Entity (AggregateRoot)

```php
// src/Blogging/Post/Domain/Post.php
final class Post extends AggregateRoot
{
    private function __construct(
        private PostId       $id,
        private PostName     $name,
        private PostSlug     $slug,
        private PostContent  $content,
        private PostLanguage $language,
        private PostCategory $categoryId,
    ) {}

    /** Factory — also records the domain event */
    public static function create(
        string $id, string $name, string $slug,
        string $content, string $language, string $categoryId,
    ): self {
        $post = new self(
            new PostId($id),
            new PostName($name),
            new PostSlug($slug),
            new PostContent($content),
            new PostLanguage($language),
            new PostCategory($categoryId),
        );
        $post->record(new PostCreatedDomainEvent($id, $name, $slug, $content, $language, $categoryId));
        return $post;
    }

    // Serialization contract used by Eloquent repositories
    public function toPrimitives(): array
    {
        return [
            'id'          => $this->id->value(),
            'name'        => $this->name->value(),
            'slug'        => $this->slug->value(),
            'content'     => $this->content->value(),
            'language'    => $this->language->value(),
            'category_id' => $this->categoryId->value(),
        ];
    }

    public static function fromPrimitives(array $data): self { /* ... */ }

    // Getters
    public function id(): PostId        { return $this->id; }
    public function name(): PostName    { return $this->name; }
    // ...
}
```

**Rules**:
- Always `final` and `readonly`-friendly.
- Constructor is `private`; use named factory methods (`create`, `fromPrimitives`).
- Record domain events inside `create()` using `$this->record(new ...DomainEvent(...))`.
- `toPrimitives()` returns a flat array of scalar values — what goes into the DB.
- `fromPrimitives()` reconstructs the aggregate from that same array.

### 1.2 Value Objects

```php
// UUID identity
final readonly class PostId extends Uuid {}

// String VO (immutable, type-safe wrapper)
final readonly class PostName extends StringValueObject {}

// Enum-like VO with allowed values
final readonly class TaskStatus extends StringValueObject
{
    private const ALLOWED = ['pending', 'in_progress', 'done'];

    public function __construct(string $value)
    {
        Assert::oneOf($value, self::ALLOWED);
        parent::__construct($value);
    }

    public static function pending(): self    { return new self('pending'); }
    public static function inProgress(): self { return new self('in_progress'); }
    public static function done(): self       { return new self('done'); }
}
```

All VOs extend one of: `StringValueObject`, `Uuid`, `IntValueObject`, `FloatValueObject`,
`BoolValueObject`, `DateTimeValueObject`, `EmailValueObject`, `UrlValueObject`, `MoneyValueObject`.

Every VO is `final readonly` and exposes `.value()`.

### 1.3 Domain Events

```php
final readonly class PostCreatedDomainEvent extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public readonly string $name,
        public readonly string $slug,
        // ...additional payload...
        ?string $eventId    = null,
        ?string $occurredOn = null,
    ) {
        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function eventName(): string { return 'post.created'; }

    public static function fromPrimitives(
        string $aggregateId, array $body,
        string $eventId, string $occurredOn,
    ): self {
        return new self($aggregateId, $body['name'], $body['slug'], ..., $eventId, $occurredOn);
    }

    public function toPrimitives(): array
    {
        return ['name' => $this->name, 'slug' => $this->slug, /* ... */];
    }
}
```

### 1.4 Repository Interface

```php
interface PostRepository
{
    public function save(Post $post): void;
    public function remove(PostId $id): void;
    public function search(PostId $id): ?Post;
    public function searchByCriteria(Criteria $criteria): array;
    public function countByCriteria(Criteria $criteria): int;
}
```

---

## 2. Application Layer

### 2.1 Commands & Handlers (CQRS — write side)

```php
// Command — plain DTO, no logic
final readonly class CreatePostCommand implements Command
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $content,
        public readonly string $language,
        public readonly string $categoryId,
    ) {}
}

// Handler — orchestrates domain objects, dispatches via CommandBus
final class CreatePostCommandHandler implements CommandHandler
{
    public function __construct(private readonly PostRepository $repository) {}

    public function __invoke(CreatePostCommand $command): void
    {
        $post = Post::create(
            $command->id, $command->name,
            $command->content, $command->language, $command->categoryId,
        );
        $this->repository->save($post);
    }
}
```

**Handler always receives its matching Command as the single argument to `__invoke()`.**

### 2.2 Queries & Handlers (CQRS — read side)

```php
final readonly class FindPostQuery implements Query
{
    public function __construct(public readonly string $id) {}
}

final class FindPostQueryHandler implements QueryHandler
{
    public function __construct(private readonly PostRepository $repository) {}

    public function __invoke(FindPostQuery $query): ?PostResponse
    {
        $post = $this->repository->search(new PostId($query->id));
        return $post ? PostResponse::fromAggregate($post) : null;
    }
}
```

### 2.3 Response DTOs

```php
final readonly class PostResponse implements Response
{
    private function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $content,
        public readonly string $language,
        public readonly string $categoryId,
    ) {}

    public static function fromAggregate(Post $post): self
    {
        return new self(
            $post->id()->value(),
            $post->name()->value(),
            $post->slug()->value(),
            $post->content()->value(),
            $post->language()->value(),
            $post->categoryId()->value(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'content'     => $this->content,
            'language'    => $this->language,
            'category_id' => $this->categoryId,
        ];
    }
}
```

---

## 3. Infrastructure Layer

### 3.1 Eloquent Repository

```php
final class EloquentPostRepository extends EloquentRepository implements PostRepository
{
    public function __construct(BlogPost $model, EventBus $eventBus)
    {
        parent::__construct($model, $eventBus);
    }

    public function save(Post $post): void
    {
        DB::transaction(function () use ($post) {
            $this->model->updateOrCreate(
                ['id' => $post->id()->value()],
                $post->toPrimitives(),
            );
        });
        $this->publishEvents($post); // dispatches recorded DomainEvents
    }

    public function remove(PostId $id): void
    {
        $this->model->where('id', $id->value())->delete();
    }

    public function search(PostId $id): ?Post
    {
        $record = $this->find($id->value());
        return $record ? Post::fromPrimitives($record->toArray()) : null;
    }

    public function searchByCriteria(Criteria $criteria): array
    {
        $query = EloquentCriteriaConverter::convert($criteria, $this->criteriaToEloquentFields());
        return array_map(
            fn ($row) => Post::fromPrimitives($row),
            $query->get()->toArray(),
        );
    }

    public function countByCriteria(Criteria $criteria): int
    {
        return EloquentCriteriaConverter::convert($criteria, $this->criteriaToEloquentFields())->count();
    }

    private function criteriaToEloquentFields(): array
    {
        return ['id' => 'id', 'name' => 'name', 'language' => 'language'];
    }
}
```

### 3.2 Controllers (Invokable)

Controllers dispatch to the bus and never contain business logic.

```php
// Write controller
final class CreatePostController extends ApiController
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Post(path: '/{tenant}/v1/posts', ...)]
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id'          => ['required', 'uuid'],
            'name'        => ['required', 'string', 'max:255'],
            'content'     => ['required', 'string'],
            'language'    => ['required', 'in:en,es,fr'],
            'category_id' => ['required', 'uuid'],
        ]);

        $this->bus->dispatch(new CreatePostCommand(
            $validated['id'],
            $validated['name'],
            $validated['content'],
            $validated['language'],
            $validated['category_id'],
        ));

        return $this->sendResponse([], 'Post created successfully', 201);
    }
}

// Read controller
final class FindPostController extends ApiController
{
    public function __construct(private readonly QueryBus $bus) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        /** @var PostResponse|null $response */
        $response = $this->bus->ask(new FindPostQuery($id));

        return null === $response
            ? $this->sendError('Post not found', [], 404)
            : $this->sendResponse($response->toArray());
    }
}
```

`sendResponse($data, $message = 'success', $code = 200)` and
`sendError($message, $errors = [], $code = 400)` come from `ApiController`.

---

## 4. Eloquent Model

```php
// app/Models/BlogPost.php
final class BlogPost extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = ['id', 'name', 'slug', 'content', 'language', 'category_id'];

    // Multi-tenancy: prefix table with current tenant's app_id
    public function getTable(): string
    {
        $appId = config('database.tenant.app_id');
        return $appId ? "{$appId}_posts" : 'posts';
    }
}
```

---

## 5. Wiring (Providers)

### 5.1 RepositoryServiceProvider

Every `Interface → Implementation` binding lives here:

```php
// app/Providers/RepositoryServiceProvider.php
$this->app->bind(PostRepository::class, function ($app) {
    return new EloquentPostRepository(
        new BlogPost(),
        $app->make(EventBus::class),
    );
});
```

### 5.2 DomainServiceProvider

All Handlers must be registered here so the buses can discover them:

```php
// app/Providers/DomainServiceProvider.php
private array $commandHandlers = [
    CreatePostCommandHandler::class,
    UpdatePostCommandHandler::class,
    // ...
];

private array $queryHandlers = [
    FindPostQueryHandler::class,
    SearchPostsByCriteriaQueryHandler::class,
    // ...
];
```

The provider tags each handler with `dba_ddd.command_handler` / `dba_ddd.query_handler`
so `LaravelCommandBus` and `LaravelQueryBus` can resolve them automatically.

---

## 6. Routes

Pattern: `/{tenant}/{version}/{resource}/{id?}`

```php
// routes/api.php
Route::prefix('{tenant}/v1')->middleware(['identify_tenant', 'api.version', 'tenant'])->group(function () {

    // Public reads
    Route::get('/posts/{id}', FindPostController::class);

    // Authenticated writes — optionally gated by module
    Route::middleware(['require.module:blog', 'auth:api'])->group(function () {
        Route::post('/posts',        CreatePostController::class);
        Route::put('/posts/{id}',    UpdatePostController::class);
        Route::delete('/posts/{id}', DeletePostController::class);
    });
});
```

---

## 7. Multi-Tenancy

- URL segment `{tenant}` is the `app_id` (e.g., `acme`).
- `IdentifyTenant` middleware calls `TenantResolver::resolve($appId)`, sets
  `config(['database.tenant.app_id' => $appId])`.
- Eloquent models use `getTable()` to prefix every table: `acme_posts`, `acme_tasks`, etc.
- The `tenants` table stores `enabled_modules` (JSON) for module gating.

---

## 8. Criteria Pattern

Use `Criteria` for filtering, sorting and pagination in query handlers:

```php
// Build from HTTP request (Infrastructure)
$criteria = (new RequestCriteriaBuilder(['name', 'language', 'category_id']))
    ->build($request);

// In the repository implementation
$eloquentQuery = EloquentCriteriaConverter::convert(
    $criteria,
    ['name' => 'name', 'language' => 'language', 'category_id' => 'category_id'],
);
$records = $eloquentQuery->get()->toArray();
```

Supported `FilterOperator` values: `=`, `!=`, `>`, `<`, `>=`, `<=`,
`IN`, `NOT_IN`, `CONTAINS`, `NOT_CONTAINS`, `STARTS_WITH`, `ENDS_WITH`,
`IS_NULL`, `IS_NOT_NULL`, `BETWEEN`, `NOT_BETWEEN`.

---

## 9. Scaffold with Artisan

Generate a complete aggregate skeleton in one command:

```bash
./vendor/bin/sail artisan dba:make:module Inventory Product
# Options:
#   --no-finder      skip Find use case
#   --no-creator     skip Create use case
#   --no-updater     skip Update use case
#   --no-searcher    skip SearchByCriteria use case
#   --no-deleter     skip Delete use case
#   --application-service  generate an Application Service instead of handlers
```

After scaffolding:
1. Fill in the generated stubs (Entity properties, VOs, handler logic).
2. Add Eloquent Model to `app/Models/`.
3. Add binding in `RepositoryServiceProvider`.
4. Register handlers in `DomainServiceProvider`.
5. Add routes to `routes/api.php`.
6. Create migration (`database/migrations/`).

---

## 10. Testing Patterns

```php
// Unit — test the aggregate / handler in isolation
class CreatePostCommandHandlerTest extends TestCase
{
    public function test_it_creates_a_post(): void
    {
        $repository = \Mockery::mock(PostRepository::class);
        $repository->shouldReceive('save')->once();

        $handler = new CreatePostCommandHandler($repository);
        $handler(new CreatePostCommand(
            Uuid::random()->value(), 'My Post', 'Content', 'en', Uuid::random()->value(),
        ));
    }
}

// Feature — test the full HTTP → controller → bus → handler pipeline
class PostRouteTest extends TestCase
{
    public function test_find_post_returns_200(): void
    {
        $this->mockTenantResolver($this->makeTenant());

        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('ask')->andReturn(PostResponse::fromAggregate(/* ... */));
        $this->app->instance(QueryBus::class, $queryBus);

        $this->getJson('/acme/v1/posts/' . $this->postId)
             ->assertStatus(200)
             ->assertJsonStructure(['data' => ['id', 'name', 'slug']]);
    }
}
```

---

## 11. Namespace Map

| Path | PHP Namespace |
|---|---|
| `src/` | `Dbapi\` |
| `src/Blogging/Post/Domain/` | `Dbapi\Blogging\Post\Domain` |
| `src/Blogging/Post/Application/Create/` | `Dbapi\Blogging\Post\Application\Create` |
| `src/Blogging/Post/Infrastructure/Controller/` | `Dbapi\Blogging\Post\Infrastructure\Controller` |
| `src/Identity/User/Domain/` | `Dbapi\Identity\User\Domain` |
| `src/TodoList/Task/Domain/` | `Dbapi\TodoList\Task\Domain` |
| `src/Shared/Infrastructure/` | `Dbapi\Shared\Infrastructure` |
| `app/` | `App\` |
| `app/Models/` | `App\Models` |
| `app/Providers/` | `App\Providers` |
| Package Domain | `Dba\DddSkeleton\Shared\Domain` |
| Package Infrastructure | `Dba\DddSkeleton\Shared\Infrastructure` |

---

## 12. Checklist: Adding a New Aggregate

- [ ] `src/{Domain}/{Agg}/Domain/{Agg}.php` — Entity (extends `AggregateRoot`)
- [ ] `src/{Domain}/{Agg}/Domain/{Agg}Id.php` — UUID VO (extends `Uuid`)
- [ ] `src/{Domain}/{Agg}/Domain/{Agg}{Prop}.php` — other VOs
- [ ] `src/{Domain}/{Agg}/Domain/{Agg}CreatedDomainEvent.php`
- [ ] `src/{Domain}/{Agg}/Domain/{Agg}Repository.php` — interface
- [ ] `src/{Domain}/{Agg}/Application/Create/Create{Agg}Command.php`
- [ ] `src/{Domain}/{Agg}/Application/Create/Create{Agg}CommandHandler.php`
- [ ] `src/{Domain}/{Agg}/Application/Find/Find{Agg}Query.php`
- [ ] `src/{Domain}/{Agg}/Application/Find/Find{Agg}QueryHandler.php`
- [ ] `src/{Domain}/{Agg}/Application/Response/{Agg}Response.php`
- [ ] `src/{Domain}/{Agg}/Infrastructure/Persistence/Eloquent{Agg}Repository.php`
- [ ] `src/{Domain}/{Agg}/Infrastructure/Controller/Create{Agg}Controller.php`
- [ ] `src/{Domain}/{Agg}/Infrastructure/Controller/Find{Agg}Controller.php`
- [ ] `app/Models/{Domain}{Agg}.php` — Eloquent model with dynamic table + `getTable()`
- [ ] `database/migrations/...create_{tenant}_{agg}s_table.php`
- [ ] `app/Providers/RepositoryServiceProvider.php` — add `bind()`
- [ ] `app/Providers/DomainServiceProvider.php` — register handlers
- [ ] `routes/api.php` — add routes with tenant prefix + middleware
