#!/usr/bin/env bash
# Скриншоты docs/stage-7.5 на СВЕЖЕЙ демо-базе (не на e2e-базе).
# 1) пересоздаёт стек с новым томом MySQL, 2) ждёт /health,
# 3) запускает только stage75-screenshots.spec.ts с KARKAS_SCREENSHOTS=1
#    (spec сам проходит установщик, ставит blog и вызывает POST /api/v1/admin/demo/reset).
# Demo API доступен только при APP_ENV=dev|test и KARKAS_ALLOW_DEMO_SEED=1 (docker-compose.yml по умолчанию).
# Переменные: DOCKER_COMPOSE (по умолчанию "docker compose"), COMPOSE_FILES (доп. -f), BASE_URL, KARKAS_ADMIN_PASSWORD.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

DC="${DOCKER_COMPOSE:-docker compose} ${COMPOSE_FILES:-}"
BASE_URL="${BASE_URL:-http://127.0.0.1:8080}"
export KARKAS_ADMIN_PASSWORD="${KARKAS_ADMIN_PASSWORD:-test-admin-password-stage2}"

echo "Recreating stack with a fresh MySQL volume…"
$DC down -v --remove-orphans
$DC up -d mysql app
for _ in $(seq 1 60); do
  curl -sf "$BASE_URL/health" >/dev/null && break
  sleep 2
done
curl -sf "$BASE_URL/health" >/dev/null || { echo "app is not healthy" >&2; exit 1; }

echo "Capturing screenshots (1440 + 390)…"
cd e2e
KARKAS_SCREENSHOTS=1 BASE_URL="$BASE_URL" npx playwright test tests/stage75-screenshots.spec.ts --retries=0 --reporter=list
echo "Done: docs/stage-7.5/screenshots/result-{1440,390}-*.png"
