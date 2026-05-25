/**
 * useSideDocument Composable
 *
 * Handles side document loading and management for the EnhancedDocumentTreeEditor.
 * Used for the External Document mode where a second document
 * is displayed alongside the main document.
 * Uses API-first architecture: fetchLightweightTree returns a pre-built tree.
 */
import { ref, computed, nextTick } from 'vue';
import { useDocumentStore } from '../stores/documentStore';
import { useEditorContextStore } from '../stores/editorContextStore';
import { logger } from '../utils/logger.js';

/**
 * @param {Object} options - Configuration options
 * @param {Function} options.onDocumentLoaded - Callback function when a side document is loaded. Called with (sideDocument, treeResponse) parameters.
 * @returns {Object} Side document state and methods
 */
export function useSideDocument(options = {}) {
  const { onDocumentLoaded } = options;

  const documentStore = useDocumentStore();
  const editorContextStore = useEditorContextStore();

  const sideDocument = ref(null);
  const sideSelectedId = ref(null);

  // Generation counter to prevent stale async fetches from overwriting
  // the result of a more recent fetch (race condition guard).
  let fetchGeneration = 0;

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

    const thisGeneration = ++fetchGeneration;

    documentStore.clearSideDocError();
    sideDocument.value = null;
    documentStore.loadingSideDocument = true;

    try {
      const treeResponse = await documentStore.fetchLightweightTree(documentId);

      // Only apply results if this is still the latest fetch
      if (thisGeneration !== fetchGeneration) {
        return null;
      }

      const doc = treeResponse.document;

      sideDocument.value = {
        id: doc.identifier,
        title: doc.title || 'Untitled',
        items: treeResponse.tree || []
      };

      await nextTick();
      await new Promise(resolve => requestAnimationFrame(resolve));
      await new Promise(resolve => requestAnimationFrame(resolve));
      await new Promise(resolve => setTimeout(resolve, 50));

      if (onDocumentLoaded) {
        onDocumentLoaded(sideDocument.value, treeResponse);
      }

      return sideDocument.value;
    } catch (error) {
      // Only handle error if this is still the latest fetch
      if (thisGeneration !== fetchGeneration) {
        return null;
      }

      logger.error('[onSideDocumentSelect] Error loading side document:', error);
      sideDocument.value = null;
      return null;
    } finally {
      // Only reset loading state if this is still the latest fetch
      if (thisGeneration === fetchGeneration) {
        documentStore.resetLoadingSideDocument();
      }
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
   * @param {string} mode - The mode to restore selection for ('externalDocument' or 'treeView')
   * @returns {Promise<Object|null>} The restored side document or null
   */
  async function restoreSideDocumentFromState(mode) {
    const selection = editorContextStore.getFrameworkSelection(mode);

    if (!selection?.documentId) {
      return null;
    }

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
    sideDocument,
    sideSelectedId,
    loadingSideDoc,
    sideDocError,

    onSideDocumentSelect,
    onSideSelect,
    clearSideDocument,
    setSideDocument,
    restoreSideDocumentFromState
  };
}
