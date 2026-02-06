import { defineStore } from 'pinia';
import { logger } from '../utils/logger.js';
import { ref } from 'vue';
import { api } from '../services/api.js';

export const useDocumentStore = defineStore('documents', () => {
  // State
  const documents = ref([]);
  const loading = ref(false);
  const error = ref(null);

  // Request deduplication cache
  const pendingRequests = new Map();
  const documentCache = new Map(); // Cache fetched documents

  // Actions
  async function fetchDocuments() {
    loading.value = true;
    error.value = null;

    try {
      const endpoint = '/api/v1/documents';
      const limit = 1000; // Adjust as needed
      let allDocuments = [];
      let cursor = null;
      let hasNextPage = true;

      while (hasNextPage) {
        const params = new URLSearchParams({
          'page[size]': limit.toString(),
        });

        if (cursor) {
          params.append('page[after]', cursor);
        }

        const url = `${endpoint}?${params.toString()}`;
        const data = await api.get(url);

        if (!data.data || !Array.isArray(data.data)) {
          throw new Error('Invalid response format: expected data array');
        }

        // Transform the data to match our expected format
        const transformedDocs = data.data.map(doc => ({
          id: doc.identifier,
          title: doc.title || 'Untitled Document',
          description: doc.description || '',
          creator: doc.creator || '',
          subject: Array.isArray(doc.subject) ? doc.subject.join(', ') : (doc.subject || ''),
          status: doc.adoptionStatus || '',
          lastModified: doc.lastChangeDateTime || '',
          language: doc.language || '',
          version: doc.version || ''
        }));

        allDocuments = allDocuments.concat(transformedDocs);

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
      error.value = err.message || 'Failed to fetch documents';
      console.error('Error fetching documents:', err);
      // Keep any previously loaded documents if there was an error
      if (documents.value.length === 0) {
        documents.value = [];
      }
    } finally {
      loading.value = false;
    }
  }

  async function fetchDocument(identifier) {

    // Check cache first
    if (documentCache.has(identifier)) {
      return documentCache.get(identifier);
    }

    // Check if request is already pending
    if (pendingRequests.has(identifier)) {
      return pendingRequests.get(identifier);
    }

    loading.value = true;
    error.value = null;

    // Create the request promise
    const requestPromise = (async () => {
      try {
        const data = await api.get(`/ims/case/v1p1/CFPackages/${identifier}`);

        // Cache the result
        documentCache.set(identifier, data);

        return data;
      } catch (err) {
        error.value = err.message || 'Failed to fetch document';
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

  async function loadExternalDocument(url) {
    loading.value = true;
    error.value = null;

    let data = null;
    let finalUrl = url;

    try {
      // Initial fetch from provided URL
      const initialData = await api.get(url);
      data = initialData;

      // Check if response has CFDocument; if not, check for CFPackageURI
      if (!data.CFDocument) {
        if (data.CFPackageURI && data.CFPackageURI.uri) {
          finalUrl = data.CFPackageURI.uri;
          data = await api.get(finalUrl);
        } else {
          throw new Error('Response does not contain CFDocument or CFPackageURI');
        }
      }

      return { data, finalUrl };

    } catch (err) {
      error.value = err.message || 'Failed to load external document';
      console.error('Error loading external document:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function clearError() {
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
