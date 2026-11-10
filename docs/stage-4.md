# Stage 4 — G4 hardening (core upgrade UI, version history, public slug)

## Goal (spec v0.8 G4 excerpt, pragmatic slice)

Extend G3 Blog with **last-10 version history** (prune on Save), **admin history UI**, **explicit Core upgrade** via install plan (`include_core`) with confirmation, and **public article by slug** with **301** from `blog_slug_redirect`. Den decisions on migrations-at-enable, journal, incoming scan, and local catalog remain unchanged.

**Out of scope (later G4+/II):** FULLTEXT search, tree/generations UI, cabinet RBAC, multisite CRUD, Landings/Services, complex template DSL.

## Delivered

| Area | Outcome |
|------|---------|
| Version prune | After each Save, `blog_post_version` trimmed to **10** rows (newest by `version_seq`) |
| History API | `GET /api/v1/blog/posts/{id}/versions` (admin session) |
| Core upgrade API | `POST /api/v1/platform/install` with `{ "modules": [], "include_core": true }` |
| Core upgrade UI | Current vs available, checkbox confirmation, `upgrade-core` button |
| Public slug | `GET /blog/{slug}`; **301** when slug changed via redirect table |
| Admin UI | Stage **4**, blog history table, core panel |
| Tests | `Stage4BlogVersionPruneTest`, `Stage4CoreUpgradeTest`, Playwright `stage4.spec.ts` |

## Den decisions (confirmed, not revisited)

1. Module migrations at **site enable** (unchanged).
2. Core upgrade in admin: **plan + `include_core`**, explicit confirmation, current/available shown.
3. **Prune to 10** versions on Save with **history UI**.

Prior Stage 2–3 policies: explicit core only via admin plan; incoming scan on button + plan; `platform_operation` in same MySQL; available versions from local catalog only.

## Small spec-aligned decisions

- Prune runs **after** append on Save (create keeps v1 only until first Save).
- History list returns at most 10 rows, descending `version_seq`.
- Core-only install allowed when `include_core` is true (empty `modules`).
- Slug redirect uses latest `to_slug` for a given `from_slug` (single-site default).

## Known limitations

- No diff/restore from history, ETag response headers, or FULLTEXT.
- History UI shows title/activity only (not full snapshot body).
- No staff RBAC beyond env admin password.
- Rollback remains registry-oriented for core/modules.

## Open questions (with proposal)

1. **Should prune delete versions created only by noop Saves?** — Proposal: yes, only non-noop Saves append versions (current behaviour).
2. **Expose plan preview before Core upgrade click?** — Proposal: add `GET /api/v1/platform/plan?include_core=1` in G5 when plan UI exists; stage 4 uses direct install after checkbox.
3. **Slug redirect scope across sites?** — Proposal: filter by `site_id` in stage 5 multisite work; default site only in v1.

## Screenshots

`docs/stage-4/screenshots/` — Playwright full-page at **1440** and **390** (admin with history panel); axe **0 violations** on desktop.

## Tests

| Suite | Count | Notes |
|-------|------:|-------|
| PHPUnit | 40 | 102 assertions, 1 skipped; +`Stage4BlogVersionPruneTest`, `Stage4CoreUpgradeTest` |
| Playwright | 10 | 2 specs × 2 viewports (smoke + stage4 core/history/screenshots) |

## CI

Green run for PR head `46ed781`: https://github.com/dsefremov/karkas-cms/actions/runs/42804585213
