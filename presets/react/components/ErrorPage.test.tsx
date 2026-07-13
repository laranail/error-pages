import { render } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { samplePayload } from '../../shared/ts/fixtures';
import { ErrorPage } from './ErrorPage';

describe('React ErrorPage', () => {
  it('renders the shared DOM contract from the payload', () => {
    const { container } = render(<ErrorPage page={samplePayload} />);
    const root = container.firstElementChild as HTMLElement;

    expect(root.className).toContain('ep-body');
    expect(root.className).toContain('ep-theme-midnight');
    expect(root.className).toContain('ep-auto-dark');
    expect(container.querySelector('.ep-shell')).not.toBeNull();
    expect(container.querySelector('.ep-status')?.textContent).toBe('404');
    expect(container.querySelector('.ep-title')?.textContent).toBe('Page not found');
    expect(container.querySelector('.ep-message')?.textContent).toContain('could not be found');
    expect(container.querySelector('.ep-actions .ep-btn-primary')?.getAttribute('href')).toBe('/');
    expect(container.querySelector('.ep-ref code')?.textContent).toBe('9f2c1a7b3d4e5f60');
  });

  it('shows a brand name without a logo and a logo when present', () => {
    const { container: noLogo } = render(<ErrorPage page={samplePayload} />);
    expect(noLogo.querySelector('.ep-brand-name')?.textContent).toBe('Acme');

    const { container: withLogo } = render(
      <ErrorPage
        page={{ ...samplePayload, brand: { ...samplePayload.brand, logo: '/logo.svg' } }}
      />,
    );
    expect(withLogo.querySelector('img.ep-logo')?.getAttribute('src')).toBe('/logo.svg');
  });

  it('omits the reference line when there is no request id', () => {
    const { container } = render(<ErrorPage page={{ ...samplePayload, requestId: null }} />);
    expect(container.querySelector('.ep-ref')).toBeNull();
  });
});
