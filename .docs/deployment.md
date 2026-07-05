# Deployment

## Document root

Must be **`public/`**, not the repository root.

## First deploy

```bash
cd /home/roselira/roselira.com/shop
git clone https://github.com/iteffa-works/roselira.shop.git .
cp .env.example .env
# edit .env, ensure storage/ is writable
```

The trailing `.` in `git clone` is required — otherwise Git creates a subfolder.

## If already cloned into a subfolder

```bash
mv roselira.shop/* .
mv roselira.shop/.git .
rmdir roselira.shop
```

## After deploy

1. Upload service account JSON to `storage/service/accounts/` (not in git)
2. Make `storage/` writable for PHP (logs, SQLite)
3. Run `/admin/install` once, then block/404 on production
4. Back up `storage/roselira.db`

## Updates

- **Admin → System → Git pull**
- Cron also runs git pull daily

`.env` and `storage/` are gitignored and preserved on update.

## Production checklist

- HTTPS + `SESSION_SECURE=true`
- `FEED_SECRET`, `CRON_SECRET` set
- Feeds connected in Merchant Center / Meta Commerce
- Meta Pixel / GA4 in `.env`, test an order
- Run **Check all** on `/admin/system`
