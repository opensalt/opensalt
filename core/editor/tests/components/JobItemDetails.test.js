import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import JobItemDetails from '@/components/tree/panels/item-types/JobItemDetails.vue';

const sampleItem = {
  identifier: 'urn:opensalt:job:abc123',
  fullStatement: 'Senior **Software** Engineer',
};

describe('JobItemDetails.vue', () => {
  it('renders the Job Title label for fullStatement', () => {
    const wrapper = mount(JobItemDetails, {
      props: { item: sampleItem },
    });
    expect(wrapper.html()).toContain('Job Title');
  });

  it('renders fullStatement as markdown when renderedFullStatement is provided', () => {
    const renderedHtml = '<p>Senior <strong>Software</strong> Engineer</p>';
    const wrapper = mount(JobItemDetails, {
      props: { item: sampleItem, renderedFullStatement: renderedHtml },
    });
    const markdownBody = wrapper.find('.markdown-body');
    expect(markdownBody.exists()).toBe(true);
    expect(markdownBody.html()).toContain('<strong>Software</strong>');
  });

  it('falls back to raw fullStatement when renderedFullStatement is empty', () => {
    const wrapper = mount(JobItemDetails, {
      props: { item: sampleItem, renderedFullStatement: '' },
    });
    expect(wrapper.html()).toContain('Senior **Software** Engineer');
    expect(wrapper.find('.markdown-body').exists()).toBe(false);
  });

  it('does not render the Job Title section when fullStatement is absent', () => {
    const noStatementItem = { ...sampleItem, fullStatement: '' };
    const wrapper = mount(JobItemDetails, {
      props: { item: noStatementItem },
    });
    expect(wrapper.html()).not.toContain('Job Title');
  });
});
