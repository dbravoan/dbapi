# Bug audit — 2026-05-12

## Summary
- Files inspected: ~145 (every PHP file under `src/`, `app/Models/`, `app/Providers/`, `app/Http/Middleware/`, `app/Jobs/`, `routes/api.php`, `tests/`, plus configuration: `composer.json`, `phpunit.xml`).
- Bugs found: 45 (critical: 11, high: 13, medium: 13, low: 8)

> Read-only audit. No code has been modified. Each finding contains an evidence
> snippet pulled directly from the file at the line referenced.

## Findings

### #1 — CRITICAL Broken import — `CategorysByCriteriaSearcher` does not exist
- **Category:** A / B
- **Files:**
  - `src/Blogging/Category/Application/SearchByCriteria/SearchCategorysByCriteriaQueryHandler.php:17` — typehints a class that has no namespace import and no definition anywhere in the repo.
- **Evidence:**
  ```php
  final class SearchCategorysByCriteriaQueryHandler implements QueryHandler
  {
      public function __construct(
          private readonly CategorysByCriteriaSearcher $searcher
      ) {}
  ```
  `grep -r "class CategorysByCriteriaSearcher"` → 0 hits.
- **Why it's a bug:** the handler cannot be instantiated; container resolution will throw `Error: class "CategorysByCriteriaSearcher" not found`. Any HTTP hit on the (unwired) search endpoint would 500 immediately. The handler is also not registered in `DomainServiceProvider`, so the bug is hidden today, but the class file itself is unloadable.
- **Proposed fix:** either remove the handler (and its query/controller) or implement `CategorysByCriteriaSearcher` (a thin domain service that delegates to `CategoryRepository::searchByCriteria()`).
- **Risk if unfixed:** crash when the controller/route is wired.

### #2 — CRITICAL Broken import — `PostsByCriteriaSearcher` does not exist
- **Category:** A / B
- **Files:**
  - `src/Blogging/Post/Application/SearchByCriteria/SearchPostsByCriteriaQueryHandler.php:17`
- **Evidence:**
  ```php
  public function __construct(
      private readonly PostsByCriteriaSearcher $searcher
  ) {}
  ```
  No `PostsByCriteriaSearcher` class file exists.
- **Why it's a bug:** identical issue to #1 — class is unresolvable.
- **Proposed fix:** introduce `PostsByCriteriaSearcher` (or inline call to `PostRepository::searchByCriteria()`).
- **Risk if unfixed:** crash on resolution.

### #3 — CRITICAL Broken import — `TagsByCriteriaSearcher` does not exist
- **Category:** A / B
- **Files:**
  - `src/Blogging/Tag/Application/SearchByCriteria/SearchTagsByCriteriaQueryHandler.php:17`
- **Evidence:**
  ```php
  public function __construct(
      private readonly TagsByCriteriaSearcher $searcher
  ) {}
  ```
- **Why it's a bug:** same as #1/#2 for Tag.
- **Proposed fix:** create `TagsByCriteriaSearcher` or inline.
- **Risk if unfixed:** crash on resolution.

### #4 — CRITICAL Broken import — `UsersByCriteriaSearcher` does not exist
- **Category:** A / B
- **Files:**
  - `src/Identity/User/Application/SearchByCriteria/SearchUsersByCriteriaQueryHandler.php:17`
- **Evidence:**
  ```php
  public function __construct(
      private readonly UsersByCriteriaSearcher $searcher
  ) {}
  ```
- **Why it's a bug:** identical pattern, fourth aggregate affected.
- **Proposed fix:** create `UsersByCriteriaSearcher` or inline.
- **Risk if unfixed:** crash on resolution.

### #5 — CRITICAL Broken imports — `CountXyzByCriteriaQuery` classes do not exist
- **Category:** A / B
- **Files:**
  - `src/Blogging/Category/Infrastructure/Controller/SearchCategorysByCriteriaController.php:8,40,48`
  - `src/Blogging/Post/Infrastructure/Controller/SearchPostsByCriteriaController.php:8,40,48`
  - `src/Blogging/Tag/Infrastructure/Controller/SearchTagsByCriteriaController.php:8,40,48`
  - `src/Identity/User/Infrastructure/Controller/SearchUsersByCriteriaController.php:8,40,48`
- **Evidence:**
  ```php
  use Dbapi\Blogging\Category\Application\SearchByCriteria\CountCategorysByCriteriaQuery;
  ...
  $filteredRecords = $this->bus->ask(new CountCategorysByCriteriaQuery(...))->count();
  ```
  `grep -r "class CountCategorysByCriteriaQuery"` → 0 hits (same for Post, Tag, User).
- **Why it's a bug:** controllers instantiate non-existent classes — guaranteed fatal error when their (unwired) route is invoked, plus broken autoload reflection in tools.
- **Proposed fix:** create the four `CountXyzByCriteriaQuery` DTOs (and matching handlers) or remove the meta-pagination code.
- **Risk if unfixed:** crash.

### #6 — CRITICAL Broken imports — `DeleteXyzCommand` classes do not exist
- **Category:** A / B
- **Files:**
  - `src/Blogging/Category/Infrastructure/Controller/DeleteCategoryController.php:7,18` — `Dbapi\Blogging\Category\Application\Delete\DeleteCategoryCommand`
  - `src/Blogging/Post/Infrastructure/Controller/DeletePostController.php:7,18`
  - `src/Blogging/Tag/Infrastructure/Controller/DeleteTagController.php:7,18`
  - `src/Identity/User/Infrastructure/Controller/DeleteUserController.php:7,18`
