import { describe, expect, it } from 'vitest';
import { samplePayload } from './fixtures';
import { readErrorPayload, themeClass } from './payload';

describe('themeClass', () => {
  it('builds the body class from the payload theme', () => {
    expect(themeClass(samplePayload)).toBe('ep-body ep-theme-midnight ep-auto-dark');
  });

  it('omits ep-auto-dark when autoDark is off', () => {
    const page = { ...samplePayload, theme: { ...samplePayload.theme, autoDark: false } };
    expect(themeClass(page)).toBe('ep-body ep-theme-midnight');
  });
});

describe('readErrorPayload', () => {
  it('parses the embedded #error-page-data script', () => {
    document.body.innerHTML = `<script id="error-page-data" type="application/json">${JSON.stringify(samplePayload)}</script>`;
    expect(readErrorPayload()?.status).toBe(404);
  });

  it('returns null when the node is absent', () => {
    document.body.innerHTML = '';
    expect(readErrorPayload()).toBeNull();
  });

  it('returns null when the JSON is malformed', () => {
    document.body.innerHTML =
      '<script id="error-page-data" type="application/json">{not json}</script>';
    expect(readErrorPayload()).toBeNull();
  });
});
