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

## Removal

Remove the requirement from the manifest, regenerate the lockfile, delete any
adapter docs/config, and confirm CI is green.
