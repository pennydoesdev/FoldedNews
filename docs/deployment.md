# Deployment

Target: GitHub or Gitea origin, deployed to a server using an atomic
release-symlink strategy. Scripts: `scripts/build.sh`, `scripts/deploy.sh`,
`scripts/rollback.sh`, `scripts/health-check.sh`.

## Prerequisites

- PHP 8.3+ (8.4 supported) **running as PHP-FPM** (FastCGI). WordPress/Bedrock
  is served by Nginx proxying `.php` to PHP-FPM (the Roots-documented stack) —
  the PHP CLI is only used for builds, Composer, WP-CLI and tests.
- A web server (Nginx recommended) with docroot at `web/`.
- Composer 2, Node 20.19+/22.12+, MySQL 8 / MariaDB.
- SSH access to the target with a `deploy` user.
- Shared, persisted paths on the server: `shared/.env`, `shared/uploads`.

## Local development

Use **DDEV** (Roots-recommended) — it provisions Nginx + **PHP-FPM** + MariaDB
with docroot `web/`:

```bash
ddev start          # boots nginx-fpm + mariadb (see .ddev/config.yaml)
ddev composer install
ddev exec --dir web/app/themes/foldednews npm install && \
ddev exec --dir web/app/themes/foldednews npm run build
ddev launch         # opens https://foldednews.ddev.site
```

Alternatives: Trellis, Lando, Valet, or `wp server` — all need a PHP-FPM/SAPI +
MySQL behind them.

## Web server (Nginx + PHP-FPM)

```nginx
server {
    listen 80;
    server_name foldednews.example.com;
    root /srv/foldednews/current/web;   # the release's web/ dir
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # Hand .php requests to PHP-FPM (FastCGI).
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;  # PHP-FPM pool socket
        fastcgi_index index.php;
    }
}
```

Media is served from the CDN (Stage 5), so no local `uploads` location is
needed. This matches the Roots-documented runtime — Nginx + PHP-FPM + MariaDB —
as provisioned by Trellis and by DDEV (`nginx-fpm`) for local development.

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

## Production environment flag

`WP_ENV=production` **must** be set in production. Otherwise Bedrock's
`roots/bedrock-disallow-indexing` mu-plugin keeps the site `noindex`. On deploy
also run Acorn caches: `wp acorn optimize` (+ `route:cache`, `view:cache`).
WP-Cron should be replaced by a system cron (`DISABLE_WP_CRON=true` + a scheduled
`wp cron event run --due-now`), as Trellis does by default.

## Trellis (recommended)

[Trellis](https://roots.io/trellis/) provisions the Roots runtime (Ubuntu +
**Nginx + PHP-FPM** + MariaDB + SSL) and does zero-downtime **atomic** deploys
with the same release-symlink/rollback model as our `scripts/`:

```bash
trellis deploy production     # composer install runs in the deploy_build_after hook
trellis rollback production   # repoints the previous release
trellis logs --error production
```

Secrets live in encrypted Ansible **Vault** files (`group_vars/<env>/vault.yml`)
— never plaintext in git, matching `docs/environment-variables.md`. Our
`scripts/deploy.sh` mirrors this model for non-Trellis hosts.

## CI

`.github/workflows/ci.yml` runs PHP lint/analyse/test, the Vite build, secret +
dependency scans, and (from Stage 3) Playwright/a11y smoke tests.
