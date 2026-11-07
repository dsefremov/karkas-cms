# Contributing to Karkas CMS

## Branch flow

1. Base integration branch for agent work: **`grok_bot`**.
2. Open **one pull request** from **`grok_bot`** into **`develop`** per integration round.
3. Do **not** push directly to **`main`** or **`develop`**, and do **not** open PRs into **`main`**.
4. Merges happen only when CI is green and Den approves.

## Local checks

```bash
make up          # Docker: Apache + PHP + MySQL + Mailpit
make lint        # PHPStan, php-cs-fixer, ESLint, TypeScript
make test        # PHPUnit matrix (in Docker) + Playwright
make release-verify
```

## Scope

Stage 0 is infrastructure only. Do not land CMS product features (installer logic, modules, blog, users) in this layer without an explicit later-stage task.

## Secrets

Never commit credentials, production hostnames, or real `.env` files. Use `.env.dist` and GitHub encrypted secrets (by name only in docs) when needed.
