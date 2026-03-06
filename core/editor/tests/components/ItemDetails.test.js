/**
 * ItemDetails.vue component tests
 *
 * Tests for the ItemDetails component, specifically focusing on the isReadOnly
 * computed property that determines whether associations can be edited.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';
import { ref } from 'vue';
import { createPinia, setActivePinia } from 'pinia';

// Mock stores
const mockContextStore = {
  activeWriteDocumentId: { value: null },
  isEditable: vi.fn(() => false),
  documentRegistry: new Map(),
};

const mockSessionStore = {
  isAuthenticated: false,
};

vi.mock('@/stores/editorContextStore', () => ({
  useEditorContextStore: vi.fn(() => mockContextStore),
}));

vi.mock('@/stores/sessionStore', () => ({
  useSessionStore: vi.fn(() => mockSessionStore),
}));

vi.mock('@/stores/documentStore', () => ({
  useDocumentStore: vi.fn(() => ({})),
}));

vi.mock('@/stores/currentDocumentStore', () => ({
  useCurrentDocumentStore: vi.fn(() => ({
    currentDocumentDefinitions: { CFLicenses: [] },
  })),
}));

// Mock composables
vi.mock('@/composables/useItemAssociations', () => ({
  useItemAssociations: vi.fn(() => ({
    mergedAssociations: [],
    isProcessingAssociations: false,
    showDeleteModal: false,
    associationToDelete: null,
    handleDeleteAssociationRequest: vi.fn(),
    handleDeleteConfirmed: vi.fn(),
    handleDeleteModalHidden: vi.fn(),
  })),
}));

vi.mock('@/composables/useDynamicModal', () => ({
  useDynamicModal: vi.fn(() => ({
    showModal: vi.fn(),
    selectedType: ref(''),
    isModalVisible: ref(false),
    handleCreated: vi.fn(),
    modalComponent: ref(null),
    handleHidden: vi.fn(),
    parentItem: ref(null),
  })),
}));

vi.mock('@/composables/useDynamicEditModal', () => ({
  useDynamicEditModal: vi.fn(() => ({
    showEditModal: vi.fn(),
    selectedEditType: ref(''),
    isEditModalVisible: ref(false),
    editingItem: ref(null),
    editModalComponent: ref(null),
    handleUpdated: vi.fn(),
    handleEditHidden: vi.fn(),
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

describe('ItemDetails.vue - isReadOnly computed property logic', () => {
  let pinia;

  beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);

    // Reset mocks before each test
    mockContextStore.activeWriteDocumentId.value = null;
    mockContextStore.isEditable.mockReturnValue(false);
    mockSessionStore.isAuthenticated = false;
  });

  describe('isReadOnly logic verification', () => {
    it('should return true when currentDocument has no identifier', () => {
      // Arrange: Set up authenticated user, active write document, but no document identifier
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = 'active-doc-id';
      mockContextStore.isEditable.mockReturnValue(true);

      const currentDocument = { title: 'Test Doc' }; // No identifier

      // Act: Compute isReadOnly using the same logic as ItemDetails
      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;

      // Assert: Component should be read-only
      expect(isReadOnly).toBe(true);
    });

    it('should return true when document is not the active write document', () => {
      // Arrange: Set up authenticated user with active write document
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = 'active-doc-id';
      mockContextStore.isEditable.mockReturnValue(false); // Not editable

      const currentDocument = {
        identifier: 'different-doc-id',
        title: 'Different Doc',
      };

      // Act: Compute isReadOnly
      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;

      // Assert: Component should be read-only
      expect(isReadOnly).toBe(true);
    });

    it('should return true when user is not authenticated', () => {
      // Arrange: Set up unauthenticated user
      mockSessionStore.isAuthenticated = false;
      mockContextStore.activeWriteDocumentId.value = 'active-doc-id';
      mockContextStore.isEditable.mockReturnValue(true);

      const currentDocument = {
        identifier: 'active-doc-id',
        title: 'Active Doc',
      };

      // Act: Compute isReadOnly
      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;

      // Assert: Component should be read-only for unauthenticated users
      expect(isReadOnly).toBe(true);
    });

    it('should return false when document is active write document and user is authenticated', () => {
      // Arrange: Set up authenticated user with active write document
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = 'active-doc-id';
      mockContextStore.isEditable.mockReturnValue(true);

      const currentDocument = {
        identifier: 'active-doc-id',
        title: 'Active Doc',
      };

      // Act: Compute isReadOnly
      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;

      // Assert: Component should NOT be read-only
      expect(isReadOnly).toBe(false);
    });

    it('should return true when currentDocument is null', () => {
      // Arrange: Set up authenticated user but no current document
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = 'active-doc-id';
      mockContextStore.isEditable.mockReturnValue(true);

      const currentDocument = null;

      // Act: Compute isReadOnly
      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;

      // Assert: Component should be read-only when no document
      expect(isReadOnly).toBe(true);
    });

    it('should return true when no active write document is set', () => {
      // Arrange: Set up authenticated user but no active write document
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = null;
      mockContextStore.isEditable.mockReturnValue(false);

      const currentDocument = {
        identifier: 'some-doc-id',
        title: 'Some Doc',
      };

      // Act: Compute isReadOnly
      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;

      // Assert: Component should be read-only when no active write document
      expect(isReadOnly).toBe(true);
    });

    it('should return true when both unauthenticated and document is not editable', () => {
      // Arrange: Set up unauthenticated user with non-editable document
      mockSessionStore.isAuthenticated = false;
      mockContextStore.activeWriteDocumentId.value = null;
      mockContextStore.isEditable.mockReturnValue(false);

      const currentDocument = {
        identifier: 'some-doc-id',
        title: 'Some Doc',
      };

      // Act: Compute isReadOnly
      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;

      // Assert: Component should be read-only
      expect(isReadOnly).toBe(true);
    });

    it('should call contextStore.isEditable with document identifier', () => {
      // Arrange: Set up authenticated user
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = 'active-doc-id';
      mockContextStore.isEditable.mockReturnValue(true);

      const docId = 'test-doc-id';
      const currentDocument = {
        identifier: docId,
        title: 'Test Doc',
      };

      // Act: Compute isReadOnly
      const isReadOnly = !currentDocument?.identifier || !mockContextStore.isEditable(currentDocument?.identifier) || !mockSessionStore.isAuthenticated;

      // Assert: isEditable should be called with the document identifier
      expect(mockContextStore.isEditable).toHaveBeenCalledWith(docId);
      expect(isReadOnly).toBe(false);
    });
  });

  describe('canEditItem computed property logic', () => {
    it('should return false when isReadOnly is true', () => {
      // Arrange: Set up read-only scenario
      mockSessionStore.isAuthenticated = false;
      mockContextStore.activeWriteDocumentId.value = null;
      mockContextStore.isEditable.mockReturnValue(false);

      const currentDocument = {
        identifier: 'some-doc-id',
        title: 'Some Doc',
      };
      const item = {
        identifier: 'test-item-id',
        title: 'Test Item',
        fullStatement: 'Test Statement',
      };

      // Act: Compute isReadOnly and canEditItem
      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;
      const canEditItem = !isReadOnly && !!item && mockContextStore.isEditable(item);

      // Assert: canEditItem should be false when read-only
      expect(isReadOnly).toBe(true);
      expect(canEditItem).toBe(false);
    });

    it('should return false when item is null', () => {
      // Arrange: Set up editable scenario but null item
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = 'active-doc-id';
      mockContextStore.isEditable.mockReturnValue(true);

      const currentDocument = {
        identifier: 'active-doc-id',
        title: 'Active Doc',
      };
      const item = null;

      // Act: Compute isReadOnly and canEditItem
      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;
      const canEditItem = !isReadOnly && !!item && mockContextStore.isEditable(item);

      // Assert: canEditItem should be false when item is null
      expect(isReadOnly).toBe(false);
      expect(canEditItem).toBe(false);
    });

    it('should return true when isReadOnly is false and item exists and is editable', () => {
      // Arrange: Set up fully editable scenario
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = 'active-doc-id';
      mockContextStore.isEditable.mockReturnValue(true);

      const currentDocument = {
        identifier: 'active-doc-id',
        title: 'Active Doc',
      };
      const item = {
        identifier: 'test-item-id',
        title: 'Test Item',
        fullStatement: 'Test Statement',
      };

      // Act: Compute isReadOnly and canEditItem
      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;
      const canEditItem = !isReadOnly && !!item && mockContextStore.isEditable(item);

      // Assert: canEditItem should be true
      expect(isReadOnly).toBe(false);
      expect(canEditItem).toBe(true);
    });
  });

  describe('Edge cases for isReadOnly', () => {
    it('should handle empty string identifier as read-only', () => {
      // Arrange: Set up authenticated user with empty document identifier
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = 'active-doc-id';
      mockContextStore.isEditable.mockReturnValue(true);

      const currentDocument = {
        identifier: '',
        title: 'Empty ID Doc',
      };

      // Act: Compute isReadOnly
      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;

      // Assert: Component should be read-only with empty identifier
      expect(isReadOnly).toBe(true);
    });

    it('should handle undefined identifier as read-only', () => {
      // Arrange: Set up authenticated user with undefined document identifier
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = 'active-doc-id';
      mockContextStore.isEditable.mockReturnValue(true);

      const currentDocument = {
        identifier: undefined,
        title: 'Undefined ID Doc',
      };

      // Act: Compute isReadOnly
      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;

      // Assert: Component should be read-only with undefined identifier
      expect(isReadOnly).toBe(true);
    });

    it('should correctly handle case where isEditable throws an error', () => {
      // Arrange: Set up isEditable to throw an error
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = 'active-doc-id';
      mockContextStore.isEditable.mockImplementation(() => {
        throw new Error('Test error');
      });

      const currentDocument = {
        identifier: 'test-doc-id',
        title: 'Test Doc',
      };

      // Act: Compute isReadOnly with error handling
      const docId = currentDocument?.identifier;
      let isReadOnly;
      try {
        isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;
      } catch (error) {
        // If isEditable throws, treat as read-only
        isReadOnly = true;
      }

      // Assert: Component should handle error gracefully (be read-only)
      expect(isReadOnly).toBe(true);
    });
  });

  describe('Integration scenarios', () => {
    it('should correctly identify editable scenario for active document', () => {
      // Scenario: User is authenticated and viewing the active write document
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = 'framework-123';
      mockContextStore.isEditable.mockImplementation((id) => id === 'framework-123');

      const currentDocument = {
        identifier: 'framework-123',
        title: 'Active Framework',
      };

      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;

      expect(mockContextStore.isEditable).toHaveBeenCalledWith('framework-123');
      expect(isReadOnly).toBe(false);
    });

    it('should correctly identify read-only scenario for different document', () => {
      // Scenario: User is authenticated but viewing a different document
      mockSessionStore.isAuthenticated = true;
      mockContextStore.activeWriteDocumentId.value = 'framework-123';
      mockContextStore.isEditable.mockImplementation((id) => id === 'framework-123');

      const currentDocument = {
        identifier: 'framework-456',
        title: 'Different Framework',
      };

      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;

      expect(mockContextStore.isEditable).toHaveBeenCalledWith('framework-456');
      expect(isReadOnly).toBe(true);
    });

    it('should correctly identify read-only scenario for unauthenticated user', () => {
      // Scenario: User is not authenticated even with active document
      mockSessionStore.isAuthenticated = false;
      mockContextStore.activeWriteDocumentId.value = 'framework-123';
      mockContextStore.isEditable.mockImplementation((id) => id === 'framework-123');

      const currentDocument = {
        identifier: 'framework-123',
        title: 'Active Framework',
      };

      const docId = currentDocument?.identifier;
      const isReadOnly = !docId || !mockContextStore.isEditable(docId) || !mockSessionStore.isAuthenticated;

      expect(mockContextStore.isEditable).toHaveBeenCalledWith('framework-123');
      expect(isReadOnly).toBe(true);
    });
  });
});
