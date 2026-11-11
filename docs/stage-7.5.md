# Stage 7.5 — Admin shell & blog wizard fixes (п. 4)

Ветка `grok_bot` → `develop`, **не мержить** до ревью.

## Blog admin — 6 находок (develop `e2d4bfb`)

### 1. Тела ошибок install / enable / bootstrap

| | |
|---|---|
| **Причина** | `installBlog`, `enableBlog`, `runInstaller` при `!response.ok` не читали JSON; bootstrap не проверялся. |
| **Фикс** | `admin-ui/src/lib/apiError.ts` — `error`, `errors`, `plan.compatibility.errors`; вызов из installer/install/enable. |
| **Тест** | Playwright `stage75-blog-admin.spec.ts` (wizard hints); HTTP `Stage75BlogAdminHttpTest::testPlatformInstallUnknownModuleReturnsJsonError`. |

### 2. `X-Karkas-Reauth` из пустого поля после reload

| | |
|---|---|
| **Причина** | Сессия жива, `password` в React сброшен → пустой заголовок → 401. |
| **Фикс** | `sessionStorage` + `ReauthModal` по макету `staff-reauth`; `POST /api/v1/admin/reauth`; `ensureReauth` перед опасными операциями. |
| **Тест** | Playwright `stage75-blog-admin.spec.ts` — modal после очистки storage и reload. |

### 3. Порядок Core → install blog → enable

| | |
|---|---|
| **Причина** | Кнопки активны без зависимостей между шагами. |
| **Фикс** | Блокировка по `karkas/core` / `karkas/blog` в versions и `blog_enabled` на сайте; подсказки `*-step-hint`. |
| **Тест** | Playwright `stage75-blog-admin.spec.ts` — `install-blog-step-hint` / disabled. |

### 4. План install и `SitesController::list`

| | |
|---|---|
| **Причина** | Построение плана вне `try` → 500 без схемы; `list()` без `AdminAuthException`. |
| **Фикс** | `PlatformInstallController` — plan в `try/catch`; `SitesController::list` → 401 + `error`. |
| **Тест** | `Stage75BlogAdminHttpTest::testSitesListRequiresAdminSession`, `testPlatformInstallUnknownModuleReturnsJsonError`. |

### 5. Повторный enable (UNIQUE mount)

| | |
|---|---|
| **Причина** | `ensureDirectMount` всегда `INSERT` → 409. |
| **Фикс** | Upsert по `(site_id, path_prefix)`; ранний выход в `SiteModuleEnableService::enable` если уже enabled. |
| **Тест** | `Stage75BlogAdminHttpTest::testDoubleEnableBlogReturnsSuccess`. |

### 6. `site_label` «Каркас — демо»

| | |
|---|---|
| **Причина** | Хардкод в `enableBlog` UI. |
| **Фикс** | `site_label` = `sites[].label` выбранного сайта. |
| **Тест** | `testDoubleEnableBlogReturnsSuccess` (явный `site_label` в API); e2e multisite — `stage6.spec.ts` selector label. |
