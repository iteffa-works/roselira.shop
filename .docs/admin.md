# Admin panel

URL: `/admin` (after one-time `/admin/install`).

## Routes

| URL | Purpose |
|-----|---------|
| `/admin` | Dashboard — orders, Local/Google analytics |
| `/admin/heatmap` | Click heatmap (local analytics) |
| `/admin/orders` | Orders |
| `/admin/catalog` | Product catalog |
| `/admin/product` | Edit product |
| `/admin/pages` | Legal pages (privacy, terms, delivery) |
| `/admin/locales` | UI strings |
| `/admin/rates` | Exchange rates |
| `/admin/notifications` | Telegram bot |
| `/admin/security` | Security logs, rate limits |
| `/admin/system` | Cron, feeds, SEO checks, git pull |
| `/admin/database` | DB maintenance |
| `/admin/updates` | Alias → `/admin/system` |

## Dashboard analytics

- **Local:** visitor tracking (`/track`), heatmap, sessions
- **Google:** GA4 Data API / Looker / Realtime — see [analytics-ga4.md](analytics-ga4.md)

## Telegram

Configured in **Admin → Notifications**, not in `.env`.

## Security

- reCAPTCHA v2 on `/admin/login`
- Login/order rate limits via `SecurityRateLimiter`
- After install, `/admin/install` should return 404 on production
