import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import CourseItemDetails from '@/components/tree/panels/item-types/CourseItemDetails.vue';

const sampleItem = {
  identifier: 'urn:opensalt:course:abc123',
  fullStatement: 'A course **description** with markdown',
  extensions: {
    'salt:type': 'course',
  },
};

describe('CourseItemDetails.vue', () => {
  it('renders fields in the expected order using dt elements', () => {
    const wrapper = mount(CourseItemDetails, {
      props: { item: sampleItem },
    });
    const labels = wrapper.findAll('dt').map((dt) => dt.text());
    expect(labels).toContain('Description:');
  });

  it('renders the Description label for fullStatement', () => {
    const wrapper = mount(CourseItemDetails, {
      props: { item: sampleItem },
    });
    expect(wrapper.html()).toContain('Description');
  });

  it('renders fullStatement as markdown when renderedFullStatement is provided', () => {
    const renderedHtml = '<p>A course <strong>description</strong> with markdown</p>';
    const wrapper = mount(CourseItemDetails, {
      props: {
        item: sampleItem,
        renderedFullStatement: renderedHtml,
      },
    });
    const markdownBody = wrapper.find('.markdown-body');
    expect(markdownBody.exists()).toBe(true);
    expect(markdownBody.html()).toContain('<strong>description</strong>');
  });

  it('falls back to raw fullStatement when renderedFullStatement is empty', () => {
    const wrapper = mount(CourseItemDetails, {
      props: {
        item: sampleItem,
        renderedFullStatement: '',
      },
    });
    expect(wrapper.html()).toContain('A course **description** with markdown');
    expect(wrapper.find('.markdown-body').exists()).toBe(false);
  });

  it('does not render the Description section when fullStatement is absent', () => {
    const noStatementItem = { ...sampleItem, fullStatement: '' };
    const wrapper = mount(CourseItemDetails, {
      props: { item: noStatementItem },
    });
    expect(wrapper.html()).not.toContain('Description');
  });
});
