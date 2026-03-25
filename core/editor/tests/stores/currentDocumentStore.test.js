import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useCurrentDocumentStore } from '@/stores/currentDocumentStore';
import { useFilterStore } from '@/stores/filterStore.js';
import { useEditorContextStore } from '@/stores/editorContextStore';

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

  it('shows local <- external chain in default filter and preserves groupIds', () => {
    const items = [makeItem('itemA', 'Item A')];
    const associations = [
      makeChildOfAssociation({
        identifier: 'assoc-1',
        originId: 'external-1',
        destinationId: 'itemA',
      })
    ];

    const transformed = currentDocumentStore.transformCASEItems(items, associations, 'doc-1');
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

  it('renders chained local <- external1 <- external2 from viewed-framework associations', () => {
    const items = [makeItem('itemA', 'Item A')];
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

    const transformed = currentDocumentStore.transformCASEItems(items, associations, 'doc-1');
    const external1 = findNode(transformed, 'external-1');
    const external2 = findNode(transformed, 'external-2');

    expect(external1).toBeTruthy();
    expect(external2).toBeTruthy();
    expect(findNode(external1.children || [], 'external-2')).toBeTruthy();
  });

  it('ignores external->external when destination is not anchored into displayed tree', () => {
    const items = [makeItem('itemA', 'Item A')];
    const associations = [
      makeChildOfAssociation({
        identifier: 'assoc-1',
        originId: 'external-2',
        destinationId: 'external-1',
      })
    ];

    const transformed = currentDocumentStore.transformCASEItems(items, associations, 'doc-1');
    const external1 = findNode(transformed, 'external-1');
    const external2 = findNode(transformed, 'external-2');

    expect(external1).toBeNull();
    expect(external2).toBeNull();
  });

  it('respects group filtering for chained cross-framework nodes', () => {
    const items = [makeItem('itemA', 'Item A')];
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

    const transformed = currentDocumentStore.transformCASEItems(items, associations, 'doc-1');

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

    expect(findNode(defaultFiltered, 'external-1')).toBeNull();
    expect(findNode(defaultFiltered, 'external-2')).toBeNull();
    expect(findNode(groupFiltered, 'external-1')).toBeTruthy();
    expect(findNode(groupFiltered, 'external-2')).toBeTruthy();
  });

  it('prefers registry item title over generic "origin node" link title for cross-framework placeholders', () => {
    contextStore.registerItem({
      identifier: 'external-1',
      uri: 'https://example.org/items/external-1',
      fullStatement: 'External Item One'
    }, 'external-doc-1');

    const items = [makeItem('itemA', 'Item A')];
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

    const transformed = currentDocumentStore.transformCASEItems(items, associations, 'doc-1');
    const external = findNode(transformed, 'external-1');

    expect(external).toBeTruthy();
    expect(external.title).toBe('External Item One');
  });

  it('hydrates same-framework descendants from a loaded related package', () => {
    contextStore.loadedPackages.set('external-doc-1', {
      CFDocument: {
        identifier: 'external-doc-1',
        uri: 'https://example.org/documents/external-doc-1',
        title: 'External Doc',
        creator: 'Tester',
        lastChangeDateTime: '2024-01-01T00:00:00Z'
      },
      CFItems: [
        makeItemForDocument('external-doc-1', 'external-1', 'External Parent'),
        makeItemForDocument('external-doc-1', 'external-1-child', 'External Child')
      ],
      CFAssociations: [
        makeChildOfAssociation({
          identifier: 'external-assoc-1',
          originId: 'external-1-child',
          destinationId: 'external-1'
        })
      ]
    });

    const transformed = currentDocumentStore.transformCASEItems(
      [makeItemForDocument('doc-1', 'itemA', 'Item A')],
      [
        makeChildOfAssociation({
          identifier: 'assoc-1',
          originId: 'external-1',
          destinationId: 'itemA'
        })
      ],
      'doc-1'
    );

    const externalParent = findNode(transformed, 'external-1');
    const externalChild = findNode(transformed, 'external-1-child');

    expect(externalParent).toBeTruthy();
    expect(externalParent.title).toBe('External Parent');
    expect(externalParent.documentId).toBe('external-doc-1');
    expect(findNode(externalParent.children || [], 'external-1-child')).toBeTruthy();
    expect(externalChild?.title).toBe('External Child');
    expect(externalChild?.documentId).toBe('external-doc-1');
  });

  it('hydrates third-framework descendants attached beneath an anchored external parent', () => {
    contextStore.loadedPackages.set('external-doc-1', {
      CFDocument: {
        identifier: 'external-doc-1',
        uri: 'https://example.org/documents/external-doc-1',
        title: 'External Doc',
        creator: 'Tester',
        lastChangeDateTime: '2024-01-01T00:00:00Z'
      },
      CFItems: [
        makeItemForDocument('external-doc-1', 'external-1', 'External Parent')
      ],
      CFAssociations: []
    });

    contextStore.loadedPackages.set('third-doc-1', {
      CFDocument: {
        identifier: 'third-doc-1',
        uri: 'https://example.org/documents/third-doc-1',
        title: 'Third Doc',
        creator: 'Tester',
        lastChangeDateTime: '2024-01-01T00:00:00Z'
      },
      CFItems: [
        makeItemForDocument('third-doc-1', 'third-1', 'Third Framework Child')
      ],
      CFAssociations: [
        makeChildOfAssociation({
          identifier: 'third-assoc-1',
          originId: 'third-1',
          destinationId: 'external-1'
        })
      ]
    });

    const transformed = currentDocumentStore.transformCASEItems(
      [makeItemForDocument('doc-1', 'itemA', 'Item A')],
      [
        makeChildOfAssociation({
          identifier: 'assoc-1',
          originId: 'external-1',
          destinationId: 'itemA'
        })
      ],
      'doc-1'
    );

    const externalParent = findNode(transformed, 'external-1');
    const thirdChild = findNode(transformed, 'third-1');

    expect(externalParent).toBeTruthy();
    expect(findNode(externalParent.children || [], 'third-1')).toBeTruthy();
    expect(thirdChild?.title).toBe('Third Framework Child');
    expect(thirdChild?.documentId).toBe('third-doc-1');
    expect(thirdChild?.isCrossFramework).toBe(true);
  });

  it('shows a viewed-framework child under an external parent when the association uses legacy node identifier fields', () => {
    const transformed = currentDocumentStore.transformCASEItems(
      [makeItemForDocument('doc-1', 'itemA', 'Viewed Framework Child')],
      [
        {
          identifier: 'assoc-legacy-1',
          associationType: 'isChildOf',
          sequenceNumber: 1,
          uri: 'https://example.org/associations/assoc-legacy-1',
          originNodeIdentifier: 'itemA',
          originNodeURI: {
            title: 'Viewed Framework Child',
            uri: 'https://example.org/items/itemA'
          },
          destinationNodeIdentifier: 'external-1',
          destinationNodeURI: {
            title: 'External Parent',
            uri: 'https://example.org/items/external-1'
          },
          lastChangeDateTime: '2024-01-01T00:00:00Z'
        }
      ],
      'doc-1'
    );

    const externalParent = findNode(transformed, 'external-1');
    const localChild = findNode(transformed, 'itemA');

    expect(externalParent).toBeTruthy();
    expect(externalParent?.title).toBe('External Parent');
    expect(findNode(externalParent.children || [], 'itemA')).toBeTruthy();
    expect(localChild?.title).toBe('Viewed Framework Child');
    expect(localChild?.documentId).toBe('doc-1');
    expect(localChild?.isCrossFramework).not.toBe(true);
  });

  it('does not surface unrelated loaded frameworks as tree roots', () => {
    contextStore.loadedPackages.set('unrelated-doc-1', {
      CFDocument: {
        identifier: 'unrelated-doc-1',
        uri: 'https://example.org/documents/unrelated-doc-1',
        title: 'Unrelated Doc',
        creator: 'Tester',
        lastChangeDateTime: '2024-01-01T00:00:00Z'
      },
      CFItems: [
        makeItemForDocument('unrelated-doc-1', 'unrelated-parent', 'Unrelated Parent'),
        makeItemForDocument('unrelated-doc-1', 'unrelated-child', 'Unrelated Child')
      ],
      CFAssociations: [
        makeChildOfAssociation({
          identifier: 'unrelated-assoc-1',
          originId: 'unrelated-child',
          destinationId: 'unrelated-parent'
        })
      ]
    });

    const transformed = currentDocumentStore.transformCASEItems(
      [makeItemForDocument('doc-1', 'itemA', 'Item A')],
      [],
      'doc-1'
    );

    expect(findNode(transformed, 'unrelated-parent')).toBeNull();
    expect(findNode(transformed, 'unrelated-child')).toBeNull();
  });

  it('does not register unresolved cross-framework placeholders into the shared item registry', () => {
    const authoritativeItem = {
      identifier: 'external-1',
      uri: 'https://example.org/items/external-1',
      fullStatement: 'Authoritative External Item'
    };
    contextStore.registerItem(authoritativeItem, 'external-doc-1');

    currentDocumentStore.selectDocument({
      identifier: 'doc-1',
      uri: 'https://example.org/documents/doc-1',
      title: 'Doc 1',
      creator: 'Tester',
      publisher: 'Tester',
      subjectURI: [],
      subject: [],
      language: 'en',
      version: '1.0',
      adoptionStatus: 'Draft',
      statusStartDate: '',
      statusEndDate: '',
      lastChangeDateTime: '2024-01-01T00:00:00Z',
      notes: '',
      items: [
        {
          identifier: 'external-1',
          uri: 'https://example.org/items/external-1',
          title: 'Loading...',
          fullStatement: 'Loading...',
          children: [],
          isCrossFramework: true
        }
      ]
    });

    expect(contextStore.itemRegistry.get('external-1')?.item.fullStatement).toBe('Authoritative External Item');
    expect(contextStore.itemRegistry.get('external-1')?.frameworkId).toBe('external-doc-1');
  });

  it('normalizes document identity when selectDocument receives an id-only document', () => {
    currentDocumentStore.selectDocument({
      id: 'doc-1',
      uri: 'https://example.org/documents/doc-1',
      title: 'Doc 1',
      creator: 'Tester',
      publisher: 'Tester',
      subjectURI: [],
      subject: [],
      language: 'en',
      version: '1.0',
      adoptionStatus: 'Draft',
      statusStartDate: '',
      statusEndDate: '',
      lastChangeDateTime: '2024-01-01T00:00:00Z',
      notes: '',
      items: []
    });

    expect(currentDocumentStore.currentDocument?.identifier).toBe('doc-1');
    expect(currentDocumentStore.currentDocument?.id).toBe('doc-1');
    expect(contextStore.activeWriteDocumentId).toBe('doc-1');
    expect(contextStore.documentRegistry.get('doc-1')?.identifier).toBe('doc-1');
  });
});
