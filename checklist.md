# InTheLoop — Build Checklist

Living progress tracker for the InTheLoop system. Aligned with [SKILL.md](SKILL.md) build order and [design.md](design.md) UI spec.

**Legend:** ✅ Done · 🟡 Partial · ⬜ Not started · 🎨 Awaiting design mockup

**Last updated:** 2026-07-01

---

## Phase 0 — Project foundation

| Item | Status | Notes |
|------|--------|-------|
| Laravel 12 scaffold | ✅ | PHP 8.2+ |
| XAMPP subdirectory routing (`/InTheLoop`) | ✅ | Root `index.php` + `.htaccess` |
| Vite + Tailwind v4 design tokens | ✅ | `resources/css/app.css` |
| Install wizard (DB + admin) | ✅ | `/install` — writes `.env` on finish |
| `README.md` setup docs | ✅ | |
| `checklist.md` (this file) | ✅ | |

---

## Phase 1 — Auth (SSO primary + local fallback)

| Item | Status | Notes |
|------|--------|-------|
| Local login (email + password) | ✅ | `AuthenticatedSessionController` |
| Login page UI | ✅ | Matches provided mockup |
| Microsoft SSO (Socialite) | ✅ | `laravel/socialite` + `socialiteproviders/microsoft`; enable in App Settings |
| SSO auto-link users by `azure_object_id` | ✅ | In callback handler |
| Single-tenant SSO restriction | ⬜ | Configure in Entra portal |
| Roles: sender / approver / admin | ✅ | `is_approver`, `is_admin` on `users` |
| Password reset | ✅ | `/forgot-password` + `/reset-password/{token}` |
| **Design:** Login page | ✅ | Provided |

---

## Phase 2 — Core data layer

| Item | Status | Notes |
|------|--------|-------|
| `users` migration | ✅ | Includes `azure_object_id`, `auth_method`, roles |
| `recipients` migration | ✅ | Shared mailbox routing targets |
| `directory_contacts` migration | ✅ | |
| `report_categories` migration | ✅ | |
| `reports` migration | ✅ | |
| `report_participants` migration | ✅ | To + CC |
| `report_messages` migration | ✅ | Outbound + inbound threading |
| `attachments` migration | ✅ | Polymorphic |
| `report_events` migration | ✅ | Audit trail |
| `app_settings` migration | ✅ | Branding row |
| Integration settings columns | ✅ | Graph + SSO fields on `app_settings` |
| Eloquent models + relationships | ✅ | |
| Enums (`ReportStatus`, etc.) | ✅ | |
| Development seeder (categories + contacts) | ✅ | `DevelopmentSeeder` |
| `shared_mailbox_email` on users | ✅ | For Graph reply flow |

---

## Phase 3 — App shell & design system

| Item | Status | Notes |
|------|--------|-------|
| Authenticated layout (sidebar + header) | ✅ | `layouts/app.blade.php` |
| Account Settings (`/settings/account`) | ✅ | Profile, notifications, password, 2FA pref, deactivate |
| App Settings (`/settings/app`) | ✅ | Admin branding + logo upload |
| Recipients (`/recipients`) | ✅ | Admin table + stats |
| Categories (`/categories`) | ✅ | Admin category cards + stats |
| Guest layout | ✅ | `layouts/guest.blade.php` |
| Branding service (logo, accent, org name) | ✅ | `App\Services\Branding` |
| CSS component library | ✅ | Buttons, forms, badges, message bubbles |
| Alpine.js + directory picker | ✅ | Typeahead component |
| **Design:** Reports list | ✅ | Dashboard with stats cards + grid |
| **Design:** New report form | ✅ | Card form + upload zone + info banner |
| **Design:** Report thread view | ✅ | Conversation bubbles + reply composer shell |
| **Design:** Admin / settings | ✅ | Account + App settings |
| **Design:** Recipients | ✅ | Manage recipients table |
| **Design:** Categories | ✅ | Category cards grid |
| **Design:** User management | ✅ | Admin user table + form |
| Install wizard UI | 🟡 | Functional; polish when design provided |

---

## Phase 4 — Directory sync

| Item | Status | Notes |
|------|--------|-------|
| `User.Read.All` app permission (Entra) | ⬜ | Requires admin consent |
| `SyncDirectoryContacts` command | ✅ | `php artisan directory:sync` |
| Scheduled directory sync | ✅ | Hourly via `routes/console.php` |
| `GET /api/directory/search` typeahead | ✅ | Searches local `directory_contacts` |
| Free-text email fallback on To/CC | ✅ | Directory picker supports manual entry |
| Link synced contacts to `users` | ✅ | Directory sync links `azure_object_id` + department |

---

## Phase 5 — Report submission

| Item | Status | Notes |
|------|--------|-------|
| Reports list (`/reports`) | ✅ | Dashboard with stats + card grid |
| New report form (`/reports/create`) | ✅ | Card layout per mockup |
| `StoreReportRequest` validation | ✅ | Includes rate limit on store |
| DB persist before email | ✅ | Status `pending` |
| `SendReportEmail` queued job | ✅ | |
| File upload validation | ✅ | 25MB max; PDF/images/Office MIME types |
| **Design:** Submission form | 🎨 | Awaiting mockup |

---

## Phase 6 — Outbound email (GraphMailer)

