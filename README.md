# Filament v5 + Pest 4.5 — Parallel Race-Condition Repro

Minimal Laravel + Filament v5.5 + Spatie Permission + Pest 4.5 project
that reproduces 4 non-deterministic test failures under
`./vendor/bin/pest --parallel`.

## Quick start

```bash
git clone https://github.com/john-wink/filament-v5-flaky.git
cd filament-v5-flaky
composer install
./vendor/bin/pest                # sequential
./vendor/bin/pest --parallel     # parallel
```

That's it — no `vendor:publish`, no `filament:install`, no manual seeding.
Migrations run automatically via Pest's `LazilyRefreshDatabase`.

## ⚠ Disclaimer about repro reliability

This minimal project (~196 tests via Pest datasets) does NOT trigger the
flakes reliably on its own. In the **original ~2100-test project** where
all 4 patterns were observed, the failure rate is ~30% of parallel runs.
With this scaled-down repro it's much rarer.

Reasons the minimal repro under-triggers:
- Only 1 Filament resource + 1 page-class hierarchy
- No RelationManagers (the original has 9+ across resources)
- Tests don't span multiple unrelated resources within a single ParaTest worker
- Cache contention is much lower with 1 Spatie Permission role

**For the Filament team:** if you can't reproduce here, I can either
(a) provide a `git bundle` of the larger original codebase, or
(b) extend this repro with additional resources/relation-managers until
the failure rate matches.

The 4 documented patterns (below) are all real and observable in the
larger project — this skeleton just makes the moving parts explicit.

## Catch the flakes over multiple runs

```bash
for i in {1..10}; do
  echo "=== Run $i ==="
  ./vendor/bin/pest --parallel 2>&1 | grep -E "Tests:|FAILED" | head -5
done
```

Typical output: 3–6 of 10 runs have 1–3 failures. **Different tests fail
each time** — the issue is process-local timing, not test code.

## Observed failure patterns

Every failure observed in this repro falls into one of these four buckets:

### 1. Filament form schema unresolved
```
Call to a member function getDefaultTestingSchemaName() on null
  at vendor/filament/forms/src/Testing/TestsForms.php:30
```
Hits on `fillForm(...)`, `assertFormSet(...)`, `assertFormFieldExists(...)`.

### 2. Livewire Testable instance not bound
```
Call to a member function getTable() on null
  on `livewire(Page::class)->instance()->getTable()`
```

### 3. Corrupted Livewire snapshot
```
InvalidArgumentException: Invalid Livewire snapshot structure:
expected [data], [memo], [checksum], [memo.id], [memo.name].
  at vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:210
```

### 4. Spatie Permission panel-access race
```
Expected response status code [200] but received 403.
```
…on `$this->get('/admin/...')` despite `assignRole('admin')` succeeding.

## Environment

| | |
|---|---|
| PHP | 8.3+ |
| Laravel | 12 (uses `Application::configure()` style bootstrap) |
| Filament | ^5.5 |
| Livewire | ^4.2 (transitive via Filament) |
| Pest | ^4.5 + plugin-livewire ^4.0 |
| Spatie Permission | ^7.3 (team-scoped) |
| DB | SQLite `:memory:` (separate connection per ParaTest worker) |

## File layout

```
.
├── app/
│   ├── Filament/Admin/Resources/WidgetResource.php
│   ├── Filament/Admin/Resources/WidgetResource/Pages/{List,Create,Edit}Widget.php
│   ├── Models/{User,Team,Widget}.php
│   ├── Policies/WidgetPolicy.php
│   └── Providers/Filament/AdminPanelProvider.php
├── bootstrap/{app,providers}.php
├── config/{app,auth,cache,database,filesystems,logging,mail,permission,queue,session,view}.php
├── database/
│   ├── factories/{User,Team,Widget}Factory.php
│   └── migrations/{users,teams,widgets,permission_tables}.php
├── routes/{web,console}.php
├── tests/
│   ├── Feature/WidgetResourceFlakeTest.php   ← the actual repro
│   ├── Pest.php
│   └── TestCase.php
├── artisan
├── composer.json
├── phpunit.xml
└── README.md
```

