import { defineStore } from 'pinia';
import { logger } from '../utils/logger.js';
import { ref, Ref, nextTick } from 'vue';
import { api } from '../services/api.js';
import { frameworkCacheService } from '../services/frameworkCacheService.js';
import type {
  CFDocument,
  CFPackage,
  CaseDocumentListResponse,
  UUID
} from '../types/case';

// API response types (the api.get returns any type, so we define expected structure)
interface ApiDocumentListResponse {
  data: CFDocument[];
  pagination?: {
    hasNextPage: boolean;
    nextCursor?: string;
  };
}

interface ApiPackageResponse {
  CFDocument: CFPackage['CFDocument'];
  CFPackageURI?: {
    uri: string;
  };
}

export const useDocumentStore = defineStore('documents', () => {
  // State
  const documents = ref<CFDocument[]>([]);
  const loading = ref<boolean>(false);
  const error = ref<string | null>(null);

  // Side document loading state (for panel-specific loading in Copy Items / Create Associations modes)
  const loadingSideDocument = ref<boolean>(false);
  const sideDocError = ref<string | null>(null);

  // NEW: Viewed document loading state (for dual framework edit/view separation)
  const loadingViewedDocument = ref<boolean>(false);
  const viewedDocError = ref<string | null>(null);

  // Request deduplication cache
  const pendingRequests = new Map<UUID, Promise<CFPackage>>();
  const documentCache = new Map<UUID, CFPackage>(); // Cache fetched documents

  // Documents metadata from /api/v1/documents endpoint (includes lastChangeDateTime)
  const documentsMetadata = new Map<UUID, string>(); // identifier -> lastChangeDateTime

  /**
   * Store documents metadata from /api/v1/documents endpoint
   * This allows us to use pre-loaded metadata for cache validation
   * instead of making individual metadata API calls
   * @param {CFDocument[]} documentsArray - Array of documents from /api/v1/documents
   */
  function setDocumentsMetadata(documentsArray: CFDocument[]): void {
    documentsMetadata.clear();
    for (const doc of documentsArray) {
      if (doc.identifier && doc.lastChangeDateTime) {
        documentsMetadata.set(doc.identifier, doc.lastChangeDateTime);
      }
    }
    logger.debug('Stored metadata for', documentsMetadata.size, 'documents');
  }

  // Actions
  async function fetchDocuments(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const endpoint = '/api/v1/documents';
      const limit = 1000; // Adjust as needed
      let allDocuments: CFDocument[] = [];
      let cursor: string | null = null;
      let hasNextPage = true;

      while (hasNextPage) {
        const params = new URLSearchParams({
          'page[size]': limit.toString(),
        });

        if (cursor) {
          params.append('page[after]', cursor);
        }

        const url = `${endpoint}?${params.toString()}`;
        const data = await api.get(url) as ApiDocumentListResponse;

        if (!data.data || !Array.isArray(data.data)) {
          throw new Error('Invalid response format: expected data array');
        }

        // The API already returns CFDocument objects
        allDocuments = allDocuments.concat(data.data);

        // Check pagination
        if (data.pagination && typeof data.pagination.hasNextPage === 'boolean') {
          hasNextPage = data.pagination.hasNextPage;
          cursor = data.pagination.nextCursor || null;
        } else {
          // If no pagination info, assume no more pages
          hasNextPage = false;
        }
      }

      documents.value = allDocuments;

      // Store document metadata for cache validation
      setDocumentsMetadata(allDocuments);

    } catch (err) {
      error.value = (err as Error).message || 'Failed to fetch documents';
      console.error('Error fetching documents:', err);
      // Keep any previously loaded documents if there was an error
      if (documents.value.length === 0) {
        documents.value = [];
      }
    } finally {
      loading.value = false;
    }
  }

  async function fetchDocument(identifier: UUID): Promise<CFPackage> {

    // Phase 1: Check memory cache first
    if (documentCache.has(identifier)) {
      logger.debug('Memory cache hit for document:', identifier);
      return documentCache.get(identifier)!;
    }

    // Check if request is already pending
    if (pendingRequests.has(identifier)) {
      return pendingRequests.get(identifier)!;
    }

    loading.value = true;
    error.value = null;

    // Type guard to validate CFPackage response
    function isCFPackage(response: unknown): response is CFPackage {
      return (
        typeof response === 'object' &&
        response !== null &&
        'CFDocument' in response
      );
    }

    // Create request promise
    const requestPromise = (async (): Promise<CFPackage> => {
      try {
        // Phase 2: Check persistent cache via IndexedDB
        // Use pre-loaded metadata from /api/v1/documents to get server's lastChangeDateTime
        // This avoids making an extra API call for metadata
        const serverLastChangeDateTime = documentsMetadata.get(identifier);

        if (!serverLastChangeDateTime) {
          logger.debug('Document metadata not found in pre-loaded list, fetching full document:', identifier);
        }

        // Check if we have a valid cached version in IndexedDB
        const cachedFramework = await frameworkCacheService.getValidFramework(
          identifier,
          serverLastChangeDateTime || ''
        ) as CFPackage | null;

        if (cachedFramework) {
          logger.debug('IndexedDB cache hit for document:', identifier);
          // Store in memory cache for faster access next time
          documentCache.set(identifier, cachedFramework);
          loading.value = false;
          return cachedFramework;
        }

        logger.debug('Cache miss for document, fetching from API:', identifier);

        // Phase 3: Fetch full document from API
        const responseData = await api.get(`/ims/case/v1p1/CFPackages/${identifier}`);

        // Validate response structure before type assertion
        if (!isCFPackage(responseData)) {
          throw new Error('Invalid response format: expected CFPackage structure');
        }

        // Safe to assert type after validation
        const data: CFPackage = responseData as CFPackage;

        // Cache result in memory
        documentCache.set(identifier, data);

        // Cache result in IndexedDB for persistence
        try {
          await frameworkCacheService.setFramework(identifier, data);
        } catch (cacheErr) {
          // Log but don't fail if IndexedDB caching fails
          logger.warn('Failed to cache framework in IndexedDB:', cacheErr);
        }

        return data;
      } catch (err) {
        error.value = (err as Error).message || 'Failed to fetch document';
        console.error('Error fetching document:', err);
        throw err;
      } finally {
        loading.value = false;
        pendingRequests.delete(identifier);
      }
    })();

    // Store the pending request
    pendingRequests.set(identifier, requestPromise);

    return requestPromise;
  }

  /**
   * Fetch a document for the side panel (Copy Items / Create Associations modes)
   * This uses a separate loading state so the main page doesn't show a spinner
   */
  async function fetchSideDocument(identifier: UUID): Promise<CFPackage> {
    console.log('[fetchSideDocument] Called for identifier:', identifier);

    // Set loading state FIRST to ensure UI shows spinner immediately
    loadingSideDocument.value = true;
    sideDocError.value = null;
    console.log('[fetchSideDocument] Set loadingSideDocument = true');

    // Wait for Vue to process the loading state change before checking cache
    // This ensures the spinner is shown even for cached documents
    await nextTick();
    console.log('[fetchSideDocument] After nextTick, loadingSideDocument =', loadingSideDocument.value);

    // Check if request is already pending - return the existing promise
    // The pending request will manage the loading state
    if (pendingRequests.has(identifier)) {
      console.log('[fetchSideDocument] Request already pending, returning existing promise');
      return pendingRequests.get(identifier)!;
    }

    // Check cache after Vue has processed the loading state
    if (documentCache.has(identifier)) {
      console.log('[fetchSideDocument] Cache hit! Returning cached document');
      const cachedDoc = documentCache.get(identifier)!;
      loadingSideDocument.value = false;
      return cachedDoc;
    }

    // Type guard to validate CFPackage response
    function isCFPackage(response: unknown): response is CFPackage {
      return (
        typeof response === 'object' &&
        response !== null &&
        'CFDocument' in response
      );
    }

    // Create request promise
    const requestPromise = (async (): Promise<CFPackage> => {
      try {
        const responseData = await api.get(`/ims/case/v1p1/CFPackages/${identifier}`);

        // Validate response structure before type assertion
        if (!isCFPackage(responseData)) {
          throw new Error('Invalid response format: expected CFPackage structure');
        }

        // Safe to assert type after validation
        const data: CFPackage = responseData as CFPackage;

        // Cache result
        documentCache.set(identifier, data);

        return data;
      } catch (err) {
        sideDocError.value = (err as Error).message || 'Failed to fetch side document';
        console.error('Error fetching side document:', err);
        throw err;
      } finally {
        loadingSideDocument.value = false;
        pendingRequests.delete(identifier);
      }
    })();

    // Store the pending request
    pendingRequests.set(identifier, requestPromise);

    return requestPromise;
  }

  /**
   * NEW: Fetch a document for viewing (dual framework edit/view separation)
   * Similar to fetchDocument but uses separate loading state for viewed documents
   * Uses the existing caching mechanism and IndexedDB persistence
   */
  async function fetchViewedDocument(identifier: UUID): Promise<CFPackage> {
    // Check memory cache first
    if (documentCache.has(identifier)) {
      logger.debug('Memory cache hit for viewed document:', identifier);
      return documentCache.get(identifier)!;
    }

    // Check if request is already pending
    if (pendingRequests.has(identifier)) {
      return pendingRequests.get(identifier)!;
    }

    loadingViewedDocument.value = true;
    viewedDocError.value = null;

    // Type guard to validate CFPackage response
    function isCFPackage(response: unknown): response is CFPackage {
      return (
        typeof response === 'object' &&
        response !== null &&
        'CFDocument' in response
      );
    }

    // Create request promise
    const requestPromise = (async (): Promise<CFPackage> => {
      try {
        // Check persistent cache via IndexedDB
        const serverLastChangeDateTime = documentsMetadata.get(identifier);

        const cachedFramework = await frameworkCacheService.getValidFramework(
          identifier,
          serverLastChangeDateTime || ''
        ) as CFPackage | null;

        if (cachedFramework) {
          logger.debug('IndexedDB cache hit for viewed document:', identifier);
          documentCache.set(identifier, cachedFramework);
          return cachedFramework;
        }

        logger.debug('Cache miss for viewed document, fetching from API:', identifier);

        // Fetch from API
        const responseData = await api.get(`/ims/case/v1p1/CFPackages/${identifier}`);

        if (!isCFPackage(responseData)) {
          throw new Error('Invalid response format: expected CFPackage structure');
        }

        const data: CFPackage = responseData as CFPackage;

        // Cache result
        documentCache.set(identifier, data);

        // Cache in IndexedDB for persistence
        try {
          await frameworkCacheService.setFramework(identifier, data);
        } catch (cacheErr) {
          logger.warn('Failed to cache viewed framework in IndexedDB:', cacheErr);
        }

        return data;
      } catch (err) {
        viewedDocError.value = (err as Error).message || 'Failed to fetch viewed document';
        logger.error('Error fetching viewed document:', err);
        throw err;
      } finally {
        loadingViewedDocument.value = false;
        pendingRequests.delete(identifier);
      }
    })();

    // Store the pending request
    pendingRequests.set(identifier, requestPromise);

    return requestPromise;
  }

  /**
   * Extract UUID from a CASE URI
   * The UUID is typically the last segment of the URI path
   */
  function extractUuidFromUri(uri: string): string | null {
    if (!uri) return null;

    try {
      // Try to parse as URL
      const url = new URL(uri);
      // Get the last segment of the pathname
      const segments = url.pathname.split('/').filter(Boolean);
      const lastSegment = segments[segments.length - 1];

      // Check if it looks like a UUID (basic check for hex characters and dashes)
      if (lastSegment && /^[0-9a-fA-F-]{36}$/.test(lastSegment)) {
        return lastSegment;
      }

      // Also check if the second-to-last segment might be an identifier type
      // (e.g., /CFDocuments/uuid or /CFPackages/uuid)
      if (segments.length >= 2) {
        const possibleUuid = segments[segments.length - 1];
        if (/^[0-9a-fA-F-]{36}$/.test(possibleUuid)) {
          return possibleUuid;
        }
      }

      return null;
    } catch {
      // If URL parsing fails, try regex extraction
      const uuidMatch = uri.match(/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})/);
      return uuidMatch ? uuidMatch[1] : null;
    }
  }

  /**
   * Try to fetch a document from local server first, then fall back to external URL
   */
  async function fetchDocumentWithFallback(url: string): Promise<unknown> {
    const uuid = extractUuidFromUri(url);

    // If we have a UUID, try local server first
    if (uuid) {
      try {
        const localUrl = `/ims/case/v1p1/CFDocuments/${uuid}`;
        const localResponse = await api.get(localUrl);
        logger.debug(`Local fetch succeeded for document ${uuid}`);
        return localResponse;
      } catch (localError) {
        // Local fetch failed, continue to try original URL
        logger.debug(`Local fetch failed for document ${uuid}, trying original URL:`, localError);
      }
    }

    // Fall back to the original URL
    return api.get(url);
  }

  /**
   * Try to fetch a package from local server first, then fall back to external URL
   */
  async function fetchPackageWithFallback(url: string): Promise<unknown> {
    const uuid = extractUuidFromUri(url);

    // If we have a UUID, try local server first
    if (uuid) {
      try {
        const localUrl = `/ims/case/v1p1/CFPackages/${uuid}`;
        const localResponse = await api.get(localUrl);
        logger.debug(`Local fetch succeeded for package ${uuid}`);
        return localResponse;
      } catch (localError) {
        // Local fetch failed, continue to try original URL
        logger.debug(`Local fetch failed for package ${uuid}, trying original URL:`, localError);
      }
    }

    // Fall back to the original URL
    return api.get(url);
  }

  async function loadExternalDocument(url: string): Promise<{ data: CFPackage, finalUrl: string }> {
    loading.value = true;
    error.value = null;

    let data: CFPackage | null = null;
    let finalUrl = url;

    // Type guard to validate CFPackage response
    function isCFPackage(response: unknown): response is CFPackage {
      return (
        typeof response === 'object' &&
        response !== null &&
        'CFDocument' in response
      );
    }

    // Type guard to validate ApiPackageResponse (with CFPackageURI)
    function isApiPackageResponse(response: unknown): response is ApiPackageResponse {
      return (
        typeof response === 'object' &&
        response !== null &&
        'CFDocument' in response
      );
    }

    // Type guard to check if response has CFPackageURI
    function hasCFPackageURI(response: unknown): response is { CFPackageURI: { uri: string } } {
      return (
        typeof response === 'object' &&
        response !== null &&
        'CFPackageURI' in response &&
        typeof (response as any).CFPackageURI === 'object' &&
        (response as any).CFPackageURI !== null &&
        typeof (response as any).CFPackageURI.uri === 'string'
      );
    }

    try {
      // Try local server first, then fall back to provided URL
      const initialResponse = await fetchDocumentWithFallback(url);

      if (isCFPackage(initialResponse)) {
        // Response is already a CFPackage
        data = initialResponse as CFPackage;
      } else if (hasCFPackageURI(initialResponse)) {
        // Response only has CFPackageURI, follow redirect with fallback
        finalUrl = initialResponse.CFPackageURI.uri;
        const finalResponse = await fetchPackageWithFallback(finalUrl);
        if (isCFPackage(finalResponse)) {
          data = finalResponse as CFPackage;
        } else {
          throw new Error('Response does not contain CFDocument');
        }
      } else if (isApiPackageResponse(initialResponse)) {
        // Response has CFDocument and possibly CFPackageURI
        const apiResponse = initialResponse as ApiPackageResponse;
        data = apiResponse as unknown as CFPackage;

        // Check if response has CFDocument; if not, check for CFPackageURI
        if (!data.CFDocument) {
          if (apiResponse.CFPackageURI && apiResponse.CFPackageURI.uri) {
            finalUrl = apiResponse.CFPackageURI.uri;
            const finalResponse = await fetchPackageWithFallback(finalUrl);
            if (isCFPackage(finalResponse)) {
              data = finalResponse as CFPackage;
            } else {
              throw new Error('Response does not contain CFDocument');
            }
          } else {
            throw new Error('Response does not contain CFDocument or CFPackageURI');
          }
        }
      } else {
        throw new Error('Response does not contain CFDocument or CFPackageURI');
      }

      return { data: data!, finalUrl };

    } catch (err) {
      error.value = (err as Error).message || 'Failed to load external document';
      console.error('Error loading external document:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function clearError(): void {
    error.value = null;
  }

  function clearSideDocError(): void {
    sideDocError.value = null;
  }

  /**
   * NEW: Clear the viewed document error
   */
  function clearViewedDocError(): void {
    viewedDocError.value = null;
  }

  /**
   * Reset the side document loading state
   * This should be called from components after all processing is complete
   */
  function resetLoadingSideDocument(): void {
    console.log('[resetLoadingSideDocument] Setting loadingSideDocument = false');
    loadingSideDocument.value = false;
  }

  /**
   * Fetch document metadata from the API
   * This is a lightweight call that returns just the document info including lastChangeDateTime
   * @param {UUID} identifier - Document UUID
   * @returns {Promise<CFDocument|null>} - The document metadata or null on error
   */
  async function fetchDocumentMetadata(identifier: UUID): Promise<CFDocument | null> {
    try {
      const metadata = await api.get(`/ims/case/v1p1/CFDocuments/${identifier}`) as CFDocument;
      return metadata;
    } catch (err) {
      logger.warn('Failed to fetch document metadata:', err);
      return null;
    }
  }

  /**
   * Check if a document is cached
   * @param {UUID} identifier - Document identifier
   * @returns {boolean} - True if document is cached
   */
  function isDocumentCached(identifier: UUID): boolean {
    return documentCache.has(identifier);
  }

  return {
    documents,
    loading,
    error,
    documentCache,
    loadingSideDocument,
    sideDocError,
    fetchDocuments,
    fetchDocument,
    fetchDocumentMetadata,
    fetchSideDocument,
    loadExternalDocument,
    clearError,
    clearSideDocError,
    resetLoadingSideDocument,
    isDocumentCached,
    setDocumentsMetadata,
    // NEW: Viewed document state and actions (dual framework edit/view separation)
    loadingViewedDocument,
    viewedDocError,
    fetchViewedDocument,
    clearViewedDocError
  };
});
