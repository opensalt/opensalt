import { describe, it, expect, beforeEach, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

const {
  getRelatedDocumentsMock,
} = vi.hoisted(() => ({
  getRelatedDocumentsMock: vi.fn(),
}));

vi.mock('@/stores/documentStore', () => ({
  useDocumentStore: vi.fn(() => ({
    fetchTree: vi.fn(),
    fetchLightweightTree: vi.fn(),
  })),
}));

vi.mock('@/stores/currentDocumentStore', () => ({
  useCurrentDocumentStore: vi.fn(() => ({
    currentItem: null,
  })),
}));

vi.mock('@/stores/editorContextStore', () => ({
  useEditorContextStore: vi.fn(() => ({
    loadedPackages: new Map(),
    documentRegistry: new Map(),
    activeWriteDocumentId: 'doc-a',
    resolveEndpoint: vi.fn(() => null),
    getAssociations: vi.fn(() => []),
    registerDocumentMetadata: vi.fn(),
  })),
}));

vi.mock('@/services/api.js', () => ({
  api: {
    getRelatedDocuments: getRelatedDocumentsMock,
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
  });

  it('fetches related documents via API', async () => {
    getRelatedDocumentsMock.mockResolvedValue([{
      identifier: 'doc-b',
      uri: 'https://example.org/documents/doc-b',
      title: 'Doc B'
    }]);

    const queue = useRelatedFrameworksQueue();
    const result = await queue.fetchAndQueueRelatedDocuments('doc-a');

    expect(getRelatedDocumentsMock).toHaveBeenCalledWith('doc-a');
    expect(result).toHaveLength(1);
    expect(result[0].identifier).toBe('doc-b');
  });

  it('caches results so sequential calls do not re-fetch', async () => {
    getRelatedDocumentsMock.mockImplementation(async (identifier) => {
      await new Promise(resolve => setTimeout(resolve, 10));
      return [{
        identifier: `${identifier}-child`,
        uri: `https://example.org/documents/${identifier}-child`,
        title: `${identifier} child`
      }];
    });

    const queue = useRelatedFrameworksQueue();

    const [result1, result2] = await Promise.all([
      queue.fetchAndQueueRelatedDocuments('seq-doc'),
      queue.fetchAndQueueRelatedDocuments('seq-doc')
    ]);

    expect(result1).toHaveLength(1);
    expect(result2).toHaveLength(1);
  });

  it('caches related documents for subsequent calls', async () => {
    getRelatedDocumentsMock.mockResolvedValue([{
      identifier: 'session-doc-child',
      uri: 'https://example.org/documents/session-doc-child',
      title: 'Session child'
    }]);

    const queue = useRelatedFrameworksQueue();

    await queue.fetchAndQueueRelatedDocuments('session-doc');
    await queue.fetchAndQueueRelatedDocuments('session-doc');

    const sessionDocCalls = getRelatedDocumentsMock.mock.calls
      .map(args => args[0])
      .filter(identifier => identifier === 'session-doc');
    expect(sessionDocCalls).toHaveLength(1);
  });

  it('returns empty array on API error', async () => {
    getRelatedDocumentsMock.mockRejectedValue(new Error('Network error'));

    const queue = useRelatedFrameworksQueue();
    const result = await queue.fetchAndQueueRelatedDocuments('error-doc');

    expect(result).toEqual([]);
  });

  it('exposes backward-compatible queue methods as no-ops', () => {
    const queue = useRelatedFrameworksQueue();

    expect(() => queue.startQueue()).not.toThrow();
    expect(() => queue.pauseQueue()).not.toThrow();
    expect(() => queue.resumeQueue()).not.toThrow();
    expect(() => queue.addToQueue('doc-x')).not.toThrow();
  });
});
