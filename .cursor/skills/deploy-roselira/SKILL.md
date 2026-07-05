---
name: deploy-roselira
description: Deploy and update roselira.shop on hosting. Use when deploying, git pull, hosting setup, document root, or production checklist.
---

# Deploy Roselira

Read `.docs/deployment.md` and `.docs/cron-and-seo.md`.

## Checklist

1. Document root = `public/`
2. `git clone … .` (dot at end)
3. `.env` from `.env.example`, never commit
4. Upload GA4 JSON to `storage/service/accounts/` (chmod 644)
5. `storage/` writable
6. Cron: `0 4 * * * php /path/cron.php`
7. Verify `/admin/system` → Перевірити все

## Git pull

Admin → System → Git pull, or cron daily. Set `GIT_BINARY` on Windows if needed.
