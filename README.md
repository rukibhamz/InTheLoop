# InTheLoop

Internal communication tool for license-less staff — report submission, email threading via Microsoft Graph, and approval workflows.

## Requirements

- PHP 8.2+
- MySQL or PostgreSQL (SQLite supported for local development)
- Composer

## Local setup

```bash
composer install --no-dev
cp .env.example .env
php artisan serve
```

### XAMPP (Apache)

The repo includes a root `index.php` and `.htaccess` so Apache can serve from `htdocs/InTheLoop` without reconfiguring the vhost. Visit:

`http://localhost/InTheLoop`

Set `APP_URL` during install to match (e.g. `http://localhost/InTheLoop`).

For production, point the vhost **document root** at `public/` instead and remove reliance on the root entry file.

### Artisan dev server

Open `http://127.0.0.1:8000` — the **install wizard** will guide you through:

1. Server requirements check
2. Database configuration (written to `.env`)
3. Application name and URL
4. Administrator account creation

After installation, sign in at `/login`. Track build progress in [checklist.md](checklist.md).

### Microsoft Graph & SSO

Configure in **App Settings** (`/settings/app`) or via `.env`:

| Purpose | Env vars |
|---------|----------|
| Outbound/inbound mail + directory sync | `GRAPH_TENANT_ID`, `GRAPH_CLIENT_ID`, `GRAPH_CLIENT_SECRET`, `GRAPH_DEFAULT_SENDER_MAILBOX`, `GRAPH_MONITORED_MAILBOXES` |
| Microsoft sign-in (SSO) | `MICROSOFT_TENANT_ID`, `MICROSOFT_CLIENT_ID`, `MICROSOFT_CLIENT_SECRET` |

**Entra app permissions (application):** `Mail.Send`, `Mail.Read`, `User.Read.All` — plus admin consent and an Application Access Policy scoped to your shared mailboxes.

**Entra app permissions (delegated, SSO):** `openid`, `profile`, `email`, `User.Read`

Redirect URI: `{APP_URL}/auth/microsoft/callback`

Verify Graph credentials:

```bash
php artisan graph:test
```

## Post-install

Run a queue worker (required for report email, replies, and status notifications):

```bash
php artisan queue:work
```

Run the scheduler via cron (directory sync hourly, mail sync every 3 minutes):

```bash
* * * * * php /path/to/artisan schedule:run
```

Scheduled commands:

- `php artisan directory:sync` — Azure AD contacts → local typeahead
- `php artisan graph:sync-mail` — inbound reply polling via Graph delta queries

## Re-installing

Delete `storage/app/installed`, set `INSTALLED=false` in `.env`, and reset your database.