- **Evidence:**
  ```php
  use Dbapi\Blogging\Category\Application\Delete\DeleteCategoryCommand;
  ...
  $this->bus->dispatch(new DeleteCategoryCommand($id));
  ```
  No `Application/Delete/` folder exists in any of the four aggregates (confirmed by `find src -type d -name Delete`).
- **Why it's a bug:** controllers cannot be loaded — Class Not Found at autoload time. None of them is wired into `routes/api.php`, but the files are still parsed by PHPUnit's test discovery (`./src` is in the Unit suite) and by `composer dump-autoload --optimize` warnings.
- **Proposed fix:** either implement the full Delete use case (Command + Handler + register handler in `DomainServiceProvider` + add route) or remove the four `Delete*Controller` files.
- **Risk if unfixed:** crash + dead code.

### #7 — CRITICAL `Tag::create()` is called with 2 args but requires 3
- **Category:** A / D
- **Files:**
  - `src/Blogging/Tag/Application/Create/CreateTagCommandHandler.php:21`
  - `src/Blogging/Tag/Domain/Tag.php:17`
- **Evidence:**
  ```php
  // CreateTagCommandHandler.php
  $model = Tag::create($id, $name);  // 2 args
  
  // Tag.php
  public static function create(TagId $id, TagName $name, TagSlug $slug): self
  ```
- **Why it's a bug:** TypeError at runtime on every `POST /tags` — missing argument `$slug`. The `CreateTagCommand` doesn't carry a slug field either, so the bug cascades.
- **Proposed fix:** add `slug` to `CreateTagCommand` and `CreateTagController` validation, then pass it through; or derive the slug from the name with `Str::slug($command->name())` in the handler (consistent with `CreatePostCommandHandler`).
- **Risk if unfixed:** crash on tag creation endpoint.

### #8 — CRITICAL `Tag::create()` does NOT record a domain event
- **Category:** C / D
- **Files:**
  - `src/Blogging/Tag/Domain/Tag.php:17`
  - `src/Blogging/Tag/Domain/TagCreatedDomainEvent.php` (file exists, never instantiated)
- **Evidence:**
  ```php
  public static function create(TagId $id, TagName $name, TagSlug $slug): self
  {
      $tag = new self($id, $name, $slug);
      return $tag;            // ← no $tag->record(...)
  }
  ```
- **Why it's a bug:** violates the aggregate contract in `docs/architecture.md §3` and `docs/conventions.md §4`: factories must record the `Created` event. Downstream event listeners will never observe tag creation.
- **Proposed fix:** add `$tag->record(new TagCreatedDomainEvent($id->value(), $name->value()));`.
- **Risk if unfixed:** silent failure of event-driven integrations.

### #9 — CRITICAL `UpdateCategoryCommandHandler` is a silent no-op
- **Category:** D
- **Files:**
  - `src/Blogging/Category/Application/Update/UpdateCategoryCommandHandler.php:16-29`
- **Evidence:**
  ```php
  $category = $this->repository->search($id);
  if (null === $category) { return; }

  // Apply updates
  // $category->rename(new CategoryName($command->name()));

  $this->repository->save($category);
  ```
- **Why it's a bug:** the command carries a `name`, but the handler never applies it. `PUT /categories/{id}` returns 200 but nothing changes in DB. Worse: the call to `$this->repository->save($category)` re-fires whatever events were on the freshly-rehydrated aggregate (none in this case, but the misleading shape is dangerous).
- **Proposed fix:** add a `Category::rename(CategoryName $name): void` mutator (recording a `CategoryRenamedDomainEvent`) and call it from the handler.
- **Risk if unfixed:** silent data corruption (writes look successful but state is unchanged).

### #10 — CRITICAL `UpdateTagCommandHandler` is a silent no-op
- **Category:** D
- **Files:**
  - `src/Blogging/Tag/Application/Update/UpdateTagCommandHandler.php:16-29`
- **Evidence:** identical pattern to #9, the rename call is commented out.
- **Why it's a bug:** `PUT /tags/{id}` updates nothing.
- **Proposed fix:** implement `Tag::rename()` and invoke it.
- **Risk if unfixed:** silent data corruption.

### #11 — CRITICAL `UpdateUserCommandHandler` is a silent no-op
- **Category:** D
- **Files:**
  - `src/Identity/User/Application/Update/UpdateUserCommandHandler.php:16-29`
- **Evidence:** same pattern.
- **Why it's a bug:** `PUT /users/{id}` updates nothing.
- **Proposed fix:** implement `User::rename()` and call it.
- **Risk if unfixed:** silent data corruption.

### #12 — HIGH Handlers exist but are NOT registered in `DomainServiceProvider`
- **Category:** A
- **Files:**
  - `src/Blogging/Category/Application/SearchByCriteria/SearchCategorysByCriteriaQueryHandler.php`
  - `src/Blogging/Post/Application/SearchByCriteria/SearchPostsByCriteriaQueryHandler.php`
  - `src/Blogging/Tag/Application/SearchByCriteria/SearchTagsByCriteriaQueryHandler.php`
  - `src/Identity/User/Application/SearchByCriteria/SearchUsersByCriteriaQueryHandler.php`
  - `app/Providers/DomainServiceProvider.php:31-41` (the four are missing from `$queryHandlers`).
- **Evidence:**
  ```php
  private array $queryHandlers = [
      \Dbapi\Blogging\Category\Application\Find\FindCategoryQueryHandler::class,
      \Dbapi\Blogging\Post\Application\Find\FindPostQueryHandler::class,
      \Dbapi\Blogging\Tag\Application\Find\FindTagQueryHandler::class,
      ...
      // no SearchXxxByCriteriaQueryHandler entries
  ];
  ```
