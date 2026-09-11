import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import JobItemDetails from '@/components/tree/panels/item-types/JobItemDetails.vue';

const sampleItem = {
  identifier: 'urn:opensalt:job:abc123',
  abbreviatedStatement: 'Senior Software Engineer',
  fullStatement: 'A role focused on **software** development',
};

describe('JobItemDetails.vue', () => {
  it('renders the Job Title label for abbreviatedStatement', () => {
    const wrapper = mount(JobItemDetails, {
      props: { item: sampleItem },
    });
    expect(wrapper.html()).toContain('Job Title');
    expect(wrapper.html()).toContain('Senior Software Engineer');
  });

  it('renders fullStatement as markdown when renderedFullStatement is provided', () => {
    const renderedHtml = '<p>A role focused on <strong>software</strong> development</p>';
    const wrapper = mount(JobItemDetails, {
      props: { item: sampleItem, renderedFullStatement: renderedHtml },
    });
    const markdownBody = wrapper.find('.markdown-body');
    expect(markdownBody.exists()).toBe(true);
    expect(markdownBody.html()).toContain('<strong>software</strong>');
  });

  it('falls back to raw fullStatement when renderedFullStatement is empty', () => {
    const wrapper = mount(JobItemDetails, {
      props: { item: sampleItem, renderedFullStatement: '' },
    });
    expect(wrapper.html()).toContain('A role focused on **software** development');
    expect(wrapper.find('.markdown-body').exists()).toBe(false);
  });

  it('does not render the Job Title section when abbreviatedStatement is absent', () => {
    const noTitleItem = { ...sampleItem, abbreviatedStatement: '' };
    const wrapper = mount(JobItemDetails, {
      props: { item: noTitleItem },
    });
    expect(wrapper.html()).not.toContain('Job Title');
  });
});
