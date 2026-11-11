# Stage 7.5 — Admin shell по макетам Дизайнера

Ветка **`grok_bot` → `develop`**, PR **#17**, **не мержить** до ревью Den.

**code-head:** `be8281a`  
**docs-head:** `2e582ec`

---

## Цель

Светлая админка на `tokens.css`, роутинг `/admin/...`, сайдбар как в макете v16, экраны на существующем API (этапы 3–7), сохранение фиксов blog (п. 4).

## Артефакты

| Артефакт | Путь |
|----------|------|
| HTML + `tokens.css` + `png/` | `docs/mockups/` |
| Стили admin-ui | `admin-ui/src/tokens.css`, `admin-ui/src/admin-ui.css` |
| SPA fallback | `AdminSpaController` + `public/admin/.htaccess` |
| Скриншоты | `docs/stage-7.5/screenshots/` (в репозитории, Docker Apache :8080 как в CI) |

---

## Скриншоты: макет / результат

Макет **390** у Дизайнера в png-наборе **нет** — в колонке «Макет 390» помечено **—**.

### Вход

| | 1440 | 390 |
|---|------|-----|
| **Макет** | ![mock login 1440](screenshots/mock-1440-login.png) | — |
| **Результат** | ![result login 1440](screenshots/result-1440-login.png) | ![result login 390](screenshots/result-390-login.png) |

### Обзор

| | 1440 | 390 |
|---|------|-----|
| **Макет** | ![mock dashboard 1440](screenshots/mock-1440-dashboard.png) | — |
| **Результат** | ![result dashboard 1440](screenshots/result-1440-dashboard.png) | ![result dashboard 390](screenshots/result-390-dashboard.png) |

### Записи

| | 1440 | 390 |
|---|------|-----|
| **Макет** | ![mock posts 1440](screenshots/mock-1440-posts.png) | — |
| **Результат** | ![result posts 1440](screenshots/result-1440-posts.png) | ![result posts 390](screenshots/result-390-posts.png) |

### Категории

| | 1440 | 390 |
|---|------|-----|
| **Макет** | ![mock categories 1440](screenshots/mock-1440-categories.png) | — |
| **Результат** | ![result categories 1440](screenshots/result-1440-categories.png) | ![result categories 390](screenshots/result-390-categories.png) |

### Страницы

| | 1440 | 390 |
|---|------|-----|
| **Макет** | ![mock pages 1440](screenshots/mock-1440-pages.png) | — |
| **Результат** | ![result pages 1440](screenshots/result-1440-pages.png) | ![result pages 390](screenshots/result-390-pages.png) |

### Модули

| | 1440 | 390 |
|---|------|-----|
| **Макет** | ![mock modules 1440](screenshots/mock-1440-modules.png) | — |
| **Результат** | ![result modules 1440](screenshots/result-1440-modules.png) | ![result modules 390](screenshots/result-390-modules.png) |

### Сайты

| | 1440 | 390 |
|---|------|-----|
| **Макет** | ![mock sites 1440](screenshots/mock-1440-sites.png) | — |
| **Результат** | ![result sites 1440](screenshots/result-1440-sites.png) | ![result sites 390](screenshots/result-390-sites.png) |

### Настройки

| | 1440 | 390 |
|---|------|-----|
| **Макет** | ![mock settings 1440](screenshots/mock-1440-settings.png) | — |
| **Результат** | ![result settings 1440](screenshots/result-1440-settings.png) | ![result settings 390](screenshots/result-390-settings.png) |

### Операции

| | 1440 | 390 |
|---|------|-----|
| **Макет** | `mock-1440-operations.png` — плейсхолдер (отдельного макета у Дизайнера нет; фрагмент в dashboard) | — |
| **Результат** | ![result operations 1440](screenshots/result-1440-operations.png) | ![result operations 390](screenshots/result-390-operations.png) |

### Core update

| | 1440 | 390 |
|---|------|-----|
| **Макет** | ![mock core 1440](screenshots/mock-1440-core-update.png) | — |
| **Результат** | ![result core 1440](screenshots/result-1440-core-update.png) | ![result core 390](screenshots/result-390-core-update.png) |

### Пользователи

| | 1440 | 390 |
|---|------|-----|
| **Макет** | ![mock users 1440](screenshots/mock-1440-users.png) | — |
| **Результат** | ![result users 1440](screenshots/result-1440-users.png) | ![result users 390](screenshots/result-390-users.png) |

### Роли и доступ

| | 1440 | 390 |
|---|------|-----|
| **Макет** | ![mock roles 1440](screenshots/mock-1440-roles.png) | — |
| **Результат** | ![result roles 1440](screenshots/result-1440-roles.png) | ![result roles 390](screenshots/result-390-roles.png) |

Снято: `e2e/tests/stage75-screenshots.spec.ts` (desktop 1440×900, mobile 390×844), локально `docker compose` → `http://127.0.0.1:8080`.

---

## Blog п. 4 (кратко)

| # | Причина | Фикс | Тест |
|---|---------|------|------|
| 1 | API-ошибки не показывались | `apiError.ts` | HTTP + UI |
| 2 | Пустой `X-Karkas-Reauth` после reload | `ReauthModal` + sessionStorage | `stage75-blog-admin.spec.ts` |
| 3 | Нет порядка шагов | wizard hints на `/modules` | `stage75-admin-navigation` |
| 4 | plan/sites API | backend try/catch + 401 | `Stage75BlogAdminHttpTest` |
| 5 | duplicate mount | idempotent enable | double enable HTTP |
| 6 | `site_label` хардкод | label из сайта | e2e multisite |

**Причина исходного бага blog:** живая PHP-сессия после reload при пустом поле пароля в React → пустой `X-Karkas-Reauth` → 401; в UI — общее «отклонено» без диалога reauth.

