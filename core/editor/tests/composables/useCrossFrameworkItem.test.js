import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { ref, nextTick } from 'vue';

// Mock the stores - use reactive objects for Pinia-style access
const createMockCurrentDocumentStore = () => {
  const store = {
    currentDocument: null,
    associatedDocuments: new Map(),
    fetchAssociatedDocument: vi.fn(),
    getAssociatedDocument: vi.fn(),
    transformCASEItems: vi.fn((items, associations, docId) => items)
  };
  return store;
};

const createMockDocumentStore = () => ({
  fetchDocument: vi.fn(),
  documentCache: new Map(),
  pendingRequests: new Map()
});

// Store instances
let mockCurrentDocumentStore;
let mockDocumentStore;

// Mock Pinia
vi.mock('pinia', () => ({
  defineStore: vi.fn(),
  storeToRefs: vi.fn((store) => ({
    currentDocument: store.currentDocument
  }))
}));

// Mock the stores
vi.mock('../../src/stores/currentDocumentStore', () => ({
  useCurrentDocumentStore: vi.fn(() => mockCurrentDocumentStore)
}));

vi.mock('../../src/stores/documentStore', () => ({
  useDocumentStore: vi.fn(() => mockDocumentStore)
}));

vi.mock('../../src/stores/editorContextStore', () => ({
  useEditorContextStore: vi.fn(() => ({
    resolveEndpoint: vi.fn((endpoint) => {
      if (endpoint === 'ext-item-1') {
        return {
          entityType: 'item',
          identifier: 'ext-item-1',
          entity: { identifier: 'ext-item-1', title: 'Test External Item' },
          frameworkId: 'ext-doc-1'
        };
      } else if (endpoint === 'doc-1') {
        return {
          entityType: 'document',
          entity: { identifier: 'doc-1', title: 'Test Document' },
          frameworkId: 'doc-1'
        };
      }
      return null;
    }),
    itemRegistry: { get: vi.fn((id) => {
      if (id === 'ext-item-1') {
        return { item: { identifier: 'ext-item-1', title: 'Test External Item' }, frameworkId: 'ext-doc-1' };
      }
      return null;
    }) },
    documentRegistry: { get: vi.fn((id) => {
      if (id === 'ext-doc-1') {
        return { title: 'Test External Document', identifier: 'ext-doc-1' };
      }
      return null;
    }) },
    fetchExternalItemData: vi.fn(async (uri) => {
      if (uri === 'http://example.com/ext-item-1') {
        return {
          item: { identifier: 'ext-item-1', title: 'Test External Item' }
        };
      } else if (uri === 'http://example.com/403') {
        throw { status: 403, message: 'Forbidden' };
      } else if (uri === 'http://example.com/404') {
        throw { status: 404, message: 'Not Found' };
      } else if (uri === 'http://example.com/network') {
        throw { message: 'Network Error' };
      }
      throw new Error('Unknown URI');
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

// Mock logger
vi.mock('../../src/utils/logger.js', () => ({
  logger: {
    warn: vi.fn(),
    error: vi.fn(),
    info: vi.fn()
  }
}));

// Mock global fetch
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Import after mocking
import {
  useCrossFrameworkItem,
  determineTargetType,
  findInCachedFrameworks,
  findItemById,
  clearCrossFrameworkItemCache,
  getCrossFrameworkItemCacheSize
} from '@/composables/useCrossFrameworkItem.js';

describe('useCrossFrameworkItem', () => {
  let composable;

  // Sample data
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

  const mockExternalDocument = {
    identifier: externalDocId,
    title: 'External Framework',
    CFDocument: {
      identifier: externalDocId,
      title: 'External Framework'
    },
    CFItems: [
      {
        identifier: 'ext-item-1',
        fullStatement: 'External Item 1',
        humanCodingScheme: 'E1',
        CFDocumentURI: {
          identifier: externalDocId,
          title: 'External Framework'
        }
      },
      {
        identifier: 'ext-item-2',
        fullStatement: 'External Item 2',
        humanCodingScheme: 'E2',
        CFDocumentURI: {
          identifier: externalDocId,
          title: 'External Framework'
        }
      }
    ],
    CFAssociations: []
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

    // Create fresh store instances for each test
    mockCurrentDocumentStore = createMockCurrentDocumentStore();
    mockDocumentStore = createMockDocumentStore();

    // Set up default store state
    mockCurrentDocumentStore.currentDocument = { ...mockCurrentDocument };
    mockCurrentDocumentStore.associatedDocuments = new Map();
    mockDocumentStore.documentCache = new Map();
    mockDocumentStore.pendingRequests = new Map();

    // Reset fetch mock
    mockFetch.mockReset();
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
    it('finds item in associatedDocuments', () => {
      const result = findInCachedFrameworks('ext-item-1', {
        resolveEndpoint: vi.fn((identifier) => {
          if (identifier === 'ext-item-1') {
            return {
              entityType: 'item',
              entity: { identifier: 'ext-item-1', title: 'Test External Item' },
              frameworkId: 'ext-doc-1'
            };
          }
          return null;
        }),
        documentRegistry: {
          get: vi.fn((frameworkId) => {
            if (frameworkId === 'ext-doc-1') {
              return { title: 'External Framework' };
            }
            return null;
          })
        }
      });

      expect(result).not.toBeNull();
      expect(result.item.identifier).toBe('ext-item-1');
      expect(result.documentTitle).toBe('External Framework');
    });

    it('finds item in documentCache', () => {
      const result = findInCachedFrameworks('doc-1', {
        resolveEndpoint: vi.fn((identifier) => {
          if (identifier === 'doc-1') {
            return {
              entityType: 'document',
              entity: { identifier: 'doc-1', title: 'Cached Framework' }
            };
          }
          return null;
        }),
        documentRegistry: { get: vi.fn() }
      });

      expect(result).not.toBeNull();
      expect(result.item.identifier).toBe('doc-1');
      expect(result.documentTitle).toBe('Cached Framework');
    });

    it('returns null when item not in any cache', () => {
      const result = findInCachedFrameworks('non-existent', {
        resolveEndpoint: vi.fn(),
        documentRegistry: { get: vi.fn() }
      });
      expect(result).toBeNull();
    });

    it('returns null for null identifier', () => {
      const result = findInCachedFrameworks(null, {
        resolveEndpoint: vi.fn(),
        documentRegistry: { get: vi.fn() }
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
    it('fetches item by URI for cross-framework CASE items', async () => {
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

      expect(mockFetch).not.toHaveBeenCalled();
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

      expect(mockFetch).not.toHaveBeenCalled();
      expect(composable.targetTypeInfo.value.isUnknown).toBe(true);
    });
  });

  describe('fallback title handling', () => {
    it('returns title from association when item not found', async () => {
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

    it('handles missing title gracefully', async () => {
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

      expect(composable.itemTitle.value).toBe('unknown-item');
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
