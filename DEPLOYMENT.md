# InTheLoop — Installation & Deployment Guide

This guide walks through a **new install** from empty server to a working production deployment with Microsoft Graph email, SSO, and background jobs.

---

## 1. What you are deploying

InTheLoop is an internal reporting app for staff who use **shared mailboxes** (no individual M365 licenses required for send/receive via Graph).

| Area | What it does |
|------|----------------|
| **Emails** | Staff submit emails; mail goes to To/Cc via Graph; replies sync into a thread |
| **Announcements** | Group/distribution-list mail captured separately (not mixed with emails) |
| **Directory** | To/Cc picker searches Azure AD contacts (`directory:sync`) |
| **SSO** | Optional Microsoft sign-in (Entra ID) |
| **Admin** | Users, recipients, categories, branding, Microsoft settings |

**Background jobs are required.** Without a queue worker, emails will sit in “pending” and mail will not send.

---

## 2. Server requirements

| Requirement | Notes |
|-------------|--------|
| **PHP 8.2+** | Extensions: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo` |
| **Database** | MySQL/MariaDB or PostgreSQL (SQLite OK for local dev only) |
| **Composer** | PHP dependency manager |
| **Node.js 18+** | Build frontend assets (`npm run build`) |
| **Web server** | Apache or Nginx |
| **HTTPS** | Required in production for SSO and secure cookies |
| **Cron** | For Laravel scheduler (mail + directory sync) |
| **Long-running process** | Queue worker for email send/sync jobs |

**Writable paths:** `storage/`, `bootstrap/cache/`, and `.env` (or project root if `.env` will be created).

---

## 3. Pre-install: Microsoft Entra (recommended order)

Do this **before** or **in parallel** with the app install so Graph works immediately after configuration.

### 3.1 App registration

1. **Entra ID → App registrations → New registration**
2. Name: e.g. `InTheLoop`
3. Supported account types: **Single tenant** (your organization only)
4. Redirect URI (Web): `{APP_URL}/auth/microsoft/callback`  
   Example: `https://reports.yourcompany.com/auth/microsoft/callback`

### 3.2 Application permissions (Graph mail + directory)

**API permissions → Microsoft Graph → Application permissions:**

| Permission | Purpose |
|------------|---------|
| `Mail.Send` | Send emails and replies |
| `Mail.Read` | Poll inboxes/sent items for sync |
| `User.Read.All` | Directory sync + To/Cc typeahead |

Click **Grant admin consent**.

### 3.3 Delegated permissions (SSO login)

**Microsoft Graph → Delegated:**

| Permission | Purpose |
|------------|---------|
| `openid`, `profile`, `email`, `User.Read` | Microsoft sign-in |

Grant admin consent if prompted.

### 3.4 Client secret

**Certificates & secrets → New client secret** — copy the **Value** immediately (shown once).  
You will enter **Tenant ID**, **Client ID**, and this secret in InTheLoop.

### 3.5 Application Access Policy (Exchange Online)

Graph can only access mailboxes you allow. In **Exchange Online PowerShell**:

```powershell
# Create a mail-enabled security group containing your shared mailboxes + group addresses
New-DistributionGroup -Name "InTheLoop Graph Mailboxes" -Type Security

# Link the Entra app to the policy (use your app's AppId / Client ID)
New-ApplicationAccessPolicy -AppId "<GRAPH_CLIENT_ID>" -PolicyScopeGroupId "<GROUP_ID>" -AccessRight RestrictAccess

# Enable sent-item copy for shared mailbox send (per mailbox)
Set-Mailbox user@company.com -MessageCopyForSentAsEnabled $true
```

Repeat `Set-Mailbox` for each shared mailbox staff send from.

### 3.6 Restrict SSO to a group (optional)

**Enterprise applications → InTheLoop → Properties:**

- Set **Assignment required?** → **Yes**
- **Users and groups** → assign only staff who should log in

SSO auto-creates app users on first login; group assignment controls who can reach the app at all.

---

## 4. Deploy the application files

### 4.1 Clone / upload code

```bash
cd /var/www
git clone <your-repo-url> intheloop
cd intheloop
```

### 4.2 Install PHP dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 4.3 Environment file

```bash
cp .env.example .env
```

Edit `.env` for production basics (install wizard will also write DB settings):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://reports.yourcompany.com
INSTALLED=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=intheloop
DB_USERNAME=intheloop
DB_PASSWORD=<strong-password>

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
```

Generate app key if not done by installer:

```bash
php artisan key:generate
```

### 4.4 Build frontend assets

```bash
npm ci
npm run build
```

### 4.5 Web server document root

**Production (recommended):** point the vhost **document root** at `public/`.

**Apache example:**

```apache
DocumentRoot /var/www/intheloop/public
<Directory /var/www/intheloop/public>
    AllowOverride All
    Require all granted