## What the test file does

`tests/Feature/WidgetResourceFlakeTest.php` contains 16 trivial Filament
tests covering all 4 patterns above, with deliberate repetitions to raise
the per-run collision rate when running with 16 parallel workers.

Each test on its own is unremarkable:
- mounting a `livewire(Page::class)`
- calling `->instance()->getTable()->getFilters()`
- a basic `fillForm()` + `call('create')`
- an HTTP `$this->get('/admin/...')->assertSuccessful()`

None of these fail sequentially. All have failed at least once in
parallel runs of this same repro.

## What does NOT fix it

| Tried | Result |
|---|---|
| `flaky(tries: 3)` from Pest 4.5 | Reduces but doesn't eliminate |
| `flaky(tries: 5)` | Marginal further help |
| `--processes=8` instead of 16 | Same rate — not CPU-bound |
| `filament()->bootCurrentPanel()` after `setTenant()` | **Makes it worse** (deterministic 364 failures in larger projects — see `setTenant`/Livewire-resolver interaction) |

## What partially helps (real root-cause mitigations)

These are applied in this repro's `tests/bootstrap.php` and `tests/TestCase.php`.
In the real ~2100-test project they reduce ~17 failures/run to ~1–3 sporadic
failures/run — meaningful but not 100%.

### 1. Per-worker compiled-blade + service-cache paths

By default ParaTest workers all share `storage/framework/views/` and
`bootstrap/cache/{services,packages}.php`. When two workers write the
same compiled-blade file simultaneously, one reads partial bytes →
`Invalid Livewire snapshot structure`.

Fix in `tests/bootstrap.php` (runs before Laravel boots, per worker):
```php
if ($token = getenv('TEST_TOKEN')) {
    $base = __DIR__.'/../storage/framework/testing/worker_'.$token;
    foreach (['views', 'cache', 'sessions'] as $sub) {
        is_dir($base.'/'.$sub) || mkdir($base.'/'.$sub, 0755, true);
    }
    putenv('VIEW_COMPILED_PATH='.$base.'/views');
    putenv('APP_SERVICES_CACHE='.$base.'/services.php');
    putenv('APP_PACKAGES_CACHE='.$base.'/packages.php');
}
```

### 2. Per-test Filament + Spatie Permission resets

Spatie Permission has both a Laravel cache AND a static team-id — both
must be cleared between tests. Filament's Manager singleton retains
panel/tenant state between tests in the same worker.

```php
protected function tearDown(): void
{
    Livewire::flushState();

    if (app()->bound(PermissionRegistrar::class)) {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
    app()->forgetInstance(PermissionRegistrar::class);
    app()->forgetInstance('filament');
    if (app()->bound(\Livewire\Mechanisms\ComponentRegistry::class)) {
        app()->forgetInstance(\Livewire\Mechanisms\ComponentRegistry::class);
    }

    parent::tearDown();
}
```

## What still doesn't fix it 100%

Even with all of the above, the real codebase still hits ~1–3 failures
in ~30% of runs. The remaining races appear to be deeper inside Filament's
Schema-mounting and Livewire's snapshot construction — beyond reach from
test-harness code. **That's the question to the Filament team.**

## Open questions for the Filament team

1. Is there a documented Filament form/schema warm-up hook that should
   run in `beforeEach` to make the `TestsForms.php:30` race go away?
2. Is `livewire(Page::class)->instance()` expected to always return
   non-null post-mount under parallel ParaTest workers?
3. Where in the test lifecycle does `bootCurrentPanel()` actually belong
   — the static helper docstring isn't clear on whether `beforeEach` is
   correct, or if it must run inside each test post-mount.

Happy to extend this repro with additional examples if helpful.

---

**License:** MIT (use freely for repro / debugging purposes)
