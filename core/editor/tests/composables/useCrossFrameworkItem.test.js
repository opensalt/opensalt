import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { ref, nextTick } from 'vue';

const createMockCurrentDocumentStore = () => {
  const store = {
    currentDocument: null,
    associatedDocuments: new Map(),
    fetchAssociatedDocument: vi.fn(),
    getAssociatedDocument: vi.fn(),
    transformCASEItems: vi.fn((items, _associations, _docId) => items)
  };
  return store;
};

const createMockDocumentStore = () => ({
  fetchDocument: vi.fn(),
  fetchDocumentMetadata: vi.fn(async (identifier) => {
    if (identifier === 'ext-doc-3') {
      return {
        identifier: 'ext-doc-3',
        uri: 'https://example.com/doc/ext-doc-3',
        title: 'Fetched External Document 3'
      };
    }
    return null;
  }),
  documentCache: new Map(),
  pendingRequests: new Map()
});

let mockCurrentDocumentStore;
let mockDocumentStore;
let mockItemDetailsCache;
let mockRegistryDocuments;
let mockApiGet;

vi.mock('pinia', () => ({
  defineStore: vi.fn(),
  storeToRefs: vi.fn((store) => ({
    currentDocument: store.currentDocument
  }))
}));

vi.mock('../../src/stores/currentDocumentStore', () => ({
  useCurrentDocumentStore: vi.fn(() => mockCurrentDocumentStore)
}));

vi.mock('../../src/stores/documentStore', () => ({
  useDocumentStore: vi.fn(() => mockDocumentStore)
}));

vi.mock('../../src/stores/editorContextStore', () => ({
  useEditorContextStore: vi.fn(() => ({
    itemDetailsCache: mockItemDetailsCache,
    documentRegistry: {
      get: vi.fn((id) => mockRegistryDocuments.get(id) || null)
    },
    registerDocumentMetadata: vi.fn((doc) => {
      mockRegistryDocuments.set(doc.identifier, doc);
    }),
    isViewingDifferentFramework: false,
    viewedDocumentId: null,
    activeWriteDocumentId: 'test-doc-id'
  }))
}));

vi.mock('../../src/stores/viewStore', () => ({
  useViewStore: vi.fn(() => ({
    viewMode: 'single',
    setLoading: vi.fn(),
    getViewState: vi.fn(() => ({ loading: false }))
  }))
}));

vi.mock('../../src/services/api.js', () => ({
  api: {
    get: (...args) => mockApiGet(...args)
  }
}));

vi.mock('../../src/utils/logger.js', () => ({
  logger: {
    warn: vi.fn(),
    error: vi.fn(),
    info: vi.fn()
  }
}));

import {
  useCrossFrameworkItem,
  determineTargetType,
  findInCachedFrameworks,
  findItemById,
  clearCrossFrameworkItemCache,
  _getCrossFrameworkItemCacheSize
} from '@/composables/useCrossFrameworkItem.js';

