#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

VERIFY_ONLY=0
if [[ "${1:-}" == "--verify-only" ]]; then
  VERIFY_ONLY=1
fi

VERSION="${RELEASE_VERSION:-0.0.0-stage1}"
BUILD_DIR="$ROOT/build/release"
STAGE="$BUILD_DIR/karkas-cms-$VERSION"
ARCHIVE="$BUILD_DIR/karkas-cms-$VERSION.zip"
MANIFEST="$BUILD_DIR/manifest.json"
CHECKSUM="$BUILD_DIR/SHA256SUMS"

rm -rf "$BUILD_DIR"
mkdir -p "$STAGE"

if [[ ! -d vendor ]]; then
  echo "Run composer install --no-dev before release build" >&2
  exit 1
fi

if [[ ! -f public/admin/index.html ]]; then
  echo "Build admin-ui first (npm run build in admin-ui/)" >&2
  exit 1
fi

tar -C "$ROOT" \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='admin-ui' \
  --exclude='e2e' \
  --exclude='tests' \
  --exclude='docker' \
  --exclude='build' \
  --exclude='var' \
  --exclude='node_modules' \
  --exclude='.phpunit.cache' \
  --exclude='.env' \
  --exclude='.env.*' \
  --exclude='phpunit.xml' \
  --exclude='phpunit.xml.dist' \
  --exclude='phpstan.neon.dist' \
  --exclude='.php-cs-fixer.dist.php' \
  --exclude='Makefile' \
  --exclude='docker-compose.yml' \
  -cf - . | tar -C "$STAGE" -xf -

composer install --no-dev --optimize-autoloader --no-interaction --working-dir="$STAGE" 2>/dev/null || true

# Release tree should ship production vendor only.
if [[ -d "$ROOT/vendor" ]]; then
  cp -a "$ROOT/vendor" "$STAGE/vendor"
fi

rm -rf "$STAGE/var/cache" "$STAGE/var/log"
mkdir -p "$STAGE/var/cache" "$STAGE/var/log"

cat > "$MANIFEST" <<EOF
{
  "format": "karkas-core-release/v1",
  "product": "Karkas CMS",
  "version": "$VERSION",
  "built_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "stage": 1,
  "php": ">=8.4",
  "notes": "Self-contained release artifact for shared hosting upload. Built in CI; no runtime Composer on production."
}
EOF

cp "$MANIFEST" "$STAGE/RELEASE_MANIFEST.json"

(
  cd "$BUILD_DIR"
  find "karkas-cms-$VERSION" -type f ! -name 'karkas-cms-*.zip' | sort | while read -r f; do
    printf '%s  %s\n' "$(sha256sum "$f" | awk '{print $1}')" "$f"
  done > SHA256SUMS.files
)

(
  cd "$BUILD_DIR"
  zip -rq "karkas-cms-$VERSION.zip" "karkas-cms-$VERSION" RELEASE_MANIFEST.json
  sha256sum "karkas-cms-$VERSION.zip" | awk '{print $1}' > SHA256SUMS
)

echo "Release archive: $ARCHIVE"
echo "Checksum: $(cat "$CHECKSUM")"

if [[ "$VERIFY_ONLY" -eq 1 ]]; then
  unzip -l "$ARCHIVE" | grep -E 'vendor/autoload|public/admin/index|public/index.php|RELEASE_MANIFEST' >/dev/null
  test ! -d "$STAGE/tests"
  test ! -d "$STAGE/e2e"
  echo "Release verification passed."
fi
