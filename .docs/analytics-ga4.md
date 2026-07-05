# Аналітика та GA4 Dashboard

## Storefront

Після згоди на cookies підвантажуються Meta Pixel / GA4 / GTM з `.env`.

## Admin Dashboard (`/admin`)

Два джерела:

| Вкладка | Джерело |
|---------|---------|
| **Локальна** | SQLite — кліки, scroll, heatmap, сесії |
| **Google** | GA4 Data API або Looker embed |

### Варіант A — Looker Studio

1. Звіт у [Looker Studio](https://lookerstudio.google.com/) на базі GA4
2. Share → Embed report
3. `.env`: `GA4_LOOKER_EMBED_URL=https://lookerstudio.google.com/embed/...`

### Варіант B — GA4 Data API

1. GA4 Admin → **Ресурс** → Деталі ресурсу → **Property ID** (число) → `GA4_PROPERTY_ID`
2. [Google Cloud Console](https://console.cloud.google.com/) → увімкнути **Google Analytics Data API**
3. Service account → JSON-ключ → `storage/service/accounts/….json` (chmod **644**)
4. GA4 Admin → **Ресурс** → Керування доступом до **ресурсу** → email SA → **Читач**
5. `.env`:
   ```env
   GA4_PROPERTY_ID=123456789
   GA4_SERVICE_ACCOUNT_JSON=storage/service/accounts/roselira-497421-….json
   ```

**Важливо:** Property ID ≠ Account ID. Доступ потрібен на рівні **ресурсу**, не лише акаунту.

### Realtime

- Вкладка **Сьогодні** → GA4 Realtime API (останні 30 хв)
- На **7/30 днів** — смуга «Активні зараз» + автооновлення 30 с
- Стандартні звіти GA4 з'являються з затримкою **24–48 год** — це нормально

### API для polling

`GET /admin/api/google-analytics?days=1` — повний звіт  
`GET /admin/api/google-analytics?live=1` — лише active users

## Admin GTM/GA4

GTM/GA4 також у адмінці (`/admin/*`) через `partials/tracking.php` — окремо від storefront consent.
