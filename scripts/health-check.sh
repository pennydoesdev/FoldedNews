#!/usr/bin/env bash
# Post-deploy smoke check. Verifies the site is up and media is served from CDN.
set -euo pipefail

URL="${1:-${WP_HOME:-}}"
[[ -n "$URL" ]] || { echo "Usage: scripts/health-check.sh <url>"; exit 2; }

echo "==> Health check: $URL"

code="$(curl -s -o /dev/null -w '%{http_code}' -L "$URL")"
echo "  homepage HTTP: $code"
[[ "$code" == "200" ]] || { echo "FAIL: homepage not 200"; exit 1; }

html="$(curl -s -L "$URL")"

# Stage 5 invariant: no public vanilla upload URLs should appear in markup.
if grep -qiE 'wp-content/uploads/' <<<"$html"; then
  echo "WARN: found wp-content/uploads/ URL in markup (should be CDN-rewritten from Stage 5)."
fi

echo "  OK"
