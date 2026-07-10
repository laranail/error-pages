# Contributing

Thanks for helping improve `laranail/server-error-pages`.

## Getting set up

```bash
git clone https://github.com/laranail/server-error-pages
cd server-error-pages
composer install
```

## Checks

All contributions must pass the same checks CI runs:

```bash
composer test      # Pest + Orchestra Testbench
composer lint      # Pint (style) + PHPStan level 8 + Rector
```

Run `composer pint-fix` and `composer rector-fix` to apply automatic fixes.

## Conventions

- PHP `>= 8.4`, `declare(strict_types=1)`, `final` classes, typed properties, backed enums.
- Follow the existing structure: `Contracts/`, `Services/`, `Support/`, `ValueObjects/`,
  `Commands/`, `Enums/`, traits under `Concerns/`.
- The public error pages must stay **self-contained** — no external CSS, JS, fonts, or
  images. The build asserts this; keep it green.
- Frontend assets are hand-authored and committed to `resources/dist/`. If you change
  component markup, regenerate with `npm run build:all` and commit the result.
- Add or update tests for any behaviour change. Update `CHANGELOG.md` under `Unreleased`.

## Commit and PR conventions

- Subject line in the imperative mood, under 72 characters; the body explains *why*.
- No AI attribution in commits or PRs.
