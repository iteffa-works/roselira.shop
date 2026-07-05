# Адмін-панель

URL: `/admin` (після `/admin/install`).

## Маршрути

| URL | Призначення |
|-----|-------------|
| `/admin` | Dashboard — замовлення, аналітика Local/Google |
| `/admin/heatmap` | Heatmap кліків (локальна аналітика) |
| `/admin/orders` | Замовлення |
| `/admin/catalog` | Каталог товарів |
| `/admin/product` | Редагування товару |
| `/admin/pages` | Юридичні сторінки (privacy, terms, delivery) |
| `/admin/locales` | Тексти UI |
| `/admin/rates` | Курси валют |
| `/admin/notifications` | Telegram bot |
| `/admin/security` | Логи безпеки, rate limits |
| `/admin/system` | Cron, feeds, SEO checks, git pull |
| `/admin/database` | Очищення БД |
| `/admin/updates` | Alias → `/admin/system` |

## Dashboard аналітика

- **Локальна:** visitor tracking (`/track`), heatmap, сесії
- **Google:** GA4 Data API / Looker / Realtime (див. [analytics-ga4.md](analytics-ga4.md))

## Telegram

Налаштовується в **Admin → Notifications**, не в `.env`.

## Безпека

- reCAPTCHA v2 на `/admin/login`
- Rate limit login/order через `SecurityRateLimiter`
- Після install — `/admin/install` має повертати 404 на production
