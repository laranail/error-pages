# Customising brand and theme

Re-brand the pages with a logo, a colour preset, and per-token overrides — no rebuild.

## Brand + preset

```dotenv
ERROR_PAGES_BRAND="Acme Inc"
ERROR_PAGES_LOGO="/images/logo.svg"
ERROR_PAGES_THEME=midnight
ERROR_PAGES_AUTO_DARK=true
```

Presets: `default`, `slate`, `midnight`, `emerald`, `crimson`. `auto_dark` follows the
visitor's `prefers-color-scheme`.

## Per-token colour overrides

Nudge individual tokens (`bg`, `surface`, `text`, `muted`, `accent`, `accent-2`, `border`)
in `config/error-pages.php` — emitted as inline, sanitised CSS scoped to the preset:

```php
'theme' => [
    'preset' => 'slate',
    'colors' => [
        'light' => ['accent' => '#ea580c'],
        'dark'  => ['accent' => '#fb923c'],
    ],
],
```

## Per-request theme

```php
ErrorPages::theme('crimson');   // e.g. for a specific tenant, from a provider
```

See [Configuration](../configuration.md#keys) for every theme key.

---
[← Docs index](../../README.md#documentation)
