#!/usr/bin/env bash
# Build all PHP + frontend assets for a release.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
THEME="$ROOT/web/app/themes/foldednews"
cd "$ROOT"

echo "==> Composer install (root)"
composer install --no-interaction --prefer-dist --no-progress "${COMPOSER_FLAGS:-}"

echo "==> Composer install (theme)"
composer install --no-interaction --prefer-dist --no-progress --working-dir="$THEME" "${COMPOSER_FLAGS:-}"

echo "==> Frontend build (Vite)"
cd "$THEME"
if command -v pnpm >/dev/null 2>&1; then
  pnpm install --frozen-lockfile && pnpm run build
else
  npm ci && npm run build
fi

echo "==> Build complete."
