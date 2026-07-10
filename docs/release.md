# Release

How `laranail/server-error-pages` is versioned and published.

## Versioning

The package follows semantic versioning, tag-driven from `main`. The single source of version truth is the git tag (`vX.Y.Z`); there is no version string committed in code. The `composer.json` carries a `dev-main` branch alias for pre-release development.

## Cutting a release

1. Update `CHANGELOG.md` with a new `## [X.Y.Z]` section describing what changed.
2. Ensure the suite is green:

   ```bash
   composer lint   # pint + phpstan + rector
   composer test   # pest
   ```

3. Tag and push:

   ```bash
   git tag vX.Y.Z
   git push origin vX.Y.Z
   ```

The GitHub release body is sourced from that version's `CHANGELOG.md` section — never a bare "see changelog" stub.

## Publishing to Packagist

Packagist tracks the repository, so a pushed `vX.Y.Z` tag becomes an installable version automatically. Consumers pull it with:

```bash
composer require laranail/server-error-pages
```

## Quality gates

| Gate | Command | Tool |
|------|---------|------|
| Tests | `composer test` | Pest 4 (+ arch, laravel plugins) |
| Static analysis | `composer phpstan` | PHPStan 2 / Larastan 3 |
| Code style | `composer pint` | Laravel Pint |
| Refactors | `composer rector` | Rector 2 (dry-run) |

`composer lint` runs Pint, PHPStan, and Rector together and must pass before a tag.

---
[← Docs index](../README.md#documentation)
