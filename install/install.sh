#!/usr/bin/env bash
# The Cure Medicall Hall - one-shot installer.
#
# Usage:   bash install/install.sh /path/to/new-project
# Needs:   PHP 8.2+, Composer 2, MySQL 8 (or MariaDB 10.6+), PHP extensions: gd, mbstring, pdo_mysql, intl, zip, fileinfo.
#
# It creates a fresh Laravel 10 app, installs the two Spatie packages, copies this overlay on top,
# patches config/app.php and app/Http/Kernel.php, then migrates and seeds.
set -euo pipefail

TARGET="${1:-}"
if [[ -z "$TARGET" ]]; then echo "Usage: bash install/install.sh /path/to/new-project"; exit 1; fi
OVERLAY="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ -e "$TARGET" ]]; then echo "Target $TARGET already exists. Choose a new folder."; exit 1; fi

echo "==> Creating Laravel 10 application"
composer create-project "laravel/laravel:^10.0" "$TARGET" --no-interaction
cd "$TARGET"

echo "==> Installing packages"
composer require "spatie/laravel-medialibrary:^10.15" "spatie/laravel-permission:^6.0" --no-interaction

echo "==> Copying application files"
for d in app config database resources routes public tests; do
  mkdir -p "$d"
  cp -R "$OVERLAY/$d/." "$d/"
done
cp "$OVERLAY/.env.cure" ./.env.cure
cp "$OVERLAY/phpunit.xml" ./phpunit.xml   # SQLite in-memory settings for the feature tests

echo "==> Publishing package config and migrations"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations" --no-interaction
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="config" --no-interaction
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction

echo "==> Patching framework files"
php "$OVERLAY/install/patch-laravel.php" "$PWD"

echo "==> Environment"
if ! grep -q "CURE_ADMIN_EMAIL" .env; then cat .env.cure >> .env; fi
php artisan key:generate --force
echo
echo "Edit .env now: set DB_DATABASE / DB_USERNAME / DB_PASSWORD, APP_URL and CURE_ADMIN_EMAIL."
read -r -p "Press Enter when the database settings are saved... " _

echo "==> Migrating and seeding"
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link || true
php artisan optimize:clear

echo
echo "Done. Start the server with: php artisan serve"
echo "Public site : $(grep '^APP_URL=' .env | cut -d= -f2)/"
echo "Admin panel : $(grep '^APP_URL=' .env | cut -d= -f2)/admin"
echo "Add this cron entry for expiry alerts:  * * * * * cd $PWD && php artisan schedule:run >> /dev/null 2>&1"
