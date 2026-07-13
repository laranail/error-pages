# Assets and progressive enhancement

The branded page is fully functional with no JavaScript. An optional, dependency-free
enhancement bundle (a copy-reference button, a retry countdown) is layered on top; the
**critical CSS is always inlined** so a page is never unstyled.

## Delivery modes

Set `assets.mode` (or `ERROR_PAGES_ASSETS`):

| Mode | Behaviour |
|------|-----------|
| `route` (default) | Serves `error-pages.js` / `error-pages.css` from a package route — no publish, no Vite manifest. Immutably cached + ETag-validated; `assets.version` (or a file hash) busts the cache on upgrade. |
| `link` | References `asset('vendor/error-pages/error-pages.js')` — publish the bundle to your public dir first. |
| `inline` | Embeds the script in the page (one fewer request, max resilience). |
| `off` | Ships no enhancement JS. |

```dotenv
ERROR_PAGES_ASSETS=route
```

The route prefix is `assets.route` (default `/_error-pages/assets`). The URL is built for
you and injected before `</body>`; consumers never reference it directly.

> The current bundle is a small vanilla script. When the visual template set lands, a richer
> Alpine-driven bundle replaces it at the same route — no consumer change.

---
[← Docs index](../../README.md#documentation)
