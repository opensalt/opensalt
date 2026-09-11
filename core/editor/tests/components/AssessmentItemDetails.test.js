import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AssessmentItemDetails from '@/components/tree/panels/item-types/AssessmentItemDetails.vue';

const sampleItem = {
  identifier: 'urn:opensalt:assessment:abc123',
  abbreviatedStatement: 'Algebra Final Exam',
  fullStatement: 'An assessment covering **algebra** fundamentals',
};

describe('AssessmentItemDetails.vue', () => {
  it('renders the Assessment Name label for abbreviatedStatement', () => {
    const wrapper = mount(AssessmentItemDetails, {
      props: { item: sampleItem },
    });
    expect(wrapper.html()).toContain('Assessment Name');
    expect(wrapper.html()).toContain('Algebra Final Exam');
  });

  it('renders fullStatement as markdown when renderedFullStatement is provided', () => {
    const renderedHtml = '<p>An assessment covering <strong>algebra</strong> fundamentals</p>';
    const wrapper = mount(AssessmentItemDetails, {
      props: { item: sampleItem, renderedFullStatement: renderedHtml },
    });
    const markdownBody = wrapper.find('.markdown-body');
    expect(markdownBody.exists()).toBe(true);
    expect(markdownBody.html()).toContain('<strong>algebra</strong>');
  });

  it('falls back to raw fullStatement when renderedFullStatement is empty', () => {
    const wrapper = mount(AssessmentItemDetails, {
      props: { item: sampleItem, renderedFullStatement: '' },
    });
    expect(wrapper.html()).toContain('An assessment covering **algebra** fundamentals');
    expect(wrapper.find('.markdown-body').exists()).toBe(false);
  });
});
