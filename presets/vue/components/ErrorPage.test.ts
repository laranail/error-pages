import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { samplePayload } from '../../shared/ts/fixtures';
import ErrorPage from './ErrorPage.vue';

describe('Vue ErrorPage', () => {
  it('renders the shared DOM contract from the payload', () => {
    const wrapper = mount(ErrorPage, { props: { page: samplePayload } });

    expect(wrapper.classes()).toContain('ep-body');
    expect(wrapper.classes()).toContain('ep-theme-midnight');
    expect(wrapper.classes()).toContain('ep-auto-dark');
    expect(wrapper.find('.ep-shell').exists()).toBe(true);
    expect(wrapper.find('.ep-status').text()).toBe('404');
    expect(wrapper.find('.ep-title').text()).toBe('Page not found');
    expect(wrapper.find('.ep-message').text()).toContain('could not be found');
    expect(wrapper.find('.ep-actions .ep-btn-primary').attributes('href')).toBe('/');
    expect(wrapper.find('.ep-ref code').text()).toBe('9f2c1a7b3d4e5f60');
  });

  it('shows a brand name when there is no logo, and a logo when there is', () => {
    expect(
      mount(ErrorPage, { props: { page: samplePayload } })
        .find('.ep-brand-name')
        .text(),
    ).toBe('Acme');

    const withLogo = mount(ErrorPage, {
      props: {
        page: { ...samplePayload, brand: { ...samplePayload.brand, logo: '/logo.svg' } },
      },
    });
    expect(withLogo.find('img.ep-logo').attributes('src')).toBe('/logo.svg');
  });

  it('omits the reference line when there is no request id', () => {
    const wrapper = mount(ErrorPage, {
      props: { page: { ...samplePayload, requestId: null } },
    });
    expect(wrapper.find('.ep-ref').exists()).toBe(false);
  });
});
