import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AssessmentItemDetails from '@/components/tree/panels/item-types/AssessmentItemDetails.vue';

const sampleItem = {
  identifier: 'urn:opensalt:assessment:abc123',
  fullStatement: 'Algebra **Final** Exam',
};

describe('AssessmentItemDetails.vue', () => {
  it('renders the Assessment Name label for fullStatement', () => {
    const wrapper = mount(AssessmentItemDetails, {
      props: { item: sampleItem },
    });
    expect(wrapper.html()).toContain('Assessment Name');
  });

  it('renders fullStatement as markdown when renderedFullStatement is provided', () => {
    const renderedHtml = '<p>Algebra <strong>Final</strong> Exam</p>';
    const wrapper = mount(AssessmentItemDetails, {
      props: { item: sampleItem, renderedFullStatement: renderedHtml },
    });
    const markdownBody = wrapper.find('.markdown-body');
    expect(markdownBody.exists()).toBe(true);
    expect(markdownBody.html()).toContain('<strong>Final</strong>');
  });

  it('falls back to raw fullStatement when renderedFullStatement is empty', () => {
    const wrapper = mount(AssessmentItemDetails, {
      props: { item: sampleItem, renderedFullStatement: '' },
    });
    expect(wrapper.html()).toContain('Algebra **Final** Exam');
    expect(wrapper.find('.markdown-body').exists()).toBe(false);
  });
});
