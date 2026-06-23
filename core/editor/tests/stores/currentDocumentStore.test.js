import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useCurrentDocumentStore } from '@/stores/currentDocumentStore';
import { useFilterStore } from '@/stores/filterStore.js';
import { useEditorContextStore } from '@/stores/editorContextStore';
import { api } from '@/services/api.js';

vi.mock('@/composables/useRelatedFrameworksQueue.js', () => ({
  useRelatedFrameworksQueue: () => ({})
}));

vi.mock('@/services/api.js', () => ({
  api: {
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    get: vi.fn(),
  }
}));

vi.mock('@/services/frameworkCacheService.js', () => ({
  frameworkCacheService: {
    deleteFramework: vi.fn(),
    clearCache: vi.fn(),
  }
}));

function makeItem(identifier, title = identifier) {
  return {
    identifier,
    uri: `https://example.org/items/${identifier}`,
    fullStatement: title,
    lastChangeDateTime: '2024-01-01T00:00:00Z',
    CFDocumentURI: {
      identifier: 'doc-1',
      uri: 'https://example.org/documents/doc-1',
      title: 'Doc 1'
    }
  };
}

function makeItemForDocument(documentId, identifier, title = identifier) {
  return {
    ...makeItem(identifier, title),
    CFDocumentURI: {
      identifier: documentId,
      uri: `https://example.org/documents/${documentId}`,
      title: documentId
    }
  };
}

function makeNode(identifier, title = identifier) {
  return {
    identifier,
    uri: `https://example.org/items/${identifier}`,
    title,
    targetType: 'CASE'
  };
}

function makeChildOfAssociation({
  identifier,
  originId,
  destinationId,
  groupId = null,
  sequenceNumber = 1,
}) {
  const association = {
    identifier,
    associationType: 'isChildOf',
    sequenceNumber,
    uri: `https://example.org/associations/${identifier}`,
    originNodeURI: makeNode(originId),
    destinationNodeURI: makeNode(destinationId),
    lastChangeDateTime: '2024-01-01T00:00:00Z'
  };

  if (groupId) {
    association.CFAssociationGroupingURI = {
      identifier: groupId,
      uri: `https://example.org/groups/${groupId}`,
      title: groupId
    };
  }

  return association;
}

function findNode(items, identifier) {
  for (const item of items) {
    if (item.identifier === identifier) return item;
    if (item.children?.length) {
      const found = findNode(item.children, identifier);
      if (found) return found;
    }
  }
  return null;
}

