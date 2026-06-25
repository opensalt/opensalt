import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import OrganizationItemDetails from '@/components/tree/panels/item-types/OrganizationItemDetails.vue';

const sampleItem = {
  identifier: 'urn:opensalt:organization:abc123',
  fullStatement: 'An organization *description* with markdown',
};

describe('OrganizationItemDetails.vue', () => {
  it('renders the Description label for fullStatement', () => {
    const wrapper = mount(OrganizationItemDetails, {
      props: { item: sampleItem },
    });
    expect(wrapper.html()).toContain('Description');
  });

  it('renders fullStatement as markdown when renderedFullStatement is provided', () => {
    const renderedHtml = '<p>An organization <em>description</em> with markdown</p>';
    const wrapper = mount(OrganizationItemDetails, {
      props: { item: sampleItem, renderedFullStatement: renderedHtml },
    });
    const markdownBody = wrapper.find('.markdown-body');
    expect(markdownBody.exists()).toBe(true);
    expect(markdownBody.html()).toContain('<em>description</em>');
  });

  it('falls back to raw fullStatement when renderedFullStatement is empty', () => {
    const wrapper = mount(OrganizationItemDetails, {
      props: { item: sampleItem, renderedFullStatement: '' },
    });
    expect(wrapper.html()).toContain('An organization *description* with markdown');
    expect(wrapper.find('.markdown-body').exists()).toBe(false);
  });
});