- **Why it's a bug:** even if the broken imports were fixed, the bus would not know how to dispatch these queries; `ask()` would throw.
- **Proposed fix:** add the four handler classes to `$queryHandlers` after they compile.
- **Risk if unfixed:** crash / dead code.

### #13 — HIGH Controllers exist but no route references them
- **Category:** A
- **Files:**
  - `src/Blogging/Category/Infrastructure/Controller/DeleteCategoryController.php`
  - `src/Blogging/Category/Infrastructure/Controller/SearchCategorysByCriteriaController.php`
  - `src/Blogging/Post/Infrastructure/Controller/DeletePostController.php`
  - `src/Blogging/Post/Infrastructure/Controller/SearchPostsByCriteriaController.php`
  - `src/Blogging/Tag/Infrastructure/Controller/DeleteTagController.php`
  - `src/Blogging/Tag/Infrastructure/Controller/SearchTagsByCriteriaController.php`
  - `src/Identity/User/Infrastructure/Controller/DeleteUserController.php`
  - `src/Identity/User/Infrastructure/Controller/SearchUsersByCriteriaController.php`
  - `routes/api.php` (none of the above appear anywhere)
- **Evidence:** `grep -n "DeleteCategoryController\|SearchCategorys" routes/api.php` → 0 hits.
- **Why it's a bug:** 8 controllers worth of dead code; consumers cannot call delete or list endpoints; orphan files mask broken imports (#1–#6).
- **Proposed fix:** either expose the routes (`Route::delete('/categories/{id}', DeleteCategoryController::class)` etc.) or delete the controller files.
- **Risk if unfixed:** dead code + DX drift between docs ("Delete present" in skill) and reality.

### #14 — HIGH `UpdatePostCommandHandler` re-records `PostCreatedDomainEvent` on every update
- **Category:** D
- **Files:**
  - `src/Blogging/Post/Application/Update/UpdatePostCommandHandler.php:74-91`
- **Evidence:**
  ```php
  $updated = Post::create(   // ← uses create(), which records PostCreatedDomainEvent
      $post->id(),
      $title,
      ...
  );
  $this->repository->save($updated);   // publishes the event
  ```
- **Why it's a bug:** every `PUT /posts/{id}` fires a `post.created` event. Subscribers will think a brand-new post was created, which is wrong (and may double-trigger CRM/index/cache flows).
- **Proposed fix:** add an `Update` factory `Post::reconstituteFrom(...)` or proper mutators (`renameTo`, `replaceContent`, ...) that record `PostUpdatedDomainEvent` instead.
- **Risk if unfixed:** wrong event semantics → wrong downstream behavior.

### #15 — HIGH `UpdateTaskCommandHandler` re-records `TaskCreatedDomainEvent` on every update
- **Category:** D
- **Files:**
  - `src/TodoList/Task/Application/Update/UpdateTaskCommandHandler.php:21-30`
- **Evidence:**
  ```php
  // Upsert: create or overwrite — same as Blogging pattern
  $task = Task::create(  // ← records TaskCreatedDomainEvent
      new TaskId($command->id()),
      ...
  );
  $this->repository->save($task);
  ```
- **Why it's a bug:** same problem as #14 for Task. The comment even acknowledges it follows the (broken) blogging pattern.
- **Proposed fix:** introduce `Task::reconstituteFrom()` or mutators with proper events.
- **Risk if unfixed:** wrong event semantics.

### #16 — HIGH `Form::create()` does NOT record a domain event
- **Category:** C
- **Files:**
  - `src/Forms/Form/Domain/Form.php:22-30`
- **Evidence:**
  ```php
  public static function create(
      string $key, string $name, ?string $recipientEmail, array $fields, bool $active,
  ): self {
      return new self(null, $key, $name, $recipientEmail, $fields, $active);
      // ← no ->record(new FormCreatedDomainEvent(...))
  }
  ```
  `find src/Forms -name "FormCreatedDomainEvent.php"` → 0 hits.
- **Why it's a bug:** violates the aggregate contract; no event class exists at all for Form.
- **Proposed fix:** add `src/Forms/Form/Domain/FormCreatedDomainEvent.php` and record it from `Form::create()`.
- **Risk if unfixed:** docs drift, no observability.

### #17 — HIGH All 8 Create controllers bypass `sendResponse()` (response-envelope contract)
- **Category:** C
- **Files:**
  - `src/Blogging/Category/Infrastructure/Controller/CreateCategoryController.php:57`
  - `src/Blogging/Post/Infrastructure/Controller/CreatePostController.php:100`
  - `src/Blogging/Tag/Infrastructure/Controller/CreateTagController.php:57`
  - `src/Forms/Form/Infrastructure/Controller/CreateFormController.php:82`
  - `src/Identity/User/Infrastructure/Controller/CreateUserController.php:57`
  - `src/Language/Language/Infrastructure/Controller/CreateLanguageController.php:73`
  - `src/PageManagement/Page/Infrastructure/Controller/CreatePageController.php:98`
  - `src/TodoList/Task/Infrastructure/Controller/CreateTaskController.php:64`
- **Evidence:** all 8 share the same anti-pattern, e.g.
  ```php
  return new JsonResponse(['success' => true, 'data' => null, 'message' => 'Task created successfully'], 201);
  ```
- **Why it's a bug:** `docs/conventions.md §5` mandates `$this->sendResponse(...)` / `sendError(...)`. Hand-rolled envelopes will drift from the base class (e.g. when the base class adds `request_id`, headers, or normalises `null` to `[]`). The actual current shape `data: null` differs from `sendResponse([])` which yields `data: []` — front-ends that have type-checked one will break on the other.
- **Proposed fix:** `return $this->sendResponse(null, '... created successfully', 201);` (or `sendResponse([], ...)` to match the read endpoints).
- **Risk if unfixed:** wrong response shape / inconsistent API contract.

