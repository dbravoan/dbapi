---
name: translatable-modules
description: >
  Build translatable modules in this Laravel DDD/CQRS multitenant API. Use when creating
  entities that need localized fields (slug, title, name, content, SEO metadata) and you must
  persist them in an intermediate translation table per tenant.
argument-hint: 'Describe the aggregate and translatable fields (e.g. "Page with title, slug, content, seo_title")'
---

# Translatable Modules Pattern (DDD + Multitenancy)

Use this pattern whenever an aggregate has language-dependent fields. The canonical reference implementation is `src/PageManagement/` (pages + page_translations). `src/Blogging/` (posts + post_translations) is also a valid reference.

---

## 1. Table Design

### Main table `{tenant}_{aggregate}s`
Stores only **invariant** fields:

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID PK | |
| `status` | varchar | optional |
| `created_at`, `updated_at` | timestamps | |
| foreign keys | UUID | categories, owners, etc. |

**Never** put `title`, `slug`, `content`, or SEO fields here.

### Translation table `{tenant}_{aggregate}_translations`
Stores all **locale-specific** fields:

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID PK | |
| `{aggregate}_id` | UUID FK | references main table |
| `language_code` | varchar(5) | ISO 639-1 e.g. `en`, `es` |
| `title` | varchar | |
| `slug` | varchar | |
| `content` | longText / JSON | |
| `seo_title` | varchar | |
| `seo_description` | text | |
| `seo_keywords` | varchar | |
| `canonical_url` | varchar | |
| `og_title` | varchar | |
| `og_description` | text | |
| `og_image` | varchar | |
| `structured_data` | JSON | JSON-LD |
| `created_at`, `updated_at` | timestamps | |

**Constraints (critical):**
```sql
UNIQUE KEY (page_id, language_code)    -- one translation per language
UNIQUE KEY (language_code, slug)       -- slug unique per language
```

### Provisioner example
```php
// src/PageManagement/Page/Infrastructure/Module/PageModuleProvisioner.php
public function provision(string $appId): void
{
    $prefix = $appId . '_';

    Schema::create($prefix . 'pages', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('status', 20)->default('draft');
        $table->timestamps();
    });

    Schema::create($prefix . 'page_translations', function (Blueprint $table) use ($prefix) {
        $table->uuid('id')->primary();
        $table->uuid('page_id');
        $table->string('language_code', 5);
        $table->string('title');
        $table->string('slug');
        $table->longText('content')->nullable();
        $table->string('seo_title')->nullable();
        $table->text('seo_description')->nullable();
        $table->string('seo_keywords')->nullable();
        $table->string('canonical_url')->nullable();
        $table->string('og_title')->nullable();
        $table->text('og_description')->nullable();
        $table->string('og_image')->nullable();
        $table->json('structured_data')->nullable();
        $table->timestamps();

        $table->foreign('page_id')->references('id')->on($prefix . 'pages')->cascadeOnDelete();
        $table->unique(['page_id', 'language_code']);
        $table->unique(['language_code', 'slug']);
    });
}
```

---

## 2. Domain Layer

### Translation Value Object
```php
// src/PageManagement/Page/Domain/PageTranslation.php
namespace Dbapi\PageManagement\Page\Domain;

final class PageTranslation
{
    public function __construct(
        public readonly string $languageCode,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $content,
        public readonly ?string $seoTitle,
        public readonly ?string $seoDescription,
        public readonly ?string $canonicalUrl,
        public readonly ?string $ogTitle,
        public readonly ?string $ogDescription,
        public readonly ?string $ogImage,
        public readonly ?array  $structuredData,
    ) {}
}
```

### Aggregate
```php
// src/PageManagement/Page/Domain/Page.php
namespace Dbapi\PageManagement\Page\Domain;

use Dbravoan\DbaCoreDdd\Domain\AggregateRoot;

final class Page extends AggregateRoot
{
    private ?PageTranslation $translation = null;

    public function __construct(
        private readonly PageId $id,
        private string $status,
    ) {}

    public static function create(PageId $id, string $status): self
    {
        return new self($id, $status);
    }

    public function withTranslation(PageTranslation $translation): self
    {
        $clone = clone $this;
        $clone->translation = $translation;
        return $clone;
    }

    public function id(): PageId { return $this->id; }
    public function status(): string { return $this->status; }
    public function translation(): ?PageTranslation { return $this->translation; }
}
```

### Repository Contract
```php
// src/PageManagement/Page/Domain/PageRepository.php
namespace Dbapi\PageManagement\Page\Domain;

interface PageRepository
{
    public function save(Page $page): void;
    public function search(PageId $id, string $languageCode = 'en'): ?Page;
}
```

