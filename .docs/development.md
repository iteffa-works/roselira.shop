# Локальна розробка

## OSPanel / OpenServer

- Document root → `public/`
- Домен: `shop.roselira.local`
- `.env`:
  ```env
  APP_ENV=local
  APP_DEBUG=true
  SESSION_SECURE=false
  APP_URL=https://shop.roselira.local
  ```

## Git на Windows

Якщо **Admin → System → Git pull** не знаходить git:

```env
GIT_BINARY=C:\Program Files\Git\cmd\git.exe
```

Або OSPanel: `C:\OSPanel\modules\Git\bin\git.exe`

## GA4 локально

1. JSON service account у `storage/service/accounts/`
2. `GA4_PROPERTY_ID` + `GA4_SERVICE_ACCOUNT_JSON` у `.env`
3. Dashboard → Google → Сьогодні (Realtime)

## Структура для змін

| Задача | Де шукати |
|--------|-----------|
| Новий route | `flowaxy/routes.php` |
| Storefront UI | `views/`, `public/assets/` |
| Admin UI | `flowaxy/Admin/Views/`, `admin.css` |
| Бізнес-логіка | `flowaxy/Services/` |
| БД | `flowaxy/Repositories/Sqlite/` |

## IDE

`.vscode/` — локальні налаштування (gitignored).  
Проєктні правила Cursor — `.cursor/rules/`, див. [AGENTS.md](../AGENTS.md).
