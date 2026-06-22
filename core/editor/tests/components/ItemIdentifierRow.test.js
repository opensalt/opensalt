import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import ItemIdentifierRow from '@/components/tree/panels/ItemIdentifierRow.vue';

describe('ItemIdentifierRow', () => {
  beforeEach(() => {
    Object.defineProperty(window, 'location', {
      value: { origin: 'http://localhost', pathname: '/cftree/doc/123' },
      writable: true,
    });
  });

  it('renders a dt/dd row with a link', () => {
    const wrapper = mount(ItemIdentifierRow, {
      props: { identifier: 'urn:opensalt:item:abc' },
    });
    expect(wrapper.find('dt').text()).toBe('Identifier:');
    const link = wrapper.find('a');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toBe('/uri/urn:opensalt:item:abc');
    expect(link.text()).toContain('urn:opensalt:item:abc');
  });

  it('uses custom label when provided', () => {
    const wrapper = mount(ItemIdentifierRow, {
      props: { identifier: 'urn:opensalt:item:abc', label: 'Item URI' },
    });
    expect(wrapper.find('dt').text()).toBe('Item URI:');
  });

  it('includes visually-hidden accessibility text', () => {
    const wrapper = mount(ItemIdentifierRow, {
      props: { identifier: 'urn:opensalt:item:abc' },
    });
    expect(wrapper.find('.visually-hidden').text()).toBe('(opens in new window)');
  });

  it('renders em-dash when identifier is empty', () => {
    const wrapper = mount(ItemIdentifierRow, {
      props: { identifier: '' },
    });
    expect(wrapper.find('a').exists()).toBe(false);
    expect(wrapper.text()).toContain('—');
  });

  it('renders em-dash when identifier is null', () => {
    const wrapper = mount(ItemIdentifierRow, {
      props: { identifier: null },
    });
    expect(wrapper.find('a').exists()).toBe(false);
    expect(wrapper.text()).toContain('—');
  });

  it('rewrites local: prefix to base URL + /uri/<id>', () => {
    const wrapper = mount(ItemIdentifierRow, {
      props: { identifier: 'local:abc123' },
    });
    const link = wrapper.find('a');
    expect(link.attributes('href')).toBe('http://localhost/cftree/doc/123/uri/abc123');
    expect(link.text()).toContain('http://localhost/cftree/doc/123/uri/abc123');
  });

  it('uses custom href when provided', () => {
    const wrapper = mount(ItemIdentifierRow, {
      props: { identifier: 'urn:test:abc', href: 'https://custom.example.com/abc' },
    });
    const link = wrapper.find('a');
    expect(link.attributes('href')).toBe('https://custom.example.com/abc');
  });

  it('renders inside correct CSS classes', () => {
    const wrapper = mount(ItemIdentifierRow, {
      props: { identifier: 'urn:test:abc' },
    });
    expect(wrapper.find('.details-identifier.item-identifier').exists()).toBe(true);
  });
});
