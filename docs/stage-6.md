# Stage 6 — Den follow-ups from stage 5 (spec v0.8 G6 pragmatic slice)

## Goal

Close Den-approved answers to stage 5 open questions 1–4: noop Save without touching `updated_at`, explicit **single** default site (API + DB), admin **site selector** for blog enable. Leave fork PR CI policy unchanged (question 4).

## Delivered

| Area | Outcome |
|------|---------|
| Noop Save / `updated_at` | Identical payload → no row update (`Stage6BlogNoopUpdatedAtTest`) |
| Default site | `is_default` + generated `default_mark` unique index; API rejects second default (`Stage6DefaultSiteHttpTest`, `Stage6DefaultSiteConstraintTest`) |
| Admin | `<select data-testid="enable-blog-site">` → `POST /api/v1/sites/{id}/modules/karkas/blog/enable` |
| Schema | `PlatformStage6SchemaUpgrade`; baseline `stage3_platform.sql` updated |

## Den decisions (confirmed)

1. Noop Save does not update `updated_at`.
2. Explicit default flag; second default forbidden (validation + DB).
3. Admin site selector for blog enable.
4. Fork PRs without manual approval — workflow unchanged.

## Small spec-aligned decisions

- New sites default to `is_default: false`; only `POST` body may request default when none exists.
- `default_mark` generated column keeps MySQL partial-unique semantics (only one `is_default=1`).
- `/api/v1/sites/default/.../enable` route retained for compatibility; admin uses site id path.

## Known limitations

- No API to **transfer** default to another site (create-time default only).
- Blog create/save API still uses default site instance context (unchanged from stage 5).
- Full spec G6 queue II (domains/basepath, Landings, template DSL) not in this slice.

## Open questions (with proposal)

1. **API to reassign default site on existing row?** — Proposal: `PATCH /api/v1/sites/{id}` with transactional unset of previous default in stage 7.
2. **Show blog-enabled state per site in sites table?** — Proposal: add column after mount lookup in admin stage 7.
3. **Block delete of default site?** — Proposal: forbid until another site promoted (not implemented).

## Screenshots

`docs/stage-6/screenshots/` — Playwright full-page **1440** and **390** (admin with site selector); axe **0** violations on desktop.

## Tests

| Suite | Count | Notes |
|-------|------:|-------|
| PHPUnit | 47 | 132 assertions, 1 skipped; +3 stage-6 tests |
| Playwright | 20 | stage6 + smoke + stage4/5 (+ desktop/mobile) |

## CI

Green on final head `e7527dd`: https://github.com/dsefremov/karkas-cms/actions/runs/42902040040 — PR [#13](https://github.com/dsefremov/karkas-cms/pull/13) → `develop` (not merged).
