# FoldedNews — Project Summary

Production newsroom platform on the Roots stack (Bedrock-compatible WordPress +
Sage/Acorn + Blade + Tailwind 4 + Vite), built in 20 staged increments. All 20
stages are implemented. Each stage builds, passes static analysis + unit tests,
and ships a documented manual-QA checklist (`docs/testing.md`).

## 1. File tree (high level)

```
config/                         env-driven WP config (no vanilla wp-content)
web/                            web root
  index.php  wp-config.php  wp/ (core, Composer-installed)
  app/                          content dir (CONTENT_DIR=/app)
    mu-plugins/foldednews-newsroom/   newsroom domain (19 modules + services)
      foldednews-newsroom.php   bootstrap + PSR-4 autoloader + module registry
      src/Modules/              ContentTypes, Taxonomies, Meta, Markdown, Media,
                                LiveBlog, Video, Podcast, Billing, Meter,
                                Newsletter, Ads, Ai, Bookmarks, DesktopMode,
                                Health, Performance, Security, Schema
      src/{Media,Live,Podcast,Billing,Meter,Newsletter,Ads,Ai,Bookmarks,Support}/
    themes/foldednews/          Sage theme (Acorn, Blade, Tailwind, Vite)
      app/{Providers,View/Composers,Support}, app/{setup,filters,helpers,newsroom}.php
      resources/{css,js}, resources/views/ (40 Blade templates + components)
      routes/web.php            Acorn routes (account, newsletter prefs, health)
chrome-extension/               MV3 internal QA extension
tests/Unit/ (14 Pest)  tests/e2e/ (Playwright smoke)
docs/ (12)  scripts/ (build/deploy/rollback/health-check)  .github/workflows/ci.yml
```

## 2. Setup

```bash
cp .env.example .env            # DB, URLs, salts (https://roots.io/salts.html)
composer install                # root: WordPress core + Bedrock libs
composer install --working-dir=web/app/themes/foldednews   # theme: Acorn
cd web/app/themes/foldednews && npm install && npm run build
# Serve web/ via Nginx + PHP-FPM + MySQL. Fastest: `ddev start` (.ddev/config.yaml)
```

## 3. Environment variables

Full catalogue in `docs/environment-variables.md` and `.env.example`. Summary:
core DB/URL/salts (Stage 1); `S3_*`/`CDN_URL` (Stage 5); `FN_COLLAB_WS_URL`
(editor collab); `STRIPE_*` (Stage 10); AI keys `OPENAI/ANTHROPIC/GEMINI/MINIMAX/
FEATHERLESS/AI_OPENAI_COMPAT_*` (Stage 14). **All secrets are read from env and
never stored in options or logs.**

## 4. Deployment

`docs/deployment.md`: Nginx + PHP-FPM + MariaDB (Trellis/DDEV), atomic
release-symlink deploys, `WP_ENV=production`, `wp acorn optimize`. Scripts:
`scripts/{build,deploy,rollback,health-check}.sh`. CI: `.github/workflows/ci.yml`
(PHP lint/PHPStan/Pest, Vite build, Gitleaks + audits, Playwright e2e).

## 5. QA report

- **Static analysis:** PHPStan level 5 (+ WordPress stubs) — clean across the
  mu-plugin domain (134 PHP files lint clean).
- **Unit tests (14, Pest):** pure cores — Markdown→blocks, SigV4 (AWS vector),
  LiveBlog API, Video, Podcast, Stripe webhook signature/replay, meter decision,
  newsletter token + CSV, ad weighted selector, AI cost, bookmarks toggle/merge,
  content model, homepage dedup.
- **Frontend:** 40 Blade templates compile; `npm run build` passes with per-type
  code-splitting (Milkdown, Chart.js, Leaflet, Mermaid, Video.js, Yjs all lazy).
- **E2E:** Playwright smoke specs (homepage, no local upload URLs, health
  endpoint) — run against `BASE_URL`.
- **Manual QA:** per-stage checklists in `docs/testing.md`.

## 6. Known limitations

- Verified in this build via static analysis, unit tests, Blade compilation and
  Vite builds; **runtime QA (admin screens, REST/webhooks, media offload, mail,
  AI calls) requires a live WordPress + MySQL** + the relevant credentials.
- The Radicle starter repo is gated, so the project uses the **Bedrock-compatible
  structure Radicle composes** (documented), not Radicle's `public/` tree.
- Documented follow-ups: collaborative editor wiring (Yjs/Hocuspocus server),
  MapLibre/D3 advanced maps, S3 WebP/AVIF + multipart for very large media,
  Mail-Mint popups/visual builder/automations/segments, front-end advertiser
  self-serve portal + full Stripe invoice UI + ad segment/trending engine,
  Puter server-side provider.

## 7. Official vendor docs

See `docs/vendor-references.md` (Roots/Bedrock/Sage/Acorn, WordPress, Composer,
Vite, Tailwind, Zilla Slab, Leaflet/OSM, Chart.js, Mermaid, Video.js, Stripe, AWS
S3, Pest/PHPStan/Playwright/Gitleaks) and `docs/{webhooks,media-offload,
ai-providers,advertising,collaboration,acorn}.md`.

## 8. Next recommended improvements

1. Stand up a live env (DDEV/Trellis) and run the full manual-QA + Playwright
   suite end-to-end; capture Lighthouse/CWV.
2. Generate the root `composer.lock` where `repo.wp-packages.org` is reachable.
3. Add WordPress integration tests (wp-env/wp-phpunit) for the WP-coupled paths.
4. Build out the documented follow-ups above as their own staged increments.
5. Add Redis object cache + FastCGI micro-caching (Trellis) for production CWV.
