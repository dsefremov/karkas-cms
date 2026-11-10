# Stage 6 — default site, blog fixes, PageTree (nested URLs)

## Goal

Complete stage 6 on branch `grok_bot` / PR [#13](https://github.com/dsefremov/karkas-cms/pull/13): Den decisions 1–4 (noop Save, single default site, admin site selector for blog enable) **plus** PageTree with nested paths, 301 redirects, admin tree UI, public delivery, tests.

## Spec alignment note

Spec v0.8 §12 mockups still describe **flat** `/p/{slug}` URLs unchanged on move; Den’s approved **stage 6 plan** requires **nested paths from parent slug chain** and **301 for page + descendants** on move/slug change. Implementation follows the **stage 6 plan** (this section).

## Delivered — Decisions 1–4

| Area | Outcome |
|------|---------|
| Noop Save / `updated_at` | Identical payload → no row update (`Stage6BlogNoopUpdatedAtTest`) |
| Default site | `is_default` + `default_mark` unique index; API rejects second default |
| Admin blog | `<select data-testid="enable-blog-site">` → per-site blog enable |
| Fork PR CI | Unchanged |

## Delivered — PageTree (stage 6 remainder)

| Area | Outcome |
|------|---------|
| Schema | `site_page`, `site_page_redirect`; `PlatformStage6PageTreeSchemaUpgrade` |
| Paths | Materialized `full_path` = chain of slugs (`about/team`) |
| CRUD API | `GET/POST /api/v1/sites/{id}/pages/tree`, create/save/delete, move + move-preview |
| Move | Cycle guard, slug/path conflicts, mount `/blog` + reserved segments; transactional move + redirects |
| Public | `GET /{nested-path}`; inactive → 404; old paths → **301** via `site_page_redirect` |
| Admin | Panel `page-tree-panel`: tree list, keyboard up/down reorder, move preview impact count |
| Tests | `Stage6PageTreeTest`, `Stage6PageTreeHttpTest`; Playwright tree + move screenshots + axe |

## v1 coverage (one site) — status for Den

| Capability | Status | Remaining (est. days) |
|------------|--------|----------------------:|
| Blog (posts, save, history10, 301 slug) | **Done** (stages 3–5) | 0 |
| PageTree nested URLs + move + 301 | **Done** (this PR) | 0 |
| FULLTEXT search | **None** | 4–6 |
| Public cabinet (register/login/profile) | **None** | 6–8 |
| Staff MFA + RBAC | **Partial** (admin password only) | 5–7 |
| Categories N:M | **None** | 3–4 |
| Media | **None** | 4–5 |
| Mail + cron outbox | **None** | 4–6 |
| Install wizard (protected) | **Partial** (bootstrap API) | 5–8 |
| Blog module settings UI | **Partial** (enable only) | 2–3 |
| Module ZIP policy file | **Partial** (read-only flag) | 2–3 |
| Multisite / Landings / Services | **Out of I** (queue II) | — |

**Rough total remaining for v1 I-queue:** ~35–50 person-days (unchanged order of magnitude; PageTree slice closed).

## Open questions

1. **Flat vs nested URL in sitemap/SEO helpers** — Proposal: canonical = `full_path` only; drop `/p/` prefix everywhere in docs/UI copy (stage 7 hygiene).
2. **Delete cascade** — Current: reject delete when children exist; Proposal: optional «удалить с потомками» with audit (stage 7).
3. **Page revisions workflow (v0.4)** — Not in this slice; generic pages still single-row + activity boolean; Proposal: stage 8+.

## Screenshots

Playwright artifacts: `tree-1440`, `tree-390`, `tree-move-1440`, `admin-1440`, `admin-390` — copied to `docs/stage-6/screenshots/` after green CI.

## Tests

| Suite | Notes |
|-------|--------|
| PHPUnit | Page path build, cycle, mount/reserved conflict, descendant 301, rollback on failed move; + stage 6 prior tests |
| Playwright | Tree seed, move preview, axe on tree panel (1440) |

## CI

_Pending final green run — head/run updated in closing doc commit._
