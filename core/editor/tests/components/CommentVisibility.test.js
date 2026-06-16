import { describe, it, expect, beforeEach, vi } from 'vitest';
import { shallowMount } from '@vue/test-utils';
import { ref } from 'vue';
import { createPinia, setActivePinia } from 'pinia';

const mockContextStore = {
  isEditable: vi.fn(() => true),
  isViewingDifferentFramework: false,
  viewedDocumentId: null,
  documentRegistry: new Map(),
};

const mockSessionStore = {
  isAuthenticated: true,
};

vi.mock('@/config/editorConfig.js', () => ({
  editorConfig: {
    features: {
      comments: true,
    },
  },
}));

vi.mock('@/utils/render-md.js', () => ({
  default: {
    block: vi.fn((value) => value),
  },
}));

vi.mock('@/utils/markdownRenderer.js', () => ({
  hasMarkdown: vi.fn(() => false),
}));

vi.mock('@/stores/sessionStore', () => ({
  useSessionStore: vi.fn(() => mockSessionStore),
}));

vi.mock('@/stores/editorContextStore', () => ({
  useEditorContextStore: vi.fn(() => mockContextStore),
}));

vi.mock('@/stores/currentDocumentStore', () => ({
  useCurrentDocumentStore: vi.fn(() => ({
    currentDocumentDefinitions: { CFLicenses: [] },
  })),
}));

vi.mock('@/stores/documentStore', () => ({
  useDocumentStore: vi.fn(() => ({})),
}));

vi.mock('@/composables/useDynamicModal.js', () => ({
  useDynamicModal: vi.fn(() => ({
    showModal: vi.fn(),
    selectedType: ref(''),
    isModalVisible: ref(false),
    handleCreated: vi.fn(),
    modalComponent: ref(null),
    handleHidden: vi.fn(),
  })),
}));

vi.mock('@/composables/useItemAssociations.js', () => ({
  useItemAssociations: vi.fn(() => ({
    mergedAssociations: [],
    isProcessingAssociations: false,
  })),
}));

vi.mock('@/composables/useCrossFrameworkItem', () => ({
  useCrossFrameworkItem: vi.fn(() => ({
    itemData: ref(null),
    isLoading: ref(false),
    frameworkTitle: ref(null),
    fetchError: ref(null),
  })),
}));

import { editorConfig } from '@/config/editorConfig.js';
import DocumentDetailsPanel from '@/components/tree/panels/DocumentDetailsPanel.vue';
import ItemDetails from '@/components/tree/panels/ItemDetails.vue';

describe('comment visibility in editor panels', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    editorConfig.features.comments = true;
    mockSessionStore.isAuthenticated = true;
    mockContextStore.isEditable.mockReturnValue(true);
    mockContextStore.isViewingDifferentFramework = false;
    mockContextStore.viewedDocumentId = null;
    mockContextStore.documentRegistry = new Map();
  });

  it('hides document comments when the comments feature is disabled', () => {
    editorConfig.features.comments = false;

    const wrapper = shallowMount(DocumentDetailsPanel, {
      props: {
        document: {
          identifier: 'doc-1',
          title: 'Test Document',
        },
      },
      global: {
        stubs: {
          CommentModule: { template: '<div data-test="comment-module" />' },
        },
      },
    });

    expect(wrapper.find('[data-test="comment-module"]').exists()).toBe(false);
  });

  it('shows document comments when the comments feature is enabled', () => {
    const wrapper = shallowMount(DocumentDetailsPanel, {
      props: {
        document: {
          identifier: 'doc-1',
          title: 'Test Document',
        },
      },
      global: {
        stubs: {
          CommentModule: { template: '<div data-test="comment-module" />' },
        },
      },
    });

    expect(wrapper.find('[data-test="comment-module"]').exists()).toBe(true);
  });

  it('hides item comments when the comments feature is disabled', () => {
    editorConfig.features.comments = false;

    const wrapper = shallowMount(ItemDetails, {
      props: {
        item: {
          identifier: 'item-1',
          title: 'Test Item',
        },
        currentDocument: {
          identifier: 'doc-1',
        },
      },
      global: {
        stubs: {
          CommentModule: { template: '<div data-test="comment-module" />' },
          ItemCrossFrameworkBanner: true,
          ItemHeaderCard: { template: '<div><slot /></div>' },
          ItemDefaultDetails: true,
          ItemActionsCard: true,
          ItemAssociationsCard: true,
          DeleteAssociationModal: true,
          JobItemDetails: true,
          CourseItemDetails: true,
          AssessmentItemDetails: true,
          CredentialItemDetails: true,
          OrganizationItemDetails: true,
          IdentifierItemDetails: true,
          PublicKeyItemDetails: true,
        },
      },
    });

    expect(wrapper.find('[data-test="comment-module"]').exists()).toBe(false);
  });

  it('shows item comments when the comments feature is enabled', () => {
    const wrapper = shallowMount(ItemDetails, {
      props: {
        item: {
          identifier: 'item-1',
          title: 'Test Item',
        },
        currentDocument: {
          identifier: 'doc-1',
        },
      },
      global: {
        stubs: {
          CommentModule: { template: '<div data-test="comment-module" />' },
          ItemCrossFrameworkBanner: true,
          ItemHeaderCard: { template: '<div><slot /></div>' },
          ItemDefaultDetails: true,
          ItemActionsCard: true,
          ItemAssociationsCard: true,
          DeleteAssociationModal: true,
          JobItemDetails: true,
          CourseItemDetails: true,
          AssessmentItemDetails: true,
          CredentialItemDetails: true,
          OrganizationItemDetails: true,
          IdentifierItemDetails: true,
          PublicKeyItemDetails: true,
        },
      },
    });

    expect(wrapper.find('[data-test="comment-module"]').exists()).toBe(true);
  });
});
