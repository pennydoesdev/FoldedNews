# Acorn (Laravel in WordPress) — Capabilities Reference

Acorn powers the theme (boots from `web/app/themes/foldednews/functions.php`).
Official docs: https://roots.io/acorn/docs/  ·  package: `roots/acorn` (`^6.0`).
> Note: core Acorn lists `illuminate/mail`, `illuminate/translation`,
> `illuminate/notifications`, `illuminate/redis`, `illuminate/broadcasting` as
> **unsupported** — Stage 12 email uses the **Acorn Mail** add-on or `wp_mail`.

## Routing — https://roots.io/acorn/docs/routing/
Enabled via `->withRouting(web: base_path('routes/web.php'))` in `functions.php`.
Routes live in the theme's `routes/web.php` (Laravel `Route` facade). Used for
virtual pages and JSON endpoints (account, bookmarks, ad serving, webhooks, AI).
Cache on deploy: `wp acorn route:cache`. SEO for dynamic routes via
`pre_get_document_title` etc.

## Controllers & Middleware — https://roots.io/acorn/docs/controllers-middleware-kernel/
`wp acorn make:controller Name [--api|--resource]` → `app/Http/Controllers/`.
`wp acorn make:middleware Name` → `app/Http/Middleware/`. Apply with
`->middleware(Class::class)`. Custom HTTP kernel extends `Roots\Acorn\Http\Kernel`.

## Eloquent models — https://roots.io/acorn/docs/eloquent-models/
Create manually in `app/Models/` (no `make:model` in Acorn). For WP tables set
`$table`, `$primaryKey = 'ID'`, `$timestamps = false`. Useful for ad metrics,
metered access, newsletter contacts (Stages 10–14).

## View Composers — https://roots.io/sage/docs/composers/
`extends Roots\Acorn\View\Composer`, `protected static $views`, `with()` /
`override()`. Auto-discovered in `app/View/Composers/` (kebab view ↔ PascalCase
composer). Our `Homepage` composer follows this.

## Blade Components — https://roots.io/sage/docs/components/
Anonymous `@props` components in `resources/views/components/` (what we use), or
class-based in `app/View/Components/` via `wp acorn make:component`.

## Error handling — https://roots.io/acorn/docs/error-handling/
Exception handler active when `WP_DEBUG && WP_DEBUG_DISPLAY` (set in
`config/environments/development.php`). Logs to `storage/logs`. Disable via
`add_filter('acorn/throw_error_exception', '__return_false')`.

## WP-CLI (artisan-style) — https://roots.io/acorn/docs/wp-cli-commands/
Key commands: `wp acorn about|optimize|optimize:clear`, `migrate`, `db:seed`,
`make:{controller,middleware,model,migration,command,job,composer,component,provider,seeder}`,
`route:cache|route:list`, `view:cache|view:clear`, `queue:work`, `schedule:run`.

## Deploy optimization
On every deploy run: `composer install`, `wp acorn optimize` (config/route/view
cache). With routes also `wp acorn route:cache`; precompile Blade with
`wp acorn view:cache`.
