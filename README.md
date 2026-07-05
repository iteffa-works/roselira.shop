# Roselira Shop

PHP storefront and admin panel for roselira.shop (Flowaxy framework, SQLite).

## Requirements

- PHP 8.2+
- Apache with `mod_rewrite`
- Writable `storage/` directory

## Local setup

1. Point the web root to `public/`.
2. Copy `.env.example` to `.env` and adjust values.
3. Open `/admin/install` once to create the admin user.
4. Ensure `storage/` is writable for SQLite (`roselira.db`).

Optional: place `storage/roselira.sql` to seed an empty database on first run.

## Production

Set in `.env`:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE=true
APP_URL=https://roselira.shop

# Analytics (after cookie consent)
META_PIXEL_ID=
GA4_MEASUREMENT_ID=
GTM_CONTAINER_ID=

# Product feeds: /feeds/meta.xml?token=SECRET
FEED_SECRET=your-random-secret

CONTACT_EMAIL=
CONTACT_TELEGRAM=
```

Deploy checklist:

- Document root = `public/`
- HTTPS enabled (required for `SESSION_SECURE=true`)
- `storage/` writable by PHP (including `storage/logs/`)
- Regular backup of `storage/roselira.db`
- Configure Telegram notifications in `/admin/notifications`
- `/admin/install` returns 404 in production after setup
- All products active with photos and uk/ru texts in `/admin/catalog`
- Meta Pixel + GA4 IDs in `.env`, test Lead event on order
- Connect feeds in Meta Commerce Manager and Google Merchant Center

## SEO & feeds

- `https://roselira.shop/robots.txt`
- `https://roselira.shop/sitemap.xml`
- `https://roselira.shop/feeds/meta.xml?token=...`
- `https://roselira.shop/feeds/google.xml?token=...`

## Legal pages

- `/privacy` — privacy policy
- `/terms` — terms of service
- `/delivery` — delivery and returns

## Structure

- `public/` — front controller and static assets
- `views/` — storefront templates
- `flowaxy/` — application code (core, services, admin)
- `storage/` — SQLite database and logs (not committed)

## Admin

- `/admin` — dashboard
- `/admin/orders` — orders
- `/admin/notifications` — Telegram bot settings
- `/admin/system` — cron, feeds, SEO checks, git pull
- `/admin/database` — cleanup and maintenance

## Git deploy & auto-update

Initial setup on hosting (files go into **current folder**, not a subfolder):

```bash
cd /home/roselira/roselira.com/shop   # your site root (where public/ will live)
git clone https://github.com/iteffa-works/roselira.shop.git .
cp .env.example .env
# configure .env, writable storage/
```

**Important:** the `.` at the end of `git clone` is required. Without it, Git creates a `roselira.shop/` subfolder.

If you already cloned without `.`:

```bash
cd /path/to/site/root
mv roselira.shop/* .
mv roselira.shop/.git .
rmdir roselira.shop
```

Manual update: **Admin → Система → Git pull зараз**

Run all checks: **Admin → Система → Перевірити все**

Daily cron (git pull + feed/SEO checks):

```bash
0 4 * * * /usr/bin/php /path/to/roselira.shop/cron.php
```

`.env` and `storage/` are gitignored and preserved on update.

## Logs

Application errors (orders, Telegram) are written to `storage/logs/app.log`.
