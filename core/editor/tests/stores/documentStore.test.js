import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useDocumentStore } from '@/stores/documentStore.js';
import { api } from '@/services/api.js';

vi.mock('@/services/api.js', () => ({
  api: {
    get: vi.fn()
  }
}));

describe('DocumentStore', () => {
  let documentStore;

  beforeEach(() => {
    setActivePinia(createPinia());
    documentStore = useDocumentStore();
    vi.resetAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('initial state', () => {
    it('has empty documents array', () => {
      expect(documentStore.documents).toEqual([]);
    });

    it('has loading set to false', () => {
      expect(documentStore.loading).toBe(false);
    });

    it('has error set to null', () => {
      expect(documentStore.error).toBeNull();
    });
  });

  describe('fetchDocuments', () => {
    it('fetches documents successfully', async () => {
      const mockDocuments = {
        data: [
          {
            identifier: 'doc-1',
            title: 'Test Document',
            creator: 'Test Creator',
            description: 'Test Description',
            subject: ['Math'],
            adoptionStatus: 'Draft',
            lastChangeDateTime: '2024-01-01T00:00:00Z',
            language: 'en',
            version: '1.0'
          }
        ],
        pagination: {
          hasNextPage: false,
          nextCursor: null
        }
      };

      api.get.mockResolvedValueOnce(mockDocuments);

      await documentStore.fetchDocuments();

      expect(documentStore.documents).toHaveLength(1);
      expect(documentStore.documents[0]).toEqual({
        identifier: 'doc-1',
        title: 'Test Document',
        description: 'Test Description',
        creator: 'Test Creator',
        subject: ['Math'],
        adoptionStatus: 'Draft',
        lastChangeDateTime: '2024-01-01T00:00:00Z',
        language: 'en',
        version: '1.0'
      });
      expect(documentStore.loading).toBe(false);
      expect(documentStore.error).toBeNull();
    });

    it('handles pagination correctly', async () => {
      const firstPage = {
        data: [{ identifier: 'doc-1', title: 'Document 1' }],
        pagination: { hasNextPage: true, nextCursor: 'cursor-1' }
      };
      const secondPage = {
        data: [{ identifier: 'doc-2', title: 'Document 2' }],
        pagination: { hasNextPage: false, nextCursor: null }
      };

      api.get
        .mockResolvedValueOnce(firstPage)
        .mockResolvedValueOnce(secondPage);

      await documentStore.fetchDocuments();

      expect(documentStore.documents).toHaveLength(2);
      expect(api.get).toHaveBeenCalledTimes(2);
      expect(api.get).toHaveBeenNthCalledWith(
        2,
        expect.stringContaining('cursor-1')
      );
    });

    it('handles fetch error', async () => {
      const errorMessage = 'Failed to fetch documents';
      api.get.mockRejectedValueOnce(new Error(errorMessage));

      await documentStore.fetchDocuments();

      expect(documentStore.error).toBe(errorMessage);
      expect(documentStore.documents).toEqual([]);
      expect(documentStore.loading).toBe(false);
    });

    it('handles invalid response format', async () => {
      api.get.mockResolvedValueOnce({ data: 'not an array' });

      await documentStore.fetchDocuments();

      expect(documentStore.error).toBe('Invalid response format: expected data array');
    });

    it('retains subject as array', async () => {
      const mockDocuments = {
        data: [
          {
            identifier: 'doc-1',
            title: 'Test',
            subject: ['Math', 'Science'],
            lastChangeDateTime: '2024-01-01T00:00:00Z'
          }
        ],
        pagination: { hasNextPage: false }
      };

      api.get.mockResolvedValueOnce(mockDocuments);

      await documentStore.fetchDocuments();

      expect(documentStore.documents[0].subject).toEqual(['Math', 'Science']);
    });

    it('handles missing optional fields with defaults', async () => {
      const mockDocuments = {
        data: [
          { identifier: 'doc-1' }
        ],
        pagination: { hasNextPage: false }
      };

      api.get.mockResolvedValueOnce(mockDocuments);

      await documentStore.fetchDocuments();

      expect(documentStore.documents[0]).toEqual({
        identifier: 'doc-1'
      });
    });

    it('sets loading state during fetch', async () => {
      let resolvePromise;
      api.get.mockImplementationOnce(() => new Promise(resolve => {
        resolvePromise = resolve;
      }));

      const fetchPromise = documentStore.fetchDocuments();
      expect(documentStore.loading).toBe(true);

      resolvePromise({
        data: [],
        pagination: { hasNextPage: false }
      });
      await fetchPromise;

      expect(documentStore.loading).toBe(false);
    });
  });

  describe('fetchDocument', () => {
    it('fetches single document by identifier', async () => {
      const mockDocument = {
        CFDocument: {
          identifier: 'doc-1',
          title: 'Test Document'
        },
        CFItems: []
      };

      api.get.mockResolvedValueOnce(mockDocument);

      const result = await documentStore.fetchDocument('doc-1');

      expect(result).toEqual(mockDocument);
      expect(api.get).toHaveBeenCalledWith('/ims/case/v1p1/CFPackages/doc-1');
    });

    it('caches fetched documents', async () => {
      const mockDocument = {
        CFDocument: { identifier: 'doc-1', title: 'Test' }
      };

      api.get.mockResolvedValueOnce(mockDocument);

      await documentStore.fetchDocument('doc-1');
      await documentStore.fetchDocument('doc-1');

      expect(api.get).toHaveBeenCalledTimes(1);
    });

    it('deduplicates concurrent requests', async () => {
      const mockDocument = {
        CFDocument: { identifier: 'doc-1', title: 'Test' }
      };

      api.get.mockResolvedValueOnce(mockDocument);

      const [result1, result2] = await Promise.all([
        documentStore.fetchDocument('doc-1'),
        documentStore.fetchDocument('doc-1')
      ]);

      expect(result1).toEqual(mockDocument);
      expect(result2).toEqual(mockDocument);
      expect(api.get).toHaveBeenCalledTimes(1);
    });

    it('handles fetch error', async () => {
      const errorMessage = 'Document not found';
      api.get.mockRejectedValueOnce(new Error(errorMessage));

      await expect(documentStore.fetchDocument('non-existent')).rejects.toThrow(errorMessage);
      expect(documentStore.error).toBe(errorMessage);
    });
  });

  describe('loadExternalDocument', () => {
    it('loads external document from URL', async () => {
      const mockDocument = {
        CFDocument: {
          identifier: 'ext-doc-1',
          title: 'External Document'
        }
      };

      api.get.mockResolvedValueOnce(mockDocument);

      const result = await documentStore.loadExternalDocument('https://external.com/package');

      expect(result.data).toEqual(mockDocument);
      expect(result.finalUrl).toBe('https://external.com/package');
    });

    it('follows CFPackageURI redirect', async () => {
      const initialResponse = {
        CFPackageURI: {
          uri: 'https://redirect.com/package'
        }
      };
      const finalResponse = {
        CFDocument: {
          identifier: 'ext-doc-1',
          title: 'Redirected Document'
        }
      };

      api.get
        .mockResolvedValueOnce(initialResponse)
        .mockResolvedValueOnce(finalResponse);

      const result = await documentStore.loadExternalDocument('https://initial.com');

      expect(result.data).toEqual(finalResponse);
      expect(result.finalUrl).toBe('https://redirect.com/package');
      expect(api.get).toHaveBeenCalledTimes(2);
    });

    it('throws error when response has neither CFDocument nor CFPackageURI', async () => {
      api.get.mockResolvedValueOnce({ otherData: true });

      await expect(
        documentStore.loadExternalDocument('https://external.com/package')
      ).rejects.toThrow('Response does not contain CFDocument or CFPackageURI');
    });

    it('handles load error', async () => {
      api.get.mockRejectedValueOnce(new Error('Network error'));

      await expect(
        documentStore.loadExternalDocument('https://external.com/package')
      ).rejects.toThrow('Network error');
      expect(documentStore.error).toBe('Network error');
    });

    describe('clearError', () => {
      it('clears error state', async () => {
        api.get.mockRejectedValueOnce(new Error('Test error'));
        await documentStore.fetchDocuments().catch(() => { });

        expect(documentStore.error).toBe('Test error');

        documentStore.clearError();
        expect(documentStore.error).toBeNull();
      });
    });
  });

  describe('fetchSideDocument', () => {
    it('has loadingSideDocument initially set to false', () => {
      expect(documentStore.loadingSideDocument).toBe(false);
    });

    it('has sideDocError initially set to null', () => {
      expect(documentStore.sideDocError).toBeNull();
    });

    it('fetches side document without affecting global loading state', async () => {
      const mockDocument = {
        CFDocument: {
          identifier: 'side-doc-1',
          title: 'Side Document'
        },
        CFItems: []
      };

      api.get.mockResolvedValueOnce(mockDocument);

      const result = await documentStore.fetchSideDocument('side-doc-1');

      expect(result).toEqual(mockDocument);
      expect(documentStore.loadingSideDocument).toBe(false);
      expect(documentStore.loading).toBe(false); // Global loading should not be affected
      expect(api.get).toHaveBeenCalledWith('/ims/case/v1p1/CFPackages/side-doc-1');
    });

    it('sets loadingSideDocument during fetch', async () => {
      let resolvePromise;
      api.get.mockImplementationOnce(() => new Promise(resolve => {
        resolvePromise = resolve;
      }));

      const fetchPromise = documentStore.fetchSideDocument('side-doc-1');

      // Wait for nextTick to ensure the async function moves past the initial await
      await new Promise(resolve => setTimeout(resolve, 0));

      // During fetch, loadingSideDocument should be true
      expect(documentStore.loadingSideDocument).toBe(true);
      expect(documentStore.loading).toBe(false); // Global loading should remain false

      // Resolve the API call
      resolvePromise({
        CFDocument: { identifier: 'side-doc-1', title: 'Test' },
        CFItems: []
      });

      await fetchPromise;

      expect(documentStore.loadingSideDocument).toBe(false);
    });

    it('caches fetched side documents', async () => {
      const mockDocument = {
        CFDocument: { identifier: 'side-doc-1', title: 'Test' }
      };

      api.get.mockResolvedValueOnce(mockDocument);

      await documentStore.fetchSideDocument('side-doc-1');
      await documentStore.fetchSideDocument('side-doc-1');

      expect(api.get).toHaveBeenCalledTimes(1);
    });

    it('deduplicates concurrent side document requests', async () => {
      const mockDocument = {
        CFDocument: { identifier: 'side-doc-1', title: 'Test' }
      };

      api.get.mockResolvedValueOnce(mockDocument);

      const [result1, result2] = await Promise.all([
        documentStore.fetchSideDocument('side-doc-1'),
        documentStore.fetchSideDocument('side-doc-1')
      ]);

      expect(result1).toEqual(mockDocument);
      expect(result2).toEqual(mockDocument);
      expect(api.get).toHaveBeenCalledTimes(1);
    });

    it('handles side document fetch error', async () => {
      const errorMessage = 'Side document not found';
      api.get.mockRejectedValueOnce(new Error(errorMessage));

      await expect(documentStore.fetchSideDocument('non-existent')).rejects.toThrow(errorMessage);
      expect(documentStore.sideDocError).toBe(errorMessage);
      expect(documentStore.loadingSideDocument).toBe(false);
    });

    it('sets sideDocError on fetch failure', async () => {
      api.get.mockRejectedValueOnce(new Error('Failed to load side document'));

      await expect(documentStore.fetchSideDocument('bad-id')).rejects.toThrow();

      expect(documentStore.sideDocError).toBe('Failed to load side document');
    });

    it('does not affect global error state on side document failure', async () => {
      api.get.mockRejectedValueOnce(new Error('Side doc error'));

      await expect(documentStore.fetchSideDocument('bad-id')).rejects.toThrow();

      expect(documentStore.sideDocError).toBe('Side doc error');
      expect(documentStore.error).toBeNull(); // Global error should not be affected
    });
  });

  describe('clearSideDocError', () => {
    it('clears side document error state', async () => {
      api.get.mockRejectedValueOnce(new Error('Side doc test error'));
      await documentStore.fetchSideDocument('test-id').catch(() => { });

      expect(documentStore.sideDocError).toBe('Side doc test error');

      documentStore.clearSideDocError();
      expect(documentStore.sideDocError).toBeNull();
    });

    it('does not affect global error state', async () => {
      // Set global error
      api.get.mockRejectedValueOnce(new Error('Global error'));
      await documentStore.fetchDocuments().catch(() => { });

      // Set side doc error
      api.get.mockRejectedValueOnce(new Error('Side error'));
      await documentStore.fetchSideDocument('test-id').catch(() => { });

      expect(documentStore.error).toBe('Global error');
      expect(documentStore.sideDocError).toBe('Side error');

      // Clear only side doc error
      documentStore.clearSideDocError();

      expect(documentStore.sideDocError).toBeNull();
      expect(documentStore.error).toBe('Global error'); // Global error should remain
    });
  });
});
