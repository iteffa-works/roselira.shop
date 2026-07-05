# Architecture

## Stack

- PHP 8.2+, no Composer/npm
- SQLite (`storage/roselira.db`)
- Apache + `mod_rewrite`, document root = **`public/`**
- Custom MVC in `flowaxy/`

## Directory layout

```
public/          → index.php, assets, robots/sitemap (copies)
views/           → storefront templates
flowaxy/         → application code
  Core/          → Router, Container, Request, Response, View
  Controllers/   → public controllers
  Admin/         → admin controllers and Views/
  Services/      → business logic
  Repositories/  → SQLite + interfaces
  Support/       → helpers, env, limiters
storage/         → DB, logs, service account JSON (not in git)
```

## Request flow

1. `public/index.php` → `flowaxy/bootstrap.php`
2. DI container registers services
3. `flowaxy/routes.php` → `Router::dispatch()`
4. Controller returns `Response`

## Views

- **Storefront:** `views/` + `View::render()`
- **Admin:** `flowaxy/Admin/Views/` + `View::renderAdmin()`

## CLI

- `cron.php` — daily tasks (git pull, SEO, system checks)
- `generate-seo.php` — manual sitemap/robots regeneration
- Shared bootstrap: `flowaxy/cli-bootstrap.php`

## Intentional duplicates

- `robots.txt` / `sitemap.xml` in project root **and** `public/` — synced by `SeoFilesService`
- Two `ProductController` classes (storefront vs admin) — different namespaces
