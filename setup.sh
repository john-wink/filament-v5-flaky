#!/usr/bin/env bash
# Sets up a fresh Laravel project + applies the repro files.
# Usage:  bash setup.sh [target-dir]
#         (default target-dir: ../filament-flake-repro)

set -euo pipefail

TARGET="${1:-../filament-flake-repro}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "==> Creating Laravel project in $TARGET"
composer create-project laravel/laravel "$TARGET" --quiet

cd "$TARGET"

echo "==> Installing Filament, Spatie Permission, Pest 4.5"
composer require filament/filament:^5.5 --no-interaction --quiet
composer require spatie/laravel-permission:^7.3 --no-interaction --quiet
composer require --dev pestphp/pest:^4.5 pestphp/pest-plugin-livewire:^4.0 \
    --no-interaction --quiet

echo "==> Publishing Spatie Permission migration"
php artisan vendor:publish \
    --provider="Spatie\Permission\PermissionServiceProvider" \
    --tag="permission-migrations"

echo "==> Installing Pest"
php artisan pest:install --no-interaction || true

echo "==> Installing Filament admin panel (id: admin)"
php artisan filament:install --panels --no-interaction <<< "admin"

echo "==> Copying repro files"
cp -R "$SCRIPT_DIR/app/." app/
cp -R "$SCRIPT_DIR/database/." database/
cp -R "$SCRIPT_DIR/tests/." tests/
cp "$SCRIPT_DIR/phpunit.xml" phpunit.xml

echo "==> Running migrations"
php artisan migrate --no-interaction

echo
echo "==> Setup complete. Reproduce with:"
echo "   cd $TARGET"
echo "   ./vendor/bin/pest                  # 100% green"
echo "   ./vendor/bin/pest --parallel       # 1-3 sporadic failures, ~30% of runs"
echo
echo "Catch flakes over multiple runs:"
echo "   for i in {1..10}; do ./vendor/bin/pest --parallel 2>&1 | grep Tests:; done"
