# Vendor References

Every dependency and integration is documented from **official sources only**.
Provider adapters (Stripe, S3, AI, ads) are added in their stages using the
[Adapter template](#adapter-template) below.

## Core stack (Stage 1)

| Component | Official docs | Package | Version | Auth | Notes |
|---|---|---|---|---|---|
| Roots Radicle (foundation umbrella) | https://roots.io/radicle/ · https://docs.roots.io/radicle/master/installation/ | — (distribution) | — | — | Unifies Bedrock + Sage + Acorn. This project follows the Bedrock-compatible structure Radicle composes. |
| Roots Bedrock (root structure) | https://roots.io/bedrock/ · https://docs.roots.io/bedrock/master/installation/ | `roots/bedrock-autoloader`, `roots/wp-config`, `roots/bedrock-disallow-indexing` | `^1.0` / `^1.0` / `^2.0` | — | `web/` web root, `web/app/` content dir, `config/` env config. |
| WordPress (via Composer) | https://developer.wordpress.org/ | `roots/wordpress` | `7.0` | — | Installed to `web/wp` (gitignored). |
| Roots Acorn (Laravel-style framework) | https://roots.io/acorn/ · https://docs.roots.io/acorn/ | `roots/acorn` | `^6.0` | — | Providers, services, Blade, container. Boots from theme `functions.php`. |
| Roots Sage (theme) | https://roots.io/sage/ · https://docs.roots.io/sage/ | (this theme) | 11.x line | — | Blade views, `app/` PSR-4 `App\`. |
| Composer | https://getcomposer.org/doc/ | — | 2.x | — | PHP dependency manager. |
| Vite | https://vite.dev/ | `vite` | `^8.0` | — | Asset bundler/dev server. |
| Laravel Vite plugin | https://laravel.com/docs/vite | `laravel-vite-plugin` | `^3.0` | — | `@vite()` Blade directive + manifest. |
| Roots Vite plugin | https://github.com/roots/vite-plugin | `@roots/vite-plugin` | `^2.0` | — | WordPress + `theme.json` integration. |
| Tailwind CSS | https://tailwindcss.com/docs | `tailwindcss`, `@tailwindcss/vite` | `^4.0` | — | CSS-first config via `@theme`. |
| Zilla Slab (brand font) | https://fonts.google.com/specimen/Zilla+Slab | `@fontsource/zilla-slab` | `^5.0` | — | Self-hosted, no external font requests. |
| oscarotero/env | https://github.com/oscarotero/env | `oscarotero/env` | `^2.0` | — | Typed env access. |
| phpdotenv | https://github.com/vlucas/phpdotenv | `vlucas/phpdotenv` | `^5.0` | — | `.env` loading. |

## Tooling / QA

| Tool | Docs | Package |
|---|---|---|
| Pest | https://pestphp.com/docs | `pestphp/pest` |
| PHPStan | https://phpstan.org/ | `phpstan/phpstan` |
| WordPress stubs | https://github.com/php-stubs/wordpress-stubs | `php-stubs/wordpress-stubs` |
| Laravel Pint | https://laravel.com/docs/pint | `laravel/pint` |
| Playwright | https://playwright.dev/ | (Stage 3+) |
| Gitleaks | https://github.com/gitleaks/gitleaks-action | CI action |

## Visualization (Stage 7)

| Component | Official docs | Package | Use |
|---|---|---|---|
| Leaflet | https://leafletjs.com/reference.html | `leaflet` `^1.9` | Simple OSM maps (markers, GeoJSON, routes, boundaries) |
| OpenStreetMap tiles | https://wiki.openstreetmap.org/wiki/Tile_servers | — | Base map tiles |
| Chart.js | https://www.chartjs.org/docs/latest/ | `chart.js` `^4.5` | Bar/line/area/scatter/stacked charts |
| Mermaid | https://mermaid.js.org/intro/ | `mermaid` `^11` | Flow/org/relationship/process diagrams |
| MapLibre GL JS | https://maplibre.org/maplibre-gl-js/docs/ | `maplibre-gl` (follow-up) | Advanced vector/cinematic/scrollytelling maps |
| D3 / Observable Plot | https://d3js.org/ · https://observablehq.com/plot/ | (follow-up) | Bespoke graphics |

Libraries are lazy-loaded per type via dynamic `import()` and an
`IntersectionObserver` (only when a viz nears the viewport), keeping the main
bundle small. Each `:::map`/`:::chart`/`:::diagram` directive carries a base64
config consumed by `resources/js/viz.js`.

## Adapter template

Each third-party provider adapter (added in its stage) must document:

- **Official docs URL**
- **SDK / package name**
- **Auth method**
- **Required env vars**
- **Webhook docs** (if applicable)
- **Rate limits** (if documented)
- **Testing instructions**

Planned adapters: Stripe (Stage 10, `docs/webhooks.md`), S3-compatible storage
(Stage 5, `docs/media-offload.md`), AI providers (Stage 14, `docs/ai-providers.md`),
advertising/Stripe invoicing (Stage 13, `docs/advertising.md`).
