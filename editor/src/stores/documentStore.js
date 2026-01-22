import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useDocumentStore = defineStore('documents', () => {
  // State
  const documents = ref([]);
  const loading = ref(false);
  const error = ref(null);

  // Helper function to get authentication token
  function getAuthToken() {
    // For now, return a placeholder - replace with actual implementation
    const token = localStorage.getItem('saltApiToken') || null;
    return token;
  }

  // Actions
  async function fetchDocuments() {
    loading.value = true;
    error.value = null;

    try {
      const baseUrl = 'http://web.salt_default';
      const endpoint = '/api/v1/documents';
      const limit = 1000; // Adjust as needed
      let allDocuments = [];
      let cursor = null;
      let hasNextPage = true;

      // Get authentication token
      const token = getAuthToken();

      while (hasNextPage) {
        const params = new URLSearchParams({
          'page[size]': limit.toString(),
        });

        if (cursor) {
          params.append('page[after]', cursor);
        }

        const url = `${baseUrl}${endpoint}?${params.toString()}`;

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }
        const response = await fetch(url, {
          method: 'GET',
          headers: headers,
        });

        if (!response.ok) {
          if (response.status === 401) {
            throw new Error('Authentication failed. Please check your token.');
          } else if (response.status === 403) {
            throw new Error('Access denied. You do not have permission to view documents.');
          } else {
            throw new Error(`Failed to fetch documents: ${response.status} ${response.statusText}`);
          }
        }

        const data = await response.json();

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
      error.value = err.message;
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
    console.log('[DEBUG] documentStore.fetchDocument called with identifier:', identifier);
    loading.value = true;
    error.value = null;

    try {
      const response = await fetch(`http://web.salt_default/ims/case/v1p1/CFPackages/${identifier}`);
      if (!response.ok) {
        throw new Error(`Failed to fetch document: ${response.statusText}`);
      }

      const data = await response.json();
      return data;

    } catch (err) {
      error.value = err.message;
      console.error('Error fetching document:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function loadExternalDocument(url) {
    console.log('[DEBUG] documentStore.loadExternalDocument called with url:', url);
    loading.value = true;
    error.value = null;

    let data = null;
    let finalUrl = url;

    try {
      // Initial fetch from provided URL
      let response = await fetch(url);
      if (!response.ok) {
        throw new Error(`Failed to fetch from initial URL: ${response.status} ${response.statusText}`);
      }

      data = await response.json();

      // Check if response has CFDocument; if not, check for CFPackageURI
      if (!data.CFDocument) {
        if (data.CFPackageURI && data.CFPackageURI.uri) {
          console.log('[DEBUG] No CFDocument found, fetching from CFPackageURI:', data.CFPackageURI.uri);
          finalUrl = data.CFPackageURI.uri;
          response = await fetch(finalUrl);
          if (!response.ok) {
            throw new Error(`Failed to fetch from CFPackageURI: ${response.status} ${response.statusText}`);
          }
          data = await response.json();
        } else {
          throw new Error('Response does not contain CFDocument or CFPackageURI');
        }
      }

      return { data, finalUrl };

    } catch (err) {
      error.value = err.message;
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
