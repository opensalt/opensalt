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
    vi.clearAllMocks();
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
        id: 'doc-1',
        title: 'Test Document',
        description: 'Test Description',
        creator: 'Test Creator',
        subject: 'Math',
        status: 'Draft',
        lastModified: '2024-01-01T00:00:00Z',
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

    it('transforms subject array to string', async () => {
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

      expect(documentStore.documents[0].subject).toBe('Math, Science');
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
        id: 'doc-1',
        title: 'Untitled Document',
        description: '',
        creator: '',
        subject: '',
        status: '',
        lastModified: '',
        language: '',
        version: ''
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
    });
  });

  describe('clearError', () => {
    it('clears error state', async () => {
      api.get.mockRejectedValueOnce(new Error('Test error'));
      await documentStore.fetchDocuments().catch(() => {});

      expect(documentStore.error).toBe('Test error');

      documentStore.clearError();
      expect(documentStore.error).toBeNull();
    });
  });
});
