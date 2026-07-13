import type { ErrorPagePayload } from './payload';

/** A representative payload for the component DOM-parity tests. */
export const samplePayload: ErrorPagePayload = {
  status: 404,
  code: '404',
  title: 'Page not found',
  message: 'The page you are looking for could not be found.',
  retryable: false,
  retryAfter: null,
  requestId: '9f2c1a7b3d4e5f60',
  homeUrl: '/',
  brand: { name: 'Acme', url: '/', logo: null },
  theme: { preset: 'midnight', autoDark: true, locale: 'en', dir: 'ltr' },
};