---

## 3. Application Layer

### Create Command
```php
// src/PageManagement/Page/Application/Create/CreatePageCommand.php
namespace Dbapi\PageManagement\Page\Application\Create;

use Dbravoan\DbaCoreDdd\Application\Command;

final class CreatePageCommand implements Command
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly string $languageCode,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $content,
        public readonly ?string $seoTitle,
        public readonly ?string $seoDescription,
        public readonly ?string $canonicalUrl,
    ) {}
}
```

### Create Handler
```php
// src/PageManagement/Page/Application/Create/CreatePageCommandHandler.php
namespace Dbapi\PageManagement\Page\Application\Create;

use Dbravoan\DbaCoreDdd\Application\CommandHandler;
use Dbapi\PageManagement\Page\Domain\{Page, PageId, PageTranslation, PageRepository};

final class CreatePageCommandHandler implements CommandHandler
{
    public function __construct(private readonly PageRepository $repository) {}

    public function __invoke(CreatePageCommand $command): void
    {
        $page = Page::create(new PageId($command->id), $command->status)
            ->withTranslation(new PageTranslation(
                languageCode:    $command->languageCode,
                title:           $command->title,
                slug:            $command->slug,
                content:         $command->content,
                seoTitle:        $command->seoTitle,
                seoDescription:  $command->seoDescription,
                canonicalUrl:    $command->canonicalUrl,
                ogTitle:         null,
                ogDescription:   null,
                ogImage:         null,
                structuredData:  null,
            ));

        $this->repository->save($page);
    }
}
```

---

## 4. Infrastructure Layer

### Eloquent Models
```php
// app/Models/Page.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('database.tenant.app_id') . '_pages';
    }

    public function translations()
    {
        return $this->hasMany(PageTranslation::class, 'page_id');
    }
}
```

```php
// app/Models/PageTranslation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageTranslation extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('database.tenant.app_id') . '_page_translations';
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}
```

### Repository — Two-table Upsert
```php
// src/PageManagement/Page/Infrastructure/Persistence/EloquentPageRepository.php
namespace Dbapi\PageManagement\Page\Infrastructure\Persistence;

use App\Models\Page as PageModel;
use Dbapi\PageManagement\Page\Domain\{Page, PageId, PageTranslation, PageRepository};
use Ramsey\Uuid\Uuid;

final class EloquentPageRepository implements PageRepository
{
    public function __construct(private readonly PageModel $model) {}

    public function save(Page $page): void
    {
        // 1. Upsert main aggregate row
        $this->model->newQuery()->updateOrCreate(
            ['id' => $page->id()->value()],
            ['status' => $page->status()],
        );

        // 2. Upsert translation row
        $translation = $page->translation();
        if ($translation !== null) {
            \App\Models\PageTranslation::updateOrCreate(
                ['page_id' => $page->id()->value(), 'language_code' => $translation->languageCode],
                [
                    'id'              => Uuid::uuid4()->toString(),
                    'title'           => $translation->title,
                    'slug'            => $translation->slug,
                    'content'         => $translation->content,
                    'seo_title'       => $translation->seoTitle,
                    'seo_description' => $translation->seoDescription,
                    'canonical_url'   => $translation->canonicalUrl,
                    'og_title'        => $translation->ogTitle,
                    'og_description'  => $translation->ogDescription,
                    'og_image'        => $translation->ogImage,
                    'structured_data' => $translation->structuredData
                        ? json_encode($translation->structuredData) : null,
                ]
            );
        }
    }

    public function search(PageId $id, string $languageCode = 'en'): ?Page
    {
        $row = $this->model->newQuery()
            ->with(['translations' => fn($q) => $q->where('language_code', $languageCode)])
            ->find($id->value());

        if ($row === null) return null;

        $t = $row->translations->first();
        $page = Page::create(new PageId($row->id), $row->status);

        if ($t !== null) {
            $page = $page->withTranslation(new PageTranslation(
                languageCode:   $t->language_code,
                title:          $t->title,
                slug:           $t->slug,
                content:        $t->content,
                seoTitle:       $t->seo_title,
                seoDescription: $t->seo_description,
                canonicalUrl:   $t->canonical_url,
                ogTitle:        $t->og_title,
                ogDescription:  $t->og_description,
                ogImage:        $t->og_image,
                structuredData: $t->structured_data ? json_decode($t->structured_data, true) : null,
            ));
        }

        return $page;
    }
}
```

