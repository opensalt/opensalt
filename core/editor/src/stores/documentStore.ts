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
import { useEditorContextStore } from './editorContextStore';
import { useCurrentDocumentStore } from './currentDocumentStore';

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
  const revalidatingRequests = new Set<UUID>();

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

      // Store document metadata for cache validation and lightweight resolution
      const contextStore = useEditorContextStore();
      setDocumentsMetadata(allDocuments);
      allDocuments.forEach(doc => {
        contextStore.registerDocumentMetadata({
          identifier: doc.identifier,
          uri: doc.uri,
          title: doc.title,
          frameworkId: doc.identifier // Documents are their own framework
        });
      });
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

  /**
   * Helper to populate centralized registries from a CFPackage
   */
  function populateRegistries(identifier: UUID, pkg: CFPackage): void {
    const contextStore = useEditorContextStore();
    contextStore.loadedPackages.set(identifier, pkg);

    if (pkg.CFDocument) {
      contextStore.registerDocumentMetadata({
        identifier: pkg.CFDocument.identifier,
        uri: pkg.CFDocument.uri,
        title: pkg.CFDocument.title,
        frameworkId: identifier
      });
    }

    if (pkg.CFItems) {
      pkg.CFItems.forEach(item => contextStore.registerItem(item, identifier));
    }

    if (pkg.CFAssociations) {
      pkg.CFAssociations.forEach(assoc => {
        contextStore.associationRegistry.set(assoc.identifier, {
          association: assoc,
          frameworkId: identifier
        });
      });
      logger.debug(`[Registry] Populated ${pkg.CFAssociations.length} associations for ${identifier}`);
    }
  }

  /**
   * Unified package loader that manages multiple loading states and implements Stale-While-Revalidate
   */
  async function loadPackage(
    identifier: UUID,
    loadingRef?: Ref<boolean>,
    errorRef?: Ref<string | null>
  ): Promise<CFPackage> {
    const contextStore = useEditorContextStore();

    // Check if a MISSION-CRITICAL request is already pending (meaning we have NO data yet)
    if (pendingRequests.has(identifier)) {
      return pendingRequests.get(identifier)!;
    }

    // 1. Try to get ANY cached version from IndexedDB (even if stale)
    const cachedEntry = await frameworkCacheService.getFramework(identifier) as any;
    const serverLastChangeDateTime = documentsMetadata.get(identifier);

    // Check if the cached entry exists and is fresh
    let isFresh = false;
    if (cachedEntry) {
      isFresh = await frameworkCacheService.isCacheValid(
        identifier,
        serverLastChangeDateTime || ''
      );
    }

    if (cachedEntry) {
      const pkg = cachedEntry.data as CFPackage;

      // Populate registries immediately so UI can show data
      populateRegistries(identifier, pkg);

      if (isFresh) {
        logger.debug('IndexedDB cache hit (fresh) for document:', identifier);
        return pkg;
      }

      // Stale cache: Start background revalidation and return stale data immediately
      logger.debug('IndexedDB cache hit (stale) for document:', identifier, '- Starting revalidation');
      revalidatePackage(identifier);
      return pkg;
    }

    // 2. Cache Miss: We must fetch from API before returning anything
    if (loadingRef) loadingRef.value = true;
    if (errorRef) errorRef.value = null;

    const requestPromise = (async (): Promise<CFPackage> => {
      try {
        const pkg = await contextStore.loadPackage(identifier);
        if (!pkg) throw new Error(`Failed to load package ${identifier}`);

        // Cache in IndexedDB
        try {
          await frameworkCacheService.setFramework(identifier, pkg);
        } catch (cacheErr) {
          logger.warn('Failed to cache framework in IndexedDB:', cacheErr);
        }

        return pkg;
      } catch (err) {
        const msg = (err as Error).message || 'Failed to load package';
        if (errorRef) errorRef.value = msg;
        throw err;
      } finally {
        if (loadingRef) loadingRef.value = false;
        pendingRequests.delete(identifier);
      }
    })();

    pendingRequests.set(identifier, requestPromise);
    return requestPromise;
  }

  /**
   * Perform background revalidation for a package
   */
  async function revalidatePackage(identifier: UUID): Promise<void> {
    if (revalidatingRequests.has(identifier)) return;
    revalidatingRequests.add(identifier);

    try {
      const contextStore = useEditorContextStore();

      // Fetch fresh version from API
      // We use contextStore.loadPackage directly to bypass our own SWR logic here
      const pkg = await contextStore.loadPackage(identifier);

      if (pkg) {
        // Update IndexedDB
        await frameworkCacheService.setFramework(identifier, pkg);

        // Update memory registries (reactive update)
        // Note: populateRegistries handles contextStore.loadedPackages.set
        populateRegistries(identifier, pkg);

        logger.debug('Background revalidation complete for:', identifier);

        // If this is the active document, we should tell currentDocumentStore to refresh its transformation
        if (contextStore.activeWriteDocumentId === identifier) {
          const currentDocumentStore = useCurrentDocumentStore();
          // We'll need to define this refresh method next
          if (typeof (currentDocumentStore as any).reloadActiveDocument === 'function') {
            (currentDocumentStore as any).reloadActiveDocument();
          }
        }
      }
    } catch (err) {
      // Ignore errors during revalidation as requested ("ignoring... if error")
      logger.warn('Background revalidation failed for:', identifier, err);
    } finally {
      revalidatingRequests.delete(identifier);
    }
  }

  async function fetchDocument(identifier: UUID): Promise<CFPackage> {
    return loadPackage(identifier, loading, error);
  }

  async function fetchSideDocument(identifier: UUID): Promise<CFPackage> {
    return loadPackage(identifier, loadingSideDocument, sideDocError);
  }

  async function fetchViewedDocument(identifier: UUID): Promise<CFPackage> {
    return loadPackage(identifier, loadingViewedDocument, viewedDocError);
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

    // If we have a UUID, try local server first (with caching)
    if (uuid) {
      try {
        const localResponse = await loadPackage(uuid);
        logger.debug(`Local fetch (cached) succeeded for document ${uuid}`);
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

    // If we have a UUID, try local server first (with caching)
    if (uuid) {
      try {
        const localResponse = await loadPackage(uuid);
        logger.debug(`Local fetch (cached) succeeded for package ${uuid}`);
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
   * Check if a document is cached in memory
   * @param {UUID} identifier - Document identifier
   * @returns {boolean} - True if document is in memory
   */
  function isDocumentCached(identifier: UUID): boolean {
    const contextStore = useEditorContextStore();
    return contextStore.loadedPackages.has(identifier);
  }

  return {
    documents,
    loading,
    error,
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
    loadPackage,
    isDocumentCached,
    setDocumentsMetadata,
    revalidatePackage,
    // NEW: Viewed document state and actions (dual framework edit/view separation)
    loadingViewedDocument,
    viewedDocError,
    fetchViewedDocument,
    clearViewedDocError
  };
});