**Доп. фикс deep-link:** до ответа `GET /api/v1/admin/session` редирект на `/login` сбрасывал URL на `/dashboard` после reload — `sessionReady` в `AdminContext`, return-path на login.

---

## Роуты admin-ui

| Путь | Экран | API |
|------|--------|-----|
| `/admin/login` | Вход | session |
| `/admin/dashboard` | Обзор (empty state) | **нет API агрегатов** |
| `/admin/posts` | Список записей | `GET /api/v1/blog/posts` |
| `/admin/posts/new`, `/admin/posts/:id` | Редактор (plain text) | create/save |
| `/admin/posts/:id/history` | История + restore | versions + restore |
| `/admin/categories` | Empty state | **нет API категорий** |
| `/admin/pages` | Дерево, move, blog bind | sites/pages API |
| `/admin/modules` | Установка / enable blog | platform/install |
| `/admin/sites` | Multisite | `/api/v1/sites` |
| `/admin/settings` | ZIP, maintenance | admin packages |
| `/admin/core-update` | Core plan/upgrade | platform/plan |
| `/admin/operations` | Журнал операций или empty | `GET /api/v1/admin/platform/operations` (cron/DLQ **нет**) |
| `/admin/users`, `/admin/roles` | Заглушка этап 8 | **нет API** |

Регрессия blog: e2e через **Новая запись → редактор → сохранение → история** (без блока «Быстрые действия»).

---

## Расхождения с макетом v16

Колонка **тип**: **сделано** — приведено к макету в рамках API; **нет API** — ограничение платформы; **отложено** — осознанно не в 7.5; **не сделано** — API есть, UI ещё не доведён.

| Экран | Расхождение | Тип | Почему |
|-------|-------------|-----|--------|
| Shell (сайдбар + шапка) | — | **сделано** | Иконки, счётчики из API, пользователь/«Открыть сайт», breadcrumbs `>`, RU/колокольчик/аватар |
| Обзор | Нет ленты статей, графиков, счётчиков | **нет API** | Нет эндпоинта сводки; empty state вместо демо-цифр |
| Записи | Таблица, пустое состояние, без e2e-блока | **сделано** (список) | Нет WYSIWYG, фильтров, колонки категорий из API — **отложено** |
| Редактор | Текстовое поле, не article-editor.html | **отложено** | API save plain text |
| Категории | Пустой экран, без table-card | **нет API** | Категории блога не exposed |
| Сайты | Заголовок и таблица; без вкладок «шаблоны» | **сделано** (multisite) / **нет API** (шаблоны) | Site templates — этап 8+ |
| Модули | Таблица Core/blog/enable, кнопки сверху, ZIP-notice | **сделано** (installer) | Полный ZIP upload/conflict UI — **отложено**; binding v16 — ссылка на `/pages`, не отдельный wizard |
| Страницы | API-дерево, не DnD mock tree.html | **отложено** | move/preview есть; DnD и карточка детали — не делали |
| Вход | Без арт-панели staff-login | **отложено** | Функциональный пароль; без демо-email/2FA notice |
| Настройки | Две platform-настройки, не settings-layout | **нет API** | Макет — site settings, secrets, доставка |
| Операции | Журнал из API; нет cron/DLQ | **нет API** | Только `platform/operations` |
| Пользователи / Роли | «появится на этапе 8» | **нет API** | По ТЗ |
| Операции (макет) | Отдельного png нет | — | Плейсхолдер `mock-1440-operations.png` |

Контраст disabled-кнопок: правки в `admin-ui.css` (не `opacity: 0.3`).

---

## Тесты

- PHPUnit: `Stage75BlogAdminHttpTest`, `Stage75BlogPostsHttpTest`, `Stage75AdminSpaHttpTest`
- Playwright: `stage75-admin-navigation`, `stage75-blog-*`, `stage75-screenshots`

---

## Вопросы к Den (с предложениями)

1. **Категории** — ждать этап 8 или минимальный read-only API в 7.5.1?
2. **Шаблоны сайтов** — часть «Сайты» этапа 8 или отдельный подэтап?
3. **Модули: ZIP upload UI** — достаточно scan-incoming + install?
4. **DnD дерева страниц** — приоритет при наличии move API?
5. **API обзора (dashboard)** — какие агрегаты нужны в первой версии?
6. **Макеты 390** — прислать png для паритета с 1440?
---

## Локальный Playwright + axe

`docker compose up`, `.env`: `KARKAS_ADMIN_PASSWORD=test-admin-password-stage2`, `admin-ui` `npm run build`, `e2e`: `npm test` с `BASE_URL=http://127.0.0.1:8080`.

Итог (полный набор 92 теста, финальный прогон mockup-прохода): **91 passed, 1 skipped, 0 failed** (~39s). Skip: mobile-only `page move preview screenshot desktop`. Axe: `stage4` (history), `stage6` (modules), `smoke` — 0 violations после правок таблиц/`aria-label`.

```
  ✓  92 passed scenarios (91 + 1 spec skip)
  1 skipped
  91 passed (39.2s)
```

Скриншоты docs: `npx playwright test tests/stage75-screenshots.spec.ts` → `docs/stage-7.5/screenshots/result-*`.

## CI

PR #17, SHA `3b8b6f4`: run [43057115534](https://github.com/dsefremov/karkas-cms/actions/runs/43057115534) — php-static, frontend, phpunit-matrix×4, release-artifact **success**; **playwright — failure** на шаге `npx playwright install chromium --with-deps` (`Temporary failure resolving 'azure.archive.ubuntu.com'`, apt/DNS), тесты не запускались. Перезапуск failed job — Den.
