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
import { useRelatedFrameworksQueue } from './useRelatedFrameworksQueue';


// Log when this composable is instantiated
console.log('[useDocumentLoader] Composable instantiated');

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
  const relatedFrameworksQueue = useRelatedFrameworksQueue();

  // Loading and error states
  const loading = computed(() => documentStore.loading);
  const error = computed(() => documentStore.error);

  // Available documents list
  const availableDocuments = computed(() => documentStore.documents);

  /**
   * Transform CASE document data into format expected by application
   * @param {Object} docData - The raw document data from the API
   * @returns {Object} The transformed document object
   */
  function transformDocumentData(docData) {
    const cfDoc = docData.CFDocument || {};
    const items = currentDocumentStore.transformCASEItems(
      docData.CFItems || [],
      docData.CFAssociations || [],
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

    console.log('[useDocumentLoader] loadDocument called with documentId:', documentId);

    const docData = await documentStore.fetchDocument(documentId);
    const transformedDoc = transformDocumentData(docData);

    const definitions = docData.CFDefinitions || {};
    const associationGroupings = definitions.CFAssociationGroupings || docData.CFAssociationGroupings || [];

    currentDocumentStore.selectDocument(
      transformedDoc,
      associationGroupings,
      docData.CFAssociations || [],
      definitions
    );

    if (onDocumentLoaded) {
      onDocumentLoaded(transformedDoc, docData);
    }

    console.log('[useDocumentLoader] About to call fetchAndQueueRelatedDocuments for:', documentId);
    // Fetch related documents and add to queue
    await relatedFrameworksQueue.fetchAndQueueRelatedDocuments(documentId);
    console.log('[useDocumentLoader] fetchAndQueueRelatedDocuments completed');

    // Start queue processing
    relatedFrameworksQueue.startQueue();
    console.log('[useDocumentLoader] Queue started');

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
      console.error('Error loading document:', error);
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
      console.error('Error loading external document:', error);
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
        await loadDocument(frameworkId);
      } else if (!currentDocumentStore.currentDocument || Object.keys(currentDocumentStore.currentDocument).length === 0) {
        await documentStore.fetchDocuments();

        if (documentStore.documents.length > 0) {
          const firstDoc = documentStore.documents[0];
          await loadDocument(firstDoc.id);
        }
      }
    } catch (e) {
      console.error('Error initializing data:', e);
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
