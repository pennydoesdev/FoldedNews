#!/usr/bin/env bash
# Release-style deploy. Refuses to run until the target is configured.
# See docs/deployment.md for the full staging/production procedure.
set -euo pipefail

ENVIRONMENT="${1:-}"
case "$ENVIRONMENT" in
  staging|production) ;;
  *) echo "Usage: scripts/deploy.sh <staging|production>"; exit 2 ;;
esac

: "${DEPLOY_HOST:?Set DEPLOY_HOST (see docs/deployment.md)}"
: "${DEPLOY_PATH:?Set DEPLOY_PATH (releases root on the server)}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
REF="${DEPLOY_REF:-$(git rev-parse --short HEAD)}"
RELEASE="$(date +%Y%m%d%H%M%S)-${REF}"

echo "==> Deploying ${REF} to ${ENVIRONMENT} (${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_PATH})"
echo "    release: ${RELEASE}"

if [[ "${DRY_RUN:-0}" == "1" ]]; then
  echo "DRY_RUN=1 — printing plan only, no changes made."
  echo "  1. build artifacts locally (scripts/build.sh)"
  echo "  2. rsync repo + vendor + theme build to ${DEPLOY_PATH}/releases/${RELEASE}"
  echo "  3. symlink shared .env + uploads into the release"
  echo "  4. wp-cli: maintenance on, run migrations/setup, cache flush, maintenance off"
  echo "  5. atomically repoint ${DEPLOY_PATH}/current -> releases/${RELEASE}"
  echo "  6. scripts/health-check.sh against the live URL"
  exit 0
fi

echo "Real deploy requires SSH access and is intentionally not auto-executed here."
echo "Follow docs/deployment.md, or run with DRY_RUN=1 to preview the plan."
exit 1