describe('CurrentDocumentStore transformCASEItems', () => {
  let currentDocumentStore;
  let filterStore;
  let contextStore;

  beforeEach(() => {
    setActivePinia(createPinia());
    currentDocumentStore = useCurrentDocumentStore();
    filterStore = useFilterStore();
    contextStore = useEditorContextStore();
  });

  it('shows local <- external chain in default filter and preserves groupIds', async () => {
    const items = [
      makeItem('itemA', 'Item A'),
      makeItem('external-1', 'External 1')
    ];
    const associations = [
      makeChildOfAssociation({
        identifier: 'assoc-1',
        originId: 'external-1',
        destinationId: 'itemA',
      })
    ];

    const transformed = await currentDocumentStore.transformCASEItems(items, associations, 'doc-1');
    const filtered = filterStore.filterItemsRecursively(
      transformed,
      '',
      filterStore.selectedFilters,
      'default'
    );

    const local = findNode(filtered, 'itemA');
    const external = findNode(filtered, 'external-1');

    expect(local).toBeTruthy();
    expect(external).toBeTruthy();
    expect(external.groupIds?.has('default')).toBe(true);
  });

  it('renders chained local <- external1 <- external2 from viewed-framework associations', async () => {
    const items = [
      makeItem('itemA', 'Item A'),
      makeItem('external-1', 'External 1'),
      makeItem('external-2', 'External 2')
    ];
    const associations = [
      makeChildOfAssociation({
        identifier: 'assoc-1',
        originId: 'external-1',
        destinationId: 'itemA',
      }),
      makeChildOfAssociation({
        identifier: 'assoc-2',
        originId: 'external-2',
        destinationId: 'external-1',
      })
    ];

    const transformed = await currentDocumentStore.transformCASEItems(items, associations, 'doc-1');
    const external1 = findNode(transformed, 'external-1');
    const external2 = findNode(transformed, 'external-2');

    expect(external1).toBeTruthy();
    expect(external2).toBeTruthy();
    expect(findNode(external1.children || [], 'external-2')).toBeTruthy();
  });

  it('ignores external->external when destination is not anchored into displayed tree', async () => {
    const items = [makeItem('itemA', 'Item A')];
    const associations = [
      makeChildOfAssociation({
        identifier: 'assoc-1',
        originId: 'external-2',
        destinationId: 'external-1',
      })
    ];

    const transformed = await currentDocumentStore.transformCASEItems(items, associations, 'doc-1');
    const external1 = findNode(transformed, 'external-1');
    const external2 = findNode(transformed, 'external-2');

    expect(external1).toBeNull();
    expect(external2).toBeNull();
  });

  it('filter uses associationGroupIdentifier which transformCASEItems does not set', async () => {
    const items = [
      makeItem('itemA', 'Item A'),
      makeItem('external-1', 'External 1'),
      makeItem('external-2', 'External 2')
    ];
    const associations = [
      makeChildOfAssociation({
        identifier: 'assoc-1',
        originId: 'external-1',
        destinationId: 'itemA',
        groupId: 'group-1'
      }),
      makeChildOfAssociation({
        identifier: 'assoc-2',
        originId: 'external-2',
        destinationId: 'external-1',
        groupId: 'group-1'
      })
    ];

    const transformed = await currentDocumentStore.transformCASEItems(items, associations, 'doc-1');

    const defaultFiltered = filterStore.filterItemsRecursively(
      transformed,
      '',
      filterStore.selectedFilters,
      'default'
    );
    const groupFiltered = filterStore.filterItemsRecursively(
      transformed,
      '',
      filterStore.selectedFilters,
      'group-1'
    );

    expect(findNode(defaultFiltered, 'external-1')).toBeTruthy();
    expect(findNode(defaultFiltered, 'external-2')).toBeTruthy();
    expect(findNode(groupFiltered, 'external-1')).toBeNull();
    expect(findNode(groupFiltered, 'external-2')).toBeNull();
  });

  it('uses item fullStatement for title when present in items list', async () => {
    const items = [
      makeItem('itemA', 'Item A'),
      makeItem('external-1', 'External Item One')
    ];
    const associations = [
      {
        identifier: 'assoc-1',
        associationType: 'isChildOf',
        sequenceNumber: 1,
        uri: 'https://example.org/associations/assoc-1',
        originNodeURI: makeNode('external-1', 'origin node'),
        destinationNodeURI: makeNode('itemA'),
        lastChangeDateTime: '2024-01-01T00:00:00Z'
      }
    ];

    const transformed = await currentDocumentStore.transformCASEItems(items, associations, 'doc-1');
    const external = findNode(transformed, 'external-1');

    expect(external).toBeTruthy();
    expect(external.title).toBe('External Item One');
  });

  it('does not surface unrelated loaded frameworks as tree roots', async () => {
    const transformed = await currentDocumentStore.transformCASEItems(
      [makeItemForDocument('doc-1', 'itemA', 'Item A')],
      [],
      'doc-1'
    );

    expect(findNode(transformed, 'unrelated-parent')).toBeNull();
    expect(findNode(transformed, 'unrelated-child')).toBeNull();
  });

  it('preserves item details cache when selectDocument is called', () => {
    contextStore.itemDetailsCache.set('external-1', {
      identifier: 'external-1',
      fullStatement: 'Authoritative External Item',
      documentIdentifier: 'external-doc-1'
    });

    currentDocumentStore.selectDocument({
      document: {
        identifier: 'doc-1',
        uri: 'https://example.org/documents/doc-1',
        title: 'Doc 1',
        lastChangeDateTime: '2024-01-01T00:00:00Z'
      },
      tree: [{
        identifier: 'external-1',
        uri: 'https://example.org/items/external-1',
        fullStatement: 'Loading...',
        isCrossFramework: true,
        children: []
      }]
    });

    expect(contextStore.itemDetailsCache.get('external-1')?.fullStatement).toBe('Authoritative External Item');
    expect(contextStore.itemDetailsCache.get('external-1')?.documentIdentifier).toBe('external-doc-1');
  });

  it('normalizes document identity when selectDocument receives a TreeResponse', () => {
    currentDocumentStore.selectDocument({
      document: {
        identifier: 'doc-1',
        uri: 'https://example.org/documents/doc-1',
        title: 'Doc 1',
        lastChangeDateTime: '2024-01-01T00:00:00Z'
      },
      tree: []
    });

    expect(currentDocumentStore.currentDocument?.identifier).toBe('doc-1');
    expect(currentDocumentStore.currentDocument?.id).toBe('doc-1');
    expect(contextStore.activeWriteDocumentId).toBe('doc-1');
    expect(contextStore.documentRegistry.get('doc-1')?.identifier).toBe('doc-1');
  });
});

describe('deleteItem', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    api.delete.mockReset();
  });

  it('appends ?includingChildren=1 when includeChildren is true', async () => {
    api.delete.mockResolvedValue({ status: 'OK' });
    const store = useCurrentDocumentStore();
    await store.deleteItem('item-1', { includeChildren: true });
    expect(api.delete).toHaveBeenCalledWith('/framework/editor/item/item-1?includingChildren=1');
  });

  it('omits the query when includeChildren is not set', async () => {
    api.delete.mockResolvedValue({ status: 'OK' });
    const store = useCurrentDocumentStore();
    await store.deleteItem('item-2');
    expect(api.delete).toHaveBeenCalledWith('/framework/editor/item/item-2');
  });
});
