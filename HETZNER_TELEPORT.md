# InTheLoop — Hetzner + Teleport Deployment Checklist

First-install and update runbook for production on a **Hetzner VPS**, accessed with **Teleport (`tsh`)**.

| Role | Responsibility |
|------|----------------|
| **Hetzner** | Hosts the app 24/7 (Nginx, PHP, DB, workers) |
| **Teleport** | Secure SSH/deploy access only — not mail delivery |
| **Public HTTPS** | Required for the app, Microsoft SSO, and (later) Graph webhooks |

Related: [DEPLOYMENT.md](DEPLOYMENT.md) (Entra/Graph detail) · `scripts/teleport-first-install.sh` · `scripts/teleport-deploy.sh`

---

## 0. Before you start (fill these in)

| Item | Your value |
|------|------------|
| Teleport proxy | `teleport.________.com` |
| Teleport node / login | e.g. `deploy@intheloop` |
| Hetzner server IP / hostname | |
| Public app URL | `https://________` |
| Git repo URL | |
| Deploy path | `/var/www/intheloop` (recommended) |
| Web user | `www-data` (Debian/Ubuntu) |

---

## Phase A — Hetzner server prep

### A1. Create / access the VPS

- [ ] Create Hetzner Cloud CX/CPX (Ubuntu 22.04 or 24.04 LTS recommended)
- [ ] Note public IPv4; attach a firewall allowing **22** (or Teleport only), **80**, **443**
- [ ] Point DNS `A`/`AAAA` for your app hostname to the VPS

### A2. Enroll the host in Teleport

Have your Teleport admin:

- [ ] Join the server as a Teleport SSH node (or use Teleport agent)
- [ ] Grant your user a role that can `tsh ssh` as the deploy user
- [ ] Prefer **disabling password SSH** from the public internet once Teleport works

### A3. Verify Teleport access (from your laptop)

```bash
tsh login --proxy=teleport.YOURCOMPANY.com
tsh ls
tsh ssh deploy@intheloop
```

- [ ] You land in a shell on the Hetzner box

---

## Phase B — System packages (on the server via `tsh ssh`)

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server git unzip curl \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl
```

Adjust PHP version (`8.2` / `8.3`) to what the image provides. InTheLoop needs **PHP 8.2+**.

```bash
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node 20 (assets)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v && npm -v && php -v && composer -V
```

- [ ] PHP 8.2+, Composer, Node, Nginx, MySQL installed

### B1. Database

```bash
sudo mysql -e "CREATE DATABASE intheloop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'intheloop'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';"
sudo mysql -e "GRANT ALL PRIVILEGES ON intheloop.* TO 'intheloop'@'localhost'; FLUSH PRIVILEGES;"
```

- [ ] DB + user created; password saved in a password manager (not git)

---

## Phase C — First code deploy

### C1. Clone

```bash
export REPO_URL='https://github.com/YOUR_ORG/InTheLoop.git'   # or SSH URL
export APP_URL='https://your.production.domain'
export APP_DIR='/var/www/intheloop'
export BRANCH='main'

sudo mkdir -p /var/www
sudo git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
sudo chown -R "$USER":"$USER" "$APP_DIR"
cd "$APP_DIR"
```

### C2. Bootstrap

```bash
composer install --no-dev --optimize-autoloader --no-interaction
cp -n .env.example .env

# Set production basics (edit DB_* by hand)
sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env
sed -i "s|^INSTALLED=.*|INSTALLED=false|" .env
sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|" .env

# Put real DB credentials in .env
nano .env   # DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_HOST=127.0.0.1

