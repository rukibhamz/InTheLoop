#!/usr/bin/env bash
# InTheLoop — first install on a clean host (after: tsh ssh <user>@<node>)
# Usage:
#   export REPO_URL='https://github.com/org/InTheLoop.git'
#   export APP_URL='https://intheloop.yourcompany.com'
#   bash scripts/teleport-first-install.sh
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/intheloop}"
WEB_USER="${WEB_USER:-www-data}"
REPO_URL="${REPO_URL:?Set REPO_URL to your git clone URL}"
APP_URL="${APP_URL:?Set APP_URL to the public HTTPS URL (no trailing slash)}"
BRANCH="${BRANCH:-main}"

echo "==> Creating app directory: $APP_DIR"
sudo mkdir -p "$(dirname "$APP_DIR")"
if [[ ! -d "$APP_DIR/.git" ]]; then
  sudo git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
fi
sudo chown -R "$USER":"$USER" "$APP_DIR"
cd "$APP_DIR"

echo "==> PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Environment"
if [[ ! -f .env ]]; then
  cp .env.example .env
fi

# Safe defaults for production first boot (DB still must be filled in)
php -r '
$env = file_get_contents(".env");
$replacements = [
  "/^APP_ENV=.*/m" => "APP_ENV=production",
  "/^APP_DEBUG=.*/m" => "APP_DEBUG=false",
  "/^APP_URL=.*/m" => "APP_URL=" . getenv("APP_URL"),
  "/^INSTALLED=.*/m" => "INSTALLED=false",
  "/^QUEUE_CONNECTION=.*/m" => "QUEUE_CONNECTION=database",
  "/^SESSION_DRIVER=.*/m" => "SESSION_DRIVER=database",
  "/^CACHE_STORE=.*/m" => "CACHE_STORE=database",
];
foreach ($replacements as $pattern => $value) {
  $env = preg_replace($pattern, $value, $env);
}
file_put_contents(".env", $env);
' 

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

echo "==> Frontend assets"
if command -v npm >/dev/null 2>&1; then
  npm ci
  npm run build
else
  echo "ERROR: npm is required for first install"; exit 1
fi

echo "==> Permissions"
sudo chown -R "$WEB_USER":"$WEB_USER" storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
# Keep deploy user able to pull/build
sudo usermod -aG "$WEB_USER" "$USER" 2>/dev/null || true

echo ""
echo "=============================================="
echo " Code is on the server. Finish these manually:"
echo "=============================================="
echo "1. Create MySQL/Postgres database + user"
echo "2. Put DB_* values in $APP_DIR/.env"
echo "3. Point the web vhost document root to: $APP_DIR/public"
echo "4. Open in browser: ${APP_URL}/install"
echo "5. Complete the install wizard (DB test → org → admin)"
echo "6. Enable queue worker + cron (see DEPLOYMENT.md §7)"
echo "7. Configure Microsoft Graph/SSO in Settings → Microsoft"
echo "8. Run: php artisan graph:test && php artisan directory:sync"
echo ""
echo "Queue worker (systemd example path in DEPLOYMENT.md):"
echo "  php artisan queue:work --queue=mail,default,sync"
echo "Cron:"
echo "  * * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
echo ""
