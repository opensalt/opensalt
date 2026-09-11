import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import CredentialItemDetails from '@/components/tree/panels/item-types/CredentialItemDetails.vue';

const sampleItem = {
  identifier: 'urn:opensalt:credential:abc123',
  abbreviatedStatement: 'Certified Cloud Architect',
  fullStatement: 'A credential for **cloud** architecture skills',
};

describe('CredentialItemDetails.vue', () => {
  it('renders the Credential Name label for abbreviatedStatement', () => {
    const wrapper = mount(CredentialItemDetails, {
      props: { item: sampleItem },
    });
    expect(wrapper.html()).toContain('Credential Name');
    expect(wrapper.html()).toContain('Certified Cloud Architect');
  });

  it('renders fullStatement as markdown when renderedFullStatement is provided', () => {
    const renderedHtml = '<p>A credential for <strong>cloud</strong> architecture skills</p>';
    const wrapper = mount(CredentialItemDetails, {
      props: { item: sampleItem, renderedFullStatement: renderedHtml },
    });
    const markdownBody = wrapper.find('.markdown-body');
    expect(markdownBody.exists()).toBe(true);
    expect(markdownBody.html()).toContain('<strong>cloud</strong>');
  });

  it('falls back to raw fullStatement when renderedFullStatement is empty', () => {
    const wrapper = mount(CredentialItemDetails, {
      props: { item: sampleItem, renderedFullStatement: '' },
    });
    expect(wrapper.html()).toContain('A credential for **cloud** architecture skills');
    expect(wrapper.find('.markdown-body').exists()).toBe(false);
  });
});