---

## 5. Controller (Infrastructure)

```php
// src/PageManagement/Page/Infrastructure/Controller/CreatePageController.php
use OpenApi\Attributes as OA;

#[OA\Post(
    path: "/{tenant}/{version}/pages",
    summary: "Create a page with translation",
    tags: ["Pages"],
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(required: true,
        content: new OA\JsonContent(
            required: ["id", "language_code", "title", "slug"],
            properties: [
                new OA\Property(property: "id",            type: "string", format: "uuid"),
                new OA\Property(property: "status",        type: "string", example: "draft"),
                new OA\Property(property: "language_code", type: "string", example: "en"),
                new OA\Property(property: "title",         type: "string"),
                new OA\Property(property: "slug",          type: "string"),
                new OA\Property(property: "content",       type: "string", description: "JSON block editor content"),
                new OA\Property(property: "seo_title",     type: "string"),
            ]
        )
    ),
    responses: [new OA\Response(response: 201, description: "Page created")]
)]
public function __invoke(Request $request): JsonResponse
{
    $validated = $request->validate([
        'id'            => 'required|uuid',
        'status'        => 'sometimes|string',
        'language_code' => 'required|string|size:2',
        'title'         => 'required|string',
        'slug'          => 'required|string',
        'content'       => 'nullable|string',
        'seo_title'     => 'nullable|string',
        'seo_description' => 'nullable|string',
        'canonical_url' => 'nullable|url',
    ]);

    $this->bus->dispatch(new CreatePageCommand(
        id:            $validated['id'],
        status:        $validated['status'] ?? 'draft',
        languageCode:  $validated['language_code'],
        title:         $validated['title'],
        slug:          $validated['slug'],
        content:       $validated['content'] ?? null,
        seoTitle:      $validated['seo_title'] ?? null,
        seoDescription:$validated['seo_description'] ?? null,
        canonicalUrl:  $validated['canonical_url'] ?? null,
    ));

    return $this->sendResponse(null, 'Page created successfully', 201);
}
```

---

## 6. Provider Bindings

### RepositoryServiceProvider
```php
use Dbapi\PageManagement\Page\Domain\PageRepository;
use Dbapi\PageManagement\Page\Infrastructure\Persistence\EloquentPageRepository;

$this->app->bind(PageRepository::class, function ($app) {
    return new EloquentPageRepository(new \App\Models\Page());
});
```

### DomainServiceProvider
```php
use Dbapi\PageManagement\Page\Application\Create\CreatePageCommand;
use Dbapi\PageManagement\Page\Application\Create\CreatePageCommandHandler;
use Dbapi\PageManagement\Page\Application\Find\FindPageQuery;
use Dbapi\PageManagement\Page\Application\Find\FindPageQueryHandler;

// In registerCommandHandlers():
CreatePageCommand::class => CreatePageCommandHandler::class,

// In registerQueryHandlers():
FindPageQuery::class => FindPageQueryHandler::class,
```

---

## 7. API Design Conventions

| Operation | Endpoint | Notes |
|-----------|----------|-------|
| Create | `POST /{tenant}/{version}/{aggregates}` | `language_code` required |
| Update translation | `PUT /{tenant}/{version}/{aggregates}/{id}` | Upserts translation |
| Find | `GET /{tenant}/{version}/{aggregates}/{id}?lang=es` | `lang` param with fallback |
| List | `GET /{tenant}/{version}/{aggregates}?lang=es` | Filter by language |

---

## 8. Checklist

- [ ] Main table `{tenant}_{aggregate}s` created in provisioner
- [ ] Translation table `{tenant}_{aggregate}_translations` created in provisioner
- [ ] `UNIQUE (aggregate_id, language_code)` constraint
- [ ] `UNIQUE (language_code, slug)` constraint (when slug exists)
- [ ] Domain `{Aggregate}Translation` value object created
- [ ] Aggregate stores `translation` field and `withTranslation()` method
- [ ] `PageRepository::search()` accepts `$languageCode`
- [ ] Repository `save()` upserts both tables
- [ ] Eloquent models override `getTable()` for tenant prefix
- [ ] `RepositoryServiceProvider` binds interface → implementation
- [ ] `DomainServiceProvider` registers command/query handlers
- [ ] Controller validates `language_code` in request
- [ ] Routes registered in `routes/api.php` with correct middleware
- [ ] Provisioner called in `CreateTenantCommand` and `EnableModuleCommand`
- [ ] OpenAPI annotations added with `language_code` documented
- [ ] SEO fields stored in translation table, not main table
