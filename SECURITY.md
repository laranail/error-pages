# Security Policy

## Supported versions

The latest released minor version receives security fixes.

## Reporting a vulnerability

Please report security issues privately to **opensource@simtabi.com**. Do not open a
public issue for a suspected vulnerability.

Include a description, reproduction steps, and the affected version. We will acknowledge
your report, work on a fix, and coordinate disclosure with you.

## Notes for this package

- Generated static pages are fully self-contained (no external requests) by design; the
  build fails if any external subresource is introduced.
- Error-page content is authored in files (config/JSON), not accepted from web input, so
  there is no runtime content-editing attack surface. Content is still escaped on render.
- Security headers for the static pages are emitted into the web-server config, since the
  application cannot set headers on files it does not serve.
