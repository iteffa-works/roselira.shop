# Деплой на хостинг

## Document root

**Обов'язково** `public/`, не корінь репозиторію.

## Перший deploy

```bash
cd /home/roselira/roselira.com/shop
git clone https://github.com/iteffa-works/roselira.shop.git .
cp .env.example .env
# відредагувати .env, chmod storage/
```

Крапка `.` в кінці `git clone` обов'язкова — інакше створиться підпапка.

## Якщо вже склоновано в підпапку

```bash
mv roselira.shop/* .
mv roselira.shop/.git .
rmdir roselira.shop
```

## Після deploy

1. Завантажити service account JSON у `storage/service/accounts/` (не в git)
2. `storage/` writable для PHP (логи, SQLite)
3. `/admin/install` — один раз, потім 404 на prod
4. Backup `storage/roselira.db`

## Оновлення

- **Admin → System → Git pull**
- Cron також робить git pull щодня

`.env` і `storage/` зберігаються при оновленні (gitignore).

## Production checklist

- HTTPS + `SESSION_SECURE=true`
- `FEED_SECRET`, `CRON_SECRET` задані
- Feeds підключені в Merchant Center / Meta Commerce
- Meta Pixel / GA4 у `.env`, тест замовлення
- **Перевірити все** на `/admin/system`
