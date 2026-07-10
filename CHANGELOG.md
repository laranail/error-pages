# Changelog

All notable changes to `laranail/server-error-pages` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial release.
- Dynamic Blade error views for 400, 401, 403, 404, 419, 429, 500, 502, 503, 504
  plus generic `4xx`/`5xx` fallbacks, resolved through Laravel's `errors::` namespace
  (an app-published `resources/views/errors/{code}.blade.php` still wins).
- `server-error-pages:build` — generates self-contained static HTML (inlined CSS/JS,
  zero external requests) plus Apache `.htaccess` / Nginx `error_page` config, all from
  the one `<x-server-error-pages::layout>` component.
- Build-time self-containment assertion that fails on any external subresource.
- File-managed content: JSON files then PHP config then built-in `HttpStatus` enum
  defaults. No database.
- `centered`, `hero`, and `minimal` layout variants; theme colours as runtime CSS
  custom properties (re-brand with no rebuild); automatic dark mode.
- `server-error-pages:server-config`, `server-error-pages:clear`, and
  `server-error-pages:install` commands.
- Server config is written as a managed block merged between sentinel markers, so
  the generated Apache `.htaccess` never overwrites Laravel's front-controller
  rewrite rules, and `clear` strips only the managed block.
