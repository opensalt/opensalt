/**
 * useDocumentLoader Composable
 *
 * Handles document fetching and initialization logic for EnhancedDocumentTreeEditor.
 * Extracts document loading, transformation, and management concerns.
 */
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useDocumentStore } from '../stores/documentStore';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { useFilterStore } from '../stores/filterStore';
import { useRelatedFrameworksQueue } from './useRelatedFrameworksQueue';
import { localFrameworkDb } from '../services/localFrameworkDb.js';
import { logger } from '../utils/logger.js';


// Log when this composable is instantiated
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

  // Loading and error states
  const loading = computed(() => documentStore.loading);
  const error = computed(() => documentStore.error);

  // Available documents list
  const availableDocuments = computed(() => documentStore.documents);

  function isCurrentDocumentLoaded(documentId) {
    if (!documentId) return false;
    const currentId =
      currentDocumentStore.currentDocument?.identifier ||
      currentDocumentStore.currentDocument?.id ||
      null;

    if (currentId !== documentId) return false;

    return Array.isArray(currentDocumentStore.currentDocument?.items);
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
   * Transform CASE document data into format expected by application
   * @param {Object} docData - The raw document data from the API
   * @returns {Object} The transformed document object
   */
  async function transformDocumentData(docData, requestedDocumentId = null) {
    const cfDoc = docData.CFDocument || {};
    let treeSource = docData;

    if (requestedDocumentId || cfDoc.identifier) {
      try {
        const dbPackage = await localFrameworkDb.getPackage(requestedDocumentId || cfDoc.identifier);
        if (dbPackage?.CFItems && dbPackage?.CFAssociations) {
          treeSource = {
            ...docData,
            CFItems: dbPackage.CFItems,
            CFAssociations: dbPackage.CFAssociations
          };
        }
      } catch (error) {
        logger.warn('[useDocumentLoader] Failed to use DB-backed package for tree read:', error);
      }
    }

    const items = currentDocumentStore.transformCASEItems(
      treeSource.CFItems || [],
      treeSource.CFAssociations || [],
      cfDoc.identifier
    );

    return {
      id: cfDoc.identifier,
      identifier: cfDoc.identifier,
      uri: cfDoc.uri || '',
      title: cfDoc.title || 'Untitled',
      description: cfDoc.description || null,
      creator: cfDoc.creator || '',
      subject: cfDoc.subject || null,
      subjectURI: cfDoc.subjectURI || [],
      status: cfDoc.adoptionStatus || 'Draft',
      statusStartDate: cfDoc.statusStartDate || null,
      statusEndDate: cfDoc.statusEndDate || null,
      lastModified: cfDoc.lastChangeDateTime || '',
      language: cfDoc.language || null,
      version: cfDoc.version || null,
      officialSourceURL: cfDoc.officialSourceURL || null,
      publisher: cfDoc.publisher || null,
      licenseURI: cfDoc.licenseURI || null,
      notes: cfDoc.notes || null,
      frameworkType: cfDoc.frameworkType || null,
      caseVersion: cfDoc.caseVersion || null,
      extensions: cfDoc.extensions || null,
      CFPackageURI: cfDoc.CFPackageURI || null,
      items: items
    };
  }

  /**
   * Load a document by its ID
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

    const docData = await documentStore.fetchDocument(documentId);
    const transformedDoc = await transformDocumentData(docData, documentId);

    const definitions = docData.CFDefinitions || {};
    const associationGroupings = definitions.CFAssociationGroupings || docData.CFAssociationGroupings || [];

    currentDocumentStore.selectDocument(
      transformedDoc,
      associationGroupings,
      docData.CFAssociations || [],
      definitions
    );

    filterStore.syncSelectedAssociationGroup({
      frameworkId: transformedDoc.identifier || documentId,
      associations: docData.CFAssociations || [],
      realGroupIds: associationGroupings
        .map(group => group.identifier || group.uri)
        .filter(Boolean)
    });

    if (onDocumentLoaded) {
      onDocumentLoaded(transformedDoc, docData);
    }

    await queueRelatedDocuments(documentId);

    return transformedDoc;
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

    // Clear any previous error
    documentStore.clearSideDocError();

    try {
      const { data, finalUrl } = await documentStore.loadExternalDocument(url);

      // Transform and set side document
      const cfDoc = data.CFDocument || {};
      // Use a unique ID for external docs if identifier is missing or clashes
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
    // State
    loading,
    error,
    availableDocuments,

    // Methods
    loadDocument,
    onDocumentChanged,
    onExternalDocumentRequested,
    onExternalDocumentUrlLoaded,
    initializeDocument,
    transformDocumentData
  };
}
