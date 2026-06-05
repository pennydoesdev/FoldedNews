# FoldedNews

A production news publishing platform (CNN/NYTimes-style newsroom) built on the
**Roots** ecosystem: a **Bedrock-compatible** WordPress root, the **Sage** theme
with **Acorn** (Laravel-style providers/services), **Blade**, **Tailwind CSS 4**,
and **Vite**. This is the structure **Radicle** composes (Bedrock + Sage +
Acorn); see `docs/vendor-references.md`.

> Build status: **Stage 12 — newsletters + internal email marketing**. The
> platform is built in 20 staged increments (see the project plan). Each stage
> must build, test, and pass manual QA before the next begins.

## Stack

| Layer | Tech |
|---|---|
| Root structure | Bedrock-compatible (`web/` root, `web/app/` content, `config/` env) |
| Framework | Acorn 6 (boots from the theme) |
| Theme | Sage 11 (Blade) |
| Styling | Tailwind CSS 4 (CSS-first `@theme` tokens) + Zilla Slab |
| Build | Vite 8 (`laravel-vite-plugin` + `@roots/vite-plugin`) |
| PHP deps | Composer | 
| Frontend deps | npm / pnpm |

## Structure

```
config/                         env-driven WP config (no vanilla wp-content paths)
web/                            web root
  index.php  wp-config.php
  wp/                           WordPress core (Composer-installed, gitignored)
  app/                          content dir (CONTENT_DIR=/app)
    mu-plugins/foldednews-newsroom/   modular newsroom domain (business logic)
    plugins/  uploads/
    themes/foldednews/          Sage theme (Acorn, Blade, Tailwind, Vite)
docs/                           official-source documentation
scripts/                        build / deploy / rollback / health-check
.github/workflows/ci.yml        CI
```

## Setup

```bash
cp .env.example .env            # fill DB creds, URLs, salts (https://roots.io/salts.html)
composer install                # root: WordPress core + Bedrock libs
composer install --working-dir=web/app/themes/foldednews   # theme: Acorn
cd web/app/themes/foldednews && npm install && npm run build
# serve web/ via a web server with PHP-FPM (FastCGI) + MySQL, then visit WP_HOME
```

Fastest path (Nginx + **PHP-FPM** + MariaDB out of the box) is DDEV — see
`.ddev/config.yaml` and `docs/deployment.md`: `ddev start && ddev composer install`.

Manual QA for this stage: `docs/testing.md`. Deployment: `docs/deployment.md`.

## Documentation

`docs/vendor-references.md`, `dependency-policy.md`, `environment-variables.md`,
`deployment.md`, `testing.md`, `acorn.md`, `webhooks.md`, `media-offload.md`,
`ai-providers.md`, `advertising.md`, `editorial-workflow.md`.
