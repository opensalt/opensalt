/**
 * useSideDocument Composable
 *
 * Handles side document loading and management for the EnhancedDocumentTreeEditor.
 * Used for Copy Items and Create Associations modes where a second document
 * is displayed alongside the main document.
 */
import { ref, computed, nextTick } from 'vue';
import { useDocumentStore } from '../stores/documentStore';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { useEditorContextStore } from '../stores/editorContextStore';

/**
 * @param {Object} options - Configuration options
 * @param {Function} options.onDocumentLoaded - Callback function when a side document is loaded. Called with (sideDocument, docData) parameters.
 * @returns {Object} Side document state and methods
 */
export function useSideDocument(options = {}) {
  const { onDocumentLoaded } = options;

  const documentStore = useDocumentStore();
  const currentDocumentStore = useCurrentDocumentStore();
  const editorContextStore = useEditorContextStore();

  // Side document state
  const sideDocument = ref(null);
  const sideSelectedId = ref(null);

  // Loading and error states from store
  const loadingSideDoc = computed(() => documentStore.loadingSideDocument);
  const sideDocError = computed(() => documentStore.sideDocError);

  /**
   * Handle side document selection from dropdown
   * @param {string} documentId - The document ID to load
   * @returns {Promise<Object|null>} The loaded side document or null on error
   */
  async function onSideDocumentSelect(documentId) {
    if (!documentId) {
      sideDocument.value = null;
      documentStore.clearSideDocError();
      return null;
    }

    // Clear any previous error and document IMMEDIATELY
    // This ensures the spinner shows instead of stale content
    documentStore.clearSideDocError();
    sideDocument.value = null;

    try {
      // Use fetchSideDocument which sets loading state
      // Note: loadingSideDocument is set to true at the start of fetchSideDocument
      // and we reset it here after all processing is complete
      const docData = await documentStore.fetchSideDocument(documentId);

      const cfDoc = docData.CFDocument || {};

      const items = currentDocumentStore.transformCASEItems(
        docData.CFItems || [],
        docData.CFAssociations || [],
        cfDoc.identifier
      );

      sideDocument.value = {
        id: cfDoc.identifier,
        title: cfDoc.title || 'Untitled',
        items: items
      };

      // CRITICAL: Wait for the browser to paint the tree before resetting loading state.
      // For large documents, the tree rendering takes significant time after nextTick() completes.
      // We use requestAnimationFrame to wait for the browser to actually paint the tree.
      // Double requestAnimationFrame ensures we're synchronized with the browser's paint cycle:
      // - First rAF schedules callback before next repaint
      // - Second rAF ensures we're after the paint has completed
      await nextTick(); // Wait for Vue's virtual DOM to update
      await new Promise(resolve => requestAnimationFrame(resolve)); // Wait for next frame
      await new Promise(resolve => requestAnimationFrame(resolve)); // Wait for paint to complete
      // Additional small delay to ensure tree is fully rendered and visible
      await new Promise(resolve => setTimeout(resolve, 50));

      if (onDocumentLoaded) {
        onDocumentLoaded(sideDocument.value, docData);
      }

      return sideDocument.value;
    } catch (error) {
      console.error('[onSideDocumentSelect] Error loading side document:', error);
      sideDocument.value = null;
      return null;
    } finally {
      // Reset loading state after tree is fully rendered and visible
      documentStore.resetLoadingSideDocument();
    }
  }

  /**
   * Handle item selection within the side document tree
   * @param {string} id - The selected item ID
   */
  function onSideSelect(id) {
    sideSelectedId.value = id;
  }

  /**
   * Clear the side document and reset state
   */
  function clearSideDocument() {
    sideDocument.value = null;
    sideSelectedId.value = null;
    documentStore.clearSideDocError();
  }

  /**
   * Restore side document from centralized framework selection state
   * @param {string} mode - The mode to restore selection for ('copyItems' or 'createAssociations')
   * @returns {Promise<Object|null>} The restored side document or null
   */
  async function restoreSideDocumentFromState(mode) {
    const selection = editorContextStore.getFrameworkSelection(mode);

    if (!selection?.documentId) {
      return null;
    }

    // Validate that the framework still exists
    const isValid = await editorContextStore.validateFrameworkSelection(mode);

    if (!isValid) {
      return null;
    }

    const result = await onSideDocumentSelect(selection.documentId);
    return result;
  }

  /**
   * Set side document directly (e.g., from external document loading)
   * @param {Object} doc - The document object to set
   */
  function setSideDocument(doc) {
    sideDocument.value = doc;
  }

  return {
    // State
    sideDocument,
    sideSelectedId,
    loadingSideDoc,
    sideDocError,

    // Methods
    onSideDocumentSelect,
    onSideSelect,
    clearSideDocument,
    setSideDocument,
    restoreSideDocumentFromState
  };
}