### #18 — HIGH Plural-naming bug: `Categorys` everywhere instead of `Categories`
- **Category:** D
- **Files:**
  - `src/Blogging/Category/Application/Response/CategorysResponse.php` (class, property `$categorys`, method `categorys()`, lines 9-21)
  - `src/Blogging/Category/Application/SearchByCriteria/SearchCategorysByCriteriaQuery.php`
  - `src/Blogging/Category/Application/SearchByCriteria/SearchCategorysByCriteriaQueryHandler.php`
  - `src/Blogging/Category/Infrastructure/Controller/SearchCategorysByCriteriaController.php` (also message "Categorys searched successfully" line 70)
- **Evidence:**
  ```php
  final class CategorysResponse implements Response
  {
      private array $categorys;
      public function categorys(): array { return $this->categorys; }
      ...
  ```
- **Why it's a bug:** wrong English plural; explicitly called out in `docs/conventions.md §3` and in `feature_list.json` feature 4. Will surface as wrong JSON key once a controller serialises a single `categorys()` payload.
- **Proposed fix:** rename `CategorysResponse → CategoriesResponse`, the query/handler/controller, and the user-facing message.
- **Risk if unfixed:** DX, wrong JSON shape, doc drift.

### #19 — HIGH Mismatch — `Form` aggregate uses plain strings, not Value Objects
- **Category:** C
- **Files:**
  - `src/Forms/Form/Domain/Form.php:11-19`
- **Evidence:**
  ```php
  public function __construct(
      private readonly ?int $id,
      private readonly string $key,
      private readonly string $name,
      private readonly ?string $recipientEmail,
      private readonly array $fields,
      private readonly bool $active,
  ) {}
  ```
  No `FormId`, `FormKey`, `FormName`, `FormRecipientEmail` value objects exist.
- **Why it's a bug:** `docs/architecture.md §3` requires VO wrappers for aggregate properties. The pattern is consistent across Post/Page/Task/Language; only Form violates it. Validation invariants (e.g. recipient email format, key slug pattern) leak to the controller.
- **Proposed fix:** introduce `FormId`, `FormKey` (slug VO), `FormName`, `FormRecipientEmail` (extending `EmailValueObject?`) and refactor.
- **Risk if unfixed:** invariant leakage; future bugs.

### #20 — HIGH `SubmitFormCommandHandler` throws `InvalidArgumentException` → maps to 500
- **Category:** D
- **Files:**
  - `src/Forms/Form/Application/Submit/SubmitFormCommandHandler.php:22,45`
- **Evidence:**
  ```php
  if ($form === null || $form->active() === false || $form->id() === null) {
      throw new \InvalidArgumentException('Form not found or inactive.');
  }
  ...
  if ($validator->fails()) {
      throw new \InvalidArgumentException($validator->errors()->first());
  }
  ```
  Yet `SubmitFormController`'s OpenAPI declares 404 / 422 outcomes.
- **Why it's a bug:** `InvalidArgumentException` is not caught/translated by the framework's default handler; Laravel will render it as a 500 response, contradicting the documented 404/422.
- **Proposed fix:** throw `Symfony\Component\HttpKernel\Exception\NotFoundHttpException` / `UnprocessableEntityHttpException` (or a domain `FormNotFound` / `FormValidationFailed` exception caught in the controller and mapped to `sendError(..., 404)` / 422).
- **Risk if unfixed:** wrong response code; broken API contract for the public submission endpoint.

### #21 — HIGH `SpamProtection._hp` vs OpenAPI `honeypot` mismatch
- **Category:** D / F
- **Files:**
  - `src/Forms/Form/Infrastructure/SpamProtection/SpamProtection.php:14`
  - `src/Forms/Form/Infrastructure/Controller/SubmitFormController.php:40` (OpenAPI example)
- **Evidence:**
  ```php
  // SpamProtection.php
  if (!empty($payload['_hp'] ?? null)) {
      throw new \InvalidArgumentException('Spam detected by honeypot.');
  }

  // SubmitFormController.php OpenAPI example
  "honeypot" => "",
  ```
- **Why it's a bug:** docs tell front-end devs to send the field as `honeypot`, but the back-end only checks `_hp`. The honeypot is therefore disarmed for any client that follows the docs.
- **Proposed fix:** pick one name. The convention should also flow through validation (today the field is silently allowed because `$validator` rules are built from form fields only).
- **Risk if unfixed:** security control silently broken.

### #22 — HIGH `Post::create()` is invoked with 13 positional args by `PostTest` — passes today, but mismatched arity to factory
- **Category:** E
- **Files:**
  - `src/Blogging/Post/Tests/Domain/PostTest.php:28-42`
  - `src/Blogging/Post/Domain/Post.php:30-45` (factory has 14 params; last is `$tagIds = []`)
- **Evidence:** test passes 13 args. Today this works because `$tagIds` has a default. But the test never exercises the tag-ids branch, never asserts the `PostCreatedDomainEvent` payload (only count), and never asserts the seo/og fields.
- **Why it's a bug:** thin coverage — refactors that change argument order will go undetected. The PostCreatedDomainEvent payload includes `name` only (see #38), which the test does not pin.
- **Proposed fix:** add an explicit `$tagIds: ['tag-1']` case and assert `$events[0]->name()`.
- **Risk if unfixed:** weak regression net.

