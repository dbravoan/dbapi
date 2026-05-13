# Conventions — style, naming, layout

> These rules are enforced by the `reviewer` subagent. If you break one of
> them on purpose, explain why in your impl report and add a test that
> protects the exception.

---

## 1. PHP basics

- `declare(strict_types=1);` at the top of **every** PHP file under `src/` and `app/`.
- Classes are `final` unless there is a documented inheritance need. Value
  objects and DTOs are `final readonly`.
- Type-hint everything: properties, parameters, returns. No `mixed` unless
  it is genuinely unconstrained (e.g. domain event payloads passed around).
- Constructor property promotion is preferred for DTOs and VOs.
- Indentation: 4 spaces. No tabs. Trailing newline on every file.

## 2. Namespaces

| Path | Namespace |
|---|---|
| `src/` | `Dbapi\` |
| `app/` | `App\` |
| `tests/` | `Tests\` |
| `vendor/dbravoan/dba-ddd-skeleton/src/` | `Dba\DddSkeleton\` |

**Never** write `Dbravoan\DbaSkeletonDdd\…` — that string is obsolete and not
autoloaded by composer.

## 3. File layout per aggregate

```
src/{Context}/{Aggregate}/
├── Domain/
│   ├── {Aggregate}.php                       (AggregateRoot)
│   ├── {Aggregate}Id.php                     (Uuid VO)
│   ├── {Aggregate}{Prop}.php                 (other VOs)
│   ├── {Aggregate}CreatedDomainEvent.php
│   ├── {Aggregate}{ChangeName}DomainEvent.php
│   └── {Aggregate}Repository.php             (interface)
├── Application/
│   ├── Create/   Create{Aggregate}Command.php + Handler
│   ├── Update/   Update{Aggregate}Command.php + Handler
│   ├── Delete/   Delete{Aggregate}Command.php + Handler   (when present)
│   ├── Find/     Find{Aggregate}Query.php   + Handler
│   ├── SearchByCriteria/                                   (when present)
│   │              Search{Aggregates}ByCriteriaQuery.php + Handler
│   └── Response/
│        ├── {Aggregate}Response.php          (single)
│        └── {Aggregates}Response.php         (collection — correct English plural!)
├── Infrastructure/
│   ├── Controller/
│   │    ├── Create{Aggregate}Controller.php
│   │    ├── Update{Aggregate}Controller.php
│   │    ├── Delete{Aggregate}Controller.php
│   │    ├── Find{Aggregate}Controller.php
│   │    └── Search{Aggregates}ByCriteriaController.php
│   └── Persistence/
│        └── Eloquent{Aggregate}Repository.php
└── Tests/
    └── Domain/{Aggregate}Test.php
```

### Plural correctness

The English plural goes on the **outer name**:
- `PostsResponse` (✅), `CategoriesResponse` (✅), `TagsResponse` (✅),
  `UsersResponse` (✅).
- ~`CategorysResponse`~ (❌ — current debt, tracked as feature 4).

For "Search by criteria", the plural goes inside the class name as well:
`SearchPostsByCriteriaQueryHandler`, `SearchCategoriesByCriteriaQueryHandler`.

## 4. Aggregate contract

```php
final class Post extends AggregateRoot
{
    private function __construct( /* private constructor */ ) {}

    public static function create(/* primitives */): self
    {
        $post = new self(/* VOs */);
        $post->record(new PostCreatedDomainEvent(/* payload */));
        return $post;
    }

    public function toPrimitives(): array { /* flat scalar array */ }
    public static function fromPrimitives(array $data): self { /* … */ }

    // Getters return VOs, not primitives.
    public function id(): PostId { return $this->id; }
}
```

## 5. Controller contract

```php
final class CreatePostController extends ApiController
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Post(path: "/{tenant}/{version}/posts", /* … */)]
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([ /* rules */ ]);

        $this->bus->dispatch(new CreatePostCommand(/* … */));

        return $this->sendResponse([], 'Post created successfully', 201);
    }
}
```

- Always invokable (`__invoke`).
- Always inject `CommandBus` or `QueryBus`, never a repository or a handler.
- Always validate the `Request` inline (or via a FormRequest) — no
  validation in handlers.
- Always return via `sendResponse(...)` / `sendError(...)` from the base
  `ApiController`. The response envelope (`success`, `data`, `message`) is
  part of the public API contract.

## 6. Routes (`routes/api.php`)

- All routes live under `Route::prefix('{tenant}/{version}')` with the
  `identify_tenant → api.version → tenant` middleware pipeline.
- Group writes inside an `auth:api` middleware group.
- Group module-specific endpoints inside `require.module:<key>`.
- Read endpoints are typically open (no `auth:api`); writes are always
  authenticated. Submissions endpoints (e.g. form submit) are open by design.

## 7. Tests

- **Domain unit tests** live at `src/{Ctx}/{Agg}/Tests/Domain/*Test.php`.
  They test the aggregate / VOs in isolation. No Laravel, no DB.
- **Feature tests** live at `tests/Feature/*Test.php`. They mock
  `CommandBus`, `QueryBus`, and `TenantResolverInterface`. They never hit
  the real database. See `tests/Feature/TaskRouteTest.php` as the reference
  template.
- Test methods use snake_case (`test_find_post_returns_404_when_not_found`).
- Use Mockery (`\Mockery::mock(...)`); the package ships it.

## 8. Eloquent models (`app/Models/`)

```php
final class BlogPost extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';
    protected $fillable  = ['id', 'name', 'slug', /* … */];

    public function getTable(): string
    {
        $appId = config('database.tenant.app_id');
        return $appId ? "{$appId}_posts" : 'posts';
    }
}
```

- `getTable()` is mandatory for tenant-scoped tables.
- No business methods on the model; Eloquent here is a thin persistence layer.
- Relationships live on the model when they help repositories hydrate
  aggregates, not for external use.

## 9. OpenAPI / Swagger

- Every controller has matching `#[OA\…]` attributes.
- Parameters include `{tenant}` and `{version}` examples.
- Request bodies declare `required` fields and types.
- At least three responses: 2xx (success), 401/422 (auth/validation), 404
  (read controllers).

## 10. No debug residue

- No `dd()`, `dump()`, `var_dump()`, raw `echo`, or `Log::debug` in `src/` or `app/`.
- No commented-out blocks of code.
- No TODOs without a `// TODO(feature-N): …` reference to a feature in
  `feature_list.json`.

## 11. Composer

- Don't add a runtime dependency unless explicitly approved. Dev
  dependencies are fine when motivated in the impl report.
- After any dependency change, run `composer dump-autoload` in your impl
  report's terminal block.

## 12. Commits & branches

This is enforced outside the harness, but as a courtesy:
- One feature per branch (e.g. `feature/4-rename-categorys-response`).
- One conceptual change per commit.
- Commit messages: imperative present (`Rename CategorysResponse to CategoriesResponse`).
