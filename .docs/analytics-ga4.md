# Analytics & GA4 Dashboard

## Storefront

After cookie consent, Meta Pixel / GA4 / GTM load from `.env`.

## Admin Dashboard (`/admin`)

Two data sources:

| Tab | Source |
|-----|--------|
| **Local** | SQLite — clicks, scroll, heatmap, sessions |
| **Google** | GA4 Data API or Looker embed |

### Option A — Looker Studio

1. Create a report in [Looker Studio](https://lookerstudio.google.com/) from GA4
2. Share → Embed report
3. `.env`: `GA4_LOOKER_EMBED_URL=https://lookerstudio.google.com/embed/...`

### Option B — GA4 Data API

1. GA4 Admin → **Property** → Property details → copy **Property ID** → `GA4_PROPERTY_ID`
2. [Google Cloud Console](https://console.cloud.google.com/) → enable **Google Analytics Data API**
3. Service account → download JSON → `storage/service/accounts/….json` (chmod **644**)
4. GA4 Admin → **Property** → Property access management → service account email → **Viewer**
5. `.env`:
   ```env
   GA4_PROPERTY_ID=123456789
   GA4_SERVICE_ACCOUNT_JSON=storage/service/accounts/roselira-497421-….json
   ```

**Important:** Property ID ≠ Account ID. Access must be granted at **property** level, not account only.

### Realtime

- **Today** tab → GA4 Realtime API (last 30 minutes)
- **7/30 days** tabs → “Active now” strip + 30s auto-refresh
- Standard GA4 reports may lag **24–48 hours** — this is normal

### Polling API

`GET /admin/api/google-analytics?days=1` — full report  
`GET /admin/api/google-analytics?live=1` — active users only

## Admin GTM/GA4

GTM/GA4 also loads in admin (`/admin/*`) via `partials/tracking.php` — separate from storefront consent.