### #23 — HIGH No FormId VO + `Form::save()` upserts by `key`, not `id`
- **Category:** D
- **Files:**
  - `src/Forms/Form/Domain/Form.php:13` (`?int $id`)
  - `src/Forms/Form/Infrastructure/Persistence/EloquentFormRepository.php:23-31`
- **Evidence:**
  ```php
  $saved = $this->model->updateOrCreate(
      ['key' => $form->key()],     // ← matches on key, not id
      [...]
  );
  ```
- **Why it's a bug:** if a caller supplies an id (legitimate via update flow) it will be ignored; if two tenants share a key they will collide (mitigated by tenant tables, but still). Worse, `save()` returns a brand-new `Form` rebuilt from primitives — caller never gets the persisted `id` back (handler discards the return). The repo interface even declares `save(Form): Form` to acknowledge this, but the create handler ignores the return value (`$this->repository->save($form)`).
- **Proposed fix:** unify on id, or unify on key, but document and use it consistently. Return the persisted id to the controller so the response can include it.
- **Risk if unfixed:** ambiguity; lost feedback to the API consumer (Create returns `data: null` so they cannot get the id).

### #24 — MEDIUM Wrong-arity `Tag::create(...)` call chain (Post handler safe, audit elsewhere)
- **Category:** D
- **Files:**
  - `src/Blogging/Post/Application/Create/CreatePostCommandHandler.php:59`
  - `src/Blogging/Post/Application/Update/UpdatePostCommandHandler.php:64`
- **Evidence:** these two call `Tag::create(new TagId(...), $tagName, new TagSlug(...))` — 3 args, which IS correct. They are paired with #7 only to highlight that `CreateTagCommandHandler` is the broken one.
- **Why it's a bug:** Note rather than bug — keep here because reviewer should also confirm the Tag pipeline used by Posts is consistent (it is). Including for completeness.
- **Proposed fix:** none for these two files.
- **Risk if unfixed:** none directly.

### #25 — MEDIUM `Post::toPrimitives()` and persistence diverge — `language` key lost on save
- **Category:** D
- **Files:**
  - `src/Blogging/Post/Domain/Post.php:102-120`
  - `src/Blogging/Post/Infrastructure/Persistence/EloquentPostRepository.php:36-44`
- **Evidence:**
  ```php
  // toPrimitives() emits 'language', 'title', 'slug', etc.
  
  // save() unsets all the translatable ones:
  unset($primitives['tag_ids'], $primitives['title'], $primitives['slug'],
        $primitives['content'], $primitives['language'], ...);
  $this->model->updateOrCreate(['id' => $model->id()->value()], $primitives);
  ```
  The remaining fields in `$primitives` are only `id` and `category_id`. The `language` is discarded entirely from the main table — only `pt.language_code` exists per translation row. On `search()`, the legacy-fallback branch reads `$legacyData['language']` from a column that is not in `$fillable` (BlogPost only fills `id`, `category_id`).
- **Why it's a bug:** `toPrimitives()` / `fromPrimitives()` asymmetry. The aggregate's `language` field carries a value that is never round-tripped via the main table; the search join must always succeed for hydration. If a tenant has a row in `*_posts` but the join row is missing, `legacy` fallback reads `language` from a column that's not in `$fillable` — Eloquent's `toArray()` only returns selected attributes, so `'language' => $legacyData['language'] ?? $languageCode` could read a non-attribute and silently fall to the query parameter.
- **Proposed fix:** mark `Post::toPrimitives()` as returning only "main table" columns (mirroring `Page::toPrimitives()`), and add a separate `translationPrimitives()`.
- **Risk if unfixed:** silent inconsistency, fragile legacy code path.

### #26 — MEDIUM `Post::create()` records event with `$title->value()` while event property is named `name`
- **Category:** F
- **Files:**
  - `src/Blogging/Post/Domain/Post.php:62`
  - `src/Blogging/Post/Domain/PostCreatedDomainEvent.php:13`
- **Evidence:**
  ```php
  $post->record(new PostCreatedDomainEvent($id->value(), $title->value()));
  // ↓
  public function __construct(string $aggregateId, private string $name, ...)
  ```
- **Why it's a bug:** the aggregate calls a value "title", the domain event calls it "name". Subscribers reading `$event->name()` will get the title — confusing but functional. Cross-aggregate consistency would name them all "title".
- **Proposed fix:** rename `PostCreatedDomainEvent::$name` to `$title` (also update `toPrimitives()`).
- **Risk if unfixed:** DX confusion.

### #27 — MEDIUM `PostCreatedDomainEvent::fromPrimitives()` will throw if payload is missing `name`
- **Category:** D
- **Files:**
  - `src/Blogging/Post/Domain/PostCreatedDomainEvent.php:26`
- **Evidence:**
  ```php
  return new self($aggregateId, $body['name'], $eventId, $occurredOn);
  // No coalesce; key access throws if absent.
  ```
- **Why it's a bug:** for an event-stream rebuild with a malformed payload, the call dies with an `ErrorException` rather than a typed domain error.
- **Proposed fix:** add a defensive guard (`if (!isset($body['name'])) throw new \LogicException(...)`).
- **Risk if unfixed:** poor diagnostics when replaying events.

### #28 — MEDIUM `TagResponse` does not expose `slug` even though Tag has one
- **Category:** D
- **Files:**
  - `src/Blogging/Tag/Application/Response/TagResponse.php:10-35`
- **Evidence:**
  ```php
  final class TagResponse implements Response
  {
      private string $id;
      private string $name;            // ← no $slug

      public function toArray(): array
      {
          return ['id' => $this->id, 'name' => $this->name];
      }
  }
  ```
