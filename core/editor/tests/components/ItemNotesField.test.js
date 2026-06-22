import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ItemNotesField from '@/components/tree/panels/ItemNotesField.vue';

describe('ItemNotesField', () => {
  it('renders rendered HTML when provided', () => {
    const wrapper = mount(ItemNotesField, {
      props: { renderedNotes: '<p>Rendered <strong>markdown</strong></p>' },
    });
    expect(wrapper.find('.markdown-body').exists()).toBe(true);
    expect(wrapper.find('.markdown-body').html()).toContain('<strong>markdown</strong>');
  });

  it('renders raw text as fallback when renderedNotes is empty', () => {
    const wrapper = mount(ItemNotesField, {
      props: { renderedNotes: '', rawNotes: 'Plain text notes' },
    });
    expect(wrapper.find('.markdown-body').exists()).toBe(false);
    expect(wrapper.text()).toContain('Plain text notes');
  });

  it('renders empty when both are empty', () => {
    const wrapper = mount(ItemNotesField, {
      props: {},
    });
    expect(wrapper.find('dt').text()).toBe('Notes:');
    expect(wrapper.find('.markdown-body').exists()).toBe(false);
  });

  it('has details-entry--full class for stacked layout', () => {
    const wrapper = mount(ItemNotesField, {
      props: { renderedNotes: '<p>test</p>' },
    });
    expect(wrapper.find('.details-entry--full').exists()).toBe(true);
  });

  it('applies markdown-body styles (scoped)', () => {
    const wrapper = mount(ItemNotesField, {
      props: { renderedNotes: '<p>test</p>' },
    });
    expect(wrapper.find('.markdown-body').exists()).toBe(true);
  });
});
