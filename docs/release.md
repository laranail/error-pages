# Release

How `laranail/error-pages` is versioned and released.

## Versioning

Semantic versioning. The package tracks the laranail family's pre-1.0 line
(`minimum-stability: dev`, `branch-alias dev-main → 0.x-dev`) until the API settles.

## Compatibility

- Laravel `^13.0`, PHP `^8.4.1 || ^8.5`.
- Optional integrations, activated when installed: Inertia v2 (Vue/React), Livewire v3,
  Filament v4/v5, Nova v5.

## Cutting a release

1. Update `CHANGELOG.md` (Keep a Changelog) with the version's changes.
2. Ensure CI is green on the `8.4 / 8.5` matrix — `composer test` + `composer lint`
   (Pint, PHPStan max, Rector).
3. Tag `vX.Y.Z`; the release workflow builds the GitHub release from the CHANGELOG section.

## Upgrading from `laranail/server-error-pages`

The former static-HTML generator was replaced by this runtime renderer. See
[`UPGRADE.md`](https://github.com/laranail/error-pages/blob/main/UPGRADE.md) for the
old→new mapping (package name, namespace, config keys, and removed commands).

---
[← Docs index](../README.md#documentation)
