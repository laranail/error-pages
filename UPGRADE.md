# Upgrade guide

## From `laranail/server-error-pages` (static generator) to `laranail/error-pages`

The package was rebuilt from a static-HTML / Apache-Nginx **generator** into a runtime,
Laravel-first **exception/error-page renderer**. This is a clean break — there is no
compatibility shim.

### Composer

```diff
- "laranail/server-error-pages": "^0.1"
+ "laranail/error-pages": "^0.2"
```

### Names

| Old | New |
|-----|-----|
| Package | `laranail/server-error-pages` → `laranail/error-pages` |
| Namespace | `Simtabi\Laranail\ServerErrorPages` → `Simtabi\Laranail\ErrorPages` (agnostic engine under `…\ErrorPages\Core`) |
| Config file | `config/laranail/server-error-pages.php` → `config/error-pages.php` |
| Config key | `laranail.server-error-pages.*` → `error-pages.*` |
| Facade | `ServerErrorPages` → `ErrorPages` |

### Removed

The static/server-config surface is gone: the `server-error-pages:build`, `:export`,
`:server-config`, and `:clear` commands; the Apache/Nginx stub generation; the standalone
inlined export; and the `output.*`, `server.*`, `security.headers`, and `assets.compiled_*`
config sections. Delete any deploy scripts that called those commands.

### What replaces it

Nothing to run — the package now renders branded pages at request time and self-registers.
Re-publish the config (`vendor:publish --tag=laranail::error-pages-config`) and migrate any
`brand`/`theme`/`codes`/`content` values into the new `config/error-pages.php`. See
[Installation](docs/installation.md) and [Configuration](docs/configuration.md).

If you deployed static pages for when PHP is down, that concern is out of scope for the
runtime renderer — keep a minimal static maintenance page at the web-server layer, or use
`php artisan down --render="errors::503"`.
