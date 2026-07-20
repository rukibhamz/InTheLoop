#!/usr/bin/env bash
# InTheLoop update deploy — run on the app host after: tsh ssh <user>@<node>
# Usage: bash scripts/teleport-deploy.sh [git-ref]
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/intheloop}"
REF="${1:-main}"

cd "$APP_DIR"

echo "==> Fetching $REF"
git fetch --all
git checkout "$REF"
git pull --ff-only origin "$REF" 2>/dev/null || git pull --ff-only

echo "==> PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Frontend assets"
if command -v npm >/dev/null 2>&1; then
  npm ci
  npm run build
else
  echo "npm not found — skipping asset build (ensure public/build is present)"
fi

echo "==> Migrate + cache"
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Restart queue workers"
php artisan queue:restart

echo "==> Done. Smoke-check: php artisan graph:test"