</Directory>
```

**Nginx example:**

```nginx
root /var/www/intheloop/public;
index index.php;
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```

**XAMPP / subdirectory (dev only):** the repo includes root `index.php` + `.htaccess` so `http://localhost/InTheLoop` works without changing the vhost. Set `APP_URL` to match.

### 4.6 Permissions (Linux)

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
```

---

## 5. Run the install wizard

1. Open `{APP_URL}/install` in a browser.
2. **Step 1 — Requirements:** confirms PHP, extensions, writable folders.
3. **Step 2 — Database:** driver, host, database name, credentials. Connection is tested before continuing.
4. **Step 3 — Application:** organization name and **exact public URL** (must match SSO redirect URI base).
5. **Step 4 — Administrator:** first admin name, email, password.

On success:

- Migrations run automatically
- `.env` is updated (`INSTALLED=true`, session/cache → database)
- You are logged in as admin
- Lock file created: `storage/app/installed`

---

## 6. Post-install configuration (admin walkthrough)

Sign in as admin and complete these in order.

### 6.1 App Settings (`/settings/app`)

- Organization name, logo, accent color  
- Used across login page and authenticated UI

### 6.2 Microsoft Integration (`/settings/microsoft`)

| Field | Purpose |
|-------|---------|
| **Graph Tenant / Client ID / Secret** | App-only mail + directory (from §3) |
| **Default Sender Mailbox** | Fallback From address for Graph send |
| **Additional Monitored Mailboxes** | Extra shared mailboxes to poll for email replies |
| **Announcement / Group Mailboxes** | Distribution lists; unmatched inbox mail → **Announcements** view |
| **SSO Tenant / Client ID / Secret** | Often same app as Graph; can differ |
| **Enable Microsoft sign-in** | Shows “Sign in with Microsoft” on login page |

Save settings, then verify from the server:

```bash
php artisan graph:test
```

Expected: token OK, directory read OK, mailbox read OK for default sender, Mail.Send test if configured.

### 6.3 Directory sync

Initial population of To/Cc picker:

```bash
php artisan directory:sync
```

Runs automatically **hourly** via scheduler once cron is configured. First login can also trigger a background sync when the cache is empty.

### 6.4 Users (`/users`)

| Field | Notes |
|-------|--------|
| **Email** | Login address (local or SSO) |
| **Shared mailbox email** | Address Graph sends from; auto-filled from Azure primary mail on first SSO login |
| **Admin / Approver** | Role flags |
| **Active** | Inactive users cannot sign in |

For SSO-only staff: add them here **or** rely on auto-provision on first Microsoft login (if allowed by Entra group assignment).

### 6.5 Recipients (`/recipients`)

Shared mailboxes used for **routing** and default To targets. Their addresses are included in mailbox polling automatically.

### 6.6 Categories (`/categories`) *(optional)*

Emails default to category **General** if none is selected on the form. Categories still support routing rules if you use them.

---

## 7. Background processes (required)

### 7.1 Queue worker

Email send, reply sync, directory sync jobs use the database queue.

**Production — systemd example** `/etc/systemd/system/intheloop-queue.service`:

```ini
[Unit]
Description=InTheLoop Queue Worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/intheloop/artisan queue:work --queue=mail,default,sync --sleep=3 --tries=3 --max-time=3600
WorkingDirectory=/var/www/intheloop

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now intheloop-queue
```

**Why `--queue=mail,default,sync`?** The `mail` queue is checked first so outbound email is not blocked by long mailbox sync jobs.

**Development (XAMPP):**

```bash
php artisan queue:work --queue=mail,default,sync
```

After code or `.env` changes:

```bash
php artisan queue:restart
```

### 7.2 Scheduler (cron)

Add to crontab for the web user:

```cron
* * * * * cd /var/www/intheloop && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks:

| Task | Interval | Purpose |
|------|----------|---------|
| `directory:sync` | Hourly | Refresh Azure AD contacts for To/Cc picker |
| `SyncGraphMailboxes` job | Every 3 minutes | Poll monitored + announcement mailboxes |

Local testing:

```bash
php artisan schedule:work
```

---

## 8. End-to-end verification checklist

Use this after deployment to confirm the full loop works.

