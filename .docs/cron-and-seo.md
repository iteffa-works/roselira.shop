# Cron, SEO та Feeds

## Cron

**CLI (рекомендовано):**

```bash
0 4 * * * /usr/bin/php /path/to/roselira.shop/cron.php
```

**HTTP (якщо немає CLI cron):**

```
https://roselira.shop/cron.php?token=CRON_SECRET
```

Cron виконує: git pull, SEO sync, system checks, retention локальної аналітики.

Інтервал повторного запуску: `CronInterval::DAILY_SECONDS` (~23 год).

## SEO

| Файл | Маршрут | Примітка |
|------|---------|----------|
| `sitemap.xml` | `/sitemap.xml` | PHP route або статичний файл у `public/` |
| `robots.txt` | `/robots.txt` | Те саме |

Apache віддає існуючий файл з `public/` напряму; якщо файлу немає — route через `SeoController`.

Ручна регенерація:

```bash
php generate-seo.php
```

Файли пишуться в **корінь проєкту і `public/`** одночасно.

## Product feeds

| Feed | URL |
|------|-----|
| Google | `/feeds/google.xml?token=FEED_SECRET` |
| Meta | `/feeds/meta.xml?token=FEED_SECRET` |

Посилання з token з'являються в **Admin → System → Посилання для реклами**.

Якщо `FEED_SECRET` порожній — feeds без token (не рекомендовано на prod).

## Логи

`storage/logs/app.log` — помилки замовлень, Telegram тощо.
