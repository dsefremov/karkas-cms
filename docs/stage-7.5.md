# Stage 7.5 — Admin shell по макетам Дизайнера

Ветка **`grok_bot` → `develop`**, PR **#17**, **не мержить** до ревью Den.

**code-head:** `d4efd9e`  
**docs-head:** `dc7ed1f`

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

На `/posts` сохранён блок демо save/history (этап 3–4) для регрессии blog.

---

## Расхождения с макетом v16

Колонка **тип**: **нет API** — ограничение платформы; **не сделано** — API есть, UI/UX не доведён до макета.

| Экран | Расхождение | Тип | Почему |
|-------|-------------|-----|--------|
| Обзор | Нет ленты статей, графиков, счётчиков | **нет API** | Нет эндпоинта сводки; empty state вместо демо-цифр |
| Записи | Нет WYSIWYG, фильтров, «36» в сайдбаре | **не сделано** / **нет API** | Список из API есть; rich editor и метаданные — не в scope |
| Редактор | Текстовое поле, не article-editor.html | **не сделано** | API save plain text |
| Категории | Пустой экран | **нет API** | Категории блога не exposed |
| Сайты | Без «шаблонов» | **нет API** | Site templates — этап 8+ |
| Модули | Нет полного ZIP/conflict UI | **не сделано** | scan-incoming + install есть; макет module-upload шире |
| Страницы | Список + кнопки, не DnD-дерево | **не сделано** | move/preview API есть; DnD UI не делали |
| Вход | Без арт-панели staff-login | **не сделано** | Функциональный вход |
| Операции | Нет cron/DLQ таблиц | **нет API** | Только platform operations log |
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
7. **Список записей** — убрать демо-блок с `/posts`, оставить только CRUD UI?

---

## CI

См. отчёт агента на tip `grok_bot` после push (workflow `CI`, jobs: php-static, phpunit-matrix, frontend, playwright).
