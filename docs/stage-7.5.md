# Stage 7.5 — Admin shell по макетам Дизайнера

Ветка **`grok_bot` → `develop`**, PR **#17**, **не мержить** до ревью Den.

**HEAD:** `fc4a078`

---

## Цель

Светлая админка на `tokens.css`, роутинг `/admin/...`, сайдбар как в макете v16, отдельные экраны поверх существующего API, сохранение фиксов blog (п. 4).

## Артефакты

| Артефакт | Путь |
|----------|------|
| HTML + `tokens.css` + `png/` | `docs/mockups/` (из архивов uploads, без build-скриптов) |
| Стили admin-ui | **только** `admin-ui/src/tokens.css` + `admin-ui/src/admin-ui.css` |
| SPA fallback | `AdminSpaController` + `public/admin/.htaccess` |
| Скриншоты пары макет/результат | `docs/stage-7.5/screenshots/` (`mock-1440-*`, `result-1440-*`, `result-390-*` из Playwright) |

### Скриншоты (1440 / 390)

| Экран | Макет (1440) | Результат |
|-------|----------------|-----------|
| Вход | `mock-1440-login.png` | `result-{1440\|390}-login.png` |
| Обзор | `mock-1440-dashboard.png` | `result-*-dashboard.png` |
| Записи | `mock-1440-posts.png` | `result-*-posts.png` |
| Категории | `mock-1440-categories.png` | `result-*-categories.png` |
| Страницы | `mock-1440-pages.png` | `result-*-pages.png` |
| Модули | `mock-1440-modules.png` | `result-*-modules.png` |
| Сайты | `mock-1440-sites.png` | `result-*-sites.png` |
| Настройки | `mock-1440-settings.png` | `result-*-settings.png` |
| Операции | — (фрагмент в dashboard) | `result-*-operations.png` |
| Core update | `mock-1440-core-update.png` | `result-*-core-update.png` |
| Пользователи / Роли | `mock-1440-users.png`, `mock-1440-roles.png` | `result-*-users.png`, `result-*-roles.png` |

Макеты **390** в png-наборе нет — для mobile только `result-390-*` (реализация).

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

**Причина исходного бага blog:** живая сессия при пустом поле пароля → заголовок reauth пустой → 401, в UI generic «отклонено».

---

## Роуты admin-ui

| Путь | Экран |
|------|--------|
| `/admin/login` | Вход |
| `/admin/dashboard` | Обзор |
| `/admin/posts` | Записи + демо save/history |
| `/admin/categories` | Категории (заглушка данных) |
| `/admin/pages` | Дерево страниц, bind/unbind blog |
| `/admin/modules` | Установка / enable blog, версии |
| `/admin/sites` | Multisite |
| `/admin/settings` | ZIP upload, maintenance |
| `/admin/core-update` | Обновление Core |
| `/admin/operations` | Фоновые операции (демо) |
| `/admin/users`, `/admin/roles` | «появится на этапе 8» |

---

## Что не совпало с макетом v16 и почему

| Макет | Расхождение | Почему |
|-------|-------------|--------|
| Dashboard | Нет живых статей, ленты авторов, графиков | API этапа 8+ / демо-данные в макете |
| Записи / editor | Нет WYSIWYG-редактора, списка 36 статей | Реализован minimal demo save + history API |
| Категории | Пустой empty-state | Нет API категорий на этапе 7.5 |
| Сайты | «Сайты и шаблоны» без шаблонов | Templates — этап 8+ |
| Модули | Нет полного lifecycle (upload ZIP UI, conflict, rollback UI) | API есть частично; UI — wizard Core/blog + versions |
| Страницы | Дерево list, не drag-and-drop как tree.html | API move есть; DnD UI не в scope |
| Вход | Упрощённый staff-login без арт-панели | Функциональный вход; арт-блок опционален |
| Sidebar | Нет счётчика «36» у Записей, нет MFA-блока профиля | Нет данных / этап 8 |
| Операции | Статические «—» вместо cron/DLQ | Нет публичного API очередей |
| Пользователи / Роли | Заглушка этап 8 | По ТЗ |

Disabled-кнопки: контраст через `admin-ui.css` (не `opacity: 0.3`), ориентир WCAG AA на светлом фоне.

---

## Тесты

- PHPUnit: `Stage75BlogAdminHttpTest`, `Stage75AdminSpaHttpTest`
- Playwright: `stage75-admin-navigation.spec.ts`, `stage75-blog-from-scratch.spec.ts`, `stage75-blog-admin.spec.ts`, `stage75-screenshots.spec.ts`; обновлены `stage6`, `stage7`, `stage7-blog-facade`

---

## Вопросы к Den (с предложениями)

1. **Категории и полноценный список статей** — ждать этап 8 или дать минимальный read-only список из blog API уже в 7.5.1?
2. **Шаблоны сайтов** (`site-templates.html`) — входят в «Сайты» этапа 8 или отдельный подэтап?
3. **Модули: ZIP upload UI** — достаточно scan-incoming + install, или обязателен экран `module-upload` из макета?
4. **Drag-and-drop дерева страниц** — приоритет относительно API move + preview?
5. **Демо-данные на обзоре** — подставлять реальные агрегаты из API (поиск, posts count) или оставить «—» до этапа 8?
6. **390 макеты** — прислать png/jpg 390 в репозиторий для паритета «макет/результат»?

---

## CI

Все джобы workflow `CI` на `grok_bot` (см. отчёт агента: php-static, phpunit-matrix, frontend, playwright при PR).
