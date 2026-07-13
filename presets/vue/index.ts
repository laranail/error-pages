import { createApp, h } from 'vue';
import ErrorPage from './ErrorPage.vue';
import { readErrorPayload } from '../shared/payload';

export { default as ErrorPage } from './ErrorPage.vue';
export * from '../shared/payload';

/**
 * Mount the branded error page onto `selector` from the embedded `#error-page-data`
 * payload (the Vue-SPA stack). No-op when the payload or element is absent, so the
 * server-rendered page stays visible.
 */
export function mountErrorPage(selector = '#app'): void {
  const payload = readErrorPayload();
  const el = document.querySelector(selector);
  if (payload && el) {
    createApp({ render: () => h(ErrorPage, { page: payload }) }).mount(el);
  }
}
