#!/usr/bin/env bash
# Чистая демо-база для скриншотов stage-7.5 (не для e2e).
# Вариант A: пересоздать том MySQL (полный сброс).
# Вариант B: API reset+seed на существующей dev-базе.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

MODE="${1:-api}"
ADMIN_PASSWORD="${KARKAS_ADMIN_PASSWORD:-test-admin-password-stage2}"
BASE_URL="${BASE_URL:-http://127.0.0.1:8080}"

if [[ "$MODE" == "volume" ]]; then
  echo "Stopping stack and removing MySQL volume…"
  docker compose down -v mysql
  docker compose up -d mysql app
  echo "Waiting for MySQL…"
  for i in $(seq 1 40); do
    if docker compose exec -T mysql mysqladmin ping -h 127.0.0.1 -uroot -proot_secret --silent 2>/dev/null; then
      break
    fi
    sleep 2
  done
  docker compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction
fi

echo "Admin session…"
SESSION=$(curl -sf -X POST "$BASE_URL/api/v1/admin/session" \
  -H 'Content-Type: application/json' \
  -d "{\"password\":\"$ADMIN_PASSWORD\"}" -c /tmp/karkas-demo-cookies.txt)

if [[ -z "$SESSION" ]]; then
  echo "Login failed; run installer/bootstrap first." >&2
  exit 1
fi

echo "POST /api/v1/admin/demo/reset"
curl -sf -X POST "$BASE_URL/api/v1/admin/demo/reset" \
  -H 'Content-Type: application/json' \
  -H "X-Karkas-Reauth: $ADMIN_PASSWORD" \
  -d '{}' -b /tmp/karkas-demo-cookies.txt

echo ""
echo "Done. Use this DB only for stage75-screenshots, not full e2e suite."