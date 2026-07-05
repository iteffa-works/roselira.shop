---
name: ga4-dashboard
description: Configure GA4 for admin Dashboard Google tab and Realtime API. Use when GA4_PROPERTY_ID, service account, PERMISSION_DENIED, or analytics dashboard issues.
---

# GA4 Dashboard

Read `.docs/analytics-ga4.md`.

## Setup

1. Enable **Google Analytics Data API** in Google Cloud (project roselira-497421)
2. `GA4_PROPERTY_ID` = numeric **Property** ID (Admin → Resource details)
3. Service account JSON → `storage/service/accounts/` (not in git, chmod 644)
4. GA4 → **Resource** access → service account email → **Viewer**
5. `.env`: `GA4_PROPERTY_ID` + `GA4_SERVICE_ACCOUNT_JSON`

## Troubleshooting

| Error | Fix |
|-------|-----|
| PERMISSION_DENIED | Wrong Property ID or missing **resource-level** access |
| JSON not readable | chmod 644, check path |
| 7/30 days = 0, Realtime works | Normal GA4 processing delay 24–48h; use **Сьогодні** tab |

## API endpoints

- `GET /admin/api/google-analytics?days=N`
- `GET /admin/api/google-analytics?live=1`