- **Why it's a bug:** API consumers cannot read the slug. The aggregate has it, the DB has it (#7-related), but it never reaches the wire.
- **Proposed fix:** add `slug` to the constructor, factory, and `toArray()`.
- **Risk if unfixed:** wrong response.

### #29 — MEDIUM Response DTOs are not `final readonly` (convention §1)
- **Category:** C
- **Files:**
  - `src/Blogging/Category/Application/Response/CategoryResponse.php:10` (`final class …`)
  - `src/Blogging/Category/Application/Response/CategorysResponse.php:9`
  - `src/Blogging/Post/Application/Response/PostResponse.php:10`
  - `src/Blogging/Post/Application/Response/PostsResponse.php:9`
  - `src/Blogging/Tag/Application/Response/TagResponse.php:10`
  - `src/Blogging/Tag/Application/Response/TagsResponse.php:9`
  - `src/Forms/Form/Application/Response/FormResponse.php:10`
  - `src/Identity/User/Application/Response/UserResponse.php:10`
  - `src/Identity/User/Application/Response/UsersResponse.php:9`
  - `src/Language/Language/Application/Response/LanguageResponse.php:10`
  - `src/Language/Language/Application/FindAll/LanguageListResponse.php:9`
- **Evidence:**
  ```php
  final class CategoryResponse implements Response   // ← missing readonly
  ```
