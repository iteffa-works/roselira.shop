# Roselira Shop

PHP storefront and admin panel for [roselira.shop](https://roselira.shop) (Flowaxy framework, SQLite).

**Documentation (UA):** [.docs/](.docs/) — architecture, env, GA4, admin, deploy, cron.

## Requirements

- PHP 8.2+
- Apache with `mod_rewrite`
- Writable `storage/` directory

## Quick start

1. Point the web root to `public/`.
2. `cp .env.example .env` — fill values ([environment](.docs/environment.md)).
3. Open `/admin/install` once to create the admin user.
4. Ensure `storage/` is writable for SQLite (`roselira.db`).

Optional: `storage/roselira.sql` seeds an empty database on first run.

**Local `.env`:**

```env
APP_ENV=local
APP_DEBUG=true
SESSION_SECURE=false
APP_URL=https://shop.roselira.local
```

## Environment (summary)

| Variable | Required | Notes |
|----------|----------|-------|
| `APP_ENV`, `APP_DEBUG`, `SESSION_SECURE`, `APP_URL` | yes | See [.docs/environment.md](.docs/environment.md) |
| `GA4_*`, `GTM_*`, `META_PIXEL_ID` | no | Analytics after cookie consent |
| `GA4_SERVICE_ACCOUNT_JSON` | no | e.g. `storage/service/accounts/….json` |
| `FEED_SECRET`, `CRON_SECRET` | prod | Protect feeds and HTTP cron |
| `GIT_BINARY` | no | Path to git if not in PHP PATH |
| `RECAPTCHA_*` | yes | Login + order form |

Values starting with `[ТУТ` in `.env.example` are treated as empty placeholders.

## Structure

- `public/` — front controller and static assets
- `views/` — storefront templates
- `flowaxy/` — application (core, services, admin)
- `storage/` — SQLite, logs, keys (not committed)
- `.docs/` — detailed documentation (Ukrainian)

## Admin

| URL | Purpose |
|-----|---------|
| `/admin` | Dashboard (local + Google analytics) |
| `/admin/orders` | Orders |
| `/admin/catalog` | Products |
| `/admin/system` | Cron, feeds, SEO, git pull |

Full list: [.docs/admin.md](.docs/admin.md)

## Deploy & cron

See [.docs/deployment.md](.docs/deployment.md) and [.docs/cron-and-seo.md](.docs/cron-and-seo.md).

```bash
0 4 * * * /usr/bin/php /path/to/roselira.shop/cron.php
```

## GA4 Dashboard

Tracking: `GA4_MEASUREMENT_ID` or `GTM_CONTAINER_ID`.  
In-admin reports: `GA4_PROPERTY_ID` + service account JSON, or `GA4_LOOKER_EMBED_URL`.

Details: [.docs/analytics-ga4.md](.docs/analytics-ga4.md)

## Logs

`storage/logs/app.log`
