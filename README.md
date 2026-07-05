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
```

Deploy checklist:

- Document root = `public/`
- HTTPS enabled (required for `SESSION_SECURE=true`)
- `storage/` writable by PHP
- Regular backup of `storage/roselira.db`
- Configure Telegram notifications in `/admin/notifications`
- `/admin/install` returns 404 in production after setup

## Structure

- `public/` — front controller and static assets
- `views/` — storefront templates
- `flowaxy/` — application code (core, services, admin)
- `storage/` — SQLite database (not committed)

## Admin

- `/admin` — dashboard
- `/admin/orders` — orders
- `/admin/notifications` — Telegram bot settings
- `/admin/database` — cleanup and maintenance
