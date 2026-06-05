# Testing & QA

## Automated

| Layer | Tool | Command |
|---|---|---|
| PHP lint | `php -l` | `find ... -name '*.php' | xargs -n1 php -l` |
| PHP static analysis | PHPStan | `composer analyse` |
| PHP unit | Pest | `composer test` |
| Frontend build | Vite | `npm run build` (in theme) |
| E2E / smoke | Playwright | `npx playwright test` (Stage 3+) |
| Accessibility | axe + Playwright | Stage 3+ |
| Secret scan | Gitleaks | CI |
| Dependency audit | `composer audit`, `npm audit` | CI |

All wired in `.github/workflows/ci.yml`. The `e2e` job self-activates when
`tests/e2e/**` exists.

## Manual QA — Stage 1 (Foundation)

Run after `composer install` (root + theme), `npm run build`, and a configured
`.env` pointing at a MySQL database with `web/wp` installed.

- [ ] Homepage loads (`WP_HOME`) without PHP errors.
- [ ] `wp-admin` loads and you can log in.
- [ ] Sage assets compile: `npm run build` produces `web/app/themes/foldednews/public/build/manifest.json`.
- [ ] Acorn boots: `wp acorn about` runs without error.
- [ ] No hardcoded vanilla WordPress paths — content dir is `/app`, not `/wp-content`.
- [ ] Zilla Slab renders for headings/brand.
- [ ] Skip-to-content link is reachable by keyboard; primary nav is keyboard navigable.

> Note: homepage/admin/Acorn checks require a live WordPress + MySQL environment,
> which is provisioned during install (see `README.md`) — they cannot run in a
> static CI-only context.

## Per-stage checklists

Each stage appends its manual QA checklist here as it lands (content model,
theme layouts, editor, media offload, live blog, maps, video, podcast,
subscriptions, meter, newsletters, ads, AI, bookmarks, desktop mode, extension,
health, performance, hardening).
