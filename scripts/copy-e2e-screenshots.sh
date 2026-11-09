#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAGE="${KARKAS_SCREENSHOT_STAGE:-2}"
DEST="$ROOT/docs/stage-${STAGE}/screenshots"
mkdir -p "$DEST"

find "$ROOT/e2e/test-results" -name 'home-1440.png' -exec cp {} "$DEST/home-1440.png" \; 2>/dev/null || true
find "$ROOT/e2e/test-results" -name 'home-390.png' -exec cp {} "$DEST/home-390.png" \; 2>/dev/null || true
find "$ROOT/e2e/test-results" -name 'admin-1440.png' -exec cp {} "$DEST/admin-1440.png" \; 2>/dev/null || true
find "$ROOT/e2e/test-results" -name 'admin-390.png' -exec cp {} "$DEST/admin-390.png" \; 2>/dev/null || true

count=$(find "$DEST" -name '*.png' | wc -l)
echo "Copied $count screenshot(s) to $DEST"