- [ ] Install wizard completed; admin can log in at `/login`
- [ ] `php artisan graph:test` passes
- [ ] Queue worker running (`mail,default,sync`)
- [ ] Cron / scheduler active
- [ ] `php artisan directory:sync` returns contact count > 0
- [ ] **New email:** `/emails/create` → pick To from directory → submit
- [ ] Email status moves to **Sent** (queue processed)
- [ ] Recipient receives email in Outlook
- [ ] Reply from Outlook appears in email thread within ~3–5 minutes
- [ ] **Announcements:** send test mail to a configured group address → appears under `/announcements` (not Emails)
- [ ] **SSO:** Microsoft login works for a user in the assigned Entra group
- [ ] Shared mailbox on user profile matches their primary/send address

---

## 9. Environment variables reference

Can be set in `.env` or overridden in **Settings → Microsoft** (secrets stored encrypted in DB).

```env
# Application
APP_NAME=InTheLoop
APP_ENV=production
APP_DEBUG=false
APP_URL=https://reports.yourcompany.com
INSTALLED=true

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=intheloop
DB_USERNAME=
DB_PASSWORD=

# Queue / session (set by installer)
QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

# Microsoft Graph (app-only)
GRAPH_TENANT_ID=
GRAPH_CLIENT_ID=
GRAPH_CLIENT_SECRET=
GRAPH_DEFAULT_SENDER_MAILBOX=outreach@company.com
GRAPH_MONITORED_MAILBOXES=mailbox1@company.com,mailbox2@company.com
GRAPH_ANNOUNCEMENT_MAILBOXES=allstaff@company.com

# Microsoft SSO (delegated)
MICROSOFT_TENANT_ID=
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
MICROSOFT_REDIRECT_URI="${APP_URL}/auth/microsoft/callback"
```

---

## 10. Troubleshooting

| Symptom | Likely cause | Action |
|---------|----------------|--------|
| Email stuck on Pending | Queue worker not running | Start `queue:work --queue=mail,default,sync` |
| Email never sends | Graph 403 / policy | Run `graph:test`; fix Application Access Policy |
| Replies not appearing | Sync not running | Check scheduler + sync queue; verify mailbox in monitored list |
| To/Cc picker empty | Directory not synced | `php artisan directory:sync` |
| SSO “not authorized” | User not in Entra group | Assign enterprise app or pre-create user in `/users` |
| SSO works but wrong send address | Primary mail not in Azure | Check user’s **Shared mailbox email** in profile |
| Announcements empty | Wrong mailbox type | Use **Announcement / Group Mailboxes** setting, not only monitored |
| 500 after deploy | Permissions / missing key | `storage` writable; `php artisan key:generate` |
| Assets unstyled | Vite not built | `npm run build` |

**Logs:** `storage/logs/laravel.log`

**Debug Graph sync:** `php artisan graph:sync-mail` (manual poll)

---

## 11. Re-installing

1. Back up database if needed.
2. Delete `storage/app/installed`
3. Set `INSTALLED=false` in `.env`
4. Drop/recreate database or `php artisan migrate:fresh` (destroys data)
5. Visit `/install` again

---

## 12. Production hardening checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] HTTPS only; `APP_URL` uses `https://`
- [ ] Strong DB password; dedicated DB user with minimal privileges
- [ ] Queue worker managed by systemd/supervisor with restart
- [ ] Cron for scheduler
- [ ] Entra: single tenant, SSO group assignment, admin consent granted
- [ ] Application Access Policy scoped to required mailboxes only
- [ ] Regular backups (database + `storage/app` uploads)
- [ ] Keep `composer install --no-dev` on deploy; run migrations after updates

---

## 13. Deploy via Teleport (`tsh`)

Teleport is the **access path** to the server (SSH / session), not a hosting platform. Typical flow: login → SSH into the host → pull/build/migrate → restart queue worker.

### 13.1 One-time: client login

```bash
# Replace with your org's Teleport proxy
tsh login --proxy=teleport.yourcompany.com --user=you@yourcompany.com

# Confirm you can see the app host
tsh ls
```

### 13.2 Open a session on the app server

```bash
tsh ssh deploy@intheloop-prod
# or: tsh ssh --login=deploy intheloop-prod
```

Use the username and node name your Teleport admin assigned (examples: `ubuntu@web-01`, `www-data@intheloop`).

### 13.3 First deploy (empty host) — recommended path

On your laptop:

```bash
tsh login --proxy=teleport.yourcompany.com
tsh ls
tsh ssh deploy@intheloop-prod
```

On the remote host (Linux assumed):