php artisan key:generate --force
npm ci && npm run build

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
sudo usermod -aG www-data "$USER"   # so deploy user can pull/build
```

Or:

```bash
bash scripts/teleport-first-install.sh
# then edit DB_* in .env
```

- [ ] `vendor/` present  
- [ ] `public/build/manifest.json` present  
- [ ] `APP_KEY=base64:...` in `.env`  
- [ ] `APP_URL` is the **public HTTPS** hostname (not localhost)

---

## Phase D — Nginx + TLS

### D1. Site config

`/etc/nginx/sites-available/intheloop`:

```nginx
server {
    listen 80;
    server_name your.production.domain;
    root /var/www/intheloop/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;  # match your PHP version
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -sf /etc/nginx/sites-available/intheloop /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

- [ ] Document root is **`/var/www/intheloop/public`** (not the repo root)

### D2. Let’s Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your.production.domain
```

- [ ] HTTPS works  
- [ ] HTTP redirects to HTTPS  

---

## Phase E — Install wizard (browser)

1. Open `https://your.production.domain/install`
2. Requirements → Database (use the MySQL user from B1) → Application URL (**exact** public URL) → Admin account
3. Confirm you land in the app as admin

- [ ] Wizard completed  
- [ ] `INSTALLED=true` / `storage/app/installed` exists  
- [ ] Login page loads with branding  

If `/install` 404s: document root is wrong, or rewrite not hitting `public/index.php`.

If redirects go to `localhost`: fix `APP_URL` and run `php artisan config:clear`.

---

## Phase F — Background workers (required)

### F1. Queue worker (systemd)

`/etc/systemd/system/intheloop-queue.service`:

```ini
[Unit]
Description=InTheLoop queue worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=/var/www/intheloop
ExecStart=/usr/bin/php /var/www/intheloop/artisan queue:work --queue=mail,default,sync --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now intheloop-queue
sudo systemctl status intheloop-queue
```

- [ ] Worker active; emails leave “Pending”

### F2. Scheduler (cron or systemd timer)

```bash
sudo crontab -u www-data -e
```

Add:

```cron
* * * * * cd /var/www/intheloop && php artisan schedule:run >> /dev/null 2>&1
```

This drives:

- Mailbox sync (~every 3 minutes)  
- Directory sync (hourly)  

- [ ] Cron installed for `www-data`

### F3. After every code deploy

```bash
php artisan queue:restart
```

---

## Phase G — Microsoft Graph + SSO

Follow [DEPLOYMENT.md](DEPLOYMENT.md) §3, then in the app **Settings → Microsoft**:

- [ ] Entra app registration (single tenant)  
- [ ] Application permissions: `Mail.Send`, `Mail.Read`, `User.Read.All` + admin consent  
- [ ] Delegated: `openid`, `profile`, `email`, `User.Read` (SSO)  
- [ ] Redirect URI: `https://your.production.domain/auth/microsoft/callback`  
- [ ] Application Access Policy scoped to shared mailboxes  
- [ ] Credentials saved in InTheLoop Microsoft settings  

On the server:

```bash
cd /var/www/intheloop
php artisan graph:test
php artisan directory:sync
```

- [ ] `graph:test` passes  
- [ ] Directory contacts > 0  
- [ ] Test: compose email → status **Sent** → Outlook receive → reply appears in thread  

---

## Phase H — Hardening checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`  
- [ ] `.env` not in git; permissions restricted (`chmod 640 .env`)  
- [ ] Hetzner firewall: only 80/443 public; SSH via Teleport  
- [ ] Fail2ban / unattended upgrades (optional but recommended)  
- [ ] DB backups scheduled (Hetzner snapshots + `mysqldump`)  
- [ ] `storage/app` (attachments) included in backups  
- [ ] Teleport roles limited to deploy/ops users  

---

## Update deploy (existing install)

From laptop:

```bash
tsh ssh deploy@intheloop
```

On server:

```bash
cd /var/www/intheloop
bash scripts/teleport-deploy.sh
# or:
# git pull && composer install --no-dev --optimize-autoloader
# npm ci && npm run build
# php artisan migrate --force
# php artisan optimize:clear && php artisan config:cache
# php artisan queue:restart
```

- [ ] Smoke: login, compose, `graph:test`

Useful one-liners:

```bash
tsh ssh deploy@intheloop "cd /var/www/intheloop && php artisan graph:test"
tsh scp deploy@intheloop:/var/www/intheloop/storage/logs/laravel.log ./laravel.log
```

---

## Architecture (target)

```
[Staff browsers] ──HTTPS──► [Hetzner: Nginx → public/ → PHP-FPM → Laravel]
                                      │
                                      ├── MySQL
                                      ├── systemd: queue:work (mail,default,sync)
                                      └── cron: schedule:run (sync + directory)

[You / CI] ──Teleport tsh──► SSH shell on Hetzner (deploy only)

[Microsoft Graph] ◄── app-only token ── Laravel (send + poll/webhook)
[Entra ID]        ◄── SSO redirect ── /auth/microsoft/callback
```

**Phase 1 (this checklist):** permanent queue worker + scheduled mailbox poll.  
**Phase 2 (optional):** Graph change-notification webhooks for near-real-time receive; keep a small renew job on the scheduler.

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `vendor/autoload.php` missing | `composer install --no-dev` on the server |
| Missing `APP_KEY` | `php artisan key:generate --force` + `config:clear` |
| Redirects to localhost | Set `APP_URL=https://your.domain` + `php artisan config:clear` |
| `/install` 404 | Nginx `root` must be `…/public` |
| Email stuck Pending | `systemctl status intheloop-queue` / start worker |
| Graph 403 | Application Access Policy + admin consent; `php artisan graph:test` |
| Icons/fonts 404 | Rebuild on server: `npm ci && npm run build` (Vite base follows `APP_URL`) |

Logs: `/var/www/intheloop/storage/logs/laravel.log`

---

## Quick copy-paste summary

```bash
# Laptop
tsh login --proxy=teleport.YOURCOMPANY.com
tsh ssh deploy@intheloop

# Server — first install
cd /var/www/intheloop   # after clone
composer install --no-dev --optimize-autoloader
cp -n .env.example .env && nano .env   # APP_URL + DB_*
php artisan key:generate --force
npm ci && npm run build
# → configure Nginx root=public + certbot
# → browser /install
sudo systemctl enable --now intheloop-queue
# → cron schedule:run
php artisan graph:test && php artisan directory:sync

# Server — updates
bash scripts/teleport-deploy.sh
```
