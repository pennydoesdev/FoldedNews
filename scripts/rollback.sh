#!/usr/bin/env bash
# Roll back to the previous release by repointing the `current` symlink.
# See docs/deployment.md (Rollback).
set -euo pipefail

: "${DEPLOY_HOST:?Set DEPLOY_HOST}"
: "${DEPLOY_PATH:?Set DEPLOY_PATH}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"

echo "==> Rollback on ${DEPLOY_USER}@${DEPLOY_HOST}:${DEPLOY_PATH}"

if [[ "${DRY_RUN:-0}" == "1" ]]; then
  echo "DRY_RUN=1 — plan only:"
  echo "  1. find previous release: ls -1dt ${DEPLOY_PATH}/releases/* | sed -n 2p"
  echo "  2. repoint ${DEPLOY_PATH}/current -> previous release"
  echo "  3. wp-cli cache flush"
  echo "  4. scripts/health-check.sh"
  exit 0
fi

echo "Real rollback requires SSH access. Follow docs/deployment.md or use DRY_RUN=1."
exit 1