describe('useCrossFrameworkItem', () => {
  let composable;

  const currentDocId = 'current-doc-uuid';
  const externalDocId = 'external-doc-uuid';

  const mockCurrentDocument = {
    identifier: currentDocId,
    title: 'Current Framework',
    items: [
      {
        identifier: 'item-1',
        fullStatement: 'Item 1 in current doc',
        humanCodingScheme: 'I1'
      },
      {
        identifier: 'item-2',
        fullStatement: 'Item 2 in current doc',
        humanCodingScheme: 'I2'
      }
    ]
  };

  const createAssociation = (originId, destId, originDocId, destDocId, options = {}) => {
    const assoc = {
      originNodeURI: {
        identifier: originId,
        title: `Item ${originId}`,
        uri: `https://example.com/uri/${originDocId}/${originId}`,
        ...options.originURI
      },
      destinationNodeURI: {
        identifier: destId,
        title: `Item ${destId}`,
        uri: `https://example.com/uri/${destDocId}/${destId}`,
        ...options.destURI
      },
      associationType: options.associationType || 'isRelatedTo'
    };
    return assoc;
  };

  beforeEach(() => {
    vi.clearAllMocks();
    clearCrossFrameworkItemCache();

    mockCurrentDocumentStore = createMockCurrentDocumentStore();
    mockDocumentStore = createMockDocumentStore();

    mockCurrentDocumentStore.currentDocument = { ...mockCurrentDocument };
    mockCurrentDocumentStore.associatedDocuments = new Map();
    mockDocumentStore.documentCache = new Map();
    mockDocumentStore.pendingRequests = new Map();

    mockItemDetailsCache = new Map([
      ['ext-item-1', {
        identifier: 'ext-item-1',
        title: 'Test External Item',
        documentIdentifier: 'ext-doc-1'
      }]
    ]);
    mockRegistryDocuments = new Map([
      ['ext-doc-1', { title: 'Test External Document', identifier: 'ext-doc-1', frameworkId: 'ext-doc-1' }]
    ]);

    mockApiGet = vi.fn(async (url) => {
      if (url === '/framework/editor/item/ext-item-1/details') {
        return { identifier: 'ext-item-1', title: 'Test External Item', documentIdentifier: 'ext-doc-1' };
      }
      if (url === '/framework/editor/item/ext-item-2/details') {
        return {
          identifier: 'ext-item-2',
          title: 'Resolved External Item 2',
          fullStatement: 'Resolved External Item 2',
          uri: 'http://example.com/ext-item-2'
        };
      }
      if (url === '/framework/editor/item/ext-item-3/details') {
        return {
          identifier: 'ext-item-3',
          uri: 'http://example.com/ext-item-3',
          humanCodingScheme: 'E3',
          CFDocumentURI: {
            identifier: 'ext-doc-3',
            uri: 'https://example.com/doc/ext-doc-3',
            title: 'External Document 3'
          }
        };
      }
      if (url === '/framework/editor/item/123e4567-e89b-12d3-a456-426614174000/details') {
        return {
          identifier: '123e4567-e89b-12d3-a456-426614174000',
          uri: 'http://example.com/ext-item-4',
          title: '123e4567-e89b-12d3-a456-426614174000',
          humanCodingScheme: 'E4'
        };
      }
      if (url === '/framework/editor/item/ext-item-5/details') {
        return {
          identifier: 'ext-item-5',
          uri: 'http://example.com/ext-item-5',
          humanCodingScheme: 'E5',
          CFDocumentURI: {
            identifier: 'ext-doc-3',
            uri: 'https://example.com/doc/ext-doc-3'
          }
        };
      }
      if (url.includes('/ext-item-403/')) {
        const err = new Error('Forbidden');
        err.status = 403;
        throw err;
      }
      if (url.includes('/ext-item-404/')) {
        const err = new Error('Not Found');
        err.status = 404;
        throw err;
      }
      if (url.includes('/ext-item-network/')) {
        throw new Error('Network Error');
      }
      throw new Error('Unknown URL: ' + url);
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('determineTargetType', () => {
    it('returns CASE as default when targetType is not specified', () => {
      const result = determineTargetType(undefined, 'isRelatedTo');
      expect(result.isCase).toBe(true);
      expect(result.isUnknown).toBe(false);
    });

    it('returns CASE when targetType is explicitly CASE', () => {
      const result = determineTargetType('CASE', 'isRelatedTo');
      expect(result.isCase).toBe(true);
      expect(result.isUnknown).toBe(false);
    });

    it('returns non-CASE for other targetTypes', () => {
      const result = determineTargetType('SomeOtherType', 'isRelatedTo');
      expect(result.isCase).toBe(false);
      expect(result.isUnknown).toBe(false);
    });

    it('returns unknown for exemplar associations without targetType', () => {
      const result = determineTargetType(undefined, 'exemplar');
      expect(result.isCase).toBe(false);
      expect(result.isUnknown).toBe(true);
    });

    it('respects explicit targetType even for exemplar', () => {
      const result = determineTargetType('CASE', 'exemplar');
      expect(result.isCase).toBe(true);
      expect(result.isUnknown).toBe(false);
    });
  });

  describe('findItemById', () => {
    it('finds item at root level', () => {
      const items = [
        { identifier: 'item-1', fullStatement: 'Item 1' },
        { identifier: 'item-2', fullStatement: 'Item 2' }
      ];

      const result = findItemById(items, 'item-1');
      expect(result).toEqual({ identifier: 'item-1', fullStatement: 'Item 1' });
    });

    it('finds item nested in children', () => {
      const items = [
        {
          identifier: 'item-1',
          fullStatement: 'Item 1',
          children: [
            { identifier: 'child-1', fullStatement: 'Child 1' }
          ]
        }
      ];

      const result = findItemById(items, 'child-1');
      expect(result).toEqual({ identifier: 'child-1', fullStatement: 'Child 1' });
    });

    it('returns null when item not found', () => {
      const items = [
        { identifier: 'item-1', fullStatement: 'Item 1' }
      ];

      const result = findItemById(items, 'non-existent');
      expect(result).toBeNull();
    });

    it('returns null for null/undefined items', () => {
      expect(findItemById(null, 'item-1')).toBeNull();
      expect(findItemById(undefined, 'item-1')).toBeNull();
      expect(findItemById('not-an-array', 'item-1')).toBeNull();
    });
  });

  describe('findInCachedFrameworks', () => {
    it('finds item in itemDetailsCache', () => {
      const itemDetailsCache = new Map([
        ['ext-item-1', {
          identifier: 'ext-item-1',
          title: 'Test External Item',
          documentIdentifier: 'ext-doc-1'
        }]
      ]);
      const documentRegistry = new Map([
        ['ext-doc-1', { title: 'External Framework', identifier: 'ext-doc-1' }]
      ]);

      const result = findInCachedFrameworks('ext-item-1', {
        itemDetailsCache,
        documentRegistry
      });

      expect(result).not.toBeNull();
      expect(result.item.identifier).toBe('ext-item-1');
      expect(result.documentTitle).toBe('External Framework');
    });

    it('finds item in documentRegistry', () => {
      const documentRegistry = new Map([
        ['doc-1', { title: 'Cached Framework', identifier: 'doc-1' }]
      ]);

      const result = findInCachedFrameworks('doc-1', {
        itemDetailsCache: new Map(),
        documentRegistry
      });

      expect(result).not.toBeNull();
      expect(result.item.identifier).toBe('doc-1');
      expect(result.documentTitle).toBe('Cached Framework');
    });

    it('returns null when item not in any cache', () => {
      const result = findInCachedFrameworks('non-existent', {
        itemDetailsCache: new Map(),
        documentRegistry: new Map()
      });
      expect(result).toBeNull();
    });

    it('returns null for null identifier', () => {
      const result = findInCachedFrameworks(null, {
        itemDetailsCache: new Map(),
        documentRegistry: new Map()
      });
      expect(result).toBeNull();
    });
  });

  describe('item found in current document', () => {
    it('returns item data when item is in current document', async () => {
      const association = createAssociation('item-1', 'item-2', currentDocId, currentDocId);

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.isLoading.value).toBe(false);
      expect(composable.itemData.value?.identifier).toBe('item-2');
      expect(composable.isCrossFramework.value).toBe(false);
    });

    it('returns correct item for reversed direction', async () => {
      const association = createAssociation('item-1', 'item-2', currentDocId, currentDocId);

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('reversed')
      });

      await nextTick();

      expect(composable.itemData.value?.identifier).toBe('item-1');
    });
  });

  describe('cross-framework item detection', () => {
    it('identifies when item is from a different framework', async () => {
      const association = createAssociation('item-1', 'ext-item-1', currentDocId, externalDocId);

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.isCrossFramework.value).toBe(true);
    });

    it('identifies when item is from the same framework', async () => {
      const association = createAssociation('item-1', 'item-2', currentDocId, currentDocId);

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.isCrossFramework.value).toBe(false);
    });
  });

  describe('targetTypeInfo', () => {
    it('returns CASE type info by default', async () => {
      const association = createAssociation('item-1', 'item-2', currentDocId, currentDocId);

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.targetTypeInfo.value.isCase).toBe(true);
      expect(composable.targetTypeInfo.value.isUnknown).toBe(false);
    });

    it('returns unknown type info for exemplar without targetType', async () => {
      const association = createAssociation('item-1', 'ext-item-1', currentDocId, externalDocId, {
        associationType: 'exemplar'
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.targetTypeInfo.value.isCase).toBe(false);
      expect(composable.targetTypeInfo.value.isUnknown).toBe(true);
    });

    it('respects explicit targetType', async () => {
      const association = createAssociation('item-1', 'ext-item-1', currentDocId, externalDocId, {
        destURI: { targetType: 'SomeOtherType' }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.targetTypeInfo.value.isCase).toBe(false);
      expect(composable.targetTypeInfo.value.isUnknown).toBe(false);
    });
  });

  describe('CASE item fetching', () => {
    it('fetches item via API for cross-framework CASE items', async () => {
      mockItemDetailsCache.delete('ext-item-1');

      const association = createAssociation('item-1', 'ext-item-1', currentDocId, externalDocId, {
        destURI: { uri: 'http://example.com/ext-item-1' }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(composable.itemData.value?.identifier).toBe('ext-item-1');
    });

    it('does not fetch non-HTTP(S) URIs and shows the title instead of raw URI', async () => {
      const dataUri = 'data:text/plain;base64,SGVsbG8=';
      const association = createAssociation('item-1', 'ext-item-data', currentDocId, externalDocId, {
        destURI: {
          identifier: 'ext-item-data',
          uri: dataUri,
          title: 'Inline data item'
        }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(mockApiGet).not.toHaveBeenCalledWith(expect.stringContaining('/framework/editor/item/'));
      expect(composable.itemData.value).toBeNull();
      expect(composable.itemTitle.value).toBe('Inline data item');
      expect(composable.frameworkTitle.value).toBeNull();
      expect(composable.fetchError.value).toBeNull();
    });

    it('ignores unresolved cached placeholders and still fetches the real external item', async () => {
      mockItemDetailsCache.set('ext-item-2', {
        identifier: 'ext-item-2',
        fullStatement: 'Loading...',
        isCrossFramework: true
      });
      mockItemDetailsCache.set('http://example.com/ext-item-2', {
        identifier: 'ext-item-2',
        fullStatement: 'Loading...',
        isCrossFramework: true
      });

      const association = createAssociation('item-1', 'ext-item-2', currentDocId, externalDocId, {
        destURI: { uri: 'http://example.com/ext-item-2', title: 'Loading...' }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(mockApiGet).toHaveBeenCalledWith('/framework/editor/item/ext-item-2/details');
      expect(composable.itemData.value?.identifier).toBe('ext-item-2');
      expect(composable.itemTitle.value).toBe('Resolved External Item 2');
    });

    it('uses the fetched item document title for the framework badge', async () => {
      mockItemDetailsCache.delete('ext-item-3');
      mockRegistryDocuments.set('ext-doc-3', {
        identifier: 'ext-doc-3',
        title: 'External Document 3',
        frameworkId: 'ext-doc-3'
      });

      const association = createAssociation('item-1', 'ext-item-3', currentDocId, externalDocId, {
        destURI: { uri: 'http://example.com/ext-item-3', title: 'Loading...' }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(composable.frameworkTitle.value).toBe('External Document 3');
    });

    it('prefers a better display value than a UUID title', async () => {
      const uuid = '123e4567-e89b-12d3-a456-426614174000';
      mockItemDetailsCache.delete(uuid);

      const association = createAssociation('item-1', uuid, currentDocId, externalDocId, {
        destURI: {
          identifier: uuid,
          uri: 'http://example.com/ext-item-4',
          title: 'Loading...'
        }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(composable.itemTitle.value).toBe('E4');
    });

    it('uses document registry for framework title when item has no CFDocumentURI title', async () => {
      mockItemDetailsCache.delete('ext-item-5');
      mockRegistryDocuments.set('ext-doc-3', {
        identifier: 'ext-doc-3',
        title: 'Fetched External Document 3',
        frameworkId: 'ext-doc-3'
      });

      const association = createAssociation('item-1', 'ext-item-5', currentDocId, externalDocId, {
        destURI: { uri: 'http://example.com/ext-item-5', title: 'Loading...' }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(composable.frameworkTitle.value).toBe('Fetched External Document 3');
    });

    it('sets fetchError on 403 response', async () => {
      const association = createAssociation('item-1', 'ext-item-403', currentDocId, externalDocId, {
        destURI: {
          identifier: 'ext-item-403',
          uri: 'http://example.com/403'
        }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(composable.fetchError.value).not.toBeNull();
      expect(composable.fetchError.value.type).toBe('permission');
    });

    it('sets fetchError on 404 response', async () => {
      const association = createAssociation('item-1', 'ext-item-404', currentDocId, externalDocId, {
        destURI: {
          identifier: 'ext-item-404',
          uri: 'http://example.com/404'
        }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(composable.fetchError.value).not.toBeNull();
      expect(composable.fetchError.value.type).toBe('not_found');
    });

    it('sets fetchError on network error', async () => {
      const association = createAssociation('item-1', 'ext-item-network', currentDocId, externalDocId, {
        destURI: {
          identifier: 'ext-item-network',
          uri: 'http://example.com/network'
        }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(composable.fetchError.value).not.toBeNull();
      expect(composable.fetchError.value.type).toBe('network');
    });
  });

  describe('non-CASE item handling', () => {
    it('does not fetch for non-CASE items', async () => {
      const association = createAssociation('item-1', 'ext-item-1', currentDocId, externalDocId, {
        destURI: { targetType: 'NonCASE' }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(mockApiGet).not.toHaveBeenCalledWith(expect.stringContaining('/framework/editor/item/'));
      expect(composable.isLoading.value).toBe(false);
    });

    it('displays URI for non-CASE items', async () => {
      const association = createAssociation('item-1', 'ext-item-1', currentDocId, externalDocId, {
        destURI: { targetType: 'NonCASE' }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.itemTitle.value).toContain('https://example.com');
    });

    it('does not fetch for unknown items (exemplar)', async () => {
      const association = createAssociation('item-1', 'ext-item-1', currentDocId, externalDocId, {
        associationType: 'exemplar'
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(mockApiGet).not.toHaveBeenCalledWith(expect.stringContaining('/framework/editor/item/'));
      expect(composable.targetTypeInfo.value.isUnknown).toBe(true);
    });
  });

  describe('fallback title handling', () => {
    it('shows the title for non-HTTP(S) endpoints when a title is present', async () => {
      const association = {
        originNodeURI: {
          identifier: 'item-1',
          uri: `/uri/${currentDocId}/item-1`
        },
        destinationNodeURI: {
          identifier: 'non-existent-item',
          title: 'Destination Item',
          uri: `/uri/${externalDocId}/non-existent-item`
        }
      };

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.itemTitle.value).toBe('Destination Item');
    });

    it('shows the raw URI for non-HTTP(S) endpoints when no title is present', async () => {
      const association = {
        originNodeURI: {
          identifier: 'item-1',
          uri: `/uri/${currentDocId}/item-1`
        },
        destinationNodeURI: {
          identifier: 'unknown-item',
          uri: '/uri/unknown-doc/unknown-item'
        }
      };

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.itemTitle.value).toBe('/uri/unknown-doc/unknown-item');
    });

    it('does not stay on Loading after an external fetch fails', async () => {
      const association = {
        originNodeURI: {
          identifier: 'item-1',
          uri: `/uri/${currentDocId}/item-1`
        },
        destinationNodeURI: {
          identifier: 'ext-item-network',
          title: 'Loading...',
          uri: 'http://example.com/network'
        }
      };

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 10));

      expect(composable.fetchError.value).not.toBeNull();
      expect(composable.itemTitle.value).toBe('ext-item-network');
    });
  });

  describe('null/undefined handling', () => {
    it('handles null association gracefully', async () => {
      composable = useCrossFrameworkItem({
        association: ref(null),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.itemData.value).toBeNull();
      expect(composable.itemTitle.value).toBe('Unknown');
      expect(composable.isLoading.value).toBe(false);
    });

    it('handles undefined association gracefully', async () => {
      composable = useCrossFrameworkItem({
        association: ref(undefined),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.itemData.value).toBeNull();
      expect(composable.itemTitle.value).toBe('Unknown');
    });

    it('handles missing node URIs gracefully', async () => {
      const association = {};

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.itemData.value).toBeNull();
    });
  });

  describe('framework title', () => {
    it('returns null when item is in current document', async () => {
      const association = createAssociation('item-1', 'item-2', currentDocId, currentDocId);

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.frameworkTitle.value).toBeNull();
    });

    it('returns null for non-CASE items', async () => {
      const association = createAssociation('item-1', 'ext-item-1', currentDocId, externalDocId, {
        destURI: { targetType: 'NonCASE' }
      });

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(composable.frameworkTitle.value).toBeNull();
    });
  });

  describe('reactivity', () => {
    it('updates when association changes', async () => {
      const association1 = createAssociation('item-1', 'item-2', currentDocId, currentDocId);
      const associationRef = ref(association1);

      composable = useCrossFrameworkItem({
        association: associationRef,
        direction: ref('normal')
      });

      await nextTick();
      expect(composable.itemData.value?.identifier).toBe('item-2');

      const association2 = createAssociation('item-2', 'item-1', currentDocId, currentDocId);
      associationRef.value = association2;

      await nextTick();
      expect(composable.itemData.value?.identifier).toBe('item-1');
    });

    it('updates when direction changes', async () => {
      const association = createAssociation('item-1', 'item-2', currentDocId, currentDocId);
      const directionRef = ref('normal');

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: directionRef
      });

      await nextTick();
      expect(composable.itemData.value?.identifier).toBe('item-2');

      directionRef.value = 'reversed';

      await nextTick();
      expect(composable.itemData.value?.identifier).toBe('item-1');
    });
  });

  describe('reload function', () => {
    it('exposes reload function', async () => {
      const association = createAssociation('item-1', 'item-2', currentDocId, currentDocId);

      composable = useCrossFrameworkItem({
        association: ref(association),
        direction: ref('normal')
      });

      await nextTick();

      expect(typeof composable.reload).toBe('function');
    });
  });
});
