# Environment variables (.env)

```bash
cp .env.example .env
```

Placeholder values like `[ТУТ …]` in `.env.example` are treated as **empty** (any value starting with `[ТУТ`).

## Required

| Variable | Local | Production |
|----------|-------|------------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `SESSION_SECURE` | `false` | `true` |
| `APP_URL` | `https://shop.roselira.local` | `https://roselira.shop` |
| `RECAPTCHA_SITE_KEY` | yes | yes |
| `RECAPTCHA_SECRET_KEY` | yes | yes |

## Analytics (optional)

| Variable | Description |
|----------|-------------|
| `META_PIXEL_ID` | Meta Pixel |
| `GA4_MEASUREMENT_ID` | GA4 Measurement ID (`G-…`) |
| `GTM_CONTAINER_ID` | Google Tag Manager |
| `GA4_PROPERTY_ID` | Numeric Property ID for Data API |
| `GA4_SERVICE_ACCOUNT_JSON` | Path to JSON, e.g. `storage/service/accounts/….json` |
| `GA4_LOOKER_EMBED_URL` | Looker Studio embed (API alternative) |

Analytics scripts load **only after cookie consent**.

## Production

| Variable | Description |
|----------|-------------|
| `FEED_SECRET` | Protects XML feeds (`?token=`) |
| `CRON_SECRET` | HTTP cron trigger |
| `GIT_REPO_URL` / `GIT_BRANCH` | Auto-update |
| `GIT_BINARY` | Path to git if not in PHP PATH |

## Secrets

```powershell
# PowerShell — 32-byte hex
$rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
$bytes = New-Object byte[] 32
$rng.GetBytes($bytes)
[BitConverter]::ToString($bytes) -replace '-',''
```

```bash
openssl rand -hex 32
```

Use **different** values for `FEED_SECRET` and `CRON_SECRET` on each environment.

## Never commit

- `.env`, `.env.local`
- `storage/service/accounts/*.json`
- `storage/roselira.db`, `storage/logs/`
