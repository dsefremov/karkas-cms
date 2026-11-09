# Stage 3 — G3 feature slice (blog enable, migrations, mount, manual Save)

## Goal (spec §27 **G3** excerpt)

Deliver the **queue I vertical slice** for Blog on a **single configured site**: global install → **site enable** with DDL migrations, **direct mount** `/blog`, **Twig** public list, and **manual Save** API (default **inactive**, full-payload **no-op**, slug redirect row on slug change). Apply Den’s Stage 2 follow-ups on installer/core policy, incoming scan, and journal storage.

**Out of scope (Stage 4 / G4+):** tree/generations, FULLTEXT search, cabinet identity, history last-10 UI, complex templates, multisite CRUD, Landings/Services.

## Delivered

| Area | Outcome |
|------|---------|
| Site model | `platform_site` (singleton id=1, editable label) |
| Site enable | `site_module`, `module_instance`, `module_mount`; `POST /api/v1/sites/default/modules/karkas/blog/enable` |
| Migrations | `ModuleMigrationRunner` + `applied_module_migration`; `blog_initial.sql` in catalog |
| Blog DDL | `blog_post`, `blog_post_version`, `blog_slug_redirect` |
| Blog API | `POST /api/v1/blog/posts`, `POST /api/v1/blog/posts/{id}/save` |
| Public | `GET /blog` Twig list (active posts only) |
| Catalog | Versioned core `1.0.0` (installer) / `1.1.0` (available) under `resources/package-catalog/karkas/core/{version}/` |
| Incoming | Scan+ingest on **plan** and **admin button** `POST /api/v1/admin/packages/scan-incoming` |
| Installer policy | Pins **baseline** `karkas.platform.core_version` (`1.0.0`); never latest catalog automatically |
| Core update | Explicit `POST /api/v1/platform/install` with `"include_core": true` |
| Admin UI | Enable blog, scan incoming, demo Save; stage **3** |
| Tests | `Stage3BlogEnableFlowTest`, updated HTTP/e2e |

## Den decisions (Stage 2 open questions → applied)

1. **Core updates** — Installer stages catalog core **`1.0.0` only**. Newer core (`1.1.0`) via authorized platform install plan with `include_core`.
2. **Incoming scan** — Bounded READY-folder scan on **plan request** and **admin button**; no cron.
3. **Operation journal** — Remains `platform_operation` in the **same MySQL** database (v1).

## Configurable limits

Unchanged from Stage 2 (`karkas.package.limits` in `config/packages/karkas_stage2.yaml`).

## Small spec-aligned decisions

- Migrations run at **site enable** (DDL may auto-commit; no whole-transaction DDL claim).
- Blog **create** defaults `activity=inactive`; public list shows **active** only.
- **No-op Save** when normalized payload hash equals stored `content_etag`.
- **301 preparation**: `blog_slug_redirect` row on slug change (routing layer uses in G4).
- Plan payload includes `incoming_scan` metadata.

## Known limitations

- No history last-10/diff/restore UI, ETag headers, or category bindings.
- No staff RBAC, cabinet, cron/outbox, SMTP, or MFA beyond Stage 2 env password.
- Generic Page workflow, search, tree, and template rule DSL are G4/II.
- Rollback still registry-metadata oriented for core/modules.

## Open questions

1. Should enable run migrations at global install (schema-ready) instead of site enable?
2. When to expose `include_core` upgrade in admin UI vs API-only?
3. Prune `blog_post_version` to last 10 at Save time (spec) — deferred to G4 hardening?

## Screenshots

See `docs/stage-3/screenshots/` (Playwright + axe at 1440px and 390px).

## Tests

| Suite | Count | Notes |
|-------|------:|-------|
| PHPUnit | 38 | 94 assertions, 1 skipped |
| Playwright | 4 | 2 viewports × {home, admin blog enable + /blog} |

## CI

_(PR check link added after push.)_
