import { createRoot } from 'react-dom/client';
import { ErrorPage } from './components/ErrorPage';
import { readErrorPayload } from '../shared/ts/payload';

export { ErrorPage, default } from './components/ErrorPage';
export * from '../shared/ts/payload';

/**
 * Mount the branded error page onto `selector` from the embedded `#error-page-data`
 * payload (the React-SPA stack). No-op when the payload or element is absent, so
 * the server-rendered page stays visible.
 */
export function mountErrorPage(selector = '#app'): void {
    const payload = readErrorPayload();
    const el = document.querySelector(selector);
    if (payload && el) {
        createRoot(el).render(<ErrorPage page={payload} />);
    }
}