```bash
# Install OS packages if missing: php 8.2+, composer, node 18+, git, mysql client
export REPO_URL='https://github.com/YOUR_ORG/InTheLoop.git'   # your real repo URL
export APP_URL='https://intheloop.yourcompany.com'            # real public HTTPS URL
export APP_DIR='/var/www/intheloop'                           # optional override
export BRANCH='main'

bash -c 'curl -fsSL https://raw.githubusercontent.com/YOUR_ORG/InTheLoop/main/scripts/teleport-first-install.sh | bash'
# Or if the repo is already cloned:
#   cd /var/www/intheloop && bash scripts/teleport-first-install.sh
```

If git clone must use Teleport/SSH deploy keys, clone first, then run the script from the app dir.

**After the script finishes**, complete these in order:

| Step | Action |
|------|--------|
| 1 | Create DB + put `DB_*` in `.env` |
| 2 | Vhost / IIS site → document root = `…/public` |
| 3 | Browser → `{APP_URL}/install` (wizard: requirements → DB → org → admin) |
| 4 | systemd queue worker: `queue:work --queue=mail,default,sync` |
| 5 | Cron: `* * * * * cd /var/www/intheloop && php artisan schedule:run` |
| 6 | Settings → Microsoft (Graph + SSO); Entra redirect = `{APP_URL}/auth/microsoft/callback` |
| 7 | `php artisan graph:test` then `php artisan directory:sync` |

Full detail: §4–§8 above. Update deploys later: §13.4 / `scripts/teleport-deploy.sh`.

### 13.3b First deploy (manual, no script)

On the remote host (same steps as §4–§7):

```bash
cd /var/www
sudo git clone <your-repo-url> intheloop
cd intheloop

composer install --no-dev --optimize-autoloader
cp .env.example .env
# Edit .env: APP_URL, DB_*, QUEUE_CONNECTION=database, APP_ENV=production, APP_DEBUG=false
php artisan key:generate

npm ci && npm run build
# Do NOT migrate yet if using the /install wizard — the wizard runs migrations.
# If skipping the wizard: php artisan migrate --force

# Permissions (Linux)
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

Point the web server document root at `public/`, then open `/install` (or finish `.env` + mark installed).

Enable queue worker + cron (§7). Verify with `php artisan graph:test`.

### 13.4 Update deploy (existing install)

From your laptop (optional: copy only — usually git pull on the server is enough):

```bash
# Optional: push a release tag locally, then on the server:
tsh ssh deploy@intheloop-prod
```

On the server:

```bash
cd /var/www/intheloop

git fetch --all
git checkout main   # or a release tag
git pull

composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan queue:restart
```

Or run the helper script (Linux):

```bash
bash scripts/teleport-deploy.sh
```

### 13.5 Useful Teleport helpers

```bash
# Copy a built artifact or .env template up (prefer editing secrets on the host)
tsh scp .env.example deploy@intheloop-prod:/var/www/intheloop/.env.example

# Download a log for debugging
tsh scp deploy@intheloop-prod:/var/www/intheloop/storage/logs/laravel.log ./laravel.log

# One-shot remote command without an interactive shell
tsh ssh deploy@intheloop-prod "cd /var/www/intheloop && php artisan graph:test"
```

### 13.6 Production notes with Teleport

| Topic | Recommendation |
|-------|----------------|
| Secrets | Keep `.env` on the server only; never commit it. Prefer Teleport Secrets / vault if your org uses them. |
| Queue worker | systemd unit from §7 — Teleport access does not keep jobs running. |
| Subdirectory installs | If `APP_URL` is `https://host/intheloop`, rebuild assets on that host so Vite `base` matches (`npm run build`). |
| SSO redirect | Entra redirect URI must match production `APP_URL` exactly. |
| Access control | Restrict Teleport roles to deploy users; prefer audit-friendly `tsh ssh` over open SSH keys. |

### 13.7 What Teleport does *not* replace

- Web server / PHP-FPM / MySQL setup  
- HTTPS certificates  
- Entra app registration + Application Access Policy  
- Long-running `queue:work` and cron `schedule:run`  

Those still follow §2–§7 after you land on the host via `tsh`.

---

## Quick reference commands

```bash
# Install / update
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force

# Operations
php artisan queue:work --queue=mail,default,sync
php artisan queue:restart
php artisan schedule:run
php artisan directory:sync
php artisan graph:test
php artisan graph:sync-mail

# Cache (after settings changes)
php artisan config:clear
php artisan cache:clear

# Teleport
tsh login --proxy=teleport.yourcompany.com
tsh ls
tsh ssh deploy@intheloop-prod
```

For day-to-day build status and feature list, see [checklist.md](checklist.md).
