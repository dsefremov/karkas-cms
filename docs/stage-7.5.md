# Stage 7.5 — Admin shell по макетам Дизайнера

Ветка **`grok_bot` → `develop`**, PR **#19** (доработка после ревью 12.11), **не мержить** до ревью Den.

**code-head:** `bcf04ff`  
**docs-head:** `bcf04ff`

---

## Цель

Светлая админка на `tokens.css`, роутинг `/admin/...`, сайдбар как в макете v16, экраны на существующем API (этапы 3–7), фиксы blog (п. 4) и расхождений ревью 12.11.

## Артефакты

| Артефакт | Путь |
|----------|------|
| HTML + `tokens.css` + `png/` | `docs/mockups/` |
| Стили admin-ui | `admin-ui/src/tokens.css`, `admin-ui/src/admin-ui.css` |
| SPA fallback | `AdminSpaController` + `public/admin/.htaccess` |
| Скриншоты | `docs/stage-7.5/screenshots/` (Docker Apache :8080) |
| Demo seed (dev/test) | `POST /api/v1/admin/demo/seed` (`kernel.debug` или `KARKAS_ALLOW_DEMO_SEED`) |
| **Чистая база для скринов** | `POST /api/v1/admin/demo/reset` + `scripts/reset-demo-database.sh` (`api` \| `volume`) |

### Чистая демо-база (скриншоты, не e2e)

Чтобы в «Сайтах» не копились e2e-сайты:

1. `./scripts/reset-demo-database.sh api` — сессия + `demo/reset` (purge не-default сайтов + seed).
2. `./scripts/reset-demo-database.sh volume` — пересоздать том MySQL, миграции, затем `api`.

`e2e/tests/stage75-screenshots.spec.ts` вызывает **`resetAdminDemoForScreenshots`**, не `seed` alone. Полный Playwright-прогон на reset-базе **не гонять**.

---

## Скриншоты: макет / результат

Макет **390** у Дизайнера в png-наборе **нет** — в колонке «Макет 390» помечено **—**.

**Обновление пар mock/result (1440 и 390):** после `reset-demo-database.sh` и `stage75-screenshots.spec.ts` — по команде Den «тесты: go». Ниже — структура каталога; актуальные `result-*` заменят старые снимки с «грязной» базой.

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
| 8 | Flake после login | `admin-authenticated` + shell до `/modules` | `stage75-blog-admin.spec.ts` |

---

## Роуты admin-ui

| Путь | Экран | API |
|------|--------|-----|
| `/admin/login` | staff-login карточка | session |
| `/admin/dashboard` | Обзор (stats, ops, posts) | `GET /api/v1/admin/dashboard` |
| `/admin/posts` | Таблица, фильтры, пагинация | `GET /api/v1/blog/posts` |
| `/admin/posts/new`, `/:id`, `/:id/history` | Редактор / история | blog posts API |
| `/admin/categories` | Заглушка этап 8 | **нет API** |
| `/admin/pages` | tree-layout, move modal, preview | sites/pages API |
| `/admin/modules` | installer + ссылка на binding | platform/install |
| `/admin/modules/blog/{binding,unbind,…}` | binding flows | bind/unbind API |
| `/admin/sites` | multisite + notice шаблонов | `/api/v1/sites` |
| `/admin/settings` | settings-layout (read-only поля) | ZIP/maintenance + multisite read |
| `/admin/core-update` | текущая + доступная, план, confirm | platform/plan + upgrade |
| `/admin/operations` | log-entry карточки | `GET /api/v1/admin/platform/operations` |
| `/admin/users`, `/admin/roles` | Заглушка этап 8 | **нет API** |

---

## Вердикт по экранам (v16, ревью 12.11)

| Экран | Вердикт | Комментарий |
|-------|---------|-------------|
| Shell (sidebar, topbar) | **как в макете** | Workspace по **названию** сайта; на ≤960px — выезжающий сайдбар + menu, `overflow-x` без горизонтального вылета |
| Вход (`staff-login`) | **как в макете** | Карточка; 2FA — этап 8 |
| Обзор | **как в макете** (данные API) | Без графиков из макета |
| Записи | **как в макете** (список) | Категория / автор / дата **МСК**, вкладки, поиск, сортировка, пагинация (client); WYSIWYG **отложено** |
| Редактор / история | **частично** | Plain text API; статус через `AdminSelect` |
| Категории | **нет API** | Заглушка этап 8 |
| Страницы (`tree.html`) | **как в макете** (без DnD) | Строка на всю ширину, **статус в колонке**; move modal; DnD **этап 8** |
| Модули | **частично** | Installer; сайт в `AdminSelect` по label |
| Blog binding | **как в макете** (сценарии) | Instance — название сайта, без дубля `site_key` |
| Сайты | **частично** | Таблица с id/key (админ-данные); скрины — на reset-базе |
| Настройки | **частично** | Секции макета; SMTP **этап 8** |
| Core update | **как в макете** (после фикса) | **Текущая + доступная** версии; таблица совместимости, требования, план **без дубля** `karkas/core` в шагах |
| Операции | **как в макете** (журнал) | Человекочитаемые типы/статусы, МСК |
| Пользователи / Роли | **нет API** | Заглушка |
| Mobile 390 | **частично → ок по shell** | Сайдбар collapsible; контентные таблицы на узкой ширине всё ещё плотные |

---

## Тесты

- PHPUnit: stage75 suite
- Playwright: `stage4`/`stage5` core upgrade по `upgrade-core` / `core-upgrade-idle`, не по тексту summary; `helpers/core-upgrade-flow.ts`

### Прогон (ожидает «тесты: go»)

Локальный и **PR CI** на push `grok_bot` — зафиксировать зелёный run в этом разделе после прогона.

PHPUnit / Playwright / axe / screenshot spec — **не запускались** в кодовом проходе (ограничение Den).

---

## CI

PR **`grok_bot` → `develop`** — смотреть актуальный зелёный **Playwright + PHPUnit** workflow на GitHub Actions после push (не только локальный итог). Workflow файлы не менялись.

---

## Вопросы к Den (с предложениями)

1. **Категории** — минимальный read-only API в 7.5.1 или ждать этап 8?
2. **Шаблоны сайтов** — отдельный экран при появлении API или вкладка на `/sites`?
3. **Binding conflict** — live API конфликта или статический route?
4. **Dashboard** — доп. агрегаты (графики)?
5. **Дерево на mobile** — адаптация move-form под 390?
6. **Demo reset** — достаточно ли guard как у seed, или отдельный env для prod-like?