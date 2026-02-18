import { defineStore } from 'pinia';
import { logger } from '../utils/logger.js';
import { ref, Ref } from 'vue';
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

  async function loadExternalDocument(url: string): Promise<{data: CFPackage, finalUrl: string}> {
    loading.value = true;
    error.value = null;

    let data: CFPackage | null = null;
    let finalUrl: string = url;

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

    try {
      // Initial fetch from provided URL
      const initialResponse = await api.get(url);

      if (isCFPackage(initialResponse)) {
        // Response is already a CFPackage
        data = initialResponse as CFPackage;
      } else if (isApiPackageResponse(initialResponse)) {
        // Response has CFDocument and possibly CFPackageURI
        const apiResponse = initialResponse as ApiPackageResponse;
        data = apiResponse as unknown as CFPackage;

        // Check if response has CFDocument; if not, check for CFPackageURI
        if (!data.CFDocument) {
          if (apiResponse.CFPackageURI && apiResponse.CFPackageURI.uri) {
            finalUrl = apiResponse.CFPackageURI.uri;
            const finalResponse = await api.get(finalUrl);
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

  return {
    documents,
    loading,
    error,
    fetchDocuments,
    fetchDocument,
    loadExternalDocument,
    clearError
  };
});
