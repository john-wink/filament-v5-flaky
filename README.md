# Filament + Pest Parallel Flakes — Minimal Repro

Reproduces intermittent failures when running Filament resource tests with
`./vendor/bin/pest --parallel`. See the Discord post at
[`../../filament-discord-parallel-flakes.md`](../../filament-discord-parallel-flakes.md)
for context.

## Setup

```bash
# 1. Create a fresh Laravel 13 project in a sibling directory
composer create-project laravel/laravel filament-flake-repro
cd filament-flake-repro

# 2. Install Filament, Spatie Permission, Pest 4.5
composer require filament/filament:^5.5
composer require spatie/laravel-permission:^7.3
composer require --dev pestphp/pest:^4.5 pestphp/pest-plugin-livewire:^4.0

# 3. Publish Spatie Permission config/migrations
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# 4. Install Pest + set up a Filament admin panel
php artisan pest:install
php artisan filament:install --panels
# Answer: panel ID = "admin"

# 5. Copy the files from THIS repro directory into the fresh project, preserving paths
cp -r app database tests bootstrap ../filament-flake-repro/
cp phpunit.xml ../filament-flake-repro/phpunit.xml

# 6. Run migrations in-memory (also runs per worker in tests)
php artisan migrate

# 7. Reproduce
./vendor/bin/pest                     # Sequential — should be 100% green
./vendor/bin/pest --parallel          # Parallel — hits the flakes
```

## Expected vs. actual

| Command | Expected | Actual |
|---|---|---|
| `./vendor/bin/pest` | all green, 10 times in a row | ✅ 100% stable |
| `./vendor/bin/pest --parallel` | all green | ❌ 1–3 intermittent failures per run in ~30% of runs |

## Observed failure patterns

Each of these has been hit in parallel runs of this exact repro, never the
same test twice in a row:

1. `Call to a member function getDefaultTestingSchemaName() on null`
   at `vendor/filament/forms/src/Testing/TestsForms.php:30`
2. `Call to a member function getTable() on null`
   on `livewire(...)->instance()->getTable()`
3. `InvalidArgumentException: Invalid Livewire snapshot structure`
   at `vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:210`
4. `HTTP 403` on `$this->get('/admin/widget')` despite correct role assignment

## Files in this repro

- `app/Models/User.php` — implements `FilamentUser`
- `app/Models/Widget.php` — tenant-scoped model
- `app/Models/Team.php` — tenant model
- `app/Filament/Admin/Resources/WidgetResource.php` — typical list+create+edit
- `app/Filament/Admin/Resources/WidgetResource/Pages/*.php`
- `app/Providers/Filament/AdminPanelProvider.php` — panel with tenancy
- `app/Policies/WidgetPolicy.php` — team-scoped policy
- `database/migrations/` — users, teams, widgets, spatie permission tables
- `database/factories/` — matching factories
- `tests/TestCase.php` — sets up a default user + permission team id
- `tests/Pest.php` — uses LazilyRefreshDatabase, TestCase
- `tests/Feature/WidgetResourceFlakeTest.php` — the flaky tests

## The critical test (copy-pasteable single file)

The test file `tests/Feature/WidgetResourceFlakeTest.php` contains ~30
simple tests that each hit one of the four failure patterns intermittently
when run with 16 parallel workers.

## How to debug further

Running 10 consecutive parallel runs to catch the flakes:

```bash
for i in {1..10}; do
  echo "=== Run $i ==="
  ./vendor/bin/pest --parallel 2>&1 | grep -E "Tests:|FAILED" | head -3
done
```

Typical output: 3–6 out of 10 runs have 1–3 failures. Different tests
each time.

## What does NOT help

- `flaky(tries: 3)` or `flaky(tries: 5)` — reduces but doesn't eliminate
- `--processes=8` instead of 16 — same rate
- Forgetting Spatie Permission cache in `beforeEach` — partial help
- Adding `filament()->bootCurrentPanel()` after `setTenant()` — **makes it
  worse** (deterministic failures, see the Discord post)

## System

- macOS (M1 Pro), PHP 8.5, Laravel 13.4, Filament 5.5, Livewire 4.2,
  Pest 4.5.0, ParaTest via `pestphp/pest-plugin-parallel`
- SQLite `:memory:` — ParaTest gives each worker a separate connection
