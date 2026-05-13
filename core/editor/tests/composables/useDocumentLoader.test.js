import { describe, it, expect, beforeEach, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

const {
  routeState,
  currentDocumentStoreState,
  fetchTreeMock,
  fetchDocumentsMock,
  clearSideDocErrorMock,
  fetchAndQueueRelatedDocumentsMock,
  startQueueMock
} = vi.hoisted(() => ({
  routeState: {
    params: {}
  },
  currentDocumentStoreState: {
    currentDocument: null,
    currentDocumentTree: [],
    selectDocument: vi.fn(),
    transformCASEItems: vi.fn(() => []),
  },
  fetchTreeMock: vi.fn(),
  fetchDocumentsMock: vi.fn(),
  clearSideDocErrorMock: vi.fn(),
  fetchAndQueueRelatedDocumentsMock: vi.fn(),
  startQueueMock: vi.fn()
}));

vi.mock('vue-router', () => ({
  useRoute: () => routeState
}));

vi.mock('@/stores/documentStore', () => ({
  useDocumentStore: () => ({
    loading: false,
    error: null,
    documents: [],
    fetchTree: fetchTreeMock,
    fetchDocuments: fetchDocumentsMock,
    clearSideDocError: clearSideDocErrorMock,
  })
}));

vi.mock('@/stores/currentDocumentStore', () => ({
  useCurrentDocumentStore: () => currentDocumentStoreState
}));

vi.mock('@/stores/filterStore', () => ({
  useFilterStore: () => ({
    syncSelectedAssociationGroup: vi.fn()
  })
}));

vi.mock('@/composables/useRelatedFrameworksQueue', () => ({
  useRelatedFrameworksQueue: () => ({
    fetchAndQueueRelatedDocuments: fetchAndQueueRelatedDocumentsMock,
    startQueue: startQueueMock
  })
}));

import { useDocumentLoader } from '@/composables/useDocumentLoader.js';

describe('useDocumentLoader', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    routeState.params = {};
    currentDocumentStoreState.currentDocument = null;
    currentDocumentStoreState.currentDocumentTree = [];
  });

  it('queues related documents even when loadDocument short-circuits on an already loaded document', async () => {
    currentDocumentStoreState.currentDocument = {
      identifier: 'doc-1',
      items: []
    };
    currentDocumentStoreState.currentDocumentTree = [{}];
    fetchAndQueueRelatedDocumentsMock.mockResolvedValue([]);

    const { loadDocument } = useDocumentLoader();
    const result = await loadDocument('doc-1');

    expect(result).toBe(currentDocumentStoreState.currentDocument);
    expect(fetchTreeMock).not.toHaveBeenCalled();
    expect(fetchAndQueueRelatedDocumentsMock).toHaveBeenCalledWith('doc-1');
    expect(startQueueMock).toHaveBeenCalledTimes(1);
  });

  it('queues related documents during initializeDocument when the routed document is already loaded', async () => {
    routeState.params.frameworkId = 'doc-2';
    currentDocumentStoreState.currentDocument = {
      identifier: 'doc-2',
      items: []
    };
    currentDocumentStoreState.currentDocumentTree = [{}];
    fetchAndQueueRelatedDocumentsMock.mockResolvedValue([]);

    const { initializeDocument } = useDocumentLoader();
    await initializeDocument();

    expect(fetchDocumentsMock).not.toHaveBeenCalled();
    expect(fetchTreeMock).not.toHaveBeenCalled();
    expect(fetchAndQueueRelatedDocumentsMock).toHaveBeenCalledWith('doc-2');
    expect(startQueueMock).toHaveBeenCalledTimes(1);
  });

  it('fetches tree and selects document when not already loaded', async () => {
    const mockTreeResponse = {
      document: { identifier: 'doc-1', title: 'Test Doc' },
      tree: [],
      definitions: {}
    };
    fetchTreeMock.mockResolvedValueOnce(mockTreeResponse);
    fetchAndQueueRelatedDocumentsMock.mockResolvedValue([]);

    const { loadDocument } = useDocumentLoader();
    const result = await loadDocument('doc-1');

    expect(fetchTreeMock).toHaveBeenCalledWith('doc-1');
    expect(currentDocumentStoreState.selectDocument).toHaveBeenCalledWith(mockTreeResponse);
    expect(fetchAndQueueRelatedDocumentsMock).toHaveBeenCalledWith('doc-1');
    expect(result).toBe(mockTreeResponse.document);
  });
});
