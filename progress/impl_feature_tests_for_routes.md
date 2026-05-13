# Implementer report — feature_tests_for_routes (feature #6)

## Summary

Created 6 new Feature test files covering route-level behaviour for Post, Category, Tag, Page, User, and Language endpoints, plus added 4 find-form tests to the existing FormRouteTest. Each file follows the TaskRouteTest mocking pattern (mockResolver, makeTenant, Mockery for CommandBus/QueryBus, never hits the database). Tests cover: find 404, find 200, create 401, create 422, create 403 module-gate, update 401, update 422, update 403 module-gate (where applicable). User routes have no module gate so they skip the 403 tests.

## Files touched

- `tests/Feature/PostRouteTest.php` — created — 8 tests (find/create/update + auth/422/403)
- `tests/Feature/CategoryRouteTest.php` — created — 8 tests (find/create/update + auth/422/403)
- `tests/Feature/TagRouteTest.php` — created — 8 tests (find/create/update + auth/422/403)
- `tests/Feature/PageRouteTest.php` — created — 8 tests (find/create/update + auth/422/403)
- `tests/Feature/UserRouteTest.php` — created — 6 tests (find/create/update + auth/422; no module gate)
- `tests/Feature/LanguageRouteTest.php` — created — 8 tests (find/find-all/create + auth/422/403)
- `tests/Feature/FormRouteTest.php` — modified — added 4 tests (find form 404/200/401/403)

## Decisions

- **User routes skip module gate tests**: Identity/User routes have no `require.module` middleware per routes/api.php:72 comment ("users are cross-module"). Following the spec.
- **Language routes include find-all test**: FindAllLanguagesController exists under GET /languages with module gate, so a `test_find_all_languages_returns_200` and `test_find_all_languages_blocked_when_module_not_enabled` are included.
- **Blog routes use `withoutMiddleware(Authenticate::class)` for 403 tests**: Laravel's middleware priority runs `auth` before custom route middleware like `RequireModule`, so module-gate tests for auth-protected routes must bypass auth. Matches the existing pattern in TaskRouteTest.
- **Form find tests include auth-required test plus bypass for 404/200/403**: FindFormController is behind `auth:api`, so 404/200 tests bypass auth, and a dedicated `test_find_form_requires_authentication` covers the 401 case.

## Test evidence

```
$ ./vendor/bin/sail artisan test --compact
  ............................................................................
  .......................................

  Tests:    115 passed (215 assertions)
  Duration: 2.98s
```

```
$ php8.4 artisan test --compact
  ............................................................................
  .......................................

  Tests:    115 passed (215 assertions)
  Duration: 1.67s
```

## init.sh evidence

```
$ ./init.sh
── 3. feature_list.json invariants ──
unknown shorthand flag: 'r' in -r
── 4. progress/ hygiene ──
unknown shorthand flag: 'r' in -r
── 6. PHPUnit test suite ──
[FAIL]  Sail PHP cannot bootstrap composer.
```

init.sh fails due to a pre-existing bug: when `PHP_RUNNER=./vendor/bin/sail`, it calls `$PHP_RUNNER -r '...'` which translates to `./vendor/bin/sail -r '...'` — but `sail` requires an explicit `php` subcommand (should be `./vendor/bin/sail php -r '...'`). This is not caused by this feature. The test suite is fully green via both Sail and host php8.4.

## Acceptance check

- [x] `tests/Feature/PostRouteTest.php` exists and passes — 8 tests
- [x] `tests/Feature/CategoryRouteTest.php` exists and passes — 8 tests
- [x] `tests/Feature/TagRouteTest.php` exists and passes — 8 tests
- [x] `tests/Feature/PageRouteTest.php` exists and passes — 8 tests
- [x] `tests/Feature/FormRouteTest.php` exists and passes — now 13 tests (was 9)
- [x] `tests/Feature/UserRouteTest.php` exists and passes — 6 tests
- [x] `tests/Feature/LanguageRouteTest.php` exists and passes — 8 tests
- [x] Each test file uses the same mocking pattern as TaskRouteTest (mockResolver, makeTenant, Mockery for CommandBus/QueryBus, no database)

## Open questions for the reviewer

- init.sh has a pre-existing bug at steps 3-4 (missing `php` subcommand when using Sail runner) and step 6 (Sail PHP can't bootstrap composer, possibly a dependency version mismatch). The test suite passes cleanly via both `php8.4 artisan test` and `./vendor/bin/sail artisan test`.

## Status

done
