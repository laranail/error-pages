/**
 * The error-page payload contract — the single shape every stack renders from,
 * emitted by the PHP `ErrorPages::payloadFor()` and, for the SPA stack, embedded
 * in the page as `<script id="error-page-data" type="application/json">`.
 */
export interface ErrorPageBrand {
  name: string;
  url: string;
  logo: string | null;
}

export interface ErrorPageTheme {
  preset: string;
  autoDark: boolean;
}

export interface ErrorPagePayload {
  status: number;
  code: string;
  title: string;
  message: string;
  retryable: boolean;
  retryAfter: number | null;
  requestId: string | null;
  homeUrl: string;
  brand: ErrorPageBrand;
  theme: ErrorPageTheme;
}

/**
 * Read the embedded payload from `#error-page-data` (the SPA stack). Returns null
 * when absent or malformed, so callers can fall back to the server-rendered page.
 */
export function readErrorPayload(root: ParentNode = document): ErrorPagePayload | null {
  const node = root.querySelector('#error-page-data');
  if (!node || !node.textContent) {
    return null;
  }
  try {
    return JSON.parse(node.textContent) as ErrorPagePayload;
  } catch {
    return null;
  }
}

/** Build the body class list for a payload's theme (mirrors the server markup). */
export function themeClass(page: ErrorPagePayload): string {
  return ['ep-body', `ep-theme-${page.theme.preset}`, page.theme.autoDark ? 'ep-auto-dark' : '']
    .filter(Boolean)
    .join(' ');
}
