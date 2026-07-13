# Release

How `laranail/error-pages` is versioned and released.

## Versioning

Semantic versioning. The package tracks the laranail family's pre-1.0 line
(`minimum-stability: dev`, `branch-alias dev-main → 0.x-dev`) until the API settles.

## Compatibility

- Laravel `^13.0`, PHP `^8.4.1 || ^8.5`.
- Optional integrations, active when installed: Inertia v2 (Vue/React), **Livewire `^4`**
  (the `livewire` stack), Filament (auto-detected panel context), Nova v5 (Inertia-routed).
  The Vue 3 / React 19 components (`@laranail/error-pages-ui`) are unit-tested (Vitest); their
  final visual CSS lands with the canonical Blade templates.

## Roadmap

Everything non-visual (the coexistence hook, all stacks and their renderers, the DSL, events,
asset route, CSP nonce, report throttle, preview gallery, test helpers, panel auto-detection,
and the Vue/React/Livewire components) ships and is tested today. The remaining work is the
canonical design and the standalone panel packages:

- [ ] **Canonical CSS-first Blade templates** (expected ~2026-07-19) — drop the maintainer's
  templates into `presets/blade`; they are the design source of truth for every stack.
- [ ] **Finalize the component visual CSS** — derive the shared stylesheet and each stack's
  styling (Blade, plain-PHP, Vue, React, Livewire) from those templates so all stacks stay
  pixel-consistent. Until then `presets/shared/critical.css` styles them and `presets/plain-php`
  is the guaranteed server render.
- [ ] **Standalone Filament Plugin + Nova Tool packages** — first-class panel integrations
  (auto-registration, panel-matched theming) beyond the current path-based auto-detection.

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
