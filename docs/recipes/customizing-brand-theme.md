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

Nudge individual tokens (`bg`, `surface`, `text`, `muted`, `accent`, `accent-2`, `on-accent`,
`border`) in `config/error-pages.php` — emitted as inline, sanitised CSS scoped to the preset:

```php
'theme' => [
    'preset' => 'slate',
    'colors' => [
        'light' => ['accent' => '#ea580c', 'on-accent' => '#ffffff'],
        'dark'  => ['accent' => '#fb923c', 'on-accent' => '#0b1120'],
    ],
],
```

> `on-accent` is the **primary-button text colour**. The shipped presets meet WCAG AA; if you
> override `accent`, set a matching `on-accent` (white or a near-black) so the button text keeps
> ≥4.5:1 contrast against your accent.

## Per-request theme

```php
ErrorPages::theme('crimson');   // e.g. for a specific tenant, from a provider
```

See [Configuration](../configuration.md#keys) for every theme key.

---
[← Docs index](../../README.md#documentation)
