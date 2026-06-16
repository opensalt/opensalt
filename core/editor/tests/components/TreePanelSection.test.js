import { describe, it, expect, beforeEach, vi } from 'vitest';
import { ref } from 'vue';
import { shallowMount } from '@vue/test-utils';
import TreePanelSection from '@/components/tree/TreePanelSection.vue';

const mockedUseViewedDoc = vi.hoisted(() => vi.fn());

vi.mock('@/composables/useViewedDoc', () => ({
  useViewedDoc: mockedUseViewedDoc,
}));

const currentDoc = { identifier: 'doc-1', id: 'doc-1', title: 'Edited Framework', items: [] };
const availableDocuments = [
  currentDoc,
  { identifier: 'doc-2', id: 'doc-2', title: 'Other Framework', creator: 'State' },
];

function baseProps(overrides = {}) {
  return {
    currentDoc,
    filteredDoc: currentDoc,
    filteredViewedDoc: null,
    availableDocuments,
    selectedId: null,
    treeSearchQuery: '',
    matchCount: null,
    matchingItemIds: new Set(),
    associationGroups: [],
    selectedAssociationGroup: 'all',
    availableSubjects: [],
    canSwitchViewedFramework: true,
    ...overrides,
  };
}

describe('TreePanelSection.vue tree-view selector', () => {
  beforeEach(() => {
    mockedUseViewedDoc.mockReturnValue({
      viewedDoc: ref(null),
      isViewingDifferentFramework: ref(false),
    });
  });

  it('does not render the selector when switching is disabled', () => {
    const wrapper = shallowMount(TreePanelSection, {
      props: baseProps({ canSwitchViewedFramework: false }),
      global: {
        stubs: {
          DocumentSelector: true,
          TreeFilter: true,
          AssociationGroupSelector: true,
          TreeView: true,
          SearchFilter: true,
        },
      },
    });

    expect(wrapper.findComponent({ name: 'DocumentSelector' }).exists()).toBe(false);
  });

  it('passes viewed-document changes to the parent', async () => {
    const wrapper = shallowMount(TreePanelSection, {
      props: baseProps(),
      global: {
        stubs: {
          TreeFilter: true,
          AssociationGroupSelector: true,
          TreeView: true,
          SearchFilter: true,
        },
      },
    });

    const selector = wrapper.findComponent({ name: 'DocumentSelector' });
    await selector.vm.$emit('viewed-document-changed', { side: 'treeView', documentId: 'doc-2' });

    expect(wrapper.emitted('viewed-document-changed')).toEqual([
      [{ side: 'treeView', documentId: 'doc-2' }],
    ]);
  });

  it('passes filtered viewed document to TreeView while viewing a different framework', () => {
    mockedUseViewedDoc.mockReturnValue({
      viewedDoc: ref({ identifier: 'doc-2', id: 'doc-2', title: 'Viewed Framework', items: [] }),
      isViewingDifferentFramework: ref(true),
    });

    const filteredViewedDoc = { identifier: 'doc-2', id: 'doc-2', title: 'Viewed Framework', items: [] };
    const wrapper = shallowMount(TreePanelSection, {
      props: baseProps({ filteredViewedDoc }),
      global: {
        stubs: {
          DocumentSelector: true,
          TreeFilter: true,
          AssociationGroupSelector: true,
          SearchFilter: true,
        },
      },
    });

    expect(wrapper.findComponent({ name: 'TreeView' }).props('doc')).toStrictEqual(filteredViewedDoc);
  });

  it('passes disableDrop to TreeView while viewing a different framework', () => {
    mockedUseViewedDoc.mockReturnValue({
      viewedDoc: ref(null),
      isViewingDifferentFramework: ref(true),
    });

    const wrapper = shallowMount(TreePanelSection, {
      props: baseProps(),
      global: {
        stubs: {
          DocumentSelector: true,
          TreeFilter: true,
          AssociationGroupSelector: true,
          SearchFilter: true,
        },
      },
    });

    expect(wrapper.findComponent({ name: 'TreeView' }).props('disableDrop')).toBe(true);
  });
});
