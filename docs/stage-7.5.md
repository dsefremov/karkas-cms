# Stage 7.5 — Admin shell по макетам Дизайнера

Ветка **`grok_bot` → `develop`**, PR **#17**, **не мержить** до ревью Den.

**code-head:** `1a4fd3e`  
**docs-head:** `b249ad5`

---

## Цель

Светлая админка на `tokens.css`, роутинг `/admin/...`, сайдбар как в макете v16, экраны на существующем API (этапы 3–7), сохранение фиксов blog (п. 4).

## Артефакты

| Артефакт | Путь |
|----------|------|
| HTML + `tokens.css` + `png/` | `docs/mockups/` |
| Стили admin-ui | `admin-ui/src/tokens.css`, `admin-ui/src/admin-ui.css` |
| SPA fallback | `AdminSpaController` + `public/admin/.htaccess` |
| Скриншоты | `docs/stage-7.5/screenshots/` (Docker Apache :8080) |
| Demo seed (dev/test) | `POST /api/v1/admin/demo/seed` (`kernel.debug` или `KARKAS_ALLOW_DEMO_SEED`) |

---

## Скриншоты: макет / результат

Макет **390** у Дизайнера в png-наборе **нет** — в колонке «Макет 390» помечено **—**.

Снято на **чистой демо-базе**: `bootstrapBlogAdmin` + `POST /api/v1/admin/demo/seed` → `e2e/tests/stage75-screenshots.spec.ts` (1440×900 / 390×844).

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

### Редактор / история записи

| | 1440 | 390 |
|---|------|-----|
| **Макет** | — (editor / history в наборе v16 частично) | — |
| **Результат** | ![result post editor 1440](screenshots/result-1440-post-editor.png) | ![result post editor 390](screenshots/result-390-post-editor.png) |
| | ![result post history 1440](screenshots/result-1440-post-history.png) | ![result post history 390](screenshots/result-390-post-history.png) |

### Страницы / модули / сайты / настройки / операции / core / users / roles

Пары `mock-1440-*` / `result-1440-*` и `result-390-*` для: `categories`, `pages`, `modules`, `sites`, `settings`, `operations`, `core-update`, `users`, `roles` — каталог `docs/stage-7.5/screenshots/`.

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
| 7 | Привязка blog | экраны `/modules/blog/*` (v16) | `stage7-blog-facade.spec.ts` (desktop) |

---

## Роуты admin-ui

| Путь | Экран | API |
|------|--------|-----|
| `/admin/login` | staff-login карточка | session |
| `/admin/dashboard` | Обзор (stats, ops, posts) | `GET /api/v1/admin/dashboard` |
| `/admin/posts` | Таблица + поиск (client) | `GET /api/v1/blog/posts` |
| `/admin/posts/new`, `/:id`, `/:id/history` | Редактор / история | blog posts API |
| `/admin/categories` | Заглушка этап 8 | **нет API** |
| `/admin/pages` | tree-layout, move modal, preview | sites/pages API |
| `/admin/modules` | installer + ссылка на binding | platform/install |
| `/admin/modules/blog/{binding,unbind,unbind-ready,conflict,address-change,settings}` | v16 binding flows | bind/unbind API |
| `/admin/sites` | multisite + notice шаблонов | `/api/v1/sites` |
| `/admin/settings` | settings-layout (read-only поля) | ZIP/maintenance + multisite read |
| `/admin/core-update` | план совместимости | platform/plan |
| `/admin/operations` | log-entry карточки | `GET /api/v1/admin/platform/operations` |
| `/admin/users`, `/admin/roles` | Заглушка этап 8 | **нет API** |

---

## Вердикт по экранам (v16)

| Экран | Вердикт | Комментарий |
|-------|---------|-------------|
| Shell (sidebar, topbar) | **как в макете** | Без шеврона workspace; футер «Ядро Каркас …» |
| Вход (`staff-login`) | **как в макете** | Карточка, поля столбцом; 2FA не делали (этап 8) |
| Обзор | **как в макете** (данные API) | `GET /api/v1/admin/dashboard`; нет графиков из макета |
| Записи | **как в макете** (список) | Client-поиск/вкладки; WYSIWYG **отложено** |
| Редактор / история | **частично** | Plain text API, не article-editor.html |
| Категории | **нет API** | Заглушка этап 8 |
| Страницы (`tree.html`) | **как в макете** (без DnD) | Отступы, бейджи, move modal, preview; DnD **этап 8** |
| Модули | **частично** | Installer + panel binding; полный ZIP UI **отложено** |
| Blog binding (v16_1…) | **как в макете** (сценарии) | Отдельные routes; конфликт — UI без live conflict API |
| Сайты и шаблоны | **частично** | Multisite таблица + notice; вкладки шаблонов **нет API** |
| Настройки | **частично** | Секции макета; SMTP/secrets read-only «этап 8» |
| Core update | **как в макете** (idle/plan) | Мастер шагов без progress-bar CI |
| Операции | **как в макете** (журнал) | Человекочитаемые типы, МСК; cron/DLQ **нет API** |
| Пользователи / Роли | **нет API** | Заглушка |

---

## Тесты

- PHPUnit: `Stage75AdminDashboardHttpTest` (2), `Stage75BlogAdminHttpTest`, `Stage75BlogPostsHttpTest`, `Stage75AdminSpaHttpTest`
- Playwright: navigation, blog, screenshots, stage4–7 regressions

### Локальный Playwright + axe

`docker compose up`, `KARKAS_ADMIN_PASSWORD=test-admin-password-stage2`, `admin-ui` `npm run build`, `e2e`: `BASE_URL=http://127.0.0.1:8080 npm test`.

**Итог (96 specs): 93 passed, 3 skipped, 0 failed** (~49s, SHA `1a4fd3e`).

Skip:

- `stage6` move preview — mobile (форма проверена на desktop)
- `stage7-blog-facade` — mobile (bind/unbind на desktop)
- `stage6` move screenshot — mobile (как раньше)

Axe: `stage4`, `stage6` (modules panel), `stage7` search, smoke — **0 violations** (footer contrast `#4a5750`).

```
  3 skipped
  93 passed (48.5s)
```

---

## CI

PR **#17**, после push `1a4fd3e` — смотреть актуальный run в GitHub Actions. На `3b8b6f4` **playwright** падал на `apt/DNS` (`azure.archive.ubuntu.com`), тесты не стартовали; **Re-run** — Den. Workflow не меняли.

---

## Вопросы к Den (с предложениями)

1. **Категории** — минимальный read-only API в 7.5.1 или ждать этап 8?
2. **Шаблоны сайтов** — отдельный экран при появлении API или вкладка на `/sites`?
3. **Binding conflict** — нужен ли live API конфликта или достаточно статического `conflict` route для дизайна?
4. **Dashboard** — какие ещё агрегаты (графики, «сегодня») кроме текущего JSON?
5. **Дерево на mobile** — приоритет адаптации move-form под 390 или ждать макеты 390?
6. **Demo seed в prod** — достаточно ли `kernel.debug` + opt-in env, или нужен отдельный guard?
