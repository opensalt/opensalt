import { describe, it, expect, beforeEach, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

const {
  routeState,
  currentDocumentStoreState,
  fetchDocumentMock,
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
    selectDocument: vi.fn(),
    transformCASEItems: vi.fn(() => []),
  },
  fetchDocumentMock: vi.fn(),
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
    fetchDocument: fetchDocumentMock,
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

vi.mock('@/services/localFrameworkDb.js', () => ({
  localFrameworkDb: {
    getPackage: vi.fn()
  }
}));

import { useDocumentLoader } from '@/composables/useDocumentLoader.js';

describe('useDocumentLoader', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    routeState.params = {};
    currentDocumentStoreState.currentDocument = null;
  });

  it('queues related documents even when loadDocument short-circuits on an already loaded document', async () => {
    currentDocumentStoreState.currentDocument = {
      identifier: 'doc-1',
      items: []
    };
    fetchAndQueueRelatedDocumentsMock.mockResolvedValue([]);

    const { loadDocument } = useDocumentLoader();
    const result = await loadDocument('doc-1');

    expect(result).toBe(currentDocumentStoreState.currentDocument);
    expect(fetchDocumentMock).not.toHaveBeenCalled();
    expect(fetchAndQueueRelatedDocumentsMock).toHaveBeenCalledWith('doc-1');
    expect(startQueueMock).toHaveBeenCalledTimes(1);
  });

  it('queues related documents during initializeDocument when the routed document is already loaded', async () => {
    routeState.params.frameworkId = 'doc-2';
    currentDocumentStoreState.currentDocument = {
      identifier: 'doc-2',
      items: []
    };
    fetchAndQueueRelatedDocumentsMock.mockResolvedValue([]);

    const { initializeDocument } = useDocumentLoader();
    await initializeDocument();

    expect(fetchDocumentsMock).not.toHaveBeenCalled();
    expect(fetchDocumentMock).not.toHaveBeenCalled();
    expect(fetchAndQueueRelatedDocumentsMock).toHaveBeenCalledWith('doc-2');
    expect(startQueueMock).toHaveBeenCalledTimes(1);
  });
});
