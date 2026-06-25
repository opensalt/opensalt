import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import CredentialItemDetails from '@/components/tree/panels/item-types/CredentialItemDetails.vue';

const sampleItem = {
  identifier: 'urn:opensalt:credential:abc123',
  fullStatement: 'Certified **Cloud** Architect',
};

describe('CredentialItemDetails.vue', () => {
  it('renders the Credential Name label for fullStatement', () => {
    const wrapper = mount(CredentialItemDetails, {
      props: { item: sampleItem },
    });
    expect(wrapper.html()).toContain('Credential Name');
  });

  it('renders fullStatement as markdown when renderedFullStatement is provided', () => {
    const renderedHtml = '<p>Certified <strong>Cloud</strong> Architect</p>';
    const wrapper = mount(CredentialItemDetails, {
      props: { item: sampleItem, renderedFullStatement: renderedHtml },
    });
    const markdownBody = wrapper.find('.markdown-body');
    expect(markdownBody.exists()).toBe(true);
    expect(markdownBody.html()).toContain('<strong>Cloud</strong>');
  });

  it('falls back to raw fullStatement when renderedFullStatement is empty', () => {
    const wrapper = mount(CredentialItemDetails, {
      props: { item: sampleItem, renderedFullStatement: '' },
    });
    expect(wrapper.html()).toContain('Certified **Cloud** Architect');
    expect(wrapper.find('.markdown-body').exists()).toBe(false);
  });
});
