# Karkas CMS («Каркас»)

Modular blog-oriented CMS for **Timeweb shared hosting**: Symfony 7.4 LTS, PHP 8.4/8.5, MySQL 8.0 (production start) with **8.0 ↔ 8.4** CI portability, static React admin built in CI.

**Stage 0 (this branch)** is infrastructure only — no installer, modules, blog, or user management yet. See [docs/stage-0.md](docs/stage-0.md).

## Stack

| Layer | Choice |
|-------|--------|
| Backend | Symfony 7.4 LTS (minimal bundles) |
| Admin | React + TypeScript, Vite → static files in `public/admin/` |
| DB | MySQL InnoDB, `utf8mb4` |
| Mail (dev) | Mailpit in Docker |
| CI / local tests | Docker + GitHub Actions (not production runtime) |

Architecture layers follow spec v0.8 §5: `Adapters/Entry`, `Application`, `Domain`, `Adapters/Infrastructure`.

## Requirements

- Docker Engine + Compose v2
- Make
- Node.js 22+ (for local frontend/e2e without Docker)

## Quick start (Den / contributors)

```bash
git clone https://github.com/dsefremov/karkas-cms.git
cd karkas-cms
cp .env.dist .env

make up          # Apache :8080, MySQL 8.0, Mailpit :8025
make up-mysql84  # same stack with MySQL 8.4 (compose override)
make lint
make test
```

### MySQL version (local Docker)

Default **MySQL 8.0**:

```bash
make up
```

**MySQL 8.4** (matches CI matrix cell):

```bash
make up-mysql84
# or: docker compose -f docker-compose.yml -f docker-compose.mysql84.yml up -d
```

Integration tests from the `cli` container use `host.docker.internal` to reach the published MySQL port (see `docker-compose.yml`).

### Composer scripts

Inside the PHP container (or locally with PHP 8.4+):

```bash
composer install
composer test              # all PHPUnit suites
composer phpstan
composer lint:php
```

### Frontend

```bash
cd admin-ui && npm ci && npm run dev    # dev server
cd admin-ui && npm run build            # output → public/admin/
```

### Hosting preflight (production upload)

Upload **`tools/hosting-check.php`** to the target host (once), open with `?token=<32+ char secret>`, download JSON, then **delete the file**. Optional DB check uses POST; passwords are never stored or echoed. See spec §4 preflight.

## Releases

- **PR / CI**: `scripts/build-release.sh --verify-only` builds a ZIP artifact (no dev/tests, includes `vendor/` and built admin).
- **Tag `vX.Y.Z`**: workflow `release-on-tag` attaches the same ZIP + `SHA256SUMS` + manifest to GitHub Releases.
- Production deploy is manual SFTP/upload per spec §22 — no runtime Composer on shared hosting.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md): work on `grok_bot`, PR into `develop`, never push to `main`.

## License

Proprietary — repository private use per project owner.
