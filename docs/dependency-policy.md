# Dependency Policy

## Principles

- **Official sources only.** Every dependency is documented in
  `docs/vendor-references.md` with its official docs URL and package name.
- **Latest stable / LTS** at build time, pinned with caret ranges in manifests
  and locked exactly in lockfiles (`composer.lock`, `package-lock.json`).
- **No vendored copies** of third-party code in git. PHP deps install to
  `/vendor`; WordPress core installs to `web/wp`; Node deps to `node_modules`.
  All are gitignored.

## PHP (Composer)

- Root project: `composer.json` (Bedrock-compatible structure, WordPress core).
- Theme: `web/app/themes/foldednews/composer.json` (Acorn, `App\` PSR-4).
- Add a dependency: `composer require vendor/pkg` in the appropriate manifest,
  then commit the updated lockfile.
- Plugins/mu-plugins are Composer-managed via `composer/installers` paths.

## Frontend (npm/pnpm)

- Theme `package.json` only. Either npm or pnpm; commit the matching lockfile.
- `type: module`, Vite 8, Tailwind 4. No build output committed.

## Updating

- `composer outdated` / `npm outdated` reviewed each release.
- Security advisories: `composer audit` + `npm audit` run in CI.
- Major upgrades land on a branch with the full CI matrix green before merge.

## WordPress plugins via Composer

WP Packages is registered in the root `composer.json`, so WordPress.org plugins
install via the `wp-plugin/` namespace (themes via `wp-theme/`):

```bash
composer require wp-plugin/akismet
composer require roots/wordpress:7.0 -W   # update WP core
```

`plugins/` and `mu-plugins/*/` are git-ignored (Composer manages them). Anything
authored by hand is committed via an explicit negation — e.g. our newsroom
mu-plugin: `!web/app/mu-plugins/foldednews-newsroom/`. To force a regular plugin
to load as a mu-plugin, add it to the `mu-plugins` `installer-paths` array.

## Bedrock plugin compatibility

A plugin that works on vanilla WordPress but not Bedrock is usually hard-coding
`wp-content`, assuming WP isn't in a subdirectory, or including `wp-load.php`.
A `"Sorry, you are not allowed to access this page."` error on a non-dev
environment typically means the plugin conflicts with `DISALLOW_FILE_MODS`
(set in `config/application.php`; relaxed in development).

## Removal

Remove the requirement from the manifest, regenerate the lockfile, delete any
adapter docs/config, and confirm CI is green.
