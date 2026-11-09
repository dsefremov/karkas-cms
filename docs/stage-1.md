# Stage 1 — Contracts and versioned manifest

## Goal (spec §6, §27 G1 excerpt)

Deliver package/Core **contracts**, **JSON Schema** for unsigned `manifest.json`, **SemVer** constraint evaluation, **whole-plan compatibility** checks (no install execution), **downgrade refusal** and **single-generation rollback** policy types, plus **current/available** version metadata surfaced in admin.

Out of scope: installer wizard, ZIP quarantine, DDL runner, Module Manager operations UI (G2+).

## Delivered

| Area | Outcome |
|------|---------|
| SDK contracts | `ModuleProviderInterface`, `ModuleLifecyclePort`, `CoreModuleBridgeInterface`, `ManifestInspectionPort`, `WholePlanCompatibilityPort` under `src/Domain/ModuleSdk/Contract/` |
| Manifest protocol | `cms-core/v1` / `cms-module/v1` schema at `resources/schemas/cms-package-manifest-v1.schema.json`; parser + JSON Schema validator (Opis) |
| SemVer | `SemVerVersion` value object; `SemVerConstraintEvaluator` wrapping `composer/semver` with initial grammar guards |
| Compatibility | `WholePlanCompatibilityChecker` — core/SDK/PHP/MySQL/deps/conflicts for a planned set |
| Policies | `VersionTransitionPolicy` — blocks ordinary downgrade; rollback only to one pinned `SavedDeploymentGeneration` |
| Inventory | `PlatformVersionInventory` — current from config, available from `resources/package-catalog/` (local/untrusted) |
| API | `GET /api/v1/platform/versions` (`karkas-platform-versions/v1`) |
| Admin UI | React table: current / available / status per package (Playwright + axe, screenshots 1440 & 390) |
| Examples | Sample core `1.1.0` and blog `1.0.0` manifests with file SHA256 listings |
| Tests | Unit (manifest, SemVer, policy, plan, schema), HTTP versions endpoint, contract interface tests |

## Layer map

- `src/Domain/` — identifiers, manifest DTOs, rollback generation, SDK ports
- `src/Application/Package/` — parsing, validation, compatibility, inventory
- `src/Adapters/Infrastructure/Package/` — `ManifestInspectionAdapter`
- `src/Adapters/Entry/Http/Controller/PlatformVersionsController.php`

## Configuration

`config/packages/karkas_platform.yaml`:

- `karkas.platform.core_version` — deployed core SemVer (default `1.0.0`)
- `karkas.platform.sdk_version` — SDK API line (`1.0.0`)
- `karkas.platform.package_catalog_dir` — local catalog root

## Known limitations

- No runtime install/update/enable; inspection does not autoload package PHP.
- Available version source is **local catalog only**; HTTPS feed is phase 2 (spec §6).
- ZIP/folder quarantine limits and MFA-gated upload are not implemented in Stage 1.
- Package tree validator checks fixtures/catalog only; production staging paths arrive in G2.
- `manifest_digest` pinning uses normalized JSON rules; full READY-marker folder workflow is G2.

## Spec alignment notes

- Manifest SHA256/file list is **consistency only**, not provenance (unsigned packages, spec §6).
- Prerelease ranges are rejected unless exact `=x.y.z-prerelease` (recommended initial grammar).
- Whole-plan evaluation is synchronous and deterministic; no network dependency resolution.

## Next stage (not in scope)

G2 installer, unsigned delivery adapters, operation journal, global installation registry, and Core updater runner.
