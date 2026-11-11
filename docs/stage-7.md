# Stage 7 — FULLTEXT search, canonical paths, default site ops

## Goal

`grok_bot` → `develop`: search (spec v0.8 plan), убрать канонический `/p/` (301 legacy), решения Den по сайту по умолчанию и колонке блога. **Не мержить** до ревью.

## Delivered — Search

| Area | Outcome |
|------|---------|
| MySQL FULLTEXT | `SearchIndexService::ensureFulltextIndexes()` на `blog_post` и `site_page` (после stage7 upgrade и при enable blog) |
| Публичный UI | `GET /search` — форма, сниппеты, пагинация; `SiteSearchService::MIN_TOKEN_LENGTH = 3`; статусы empty/short query |
| Фильтры | Только **active** посты и страницы; UNION без N+1 |
| Тесты | `Stage7SearchTest`, `Stage7SearchHttpTest` |

## Delivered — URL hygiene

| Area | Outcome |
|------|---------|
| Канон | Ссылки в шаблонах поиска: `/blog/{slug}`, `/{full_path}` |
| Legacy | `GET /p/{slug}` → **301** на `full_path` (`LegacyFlatPageController`) |
| Sitemap / SEO helpers | Ещё нет генератора sitemap — при появлении только `full_path` |

## Delivered — Den (sites)

| Area | Outcome |
|------|---------|
| Make default | `POST /api/v1/sites/{id}/make-default` — транзакционный сброс старого флага |
| Колонка «Блог включён» | `blog_enabled` в `GET /api/v1/sites` + таблица админки |
| Удаление default | API **409** + текст в UI: сначала передать default другому сайту |
| Enable blog «по умолчанию» | Включается на сайте с `is_default = 1` (не жёстко `id = 1`) |
| Дочерние страницы | Как в stage 6 — удаление с детьми запрещено (каскад не делаем) |
| Ревизии страниц | Не в scope (этап 8+) |

## Мелкие решения и ограничения

- FULLTEXT — natural language mode; MySQL `ft_min_word_len` / `innodb_ft_min_token_size` на стороне хостинга (ожидаем ≥ 3).
- Индексы blog создаются только когда таблица `blog_post` уже есть.
- Поиск привязан к **текущему** публичному сайту (host → site, иначе default).
- Legacy `/p/{slug}` разрешается по slug на сайте (коротest `full_path` при коллизии slug).
- Sitemap, медиа, cabinet — вне этапа.

## v1 coverage (one site) — status for Den

| Capability | Status | Порядок / комментарий |
|------------|--------|------------------------|
| Blog + PageTree + 301 | **Done** (stages 3–6) | — |
| FULLTEXT search | **Done** (this PR) | — |
| Public cabinet + MFA + staff RBAC | **None** | **Этап 8** |
| Categories N:M | **None** | После 8 |
| Media | **None** | После категорий |
| Mail + cron outbox | **None** | После медиа |
| Install wizard (protected) | **Partial** | После почты/cron |
| Blog module settings UI | **Partial** (enable only) | Параллельно v1 |
| Module ZIP policy file | **Partial** | Параллельно v1 |
| Release / GA packaging | **None** | **Последним** |
| Multisite / Landings / Services | **Out of I** | Очередь II |

## Open questions (варианты агента)

1. **Поиск на не-default сайте** — Сейчас только публичный контекст сайта. *Вариант:* глобальный поиск в админке по всем сайтам (этап 9+).
2. **Подсветка в сниппетах** — Сейчас plain text excerpt. *Вариант:* `<mark>` по термам без XSS (Twig escape).
3. **Sitemap** — *Вариант:* статический XML при stage 8 SEO slice, URL = `full_path` + `/blog/{slug}`.

## Screenshots

| File | Viewport |
|------|----------|
| `docs/stage-7/screenshots/search-1440.png` | Поиск desktop |
| `docs/stage-7/screenshots/search-390.png` | Поиск mobile |
| `docs/stage-7/screenshots/admin-1440.png` | Админка (сайты) 1440 |
| `docs/stage-7/screenshots/admin-390.png` | Админка 390 |

## Tests

| Suite | Count | Notes |
|-------|------:|-------|
| PHPUnit | 59 | 175 assertions, 1 skipped |
| Playwright stage7 | 6 | axe 0 на `search-main` и `sites-panel` (desktop) |

## CI

_Обновляется финальным коммитом (head + run URL)._