| Item | Status | Notes |
|------|--------|-------|
| Entra app registration (mail permissions) | ⬜ | `Mail.Send`, `Mail.Read`, `User.Read.All` |
| Application Access Policy on mailboxes | ⬜ | Mandatory per SKILL |
| Shared mailboxes for senders | ⬜ | Exchange admin task |
| `GraphTokenService` (client credentials) | ✅ | Token cached ~58 min |
| `GraphMailer` service | ✅ | Falls back to mock when unconfigured |
| Outbound email Blade template | ✅ | `emails/report-submitted.blade.php` |
| Capture `conversation_id` on send | ✅ | Fetches from Sent Items when Graph live |
| Queue worker documented | ✅ | `php artisan queue:work --queue=mail,default,sync --queue=mail,default,sync` |
| Real test email (To + CC) | ⬜ | Blocked on Entra credentials |

---

## Phase 7 — Approval workflow

| Item | Status | Notes |
|------|--------|-------|
| Approval token generation on send | ✅ | Hashed token on `reports.approval_token_hash` |
| Signed approval URL in outbound email | ✅ | Link in email template |
| In-app Approve / Reject buttons | ✅ | On report thread page |
| Auth + approver role check on approval | ✅ | `ReportPolicy::approve` |
| `report_events` on approve/reject | ✅ | |
| Post-approval notifications | ✅ | `SendReportStatusNotification` job emails participants |
| **Design:** Approval confirmation | 🎨 | Optional mockup |

---

## Phase 8 — Inbound sync & threading

| Item | Status | Notes |
|------|--------|-------|
| `GraphMailSync` service | ✅ | Delta query + conversation matching |
| `graph:sync-mail` scheduled command | ✅ | Every 3 min when configured |
| Poll all unlicensed shared mailboxes | ✅ | Auto-aggregates user + recipient + configured mailboxes |
| Match inbound by `conversation_id` | ✅ | Updates status to `in_review` |
| Thread view (chronological messages) | ✅ | Bubbles + in-app reply composer |
| In-app reply composer | ✅ | In-app + Graph `replyAll` via `GraphReplySender` |
| "Copy not synced yet" edge case | ✅ | `email_pending` flag + retry job + UI banner |
| **Design:** Thread / reply UI | 🎨 | Awaiting mockup |

---

## Phase 9 — Admin (Filament)

| Item | Status | Notes |
|------|--------|-------|
| Install Filament v3 | ⬜ | Optional; native admin UIs built |
| CRUD: recipients | ✅ | List, create, edit, delete, CSV import/export, routing |
| CRUD: report categories | ✅ | Grid, create, edit, delete |
| Report list + filters + detail | ✅ | Main app reports dashboard + thread |
| Manual status override | ✅ | Admin dropdown on report thread |
| Graph + SSO settings page | ✅ | `/settings/app` — branding + Graph/SSO fields |
| Branding settings (logo, accent) | ✅ | `/settings/app` |
| User management (roles) | ✅ | `/users` — CRUD, roles, active flag, public profile |

---

## Phase 10 — Polish & production readiness

| Item | Status | Notes |
|------|--------|-------|
| Audit trail completeness | ✅ | created / queued / sent / failed / viewed / replied / status_changed |
| Rate limiting on submissions | ✅ | `throttle:10,1` |
| Attachment download (authenticated) | ✅ | `/attachments/{id}/download` |
| Status change notifications | ✅ | On approve, reject, and manual status override |
| Error monitoring / failed send retry | ✅ | `SendReportEmail` 3 tries; `SendGraphReply` 5 tries with backoff |
| Security review (approval tokens, uploads) | ⬜ | See SKILL security section |
| NDPR / retention policy | ⬜ | Organizational sign-off |

---

## Design files received

| Screen | Status | File / notes |
|--------|--------|--------------|
| Login | ✅ Received | Centered card, Microsoft SSO, email/password |
| Reports dashboard | ✅ Received | Stats cards + recent submissions grid |
| New report | ✅ Received | Card form, CC chips, upload zone |
| Report thread | ✅ Received | Message bubbles, approve, reply composer |
| Report Categories | ✅ Received | Stats + category cards grid |
| Recipients | ✅ Received | Table + stats + bulk import cards |
| Account Settings | ✅ Received | Profile, security, notifications |
| App Settings | ✅ Received | Branding, logo, accent color |

> Drop HTML mockups or screenshots into chat; we'll implement against `design.md` tokens.

---

## Environment variables

```env
# Application
APP_URL=http://localhost/InTheLoop
INSTALLED=true

# Microsoft Graph (app-only — mail + directory)
GRAPH_TENANT_ID=
GRAPH_CLIENT_ID=
GRAPH_CLIENT_SECRET=
GRAPH_DEFAULT_SENDER_MAILBOX=
GRAPH_MONITORED_MAILBOXES=mailbox1@org.com,mailbox2@org.com

# Microsoft SSO (delegated — login)
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
MICROSOFT_TENANT_ID=
MICROSOFT_REDIRECT_URI="${APP_URL}/auth/microsoft/callback"
```

Settings can be stored in `app_settings` via **App Settings** (`/settings/app`) or `.env`.

Test Graph connectivity: `php artisan graph:test`

---

## Quick dev commands

```bash
composer install --no-dev
npm install && npm run build
php artisan migrate
php artisan db:seed --class=DevelopmentSeeder
php artisan queue:work --queue=mail,default,sync
php artisan schedule:work   # local scheduler
```

---

## Recommended build sequence (what's next)

1. **Configure Entra** — register app, grant permissions, set Application Access Policy on shared mailboxes.
2. **Enter credentials** in App Settings; enable SSO; run `php artisan graph:test`.
3. **Live test** — submit a report, verify outbound email + `conversation_id`, reply from Outlook, confirm inbound sync.
4. **Production** — queue worker, scheduler cron, HTTPS for production `APP_URL`.
