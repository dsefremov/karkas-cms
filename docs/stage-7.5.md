# Stage 7.5 — админка по макету v16

Ветка **`grok_bot`** (база `develop`), **не мержить** до ревью Den. Этап 8 не начат.

**code-head:** `4bd831c` (коммиты этого прохода — см. `git log 43ef5a8..grok_bot`)

---

## Что сделано в этом проходе

| # | Пункт | Статус | Как проверено |
|---|---|---|---|
| 1 | e2e stage4/stage5: обновление ядра | **готово** | `CoreUpdatePage` показывает текущую и доступную версию ядра. `helpers/core-upgrade-flow.ts` ждёт данные (`core-version-summary` / `core-version-available`) и ветвится: на чистой базе (Core 1.0.0, в каталоге 1.1.0) — полное обновление (кнопка disabled → чекбокс → enabled, шаг плана `karkas/core 1.0.0 → 1.1.0`, сообщение «Core обновлён до 1.1.0», summary, пустой план); если ядро уже обновлено — проверяется idle-состояние. В прогоне: desktop — `upgraded`, mobile — `already-current` (та же база). |
| 2 | Flake `stage75-blog-admin` | **готово** | Перед `/admin/modules` ждём `admin-authenticated`, URL и `admin-shell` (без force/таймаутов). |
| 3 | Скриншоты только на свежей демо-базе | **готово** | `stage75-screenshots.spec.ts` пропускается без `KARKAS_SCREENSHOTS=1`, поэтому в обычном `npm test` (и в CI) не трогает e2e-базу. Снимки делает `scripts/capture-stage75-screenshots.sh`: `down -v` → `up mysql app` → reset+seed демо → снимки 1440/390. |
| 4 | Соответствие макету v16 | **готово, с оговорками ниже** | Дерево: строки на всю ширину, отступ по уровню, свёртка, статус в отдельной колонке. Записи: категория, автор, дата/время МСК, поиск + фильтры, сортировка, пагинация по 10. Обновление ядра: таблица совместимости, требования, план, `karkas/core` один раз. Операции: человекочитаемые типы и статусы, МСК. |
| 5 | Названия вместо ID, компоненты вместо `<select>`, без «(макет v16)» | **готово** | Сайт везде — по названию (при дублях названия добавляется домен). Нативные `select` заменены на `AdminSelect` (combobox + listbox, клавиатура). Подписи «(макет v16)» и упоминания макета в UI убраны. |
| 6 | 390: без горизонтального вылета, сворачиваемый сайдбар | **готово** | Новый `stage75-layout-overflow.spec.ts` проверяет все экраны админки на 1440 и 390. `overflow-x: hidden` с `.admin-main` **убран**; широкие таблицы на 390 прокручиваются внутри своей карточки (`.table-card { overflow-x: auto }`), записи на 390 — карточками. Сайдбар на ≤960px — выезжающий, кнопка `admin-menu-toggle`, закрытие по Esc/подложке/смене маршрута. |
| 7 | Этот документ | **готово** | Пары макет/результат ниже; ссылка на CI — плейсхолдер. |

### Оговорки (честно)

- **Категории и авторы:** API нет до этапа 8. В списке записей колонка «Категория» показывает «Без категории», «Автор» — «Служебный админ» (единственный пользователь); фильтры работают по этим полям, когда API их отдаст (поля `category?` / `author?` уже в типе).
- **Дерево страниц:** в демо-данных нет «Главной» и узла «Журнал (Модуль: Блог)» из макета — показано реальное дерево из API. Drag-and-drop — этап 8, перемещение через форму.
- **Пользователи / Роли / Категории:** экраны-заглушки «нет API (этап 8)».
- **390:** макетов 390 в наборе нет — сравнивали с 1440-макетом по структуре. Таблицы модулей/сайтов/совместимости на 390 прокручиваются внутри карточки — это допустимо, страница целиком по ширине не вылезает (проверяется тестом).
- **Операции:** отдельного макета нет; `mock-1440-operations.png` раньше был копией макета обзора — удалён, чтобы не вводить в заблуждение.
- Запись журнала `package.install` для ядра называется «Установка пакетов» (по payload нельзя отличить первую установку от обновления).

