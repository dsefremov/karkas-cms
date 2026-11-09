# Stage 2 — Package delivery, install orchestration, registry

## Goal (spec §6–§7, roadmap G2 excerpt)

Deliver **unsigned package ingestion** (ZIP quarantine + folder/READY), **bounded archive safety**, **staging**, **protected installer bootstrap**, **atomic install orchestration** with **operation journal**, **maintenance mode**, **single-generation rollback slot**, and **MySQL-backed installation registry** so admin shows **current** module/core versions. ZIP upload stays **off** until `var/flags/zip-upload.enabled` exists on the server.

Out of scope: full MFA stack, remote version feeds, site enable/disable UI, DDL migration runner, Blog feature (G3+).

## Delivered

| Area | Outcome |
|------|---------|
| Ingestion | `ZipQuarantineExtractor`, `IncomingFolderIngestor` (READY marker), `FolderIngestService`, `ZipIngestService` |
| Limits | Configurable `PackageDeliveryLimits` (see table below) |
| Staging | `PackageStagingService` → immutable `var/packages/staging/{id}/{version}/{digest}/` |
| Installer API | `POST /api/v1/installer/bootstrap`, `POST /api/v1/installer/complete` |
| Plan API | `GET` + `POST /api/v1/platform/plan` |
| Install API | `POST /api/v1/platform/install` (admin + `X-Karkas-Reauth`) |
| Registry | Tables `module_installation`, `platform_core_state`; versions API reads DB |
| Journal | `platform_operation` + `GET /api/v1/admin/platform/operations` |
| Maintenance | DB flag + subscriber blocking non-admin **POST** while enabled |
| Rollback | `deployment_rollback_slot` (one row) + `POST /api/v1/admin/platform/rollback` |
| Admin auth | Session login `POST /api/v1/admin/session`; re-auth header for risky ops |
| ZIP gate | `GET/POST /api/v1/admin/packages/zip-upload` (POST requires flag file) |
| Admin UI | Login, installer, blog install, versions table, ZIP disabled indicator |

## Configurable ZIP / manifest limits (defaults)

| Parameter | Default | Meaning |
|-----------|--------:|---------|
| `zip_max_compressed_bytes` | 52428800 (50 MiB) | Max uploaded ZIP size |
| `manifest_max_bytes` | 1048576 (1 MiB) | Max uncompressed `manifest.json` size |
| `max_zip_entries` | 5000 | Max ZIP central directory entries |
| `max_files_in_package` | 2000 | Max manifest file rows / extracted files |
| `max_uncompressed_bytes_total` | 209715200 (200 MiB) | Total extracted payload budget |
| `max_uncompressed_bytes_per_file` | 52428800 (50 MiB) | Per-file uncompressed cap |
| `max_compression_ratio` | 200 | Zip-bomb ratio guard (uncompressed/compressed) |
| `max_path_depth` | 12 | Max `/`-separated segments |
| `max_path_segment_bytes` | 120 | Max bytes per path segment |
| `max_processing_walltime_seconds` | 120 | Extraction time budget |

Defined in `config/packages/karkas_stage2.yaml` under `karkas.package.limits`.

## Open questions from Stage 1 — decisions (Den)

1. **`requires.core` on core manifest** — Removed. Core manifests require only `sdk` + `php`; modules still require `requires.core` (spec §6).
2. **ZIP limits** — Configurable with defaults above (`karkas.package.limits`).
3. **Available versions source** — Local catalog only; network feed is phase 2.
4. **`manifest_digest` in catalog** — Added to `resources/package-catalog/*/manifest.json` (digest of JSON **without** the `manifest_digest` field).
5. **Plan preview endpoint** — `GET` (query `modules[]`) and `POST` (JSON body `{ "modules": ["karkas/blog"] }`) at `/api/v1/platform/plan`; install uses `POST /api/v1/platform/install`.

## Small spec-aligned decisions

- Admin re-auth via `X-Karkas-Reauth` within 5 minutes after login (no separate MFA implementation in this stage).
- ZIP upload disabled unless `var/flags/zip-upload.enabled` file exists (server-side flag, not env).
- Install enables **global maintenance** for the duration of the orchestrated operation.
- Rollback slot overwritten on each install preamble; only **one** saved generation (spec §6).
- Rollback in Stage 2 restores **registry versions** (metadata); filesystem paths are unchanged from pre-slot staging copies.
- Installer `bootstrap` applies `resources/schema/stage2_platform.sql` idempotently (`CREATE TABLE IF NOT EXISTS`).

## Known limitations

- No module enable/site scope, migrations execution, or Core updater artifact switch (remaining G2/G3).
- Rollback does not yet restore binary staging paths or run compatibility proofs beyond version metadata.
- ZIP upload tested with guards; production should tune limits per Timeweb preflight.
- Admin password is env-based (`KARKAS_ADMIN_PASSWORD`); staff identity/RBAC arrives later.

## Open questions

1. Should installer `complete` pin Core to catalog `1.1.0` or configurable deployment baseline `karkas.platform.core_version`?
2. When to auto-scan `var/packages/incoming` (cron vs admin button only)?
3. Persist operation journal payloads to external audit store on shared hosting?

## Screenshots

![Admin desktop](/opt/cursor/artifacts/admin-1440.png)

![Admin mobile](/opt/cursor/artifacts/admin-390.png)

Artifacts (also in repo): `docs/stage-2/screenshots/{home,admin}-{1440,390}.png` — Playwright + axe (0 violations) at 1440px and 390px.

## Tests

| Suite | Count | Notes |
|-------|------:|-------|
| PHPUnit (unit + integration + http + hosting-check) | 37 | 90 assertions, 1 skipped (DB-less hosting probe) |
| Playwright e2e | 4 | 2 viewports × {home, admin install + axe} |

New Stage 2 coverage includes ZIP quarantine refusal, install/rollback/downgrade integration, maintenance + admin HTTP flows, and installer bootstrap.

## CI

PR CI run: https://github.com/dsefremov/karkas-cms/actions/runs/42652731184

## Additional small decisions (this PR)

- `manifest_digest` validation hashes catalog JSON **without** the `manifest_digest` field (matches catalog examples).
- Doctrine DBAL 4 connection bootstrap uses `DsnParser` (raw `url` params are no longer accepted).
- Stage 2 persistence timestamps use `DateTime` for DBAL `DATETIME_MUTABLE` columns.
- PHPUnit uses committed `.env.test` for local/CI `DATABASE_URL` pointing at `127.0.0.1`.
