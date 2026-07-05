# Roselira Shop

PHP storefront and admin panel for [roselira.shop](https://roselira.shop) (Flowaxy framework, SQLite).

## Requirements

- PHP 8.2+
- Apache with `mod_rewrite`
- Writable `storage/` directory

## Local setup

1. Point the web root to `public/`.
2. Copy `.env.example` to `.env` and fill in values (see [Environment variables](#environment-variables)).
3. Open `/admin/install` once to create the admin user.
4. Ensure `storage/` is writable for SQLite (`roselira.db`).

Optional: place `storage/roselira.sql` to seed an empty database on first run.

**Local `.env` example:**

```env
APP_ENV=local
APP_DEBUG=true
SESSION_SECURE=false
APP_URL=https://shop.roselira.local
```

## Environment variables

Copy the template:

```bash
cp .env.example .env
```

`.env.example` uses Ukrainian hints in the form `[ТУТ …]` — these are **placeholders only**. The app treats any value starting with `[ТУТ` as empty. Replace them with real values before production.

### Reference

| Variable | Required | Description |
|----------|----------|-------------|
| `APP_ENV` | yes | `local` or `production` |
| `APP_DEBUG` | yes | `true` locally, `false` on production |
| `SESSION_SECURE` | yes | `false` locally, `true` on production (HTTPS cookies) |
| `APP_URL` | yes | Public site URL without trailing slash |
| `META_PIXEL_ID` | no | Meta Pixel ID from [Events Manager](https://business.facebook.com/events_manager) |
| `GA4_MEASUREMENT_ID` | no | Google Analytics 4 ID, e.g. `G-XXXXXXXXXX` |
| `GTM_CONTAINER_ID` | no | Google Tag Manager ID, e.g. `GTM-XXXXXXX` |
| `GA4_PROPERTY_ID` | no | Numeric GA4 Property ID (Admin → Property settings) — for Dashboard **Google** tab via Data API |
| `GA4_SERVICE_ACCOUNT_JSON` | no | Path to service account JSON (e.g. `storage/ga4-service-account.json`) — pair with `GA4_PROPERTY_ID` |
| `GA4_LOOKER_EMBED_URL` | no | Looker Studio embed URL — alternative to Data API for Dashboard **Google** tab |
| `FEED_SECRET` | prod | Random secret to protect product XML feeds |
| `CONTACT_EMAIL` | no | Email shown in site footer |
| `CONTACT_TELEGRAM` | no | Telegram username for footer, e.g. `@roselira` |
| `GIT_REPO_URL` | no | Git remote for auto-update (default: this repo) |
| `GIT_BRANCH` | no | Branch to pull (default: `main`) |
| `GIT_BINARY` | no | Path to `git` if not in PHP `PATH` |
| `CRON_SECRET` | prod | Secret for HTTP cron trigger |
| `RECAPTCHA_SITE_KEY` | yes | reCAPTCHA v2 site key |
| `RECAPTCHA_SECRET_KEY` | yes | reCAPTCHA v2 secret key |

Analytics scripts load **only after cookie consent**. At least one of `META_PIXEL_ID`, `GA4_MEASUREMENT_ID`, or `GTM_CONTAINER_ID` is needed for tracking.

### Google Analytics у Dashboard (`/admin` → вкладка **Google**)

Базовий трекінг: `GA4_MEASUREMENT_ID` або `GTM_CONTAINER_ID` (див. вище).

Щоб звіти відкривались **прямо в адмінці**, додайте в `.env` **один** із варіантів:

**Варіант A — Looker Studio (простіше):**

1. Створіть звіт у [Looker Studio](https://lookerstudio.google.com/) на базі GA4.
2. **Share → Embed report** → скопіюйте embed URL.
3. У `.env`: `GA4_LOOKER_EMBED_URL=https://lookerstudio.google.com/embed/...`

**Варіант B — GA4 Data API:**

1. У [GA4 Admin](https://analytics.google.com/) → Property settings скопіюйте **Property ID** (число) → `GA4_PROPERTY_ID`.
2. У [Google Cloud Console](https://console.cloud.google.com/) створіть service account, завантажте JSON-ключ.
3. Збережіть файл поза web root, напр. `storage/ga4-service-account.json`.
4. У GA4 Admin → Property access management додайте email service account з роллю **Viewer**.
5. У `.env`:
   ```env
   GA4_PROPERTY_ID=123456789
   GA4_SERVICE_ACCOUNT_JSON=storage/ga4-service-account.json
   ```

Без цих змінних вкладка **Google** показує статус трекінгу та посилання в GA/GTM. Локальна аналітика (heatmaps, кліки) працює без GA4 API — вкладка **Локальна**.

### Production `.env` example

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE=true
APP_URL=https://roselira.shop

META_PIXEL_ID=1234567890123456
GA4_MEASUREMENT_ID=G-XXXXXXXXXX
GTM_CONTAINER_ID=GTM-XXXXXXX

FEED_SECRET=your-64-char-random-hex
CONTACT_EMAIL=content@roselira.com
CONTACT_TELEGRAM=@roselira

GIT_REPO_URL=https://github.com/iteffa-works/roselira.shop.git
GIT_BRANCH=main
GIT_BINARY=
CRON_SECRET=another-64-char-random-hex

RECAPTCHA_SITE_KEY=your-site-key
RECAPTCHA_SECRET_KEY=your-secret-key
```

Generate random secrets (PowerShell):

```powershell
$rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
$bytes = New-Object byte[] 32
$rng.GetBytes($bytes)
[BitConverter]::ToString($bytes) -replace '-',''
```

Or with OpenSSL:

```bash
openssl rand -hex 32
```

Use **different** values for `FEED_SECRET` and `CRON_SECRET` on each environment (local ≠ production).

### reCAPTCHA

1. Create reCAPTCHA v2 keys at [google.com/recaptcha/admin](https://www.google.com/recaptcha/admin).
2. Add domains: `roselira.shop`, `shop.roselira.local` (local).
3. Put **Site key** → `RECAPTCHA_SITE_KEY`, **Secret key** → `RECAPTCHA_SECRET_KEY`.

Used on `/admin/login` and the order form.

### Telegram notifications

Not configured in `.env`. Set bot token and chat ID in **Admin → Notifications** (`/admin/notifications`).

## Public URLs & feeds

After `APP_URL` is set, ready-to-use links appear in **Admin → System → Посилання для реклами** (`/admin/system`).

| Resource | Path | Notes |
|----------|------|-------|
| Google Merchant feed | `/feeds/google.xml?token=FEED_SECRET` | Google Merchant Center |
| Meta Catalog feed | `/feeds/meta.xml?token=FEED_SECRET` | Meta Commerce Manager |
| Sitemap | `/sitemap.xml` | Auto-regenerated by cron |
| robots.txt | `/robots.txt` | Auto-regenerated by cron |

**Production examples:**

```
https://roselira.shop/feeds/google.xml?token=YOUR_FEED_SECRET
https://roselira.shop/feeds/meta.xml?token=YOUR_FEED_SECRET
https://roselira.shop/sitemap.xml
https://roselira.shop/robots.txt
```

If `FEED_SECRET` is empty, feeds work without `?token=` (not recommended on production).

Paste feed URLs into:

- [Google Merchant Center](https://merchants.google.com/) → Products → Feeds
- [Meta Commerce Manager](https://business.facebook.com/commerce/) → Catalog → Data sources

Run **Перевірити все** on `/admin/system` to verify HTTP 200 and feed content.

## Production checklist

- Document root = `public/`
- HTTPS enabled (required for `SESSION_SECURE=true`)
- `.env` filled (see [Production `.env` example](#production-env-example))
- `storage/` writable by PHP (including `storage/logs/`)
- Regular backup of `storage/roselira.db`
- Telegram configured in `/admin/notifications`
- `/admin/install` returns 404 in production after setup
- All products active with photos and uk/ru texts in `/admin/catalog`
- Meta Pixel / GA4 in `.env`, test Lead event on order
- Feeds connected in Meta Commerce Manager and Google Merchant Center

## SEO & legal pages

- `/privacy` — privacy policy (editable in `/admin/pages`)
- `/terms` — terms of service
- `/delivery` — delivery and returns

## Structure

- `public/` — front controller and static assets
- `views/` — storefront templates
- `flowaxy/` — application code (core, services, admin)
- `storage/` — SQLite database and logs (not committed)

## Admin

| URL | Purpose |
|-----|---------|
| `/admin` | Dashboard |
| `/admin/orders` | Orders |
| `/admin/catalog` | Products |
| `/admin/pages` | Legal pages (privacy, terms, delivery) |
| `/admin/notifications` | Telegram bot settings |
| `/admin/security` | Security logs, rate limits |
| `/admin/system` | Cron, feeds, SEO checks, git pull |
| `/admin/database` | Cleanup and maintenance |

## Git deploy & auto-update

Initial setup on hosting (files go into **current folder**, not a subfolder):

```bash
cd /home/roselira/roselira.com/shop   # site root (where public/ lives)
git clone https://github.com/iteffa-works/roselira.shop.git .
cp .env.example .env
# edit .env, ensure storage/ is writable
```

**Important:** the `.` at the end of `git clone` is required. Without it, Git creates a `roselira.shop/` subfolder.

If you already cloned without `.`:

```bash
cd /path/to/site/root
mv roselira.shop/* .
mv roselira.shop/.git .
rmdir roselira.shop
```

Manual update: **Admin → System → Git pull**

Run all checks: **Admin → System → Перевірити все**

### Cron

CLI (recommended):

```bash
0 4 * * * /usr/bin/php /path/to/site/cron.php
```

HTTP (if CLI cron is unavailable):

```
https://roselira.shop/cron.php?token=YOUR_CRON_SECRET
```

Cron runs git pull, regenerates sitemap/robots, and stores system check results.

Set `GIT_BINARY` if `git` is not in PHP `PATH`, e.g. `/usr/bin/git` or `C:\OSPanel\modules\Git\bin\git.exe`.

`.env` and `storage/` are gitignored and preserved on update.

## Logs

Application errors (orders, Telegram) are written to `storage/logs/app.log`.
