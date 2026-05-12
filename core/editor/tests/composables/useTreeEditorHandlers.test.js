import { describe, it, expect, vi } from 'vitest';
import { ref } from 'vue';
import { useTreeEditorHandlers } from '../../src/composables/useTreeEditorHandlers';

/**
 * Helper to create a minimal context object for useTreeEditorHandlers.
 * All stores and callbacks are replaced with mocks so no real side-effects occur.
 */
function createMockContext(overrides = {}) {
  const createItemMock = vi.fn().mockResolvedValue({ identifier: 'new-item-id' });
  const revalidatePackageMock = vi.fn().mockResolvedValue(undefined);
  const reloadActiveDocumentMock = vi.fn().mockResolvedValue(undefined);

  const currentDocumentStore = {
    currentDocument: { id: 'doc-1', identifier: 'doc-1' },
    createItem: createItemMock,
    ...overrides.currentDocumentStore,
  };

  // If the override didn't provide its own createItem, keep the default mock
  if (!overrides.currentDocumentStore?.createItem) {
    currentDocumentStore.createItem = createItemMock;
  }
  if (!overrides.currentDocumentStore?.reloadActiveDocument) {
    currentDocumentStore.reloadActiveDocument = reloadActiveDocumentMock;
  }

  const documentStore = {
    revalidatePackage: revalidatePackageMock,
    ...overrides.documentStore,
  };
  if (!overrides.documentStore?.revalidatePackage) {
    documentStore.revalidatePackage = revalidatePackageMock;
  }

  const router = { push: vi.fn(), ...(overrides.router || {}) };
  const onSelectSpy = vi.fn();

  const viewStore = {
    setCurrentItem: vi.fn(),
    setLastSelectedItem: vi.fn(),
    setFocusedItemId: vi.fn(),
    currentItem: null,
    ...overrides.viewStore,
  };

  const ctx = {
    documentStore,
    currentDocumentStore,
    filterStore: { setSearchQuery: vi.fn(), setFilters: vi.fn(), clearFilters: vi.fn(), setSelectedAssociationGroup: vi.fn() },
    itemStore: {},
    viewStore,
    contextStore: {
      viewedDocumentId: ref(null),
      activeWriteDocumentId: ref('doc-1'),
      getFrameworkSelection: vi.fn(),
      setFrameworkSelection: vi.fn(),
      ...overrides.contextStore,
    },
    router,
    currentDoc: ref({ id: 'doc-1', identifier: 'doc-1', items: [] }),
    viewedDoc: ref(null),
    isViewingDifferentFramework: ref(false),
    rightPanelMode: ref('itemDetails'),
    filteredDoc: ref({ items: [] }),
    showEditDocModal: ref(false),
    showEditAssociationModal: ref(false),
    showDeleteModal: ref(false),
    editingAssociation: ref(null),
    itemsToDelete: ref([]),
    deleteType: ref('single'),
    addingAssociation: ref(false),
    addingAssociationType: ref(''),
    addingAssociationOrigin: ref(null),
    addingAssociationDestination: ref(null),
    closeEditAssociationModal: vi.fn(),
    closeCrossTreeModal: vi.fn(),
    crossTreeSource: ref(null),
    crossTreeTarget: ref(null),
    showAssocGroupModal: ref(false),
    showLoadExternalModal: ref(false),
    showEditModal: vi.fn(),
    documentLoaderOnExternalDocumentRequested: vi.fn(),
    documentLoaderOnExternalDocumentUrlLoaded: vi.fn(),
    sideDocument: ref(null),
    onSideDocumentSelect: vi.fn(),
    announcer: { announceNavigation: vi.fn() },
    openDeleteAssociationModal: vi.fn(),
    closeDeleteAssociationModal: vi.fn(),
    _connectMercure: vi.fn(),
  };

  return { ctx, createItemMock, revalidatePackageMock, reloadActiveDocumentMock, router, onSelectSpy, viewStore };
}

describe('useTreeEditorHandlers', () => {
  describe('handleAddChild', () => {
    it('should select the newly added child item after creation', async () => {
      // Arrange
      const { ctx, createItemMock, reloadActiveDocumentMock, router } = createMockContext();
      const { handleAddChild, onSelect } = useTreeEditorHandlers(ctx);

      const newItem = { fullStatement: 'New Child Item' };
      const parentItem = { identifier: 'parent-1' };

      // Act
      await handleAddChild(newItem, parentItem);

      // Assert – createItem was called with the parent identifier
      expect(createItemMock).toHaveBeenCalledWith('parent-1', newItem);

      // Assert – reloadActiveDocument was awaited
      expect(reloadActiveDocumentMock).toHaveBeenCalled();

      // Assert – router.push was called with the new item identifier (URL updated)
      expect(router.push).toHaveBeenCalledWith('/doc-1/new-item-id');
    });

    it('should not select anything if createItem returns no identifier', async () => {
      // Arrange
      const { ctx, router } = createMockContext({
        currentDocumentStore: {
          createItem: vi.fn().mockResolvedValue({}), // no identifier
          reloadActiveDocument: vi.fn().mockResolvedValue(undefined),
        },
      });
      const { handleAddChild } = useTreeEditorHandlers(ctx);

      // Act
      await handleAddChild({ fullStatement: 'Test' }, { identifier: 'parent-1' });

      // Assert – router.push should NOT have been called
      expect(router.push).not.toHaveBeenCalled();
    });

    it('should not throw when parentItem has no identifier', async () => {
      const { ctx, createItemMock } = createMockContext();
      const { handleAddChild } = useTreeEditorHandlers(ctx);

      // Act – should bail out early, no error thrown
      await handleAddChild({ fullStatement: 'Test' }, null);

      expect(createItemMock).not.toHaveBeenCalled();
    });
  });

  describe('handleAddRootItem', () => {
    it('should select the newly added root item after creation', async () => {
      // Arrange
      const { ctx, createItemMock, reloadActiveDocumentMock, router } = createMockContext();
      const { handleAddRootItem } = useTreeEditorHandlers(ctx);

      const newItem = { fullStatement: 'New Root Item' };

      // Act
      await handleAddRootItem(newItem);

      // Assert – createItem was called with the document id
      expect(createItemMock).toHaveBeenCalledWith('doc-1', newItem);

      // Assert – reloadActiveDocument was awaited
      expect(reloadActiveDocumentMock).toHaveBeenCalled();

      // Assert – router.push was called with the new item identifier (URL updated)
      expect(router.push).toHaveBeenCalledWith('/doc-1/new-item-id');
    });

    it('should not select anything if createItem returns no identifier', async () => {
      // Arrange
      const { ctx, router } = createMockContext({
        currentDocumentStore: {
          createItem: vi.fn().mockResolvedValue({}), // no identifier
          reloadActiveDocument: vi.fn().mockResolvedValue(undefined),
        },
      });
      const { handleAddRootItem } = useTreeEditorHandlers(ctx);

      // Act
      await handleAddRootItem({ fullStatement: 'Test' });

      // Assert – router.push should NOT have been called
      expect(router.push).not.toHaveBeenCalled();
    });

    it('should not throw when currentDoc is null', async () => {
      const { ctx, createItemMock } = createMockContext();
      ctx.currentDoc = ref(null);
      const { handleAddRootItem } = useTreeEditorHandlers(ctx);

      // Act – should bail out early, no error thrown
      await handleAddRootItem({ fullStatement: 'Test' });

      expect(createItemMock).not.toHaveBeenCalled();
    });
  });
});
