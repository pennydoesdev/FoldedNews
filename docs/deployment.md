# Deployment

Target: GitHub or Gitea origin, deployed to a server using an atomic
release-symlink strategy. Scripts: `scripts/build.sh`, `scripts/deploy.sh`,
`scripts/rollback.sh`, `scripts/health-check.sh`.

## Prerequisites

- PHP 8.3+ (8.4 supported), Composer 2, Node 20.19+/22.12+, MySQL 8 / MariaDB.
- SSH access to the target with a `deploy` user.
- Shared, persisted paths on the server: `shared/.env`, `shared/uploads`.

## Server layout

```
/srv/foldednews/
  releases/<timestamp>-<sha>/   # one dir per deploy
  shared/.env                   # symlinked into each release
  shared/uploads -> web/app/uploads
  current -> releases/<active>  # atomic symlink; web root = current/web
```

## Build (local or CI)

```bash
scripts/build.sh           # composer install (root + theme) + vite build
```

## Staging deploy

```bash
export DEPLOY_HOST=staging.example.com DEPLOY_PATH=/srv/foldednews DEPLOY_USER=deploy
DRY_RUN=1 scripts/deploy.sh staging   # preview the plan
scripts/deploy.sh staging             # execute (requires SSH)
```

Steps performed: rsync release → link shared `.env`/uploads → `wp acorn optimize`,
WordPress DB updates, cache flush → atomically repoint `current` → health check.

## Production deploy

Same as staging with `production`. Always deploy to staging first and run the
QA checklist in `docs/testing.md`.

```bash
DRY_RUN=1 scripts/deploy.sh production
scripts/deploy.sh production
```

## Rollback

Repoints `current` to the previous release (zero rebuild):

```bash
DRY_RUN=1 scripts/rollback.sh
scripts/rollback.sh
```

## Health check

```bash
scripts/health-check.sh https://foldednews.example.com
```

Verifies homepage `200` and warns if any public `wp-content/uploads/` URL is
present (Stage 5 requires CDN-rewritten media).

## CI

`.github/workflows/ci.yml` runs PHP lint/analyse/test, the Vite build, secret +
dependency scans, and (from Stage 3) Playwright/a11y smoke tests.
