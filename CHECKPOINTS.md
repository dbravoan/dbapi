# CHECKPOINTS — Objective end-state criteria for `dbapi`

> In multi-agent systems you don't evaluate the path, you evaluate the destination.
> These checkpoints can be ticked by a human or by the `reviewer` subagent.
> If any box in C1–C5 is empty, the session **cannot be closed**.

---

## C1 — The harness is complete

- [ ] `AGENTS.md`, `OPENCODE.md`, `CHECKPOINTS.md`, `init.sh`, `feature_list.json` exist.
- [ ] `progress/current.md` and `progress/history.md` exist.
- [ ] `docs/architecture.md`, `docs/conventions.md`, `docs/verification.md` exist.
- [ ] `.agents/agents/leader.md`, `implementer.md`, `reviewer.md` exist.
- [ ] `./init.sh` exits with code 0.

## C2 — Workflow state is coherent

- [ ] `feature_list.json` has at most **one** feature with `status: "in_progress"`.
- [ ] Every feature flagged `done` has either: (a) a `progress/impl_<feature>.md`
      report and a `progress/review_<feature>.md` verdict, OR (b) is a historic
      `done` (i.e. pre-existed before the harness was installed — explicitly
      annotated as such in the file).
- [ ] `progress/current.md` is either:
      - **empty/template** (no session in progress), OR
      - describing a **single** active session that matches the `in_progress` feature.
- [ ] No orphan `progress/impl_*.md` or `progress/review_*.md` without a matching
      feature in `feature_list.json`.

## C3 — Architecture is respected

- [ ] All new code under `src/` lives in the `Domain / Application / Infrastructure`
      three-layer layout for its aggregate. No layer skips (no Eloquent in
      `Application/`, no `Request::validate()` in `Domain/`, no business logic
      in controllers).
- [ ] All PHP files in `src/` declare `declare(strict_types=1);` and use the
      `Dbapi\…` namespace.
- [ ] All references to the skeleton package use the real namespace
      `Dba\DddSkeleton\…` — **never** `Dbravoan\DbaSkeletonDdd\…`.
- [ ] Every new Command/Query handler is registered in
      `app/Providers/DomainServiceProvider.php`.
- [ ] Every new repository interface is bound to its Eloquent implementation
      in `app/Providers/RepositoryServiceProvider.php`.
- [ ] Every new HTTP endpoint is exposed in `routes/api.php` under the correct
      `{tenant}/{version}` group and (if applicable) `require.module:<name>`
      gate + `auth:api` middleware.
- [ ] Every new Eloquent model in `app/Models/` computes `getTable()` from
      `config('database.tenant.app_id')` (or otherwise honors multi-tenancy).

## C4 — Conventions are respected

- [ ] All controllers are `final`, invokable, extend `ApiController`, receive
      `CommandBus` or `QueryBus`, and return `JsonResponse` via
      `$this->sendResponse(...)` / `$this->sendError(...)` — **not** raw
      `new JsonResponse([...])`.
- [ ] Aggregates extend `Dba\DddSkeleton\Shared\Domain\Aggregate\AggregateRoot`,
      have a private constructor and a `create()` factory that records the
      corresponding `…CreatedDomainEvent`.
- [ ] All Value Objects are `final readonly` and extend one of the package's
      VO bases (`StringValueObject`, `Uuid`, `IntValueObject`, …).
- [ ] All OpenAPI attributes (`#[OA\…]`) on controllers are present and consistent.
- [ ] No `dd()`, `dump()`, `var_dump()`, raw `echo`, or `Log::debug` left in
      `src/` or `app/`.

## C5 — Verification is real

- [ ] `./vendor/bin/sail artisan test` (or `php artisan test` if Sail isn't
      running) exits 0.
- [ ] Every aggregate touched in the session has either a Domain unit test
      under `src/{Context}/{Agg}/Tests/Domain/` or a Feature test under
      `tests/Feature/` — **or both** when behaviour changed.
- [ ] Every new route has at least one Feature test covering:
      - 200/201 happy path,
      - 401 when `auth:api` is expected and absent,
      - 403 when the module gate is missing from `enabled_modules`,
      - 404 / 422 where applicable.
- [ ] `php artisan route:list` shows every controller introduced by the
      session (no controllers without a route).

## C6 — Session is closed cleanly

- [ ] No untracked junk: `*.tmp`, `*.bak`, `*.log`, editor swap files, vendored
      libraries not in `.gitignore`.
- [ ] `progress/history.md` has a new entry for this session at the bottom.
- [ ] `progress/current.md` is back to the empty template.
- [ ] The feature is flagged `done` in `feature_list.json` (or `blocked` with a
      clear reason).

---

**How `reviewer` uses this file:** it reads each section, ticks `[x]` against
each item it verified, and refuses to approve a session close if any box in
C1–C5 is empty. C6 is the leader's responsibility at session end.
