import { defineStore } from 'pinia';
import { logger } from '../utils/logger.js';
import { ref, Ref, nextTick } from 'vue';
import { api } from '../services/api.js';
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

  // Request deduplication cache
  const pendingRequests = new Map<UUID, Promise<CFPackage>>();
  const documentCache = new Map<UUID, CFPackage>(); // Cache fetched documents

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

    // Check cache first
    if (documentCache.has(identifier)) {
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
    // The spinner is now visible, but we DON'T reset loading state here
    // The caller is responsible for resetting loadingSideDocument after
    // all post-fetch processing (transformCASEItems, etc.) is complete
    if (documentCache.has(identifier)) {
      console.log('[fetchSideDocument] Cache hit! Returning cached document');
      const cachedDoc = documentCache.get(identifier)!;
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
        // Note: loadingSideDocument is NOT reset here because the caller
        // (onSideDocumentSelect) is responsible for resetting it after
        // all post-fetch processing is complete
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
   * Reset the side document loading state
   * This should be called from components after all processing is complete
   */
  function resetLoadingSideDocument(): void {
    console.log('[resetLoadingSideDocument] Setting loadingSideDocument = false');
    loadingSideDocument.value = false;
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
    fetchSideDocument,
    loadExternalDocument,
    clearError,
    clearSideDocError,
    resetLoadingSideDocument
  };
});
