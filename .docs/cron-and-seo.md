# Cron, SEO & Feeds

## Cron

**CLI (recommended):**

```bash
0 4 * * * /usr/bin/php /path/to/roselira.shop/cron.php
```

**HTTP (if CLI cron unavailable):**

```
https://roselira.shop/cron.php?token=CRON_SECRET
```

Cron runs: git pull, SEO sync, system checks, local analytics retention.

Repeat interval: `CronInterval::DAILY_SECONDS` (~23 hours).

## SEO

| File | Route | Notes |
|------|-------|-------|
| `sitemap.xml` | `/sitemap.xml` | PHP route or static file in `public/` |
| `robots.txt` | `/robots.txt` | Same |

Apache serves an existing file from `public/` directly; if missing, the route hits `SeoController`.

Manual regeneration:

```bash
php generate-seo.php
```

Files are written to **project root and `public/`** at once.

## Product feeds

| Feed | URL |
|------|-----|
| Google | `/feeds/google.xml?token=FEED_SECRET` |
| Meta | `/feeds/meta.xml?token=FEED_SECRET` |

Links with token appear in **Admin → System → Ad links**.

If `FEED_SECRET` is empty, feeds work without `?token=` (not recommended on production).

## Logs

`storage/logs/app.log` — order errors, Telegram failures, etc.
