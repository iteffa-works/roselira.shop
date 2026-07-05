# Змінні оточення (.env)

```bash
cp .env.example .env
```

Placeholder `[ТУТ …]` у `.env.example` трактується як **порожнє** значення (будь-який рядок, що починається з `[ТУТ`).

## Обов'язкові

| Змінна | Локально | Production |
|--------|----------|------------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `SESSION_SECURE` | `false` | `true` |
| `APP_URL` | `https://shop.roselira.local` | `https://roselira.shop` |
| `RECAPTCHA_SITE_KEY` | так | так |
| `RECAPTCHA_SECRET_KEY` | так | так |

## Аналітика (опційно)

| Змінна | Опис |
|--------|------|
| `META_PIXEL_ID` | Meta Pixel |
| `GA4_MEASUREMENT_ID` | GA4 Measurement ID (`G-…`) |
| `GTM_CONTAINER_ID` | Google Tag Manager |
| `GA4_PROPERTY_ID` | Числовий Property ID для Data API |
| `GA4_SERVICE_ACCOUNT_JSON` | Шлях до JSON, напр. `storage/service/accounts/….json` |
| `GA4_LOOKER_EMBED_URL` | Embed Looker Studio (альтернатива API) |

Скрипти аналітики завантажуються **лише після cookie consent**.

## Production

| Змінна | Опис |
|--------|------|
| `FEED_SECRET` | Захист XML feeds (`?token=`) |
| `CRON_SECRET` | HTTP-тригер cron |
| `GIT_REPO_URL` / `GIT_BRANCH` | Auto-update |
| `GIT_BINARY` | Шлях до git, якщо не в PATH PHP |

## Секрети

```powershell
# PowerShell — 32 байти hex
$rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
$bytes = New-Object byte[] 32
$rng.GetBytes($bytes)
[BitConverter]::ToString($bytes) -replace '-',''
```

```bash
openssl rand -hex 32
```

`FEED_SECRET` і `CRON_SECRET` мають бути **різними** на кожному середовищі.

## Не комітити

- `.env`, `.env.local`
- `storage/service/accounts/*.json`
- `storage/roselira.db`, `storage/logs/`
