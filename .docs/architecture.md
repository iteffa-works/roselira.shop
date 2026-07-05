# Архітектура

## Стек

- PHP 8.2+, без Composer/npm
- SQLite (`storage/roselira.db`)
- Apache + `mod_rewrite`, document root = **`public/`**
- Custom MVC у `flowaxy/`

## Структура каталогів

```
public/          → index.php, assets, robots/sitemap (копії)
views/           → шаблони вітрини (storefront)
flowaxy/         → додаток
  Core/          → Router, Container, Request, Response, View
  Controllers/   → публічні контролери
  Admin/         → адмін-контролери та Views/
  Services/      → бізнес-логіка
  Repositories/  → SQLite + інтерфейси
  Support/       → helpers, env, limiters
storage/         → БД, логи, service account JSON (не в git)
```

## Запит

1. `public/index.php` → `flowaxy/bootstrap.php`
2. DI-контейнер реєструє сервіси
3. `flowaxy/routes.php` → `Router::dispatch()`
4. Контролер повертає `Response`

## Views

- **Storefront:** `views/` + `View::render()`
- **Admin:** `flowaxy/Admin/Views/` + `View::renderAdmin()`

## CLI

- `cron.php` — щоденні задачі (git pull, SEO, перевірки)
- `generate-seo.php` — ручна регенерація sitemap/robots
- Спільний bootstrap: `flowaxy/cli-bootstrap.php`

## Навмисні дублікати

- `robots.txt` / `sitemap.xml` у корені **і** `public/` — синхронізує `SeoFilesService`
- Два `ProductController` (storefront vs admin) — різні namespace
