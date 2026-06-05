# Environment Variables

Secrets live in `.env` (never committed). `.env.example` documents the active
set. Variables are added **per stage**; this file is the full catalogue.

## Stage 1 — Foundation (active)

| Variable | Required | Example | Purpose |
|---|---|---|---|
| `DB_NAME` | yes* | `foldednews` | Database name |
| `DB_USER` | yes* | `foldednews` | Database user |
| `DB_PASSWORD` | yes* | — | Database password |
| `DB_HOST` | no | `127.0.0.1` | Database host (default `localhost`) |
| `DB_PREFIX` | no | `wp_` | Table prefix |
| `DATABASE_URL` | no | `mysql://u:p@h:3306/db` | DSN alternative to `DB_*` |
| `WP_ENV` | yes | `development` | Environment: development/staging/production |
| `WP_HOME` | yes | `http://foldednews.test` | Site home URL |
| `WP_SITEURL` | yes | `${WP_HOME}/wp` | WordPress core URL |
| `WP_ENVIRONMENT_TYPE` | no | `development` | WP environment type |
| `WP_DEFAULT_THEME` | no | `foldednews` | Active theme slug |
| `APP_URL` | no | `${WP_HOME}` | Used by the theme's Vite/Laravel plugin |
| `AUTH_KEY` … `NONCE_SALT` | yes | — | 8 WordPress salts (https://roots.io/salts.html) |

\* Required unless `DATABASE_URL` is set.

## Later stages (added with their adapter)

| Stage | Variables | Doc |
|---|---|---|
| 5 — Media offload | `S3_ENDPOINT`, `S3_REGION`, `S3_BUCKET`, `S3_KEY`, `S3_SECRET`, `S3_PATH_PREFIX`, `CDN_URL`, `S3_FORCE_HTTPS`, `S3_DELETE_LOCAL`, `S3_SIGNED_URLS`, `S3_CACHE_CONTROL`, `S3_MULTIPART_THRESHOLD` | `docs/media-offload.md` |
| 10 — Subscriptions | `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_PRICE_*` | `docs/webhooks.md` |
| 13 — Advertising | `STRIPE_*` (invoicing) | `docs/advertising.md` |
| 14 — AI Copilot | `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `GEMINI_API_KEY`, `MINIMAX_API_KEY`, `FEATHERLESS_API_KEY`, `PUTER_*`, `AI_OPENAI_COMPAT_BASE_URL` | `docs/ai-providers.md` |

## Rules

- No secret is ever read from anywhere but the environment.
- No secret is committed, logged, or echoed (CI runs a secret scan).
- Production secrets are injected by the host/CI secret store, not files in git.
