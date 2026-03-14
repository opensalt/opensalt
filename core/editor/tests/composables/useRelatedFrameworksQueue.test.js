import { describe, it, expect, beforeEach, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

const {
  loadPackageMock,
  reloadActiveDocumentMock,
  getRelatedDocumentsMock,
  getRelatedFrameworksMock,
  setRelatedFrameworksMock
} = vi.hoisted(() => ({
  loadPackageMock: vi.fn(),
  reloadActiveDocumentMock: vi.fn(),
  getRelatedDocumentsMock: vi.fn(),
  getRelatedFrameworksMock: vi.fn(),
  setRelatedFrameworksMock: vi.fn()
}));

vi.mock('@/stores/documentStore', () => ({
  useDocumentStore: vi.fn(() => ({
    loadPackage: loadPackageMock,
  })),
}));

vi.mock('@/stores/currentDocumentStore', () => ({
  useCurrentDocumentStore: vi.fn(() => ({
    currentItem: null,
    reloadActiveDocument: reloadActiveDocumentMock,
  })),
}));

vi.mock('@/stores/editorContextStore', () => ({
  useEditorContextStore: vi.fn(() => ({
    loadedPackages: new Map(),
    activeWriteDocumentId: 'doc-a',
    resolveEndpoint: vi.fn(() => null),
    getAssociations: vi.fn(() => []),
  })),
}));

vi.mock('@/services/api.js', () => ({
  api: {
    getRelatedDocuments: getRelatedDocumentsMock,
  },
}));

vi.mock('@/services/frameworkCacheService.js', () => ({
  frameworkCacheService: {
    getRelatedFrameworks: getRelatedFrameworksMock,
    setRelatedFrameworks: setRelatedFrameworksMock,
  },
}));

vi.mock('@/utils/logger.js', () => ({
  logger: {
    debug: vi.fn(),
    warn: vi.fn(),
    error: vi.fn(),
  },
}));

import { useRelatedFrameworksQueue } from '@/composables/useRelatedFrameworksQueue.js';

describe('useRelatedFrameworksQueue', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    getRelatedFrameworksMock.mockResolvedValue(null);
    setRelatedFrameworksMock.mockResolvedValue(true);
  });

  it('recursively queues related frameworks discovered from fetched packages', async () => {
    loadPackageMock.mockResolvedValue({});

    getRelatedDocumentsMock.mockImplementation(async (identifier) => {
      if (identifier === 'doc-a') {
        return [{
          identifier: 'doc-b',
          uri: 'https://example.org/documents/doc-b',
          title: 'Doc B'
        }];
      }

      if (identifier === 'doc-b') {
        return [{
          identifier: 'doc-c',
          uri: 'https://example.org/documents/doc-c',
          title: 'Doc C'
        }];
      }

      return [];
    });

    const queue = useRelatedFrameworksQueue();

    await queue.fetchAndQueueRelatedDocuments('doc-a');
    queue.startQueue();

    await new Promise(resolve => setTimeout(resolve, 50));

    expect(loadPackageMock).toHaveBeenCalledWith('doc-b');
    expect(getRelatedDocumentsMock).toHaveBeenCalledWith('doc-b');
    expect(loadPackageMock).toHaveBeenCalledWith('doc-c');
    expect(reloadActiveDocumentMock).toHaveBeenCalled();
  });
});
