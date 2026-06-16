import { describe, it, expect, beforeEach, vi } from 'vitest';
import { shallowMount } from '@vue/test-utils';
import DocumentSelector from '@/components/shared/common/DocumentSelector.vue';

const documents = [
  { identifier: 'doc-1', title: 'Edited Framework', creator: 'State' },
  { identifier: 'doc-2', title: 'Other Framework', creator: 'State' },
];

describe('DocumentSelector.vue tree-view mode', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('hides the external document option when requested', () => {
    const wrapper = shallowMount(DocumentSelector, {
      props: {
        currentDoc: documents[0],
        availableDocuments: documents,
        side: 'treeView',
        hideExternal: true,
        compact: true,
      },
    });

    expect(wrapper.text()).not.toContain('Load external document');
  });

  it('keeps the external document option for side-panel use', () => {
    const wrapper = shallowMount(DocumentSelector, {
      props: {
        currentDoc: documents[0],
        availableDocuments: documents,
        side: 'right',
        hideExternal: false,
        compact: false,
      },
    });

    expect(wrapper.text()).toContain('Load external document');
  });

  it('renders compactly without card chrome or placeholder', () => {
    const wrapper = shallowMount(DocumentSelector, {
      props: {
        currentDoc: documents[0],
        availableDocuments: documents,
        side: 'treeView',
        hideExternal: true,
        compact: true,
      },
    });

    expect(wrapper.find('.document-selector.card').exists()).toBe(false);
    expect(wrapper.find('label').text()).toContain('Main framework');
    expect(wrapper.text()).not.toContain('Select a document');
    expect(wrapper.find('select').attributes('id')).toMatch(/^documentSelector-treeView-/);
  });

  it('emits viewed-document-changed only after user selection', async () => {
    const wrapper = shallowMount(DocumentSelector, {
      props: {
        currentDoc: documents[0],
        availableDocuments: documents,
        side: 'treeView',
        hideExternal: true,
        compact: true,
      },
    });

    expect(wrapper.emitted('viewed-document-changed')).toBeUndefined();

    await wrapper.find('select').setValue('doc-2');

    expect(wrapper.emitted('viewed-document-changed').at(-1)).toEqual([
      { side: 'treeView', documentId: 'doc-2' },
    ]);
  });

  it('preserves mount-time emit for non-compact (side-panel) usage', () => {
    const wrapper = shallowMount(DocumentSelector, {
      props: {
        currentDoc: documents[0],
        availableDocuments: documents,
        side: 'right',
        hideExternal: false,
        compact: false,
      },
    });

    expect(wrapper.emitted('viewed-document-changed')).toEqual([
      [{ side: 'right', documentId: 'doc-1' }],
    ]);
  });
});