- **Why it's a bug:** `docs/conventions.md §1` requires DTOs to be `final readonly`. Mutability allows accidental state changes between query handler and controller.
- **Proposed fix:** change every `final class XxxResponse` to `final readonly class XxxResponse` (PHP 8.2+ — the project's `composer.json` requires `^8.3`).
- **Risk if unfixed:** DX / DDD discipline.

### #30 — MEDIUM `LanguageResponse` and `CreateLanguageCommand` are not `readonly`
- **Category:** C
- **Files:**
  - `src/Language/Language/Application/Response/LanguageResponse.php:10`
  - `src/Language/Language/Application/Create/CreateLanguageCommand.php:9`
  - `src/Language/Language/Application/Find/FindLanguageQuery.php:9`
- **Evidence:** all three use `final class ... { private $x; ... }` instead of constructor-promoted `readonly` properties.
- **Why it's a bug:** same as #29 but explicitly in Language module; properties are mutable.
- **Proposed fix:** convert to `final readonly class`.
- **Risk if unfixed:** convention drift.

### #31 — MEDIUM `Page` / `PageTranslation` are not `final readonly` (convention §1)
- **Category:** C
- **Files:**
  - `src/PageManagement/Page/Domain/Page.php:9` (no readonly props; assigned via constructor body)
  - `src/PageManagement/Page/Domain/PageTranslation.php:11` (`final class`, no readonly)
- **Evidence:**
  ```php
  final class PageTranslation
  {
      public function __construct(
          public string  $languageCode,        // ← not readonly
          public string  $slug,
          ...
      ) {}
  ```
- **Why it's a bug:** `PageTranslation` carries domain invariants but is mutable from anywhere. Aggregate roots are conventionally `final` (Page is) but their internal entities/VOs should be readonly.
- **Proposed fix:** mark `PageTranslation` as `final readonly` and the `Page` properties as `private readonly` in the constructor.
- **Risk if unfixed:** convention drift; possible accidental mutation.

### #32 — MEDIUM `FormField`, `FormSubmission` are good but no `FormCreatedDomainEvent` exists (paired with #16)
- **Category:** A / C
- **Files:**
  - `src/Forms/Form/Domain/Form.php`, `src/Forms/Form/Domain/FormField.php`, `src/Forms/Form/Domain/FormSubmission.php`
- **Evidence:** `find src/Forms -name "*DomainEvent*"` → 0 hits.
- **Why it's a bug:** no domain events at all → no traceability for form lifecycle. Coupled to #16.
- **Proposed fix:** add `FormCreatedDomainEvent`, `FormSubmittedDomainEvent`.
- **Risk if unfixed:** weak event model.

### #33 — MEDIUM Tag/Category Update commands accept `?string $name` but controllers always require it
- **Category:** D
- **Files:**
  - `src/Blogging/Tag/Application/Update/UpdateTagCommand.php:13` (`?string $name`)
  - `src/Blogging/Tag/Infrastructure/Controller/UpdateTagController.php:46` (`'name' => ['required', 'string', 'max:255']`)
  - `src/Blogging/Category/Application/Update/UpdateCategoryCommand.php:13`
  - `src/Blogging/Category/Infrastructure/Controller/UpdateCategoryController.php:46`
- **Evidence:**
  ```php
  // Command
  private ?string $name
  // Controller validator
  'name' => ['required', 'string', 'max:255'],
  ```
- **Why it's a bug:** the command is shaped as a partial update DTO, but the only entry point demands a full name. Either it should be `string $name` (non-nullable, matching the controller's contract) or the controller should validate `sometimes|nullable`.
- **Proposed fix:** make both consistent. Recommend dropping nullability in the command since update is full-replace today.
- **Risk if unfixed:** DX confusion; tests of the command may pass `null` and find the no-op handler ignores it (already covered by #9/#10).

### #34 — MEDIUM Several VOs use legacy declaration `readonly final class` (vs `final readonly class`)
- **Category:** C
- **Files:**
  - `src/Language/Language/Domain/LanguageCode.php:10` — `readonly final class LanguageCode extends StringValueObject`
  - `src/Language/Language/Domain/LanguageIsActive.php:9` — `readonly final class LanguageIsActive ...`
  - `src/Language/Language/Domain/LanguageIsDefault.php:9` — `readonly final class LanguageIsDefault ...`
- **Evidence:**
  ```php
  readonly final class LanguageCode extends StringValueObject { ... }
  ```
- **Why it's a bug:** order is syntactically legal in PHP 8.3 but inconsistent with the rest of the codebase (which uses `final readonly`). PSR/style consistency.
- **Proposed fix:** reorder modifiers.
- **Risk if unfixed:** style drift.

### #35 — MEDIUM `Form::active() === false` branch — sentinel logic accepts no id as "not found"
- **Category:** D
- **Files:**
  - `src/Forms/Form/Application/Submit/SubmitFormCommandHandler.php:21`
- **Evidence:**
  ```php
  if ($form === null || $form->active() === false || $form->id() === null) {
      throw new \InvalidArgumentException('Form not found or inactive.');
  }
  ```
- **Why it's a bug:** lumps three different conditions ("doesn't exist", "inactive", "has no id") under one error string. From a consumer's perspective "inactive" should arguably be 403, "not found" should be 404, "no id" should be 500 (data corruption). Couples to #20.
- **Proposed fix:** branch and throw distinct typed exceptions mapped to their proper HTTP codes.
- **Risk if unfixed:** poor diagnostics; wrong HTTP semantics.

### #36 — MEDIUM `EloquentPostRepository::searchByCriteria()` shares `$toEloquentFields` with `countByCriteria()` but the latter does NOT join translations
- **Category:** D
- **Files:**
  - `src/Blogging/Post/Infrastructure/Persistence/EloquentPostRepository.php:21-27, 167-177`
- **Evidence:**
  ```php
  private static array $toEloquentFields = [
      'id' => 'p.id',
      'title' => 'pt.title',      // ← references pt.* alias
      ...
  ];
  
  public function countByCriteria(Criteria $criteria): int
  {
      $eloquentCriteria = EloquentCriteriaConverter::convert($criteria, self::$toEloquentFields);
      $query = $this->model->newQuery();   // ← bare query, no `pt` alias
      ...
  ```
- **Why it's a bug:** if a caller passes a `title` filter, the count query will reference `pt.title` against a query that does not alias the translation table → SQL error at runtime ("unknown column pt.title"). `searchByCriteria` does the join (lines 142-146); `countByCriteria` does not.
- **Proposed fix:** mirror the same join in `countByCriteria` (or separate the field maps).
- **Risk if unfixed:** crash on filtered counts.

### #37 — MEDIUM `EloquentPageRepository::countByCriteria()` same join-mismatch bug as #36
- **Category:** D
- **Files:**
  - `src/PageManagement/Page/Infrastructure/Persistence/EloquentPageRepository.php:21-26, 103-113`
- **Evidence:** `$toEloquentFields` references `p.*` and `pt.*` but `countByCriteria` uses `$this->model->newQuery()` (no aliasing or join).
- **Why it's a bug:** identical to #36 for Page.
- **Proposed fix:** apply the join in `countByCriteria`.
- **Risk if unfixed:** crash on filtered counts.

### #38 — LOW `BlogPost` Eloquent model — `$fillable` omits `category_id` is fine but missing `slug`/`title` is intentional (lives in translation table). Document this.
- **Category:** F
- **Files:** `app/Models/BlogPost.php:10`
- **Evidence:**
  ```php
  protected $fillable = ['id', 'category_id'];
  ```
- **Why it's a bug:** not a bug; called out so the reviewer doesn't flag it as missing fields. Just verify there is a comment explaining the deliberate trim. Currently no comment exists.
- **Proposed fix:** add `/** Non-translatable columns live here; translatable in BlogPostTranslation */` doc-block.
- **Risk if unfixed:** DX / future-developer confusion.

### #39 — LOW All `app/Models/*.php` (except `Tenant.php`) miss `declare(strict_types=1)`
- **Category:** C
- **Files:**
  - `app/Models/BlogPost.php`, `BlogPostTranslation.php`, `BlogCategory.php`, `BlogTag.php`, `Page.php`, `PageTranslation.php`, `Form.php`, `FormSubmission.php`, `Task.php`, `Language.php`, `User.php`
- **Evidence:** `grep -L "declare(strict_types=1)" app/Models/*.php` returns 11 files.
- **Why it's a bug:** `docs/conventions.md §1` requires `declare(strict_types=1)` in every PHP file under `app/`.
- **Proposed fix:** add the declaration to each file (and likely mark them `final`).
- **Risk if unfixed:** convention drift; subtle bool-coercion bugs.

### #40 — LOW `app/Models/*.php` are not `final`
- **Category:** C
- **Files:** same as #39 (plus `Tenant.php` IS final — the model is correct).
- **Evidence:** e.g. `app/Models/Task.php:7` → `class Task extends Model {` (no `final`).
- **Why it's a bug:** convention §1 says final unless documented inheritance need; no model has a documented reason.
- **Proposed fix:** add `final`.
- **Risk if unfixed:** convention drift.

### #41 — LOW `BlogCategory::getTable()` and `BlogTag::getTable()` are missing the return type
- **Category:** C
- **Files:**
  - `app/Models/BlogCategory.php:13` — `public function getTable()`
  - `app/Models/BlogTag.php:13`
  - `app/Models/User.php:30`
- **Evidence:**
  ```php
  public function getTable()
  {
      $appId = config('database.tenant.app_id');
      return $appId ? $appId . '_categories' : 'categories';
  }
  ```
- **Why it's a bug:** missing `: string` return type. Other models declare it.
- **Proposed fix:** add `: string`.
- **Risk if unfixed:** type-hint inconsistency.

### #42 — LOW `tests/Unit/ExampleTest.php` is an assertion-free smoke test
- **Category:** E
- **Files:** `tests/Unit/ExampleTest.php:14-17`
- **Evidence:**
  ```php
  public function test_example()
  {
      $this->assertTrue(true);
  }
  ```
- **Why it's a bug:** `docs/conventions.md §10` rules out dead/trivial tests; this is the canonical "test that always passes". Same applies to `tests/Feature/ExampleTest.php` (asserts `GET /` returns 200 with no app context).
- **Proposed fix:** remove the two `ExampleTest.php` files, or rewrite them to test something real.
- **Risk if unfixed:** test suite bloat; false sense of coverage.

### #43 — LOW Domain unit tests (Category, Tag, User, Post) don't pin `pullDomainEvents()` payload
- **Category:** E
- **Files:**
  - `src/Blogging/Category/Tests/Domain/CategoryTest.php`
  - `src/Blogging/Tag/Tests/Domain/TagTest.php` (also misses the event assertion entirely — see #8)
  - `src/Identity/User/Tests/Domain/UserTest.php`
  - `src/Blogging/Post/Tests/Domain/PostTest.php`
- **Evidence:**
  ```php
  // CategoryTest.php
  $events = $category->pullDomainEvents();
  $this->assertCount(1, $events);
  $this->assertInstanceOf(CategoryCreatedDomainEvent::class, $events[0]);
  // No assertions on the event's payload (id/name).
  ```
- **Why it's a bug:** if the factory silently records the wrong values, the test will not catch it.
- **Proposed fix:** assert `$events[0]->name()` (or whatever getters exist) match the inputs.
- **Risk if unfixed:** weak regression net.

### #44 — LOW `TagTest.php` does not assert that a domain event is recorded
- **Category:** E (also evidence supporting #8)
- **Files:** `src/Blogging/Tag/Tests/Domain/TagTest.php:17-28`
- **Evidence:**
  ```php
  public function it_should_create_a_tag(): void
  {
      ...
      $tag = Tag::create($id, $name, $slug);
      // no $tag->pullDomainEvents() assertion
  }
  ```
- **Why it's a bug:** every other aggregate test asserts the event. Combined with #8, the missing event was never caught.
- **Proposed fix:** add `pullDomainEvents()` assertion (which will currently fail and surface #8).
- **Risk if unfixed:** the bug in #8 stays hidden.

### #45 — LOW `tests/Unit/ExampleTest.php` references `PHPUnit\Framework\TestCase` whereas Feature tests use `Tests\TestCase`
- **Category:** E
- **Files:** `tests/Unit/ExampleTest.php:5`, `tests/Feature/ExampleTest.php:6`
- **Evidence:** different base classes for similar trivial tests; inconsistent — the Unit suite also picks up `./src` per `phpunit.xml`, so the Tests namespace expectation is inconsistent.
- **Why it's a bug:** style drift; reviewers expect a single base class for each suite.
- **Proposed fix:** drop the example tests entirely (see #42).
- **Risk if unfixed:** style drift.

## Cross-cutting observations

1. **Search-by-criteria pipeline is broken across 4 aggregates (Category, Post, Tag, User).** The pattern is identical (handler depends on a `XyzByCriteriaSearcher` that doesn't exist, controller asks a `CountXyzByCriteriaQuery` that doesn't exist, controller is not even routed). All four were probably generated by a scaffolder whose searcher template was never landed. **Treat the four #1–#5 findings as a single epic.**

2. **Delete pipeline is broken across the same 4 aggregates.** The four `Delete*Controller`s import a `Delete*Command` that has no class. None of the four is routed today, so it's a latent bug. **Treat #6 as the second leg of the same epic.**

3. **Update handlers in Category / Tag / User are silent no-ops** (#9, #10, #11). This is the most dangerous category of bug in this audit: writes succeed (200 OK) but DB state never changes. Front-end devs and integration tests will not notice without explicit read-back assertions.

4. **8 Create controllers all bypass `sendResponse()`** (#17). The wrong-envelope payload differs from the read envelope and from the (correct) Page/Form `sendResponse` calls elsewhere. This is a systemic copy-paste artifact and should be batched into one PR.

5. **`docs/conventions.md §1` `final readonly` rule is widely ignored** in Response DTOs (11 files, #29), Commands (#30 — Language), and PageTranslation (#31). All easily mechanically fixable.

6. **Domain events are missing or wrongly used** in three places: Form (none at all, #16, #32), Tag (factory does not record, #8), Post/Task (`Update` re-fires the `Created` event, #14/#15). The event story is inconsistent across aggregates.

7. **Plural-naming debt (`Categorys`)** is tracked in `feature_list.json` feature 4 but still very much alive in the codebase (12+ occurrences across 4 files, #18). The English-plural fix has not been done.

8. **`countByCriteria()` join mismatch** affects Post and Page repositories (#36, #37) — both have a join in `searchByCriteria` but not in `countByCriteria`, with the field map referencing the joined alias. Will surface as a SQL error the moment a filter is applied to a count.

9. **Test coverage is shallow.** The unit tests check factory happy paths and either skip the domain-event assertion (#44) or do not pin the payload (#43). There is no test that would have caught #7, #8, #9, #10, #11, #14, #15, #20, or #36. Building targeted regression tests is the cheapest insurance.

10. **Skill cheat-sheet drift acknowledged.** `.agents/skills/dba-ddd-skeleton/SKILL.md §11` still lists `Dbravoan\DbaSkeletonDdd` as the package namespace — this audit confirms (per `grep`) that the codebase itself correctly uses `Dba\DddSkeleton\…` everywhere. Only the doc is wrong. Suggest amending the skill in a follow-up; not in scope for this audit.
