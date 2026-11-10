# Stage 5 — multisite CRUD, Core plan preview, slug redirect scope (spec v0.8 G5 slice)

## Goal (spec v0.8 §27 G5 / queue II entry, pragmatic slice)

Close Stage 4 open questions with Den decisions: **noop Save** (no version/prune), **Core upgrade plan preview** in admin, **multisite CRUD** with **slug redirects scoped by `site_id`**. Continue queue-I blog/installer policies; defer FULLTEXT, cabinet, Landings/Services, template rule DSL.

## Delivered

| Area | Outcome |
|------|---------|
| Noop Save | Unchanged payload → `noop: true`, no version append, no prune (`Stage5BlogNoopSaveTest`) |
| Core plan preview | `GET /api/v1/platform/plan?include_core=1` + admin panel `load-core-plan` / `core-plan-preview` |
| Multisite | `GET/POST /api/v1/sites`, `site_key`, `canonical_host`, enable blog per `POST /api/v1/sites/{id}/modules/karkas/blog/enable` |
| Slug redirect | `blog_slug_redirect.site_id` + resolver filter; public site from host (`SiteRequestResolver`) |
| Schema | `platform_site` extended; idempotent `PlatformStage5SchemaUpgrade`; blog manifest checksum updated |
| Tests | `Stage5BlogNoopSaveTest`, `Stage5MultisiteSlugRedirectTest`, `Stage5CorePlanHttpTest`, `Stage5SitesHttpTest`, `e2e/stage5.spec.ts` |

## Den decisions (confirmed)

1. Save without content change does not create a version or run prune — covered by integration test.
2. Core upgrade plan preview via `GET .../plan?include_core=1` before the upgrade button.
3. Slug redirect filtering by `site_id` delivered together with multisite.

## Small spec-aligned decisions

- Unknown public host returns **404** (no silent default-site fallback except `localhost` / `127.0.0.1` for dev/CI).
- Second sites use unique `site_key` and optional `canonical_host` for host-based resolution.
- `include_core=1` on plan GET allows empty `modules[]` (core-only preview).
- Blog catalog `blog_initial.sql` migration lists `site_id` on redirect rows for new enables; platform upgrade backfills existing rows.

## Known limitations

- No domain DNS/TLS verification UI, site disable/archive, or per-site RBAC grants.
- No host+basepath longest-prefix routing (stage II).
- Blog create API remains on default site instance context (multisite posts via same API path as stage 4).
- G5 production readiness (forms privacy drill, backup restore evidence) not in this slice.

## Open questions (with proposal)

1. **Should noop Save still update `updated_at` metadata?** — Proposal: no DB writes on noop (current behaviour).
2. **Default site when multiple sites lack `canonical_host`?** — Proposal: require explicit default flag + reject duplicate defaults in a later migration.
3. **Enable blog on non-default site from admin UI button?** — Proposal: add site selector in stage 6; stage 5 uses API route only.

## Screenshots

`docs/stage-5/screenshots/` — Playwright full-page **1440** and **390** (admin with core plan preview + sites panel); axe **0 violations** on desktop (CI artifact).

## Tests

| Suite | Count | Notes |
|-------|------:|-------|
| PHPUnit | 44 | 122 assertions, 1 skipped; +4 stage-5 tests |
| Playwright | 8 | 2×viewport (smoke + stage4 + stage5: core plan, multisite, screenshots) |

## CI

_(Green run URL updated on final push to `grok_bot`.)_
