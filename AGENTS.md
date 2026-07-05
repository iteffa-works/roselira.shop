# Roselira Shop — Agent Guide

PHP 8.2+ e-commerce monolith. **Document root:** `public/`. **DB:** SQLite in `storage/`.

## Where to look

| Area | Path |
|------|------|
| Routes | `flowaxy/routes.php` |
| DI bootstrap | `flowaxy/bootstrap.php` |
| Storefront | `flowaxy/Controllers/`, `views/` |
| Admin | `flowaxy/Admin/`, `flowaxy/Admin/Views/` |
| Business logic | `flowaxy/Services/` |
| SQLite | `flowaxy/Repositories/Sqlite/` |
| Helpers | `flowaxy/Support/helpers.php` |
| CLI | `cron.php`, `generate-seo.php`, `flowaxy/cli-bootstrap.php` |
| Docs | `.docs/` |

## Conventions

- `declare(strict_types=1);` in all PHP files
- Controllers stay thin; logic in Services
- Admin UI copy in **Ukrainian**
- Escape output with `e()` in views
- Minimal diffs; match existing style
- **Never commit** `.env`, `storage/service/accounts/*.json`, `storage/roselira.db`

## Commits

Semantic, one concern per commit: `fix:`, `feat:`, `refactor:`, `docs:`, `chore:`.

## Key features

- Analytics: local visitor tracking + GA4 Dashboard (Data API / Realtime / Looker)
- Git pull from Admin → System
- Product feeds: `/feeds/google.xml`, `/feeds/meta.xml`
- reCAPTCHA v2 on login and orders

See [.docs/README.md](.docs/README.md) for full documentation index.
