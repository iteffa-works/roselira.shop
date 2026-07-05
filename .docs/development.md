# Local development

## OSPanel / OpenServer

- Document root → `public/`
- Domain: `shop.roselira.local`
- `.env`:
  ```env
  APP_ENV=local
  APP_DEBUG=true
  SESSION_SECURE=false
  APP_URL=https://shop.roselira.local
  ```

## Git on Windows

If **Admin → System → Git pull** cannot find git:

```env
GIT_BINARY=C:\Program Files\Git\cmd\git.exe
```

Or OSPanel: `C:\OSPanel\modules\Git\bin\git.exe`

## GA4 locally

1. Service account JSON in `storage/service/accounts/`
2. `GA4_PROPERTY_ID` + `GA4_SERVICE_ACCOUNT_JSON` in `.env`
3. Dashboard → Google → **Today** (Realtime)

## Where to change things

| Task | Location |
|------|----------|
| New route | `flowaxy/routes.php` |
| Storefront UI | `views/`, `public/assets/` |
| Admin UI | `flowaxy/Admin/Views/`, `admin.css` |
| Business logic | `flowaxy/Services/` |
| Database | `flowaxy/Repositories/Sqlite/` |

## IDE

`.vscode/` — local settings (gitignored).  
Cursor rules: `.cursor/rules/`, see [AGENTS.md](../AGENTS.md).