### Demo reset / seed

`POST /api/v1/admin/demo/seed` и `/demo/reset` работают **только** при `APP_ENV=dev|test` **и** `KARKAS_ALLOW_DEMO_SEED=1` (`DemoApiGate`); иначе **404**, в prod — всегда 404 (`AdminDemoRoutesEnvironmentTest`: матрица gate, prod-kernel → 404, test+флаг → 401 без сессии). В CI-джобе Playwright флаг приходит из `docker-compose.yml` (`KARKAS_ALLOW_DEMO_SEED: ${KARKAS_ALLOW_DEMO_SEED:-1}` в env сервиса app) — workflow не менялся.

Ручной сброс демо-базы: `./scripts/reset-demo-database.sh api|volume`; снимки целиком — `./scripts/capture-stage75-screenshots.sh` (на этом боксе: `DOCKER_COMPOSE="sudo docker compose" COMPOSE_FILES="-f docker-compose.yml -f docker-compose.mysql84.yml"`).

---

## Скриншоты: макет / результат

Результаты сняты на **свежей демо-базе** (`capture-stage75-screenshots.sh`, MySQL 8.4), не на e2e-базе. Макетов 390 нет.

### Вход

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет login 1440](screenshots/mock-1440-login.png) | ![результат login 1440](screenshots/result-1440-login.png) | ![результат login 390](screenshots/result-390-login.png) |

### Обзор

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет dashboard 1440](screenshots/mock-1440-dashboard.png) | ![результат dashboard 1440](screenshots/result-1440-dashboard.png) | ![результат dashboard 390](screenshots/result-390-dashboard.png) |

### Записи

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет posts 1440](screenshots/mock-1440-posts.png) | ![результат posts 1440](screenshots/result-1440-posts.png) | ![результат posts 390](screenshots/result-390-posts.png) |

### Редактор записи

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет post-editor 1440](screenshots/mock-1440-post-editor.png) | ![результат post-editor 1440](screenshots/result-1440-post-editor.png) | ![результат post-editor 390](screenshots/result-390-post-editor.png) |

### История версий

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет post-history 1440](screenshots/mock-1440-post-history.png) | ![результат post-history 1440](screenshots/result-1440-post-history.png) | ![результат post-history 390](screenshots/result-390-post-history.png) |

### Категории

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет categories 1440](screenshots/mock-1440-categories.png) | ![результат categories 1440](screenshots/result-1440-categories.png) | ![результат categories 390](screenshots/result-390-categories.png) |

### Страницы (дерево)

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет pages 1440](screenshots/mock-1440-pages.png) | ![результат pages 1440](screenshots/result-1440-pages.png) | ![результат pages 390](screenshots/result-390-pages.png) |

### Модули

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет modules 1440](screenshots/mock-1440-modules.png) | ![результат modules 1440](screenshots/result-1440-modules.png) | ![результат modules 390](screenshots/result-390-modules.png) |

### Сайты и шаблоны

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет sites 1440](screenshots/mock-1440-sites.png) | ![результат sites 1440](screenshots/result-1440-sites.png) | ![результат sites 390](screenshots/result-390-sites.png) |

### Настройки

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет settings 1440](screenshots/mock-1440-settings.png) | ![результат settings 1440](screenshots/result-1440-settings.png) | ![результат settings 390](screenshots/result-390-settings.png) |

### Операции

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| — (отдельного макета нет в наборе v16; ближайший — блок «Фоновые операции» на обзоре) | ![результат operations 1440](screenshots/result-1440-operations.png) | ![результат operations 390](screenshots/result-390-operations.png) |

### Обновление ядра

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет core-update 1440](screenshots/mock-1440-core-update.png) | ![результат core-update 1440](screenshots/result-1440-core-update.png) | ![результат core-update 390](screenshots/result-390-core-update.png) |

