# Stage 0 — Infrastructure report

## Goal

Deliver a testable skeleton aligned with Karkas spec v0.8 sections 5 (layers), 22 (shared build/deploy), 26 (test matrix), and 27 (roadmap G0/G1 enablers). No CMS product logic.

## Delivered

| Area | Outcome |
|------|---------|
| Backend | Minimal Symfony 7.4 LTS, PSR-4 layers under `src/`, `/` and `/health` JSON routes |
| Admin UI | Vite + React + TypeScript placeholder built to `public/admin/` |
| Docker | Apache + mod_rewrite, PHP 8.4/8.5 (build arg), MySQL 8.0 (+ profile 8.4), Mailpit, `disable_functions` in web SAPI |
| Static analysis | PHPStan level 8, php-cs-fixer (@Symfony), ESLint + `tsc` |
| Tests | PHPUnit unit/integration/http/hosting-check; Playwright + axe at 1440px and 390px |
| CI | GitHub Actions matrix PHP×MySQL, frontend, Playwright artifacts, PR release ZIP |
| Release | `scripts/build-release.sh` → ZIP + `RELEASE_MANIFEST.json` + SHA256 |
| Preflight | `tools/hosting-check.php` standalone capability report |

## Layer map (spec §5)

- `src/Adapters/Entry/` — HTTP kernel, controllers (future CLI/cron entry here)
- `src/Application/` — use-case DTOs (health placeholder)
- `src/Domain/` — pure domain types
- `src/Adapters/Infrastructure/` — MySQL probe adapter

## Known limitations

- Real Timeweb panel cron, Nginx static bypass, `open_basedir`, and exact PHP builds are **not** verified in this environment.
- MySQL 8.4 local profile is optional (`docker compose --profile mysql84`); CI uses service containers.
- Playwright uses PHP built-in server in CI, not Apache; Docker e2e uses Apache when `make test` is run locally.
- PHP 8.5 images and matrix cells depend on upstream availability in CI (`shivammathur/setup-php`).

## Spec notes

- Product name «Каркас / Karkas CMS» is reflected in docs; PHP namespaces use `Karkas\` without implying table prefixes (per spec FYI § naming).
- Dual MySQL 8.0/8.4 CI contract matches spec §4 portability; EOL 8.0 exception is documented, not waived for security gates.

## Next stages (not in scope)

Installer wizard, module SDK, Blog, Identity, cron dispatcher implementation — roadmap G2+.
