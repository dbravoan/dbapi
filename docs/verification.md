# Verification — how to prove a feature really works

> The `reviewer` subagent walks through this file before approving a session
> close. The `implementer` is responsible for showing the evidence inline in
> `progress/impl_<feature>.md`. "It worked on my machine" is not evidence.

---

## 1. The verification ladder

A feature is verified when **each rung** below holds:

1. **Static** — `./init.sh` exits 0.
2. **Unit** — Domain / use-case tests for the aggregate touched by the
   feature pass.
3. **Integration** — Feature tests covering the affected route(s) pass.
4. **Wiring** — `php artisan route:list --path=<resource>` shows the new
   routes. `php artisan tinker` resolves the new handlers via the bus.
5. **Documentation** — `php artisan l5-swagger:generate` runs without errors
   and the generated `/documentation` UI shows the new endpoints.

Skipping a rung is acceptable only when the feature genuinely doesn't touch
that layer (e.g. renaming a Response DTO doesn't change Swagger). When you
skip a rung, **say so** in the impl report.

## 2. Local execution checklist

Run these in order. The impl report must paste the relevant terminal output
(can be the last 30 lines, do not paste hundreds of lines).

```bash
# Always
./init.sh

# When you touched the test suite or any src/ class
./vendor/bin/sail artisan test
# or, if Sail isn't running:
php artisan test

# When you changed a route or controller
./vendor/bin/sail artisan route:list --path=<resource>

# When you changed OpenAPI attributes on a controller
./vendor/bin/sail artisan l5-swagger:generate

# When you added a handler
./vendor/bin/sail artisan tinker
# >>> app(\Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus::class)
# >>> // should resolve without throwing
```

## 3. Test-coverage matrix per layer change

| If you changed… | You must add or update… |
|---|---|
| A Value Object | A unit test asserting valid values pass and invalid values throw |
| An aggregate factory or method | A Domain unit test under `src/{Ctx}/{Agg}/Tests/Domain/` |
| A command/query handler | A unit test mocking the repository, OR a feature test going through the bus |
| A controller (new route) | A Feature test in `tests/Feature/` covering: 200/201 + 401 + 403 + 404 + 422 (whichever apply) |
| An Eloquent repository | A unit or integration test exercising `save → search → toPrimitives` |
| A provider binding/registration | A feature test that exercises the bus path (the binding is implicitly proved) |

## 4. The Feature-test reference

`tests/Feature/TaskRouteTest.php` is the canonical template. Copy its
structure:

- `mockResolver(?Tenant $tenant)` — replaces `TenantResolverInterface`.
- `makeTenant(array $overrides = [])` — builds a `Tenant` instance without
  hitting the DB. Set `enabled_modules` to control module gating.
- `\Mockery::mock(CommandBus::class)` / `\Mockery::mock(QueryBus::class)` —
  the bus is the seam.
- One test per behaviour: `_returns_404_when_not_found`,
  `_returns_200_when_found`, `_requires_authentication`,
  `_validates_required_fields`, `_blocked_when_module_not_enabled`.

## 5. Negative-path coverage is mandatory

Happy-path-only is not enough. Every new endpoint must have at least one
negative test:

- For writes: a `_requires_authentication` test.
- For module-gated routes: a `_blocked_when_module_not_enabled` test.
- For reads: a `_returns_404_when_not_found` test.
- For mutating endpoints with required fields: a `_validates_required_fields` test.

## 6. The "no orphan" rule

After your change:

- No controller class in `src/` is unreferenced by `routes/api.php`. Either
  wire it or delete it.
- No handler class is unreferenced by `app/Providers/DomainServiceProvider.php`.
  Either register it or delete it.
- No repository implementation is unbound in `RepositoryServiceProvider`.

The reviewer will check this with:

```bash
# Controllers without routes
grep -roh "Dbapi.*Controller::class" routes/api.php | sort -u > /tmp/used.txt
find src -path '*Infrastructure/Controller/*.php' \
  -exec basename {} .php \; | sort -u > /tmp/exist.txt
diff /tmp/exist.txt /tmp/used.txt
```

## 7. Acceptance vs. checkpoints

- **Acceptance** (in `feature_list.json`) is feature-specific. It tells you
  when the *individual* feature is done.
- **Checkpoints** (in `CHECKPOINTS.md`) are repo-wide. They tell you whether
  the *session* can be closed.

A feature is `done` only when both pass.
