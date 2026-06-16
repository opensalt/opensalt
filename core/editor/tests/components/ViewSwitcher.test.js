import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { nextTick } from 'vue';

const mockRoute = { path: '/doc-A/association' };
const mockRouter = { push: vi.fn() };

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
  useRouter: () => mockRouter,
}));

const editorContextStoreMock = {
  canEdit: true,
  getFrameworkSelection: vi.fn(() => ({ documentId: null })),
  setFrameworkSelection: vi.fn(),
};

vi.mock('@/stores/editorContextStore', () => ({
  useEditorContextStore: () => editorContextStoreMock,
}));

vi.mock('@/stores/currentDocumentStore', () => ({
  useCurrentDocumentStore: () => ({
    currentDocument: { id: 'doc-A', identifier: 'doc-A', title: 'Edited Framework' },
  }),
}));

vi.mock('@/stores/sessionStore', () => ({
  useSessionStore: () => ({ isAuthenticated: true }),
}));

const viewStoreMock = {
  getLastItemIdForDocument: vi.fn(() => null),
};

vi.mock('@/stores/viewStore', () => ({
  useViewStore: () => viewStoreMock,
}));

import ViewSwitcher from '@/components/shared/common/ViewSwitcher.vue';

describe('ViewSwitcher navigation with a viewed-framework overlay', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    mockRouter.push.mockClear();
    editorContextStoreMock.getFrameworkSelection.mockReset();
    editorContextStoreMock.setFrameworkSelection.mockClear();
    viewStoreMock.getLastItemIdForDocument.mockReset();
    viewStoreMock.getLastItemIdForDocument.mockReturnValue(null);
  });

  it('returns to the edited framework, not the saved viewed-framework overlay, on Tree View', async () => {
    // A "viewed framework" overlay is saved for a different document than the one being edited
    editorContextStoreMock.getFrameworkSelection.mockReturnValue({ documentId: 'doc-B' });

    const wrapper = mount(ViewSwitcher);
    await nextTick();

    await wrapper.find('#displayTreeBtn').trigger('click');

    expect(mockRouter.push).toHaveBeenCalledWith('/doc-A');
  });

  it('clears the viewed-framework overlay when switching to Association View', async () => {
    editorContextStoreMock.getFrameworkSelection.mockReturnValue({ documentId: 'doc-B' });

    const wrapper = mount(ViewSwitcher);
    await nextTick();

    await wrapper.find('#displayAssocBtn').trigger('click');

    expect(editorContextStoreMock.setFrameworkSelection).toHaveBeenCalledWith('treeView', null);
    expect(mockRouter.push).toHaveBeenCalledWith('/doc-A/association');
  });

  it('navigates Tree View to the edited framework including the last selected item', async () => {
    viewStoreMock.getLastItemIdForDocument.mockReturnValue('item-9');

    const wrapper = mount(ViewSwitcher);
    await nextTick();

    await wrapper.find('#displayTreeBtn').trigger('click');

    expect(mockRouter.push).toHaveBeenCalledWith('/doc-A/item-9');
  });
});