### Пользователи

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет users 1440](screenshots/mock-1440-users.png) | ![результат users 1440](screenshots/result-1440-users.png) | ![результат users 390](screenshots/result-390-users.png) |

### Роли и доступ

| Макет 1440 | Результат 1440 | Результат 390 |
|---|---|---|
| ![макет roles 1440](screenshots/mock-1440-roles.png) | ![результат roles 1440](screenshots/result-1440-roles.png) | ![результат roles 390](screenshots/result-390-roles.png) |

---

## Локальная проверка (как CI, Docker Compose, PHP 8.4 + MySQL 8.4)

Финальные два раунда на коде `4bd831c` (тот же код, что в коммитах; 21.11.2026 ночью по МСК). Скрипт раунда — локальный (`/workspace/karkas-proof/ci-round.sh`, не в репозитории); шаги повторяют джобы `.github/workflows/ci.yml`.

| Шаг | Команда | Раунд 1 | Раунд 2 |
|---|---|---|---|
| php-static | `composer phpstan && composer lint:php` (образ `karkas-cms-cli`, PHP 8.4) | phpstan `[OK] No errors`, cs-fixer 0 файлов | то же |
| PHPUnit | `vendor/bin/phpunit` с `DATABASE_URL=mysql://…/karkas_test?serverVersion=8.4`, `APP_SECRET` как в CI | Tests 74, Assertions 257, Skipped 1 (`ApacheWebSapiProbeTest` — нужен `STAGE0_HTTP_PROBE_BASE_URL`), 0 failures | то же |
| admin-ui | `npm run lint && npm run typecheck && npm run build` | 0 errors, 2 warnings (уже были до прохода: react-refresh, exhaustive-deps в AdminContext) | то же |
| Playwright + axe | свежий стек `docker compose up -d mysql app` (MySQL 8.4), `CI=1 npx playwright test --retries=0 --workers=1`, проекты desktop-chrome 1440 и mobile-chrome 390 | **89 passed, 31 skipped, 0 failed** | **89 passed, 31 skipped, 0 failed** |

31 skipped = 28 снимков `stage75-screenshots` (opt-in, `KARKAS_SCREENSHOTS=1`) + 3 desktop-only теста на mobile (`stage6` ×2, `stage7-blog-facade`). Retries выключены (в CI — 1). Обновление ядра: desktop — `upgraded` (1.0.0 → 1.1.0), mobile — `already-current`.

Скриншоты: `./scripts/capture-stage75-screenshots.sh` на свежей демо-базе — 30 passed (28 снимков + 2 проверки).

Отличия от CI: Playwright-джоба CI использует MySQL 8.0 (локально 8.4); Node локально 24, в CI 22.

---

## CI

Зелёный run GitHub Actions для этого head: **_ссылка на CI run — добавить после push_**. Workflow-файлы не менялись; Actions в этом проходе не запускались.

---

## Вопросы к Den (с предложениями)

1. **Категории и авторы записей** — в списке пока «Без категории» / «Служебный админ». *Предложение:* отдать `category`/`author` в `GET /api/v1/blog/posts` на этапе 8 вместе с API категорий и пользователей; UI уже готов.
2. **Узел «Журнал (Модуль: Блог)» в демо-дереве** — *предложение:* в демо-seed привязывать блог к странице, чтобы дерево совпадало с макетом.
3. **Подключения к БД** — `DatabaseConnectionFactory::create()` открывает новое соединение на каждый вызов (66 мест). Не менял (риск для транзакций). *Предложение:* отдельной задачей сделать request-scoped соединение.
4. **MySQL в CI** — Playwright-джоба CI использует MySQL 8.0 (default compose), локально гоняли 8.4. *Предложение:* оставить как есть (PHPUnit-матрица CI покрывает обе версии).
5. **Node** — локально Node 24, в CI 22. *Предложение:* ничего не менять; если появятся расхождения сборки — зафиксировать `engines`.
