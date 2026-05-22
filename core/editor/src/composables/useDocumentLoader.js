/**
 * useDocumentLoader Composable
 *
 * Handles document fetching and initialization logic for EnhancedDocumentTreeEditor.
 * Extracts document loading and management concerns.
 * Uses API-first architecture: fetchTree returns a pre-built tree.
 */
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useDocumentStore } from '../stores/documentStore';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { useFilterStore } from '../stores/filterStore';
import { useRelatedFrameworksQueue } from './useRelatedFrameworksQueue';
import { logger } from '../utils/logger.js';


logger.debug('[useDocumentLoader] Composable instantiated');

/**
 * @param {Object} options - Configuration options
 * @param {import('vue').Ref} options.sideDocument - Reference to side document
 * @param {Function} options.onDocumentLoaded - Callback when a document is loaded
 * @returns {Object} Document loader state and methods
 */
export function useDocumentLoader(options = {}) {
  const {
    sideDocument,
    onDocumentLoaded
  } = options;

  const route = useRoute();
  const documentStore = useDocumentStore();
  const currentDocumentStore = useCurrentDocumentStore();
  const filterStore = useFilterStore();
  const relatedFrameworksQueue = useRelatedFrameworksQueue();

  const loading = computed(() => documentStore.loading);
  const error = computed(() => documentStore.error);

  const availableDocuments = computed(() => documentStore.documents);

  function isCurrentDocumentLoaded(documentId) {
    if (!documentId) return false;
    const currentId =
      currentDocumentStore.currentDocument?.identifier ||
      currentDocumentStore.currentDocument?.id ||
      null;

    if (currentId !== documentId) return false;

    return Array.isArray(currentDocumentStore.currentDocumentTree);
  }

  async function queueRelatedDocuments(documentId) {
    if (!documentId) return [];

    logger.debug('[useDocumentLoader] About to call fetchAndQueueRelatedDocuments for:', documentId);
    const relatedDocs = await relatedFrameworksQueue.fetchAndQueueRelatedDocuments(documentId);
    logger.debug('[useDocumentLoader] fetchAndQueueRelatedDocuments completed');

    relatedFrameworksQueue.startQueue();
    logger.debug('[useDocumentLoader] Queue started');

    return relatedDocs;
  }

  /**
   * Load a document by its ID using the API tree endpoint.
   * @param {string} documentId - The document identifier
   * @returns {Promise<Object>} The loaded document data
   */
  async function loadDocument(documentId) {
    if (!documentId) return null;

    if (isCurrentDocumentLoaded(documentId)) {
      await queueRelatedDocuments(documentId);
      return currentDocumentStore.currentDocument;
    }

    logger.debug('[useDocumentLoader] loadDocument called with documentId:', documentId);

    const treeResponse = await documentStore.fetchTree(documentId);

    currentDocumentStore.selectDocument(treeResponse);

    const definitions = treeResponse.definitions || {};
    const associationGroupings = definitions.CFAssociationGroupings || [];

    filterStore.syncSelectedAssociationGroup({
      frameworkId: treeResponse.document.identifier || documentId,
      definedGroupIds: associationGroupings
        .map(group => group.identifier || group.uri)
        .filter(Boolean),
      treeNodes: treeResponse.tree || []
    });

    if (onDocumentLoaded) {
      onDocumentLoaded(treeResponse.document, treeResponse);
    }

    await queueRelatedDocuments(documentId);

    return treeResponse.document;
  }

  /**
   * Handle document change event from DocumentSelector
   * @param {Object} params - Event parameters
   * @param {string} params.documentId - The new document ID
   * @returns {Promise<void>}
   */
  async function onDocumentChanged({ documentId }) {
    try {
      if (documentId) {
        await loadDocument(documentId);
      }
    } catch (error) {
      logger.error('Error loading document:', error);
    }
  }

  /**
   * Handle external document request (show modal)
   * @returns {boolean} Always returns true to indicate modal should be shown
   */
  function onExternalDocumentRequested() {
    return true;
  }

  /**
   * Handle loading an external document from URL
   * @param {string} url - The URL of external document
   * @returns {Promise<Object|null>} The loaded side document or null on error
   */
  async function onExternalDocumentUrlLoaded(url) {
    if (!url) return null;

    documentStore.clearSideDocError();

    try {
      const { data, finalUrl } = await documentStore.loadExternalDocument(url);

      const cfDoc = data.CFDocument || {};
      const externalId = cfDoc.identifier || 'external-' + Date.now();

      const items = currentDocumentStore.transformCASEItems(
        data.CFItems || [],
        data.CFAssociations || [],
        externalId
      );

      const sideDoc = {
        id: externalId,
        uri: cfDoc.uri || finalUrl,
        title: cfDoc.title || 'External Document',
        description: cfDoc.description || null,
        items: items
      };

      if (sideDocument) {
        sideDocument.value = sideDoc;
      }

      return sideDoc;
    } catch (error) {
      logger.error('Error loading external document:', error);
      if (sideDocument) {
        sideDocument.value = null;
      }
      return null;
    }
  }

  /**
   * Initialize document based on route or load default
   * Should be called in onMounted
   */
  async function initializeDocument() {
    try {
      const frameworkId = route.params.frameworkId;

      if (frameworkId) {
        if (isCurrentDocumentLoaded(frameworkId)) {
          await queueRelatedDocuments(frameworkId);
          return;
        }
        await loadDocument(frameworkId);
      } else if (!currentDocumentStore.currentDocument || Object.keys(currentDocumentStore.currentDocument).length === 0) {
        await documentStore.fetchDocuments();

        if (documentStore.documents.length > 0) {
          const firstDoc = documentStore.documents[0];
          await loadDocument(firstDoc.id);
        }
      }
    } catch (e) {
      logger.error('Error initializing data:', e);
    }
  }

  return {
    loading,
    error,
    availableDocuments,

    loadDocument,
    onDocumentChanged,
    onExternalDocumentRequested,
    onExternalDocumentUrlLoaded,
    initializeDocument
  };
}
