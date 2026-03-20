import { performance } from 'node:perf_hooks';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

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

import { useCurrentDocumentStore } from '@/stores/currentDocumentStore';
import { useFilterStore } from '@/stores/filterStore.js';
import { useEditorContextStore } from '@/stores/editorContextStore';
import { useItemSearch } from '@/composables/useItemSearch.js';

function makeNode(identifier, title = identifier) {
  return {
    identifier,
    uri: `https://example.org/items/${identifier}`,
    title,
    targetType: 'CASE'
  };
}

function makeGroup(groupId) {
  if (!groupId || groupId === 'default') return undefined;
  return {
    identifier: groupId,
    uri: `https://example.org/groups/${groupId}`,
    title: groupId
  };
}

function makeItem(identifier, docId, depth, ordinal) {
  return {
    identifier,
    uri: `https://example.org/items/${identifier}`,
    humanCodingScheme: `${depth}.${ordinal}`,
    fullStatement: `Synthetic item ${identifier}`,
    abbreviatedStatement: `Item ${identifier}`,
    lastChangeDateTime: '2024-01-01T00:00:00Z',
    subject: [],
    educationLevel: [],
    CFDocumentURI: {
      identifier: docId,
      uri: `https://example.org/documents/${docId}`,
      title: docId
    }
  };
}

function makeAssociation({
  identifier,
  type,
  originId,
  destinationId,
  groupId = 'default',
  sequenceNumber = 1
}) {
  const association = {
    identifier,
    associationType: type,
    sequenceNumber,
    uri: `https://example.org/associations/${identifier}`,
    originNodeURI: makeNode(originId),
    destinationNodeURI: makeNode(destinationId),
    lastChangeDateTime: '2024-01-01T00:00:00Z'
  };

  const group = makeGroup(groupId);
  if (group) {
    association.CFAssociationGroupingURI = group;
  }

  return association;
}

function buildSyntheticFramework({
  docId = 'perf-doc',
  roots = 6,
  depth = 4,
  childrenPerNode = 5,
  extraAssociationStride = 3
} = {}) {
  const items = [];
  const associations = [];
  const allIds = [];
  const groupIds = ['default', 'group-a', 'group-b'];

  let levelIds = [];
  let itemCounter = 0;
  let assocCounter = 0;

  for (let rootIndex = 0; rootIndex < roots; rootIndex++) {
    const identifier = `item-${itemCounter++}`;
    items.push(makeItem(identifier, docId, 0, rootIndex));
    levelIds.push(identifier);
    allIds.push(identifier);
  }

  for (let level = 1; level <= depth; level++) {
    const nextLevelIds = [];
    let childOrdinal = 0;

    for (const parentId of levelIds) {
      for (let childIndex = 0; childIndex < childrenPerNode; childIndex++) {
        const identifier = `item-${itemCounter++}`;
        items.push(makeItem(identifier, docId, level, childOrdinal++));
        associations.push(
          makeAssociation({
            identifier: `assoc-child-${assocCounter++}`,
            type: 'isChildOf',
            originId: identifier,
            destinationId: parentId,
            groupId: groupIds[(level + childIndex) % groupIds.length],
            sequenceNumber: childIndex + 1
          })
        );
        nextLevelIds.push(identifier);
        allIds.push(identifier);
      }
    }

    levelIds = nextLevelIds;
  }

  for (let index = 0; index < allIds.length - extraAssociationStride; index++) {
    if (index % extraAssociationStride !== 0) continue;
    associations.push(
      makeAssociation({
        identifier: `assoc-rel-${assocCounter++}`,
        type: index % 2 === 0 ? 'relatesTo' : 'exactMatchOf',
        originId: allIds[index],
        destinationId: allIds[index + extraAssociationStride],
        groupId: groupIds[index % groupIds.length]
      })
    );
  }

  return {
    docId,
    items,
    associations,
    stats: {
      itemCount: items.length,
      associationCount: associations.length
    }
  };
}

function mean(values) {
  return values.reduce((total, value) => total + value, 0) / values.length;
}

function percentile(values, rank) {
  const sorted = [...values].sort((a, b) => a - b);
  const index = Math.min(sorted.length - 1, Math.floor((sorted.length - 1) * rank));
  return sorted[index];
}

function measureOperation(label, fn, iterations = 5) {
  fn();
  const samples = [];
  let result;

  for (let index = 0; index < iterations; index++) {
    const start = performance.now();
    result = fn();
    samples.push(performance.now() - start);
  }

  return {
    label,
    result,
    meanMs: Number(mean(samples).toFixed(2)),
    p95Ms: Number(percentile(samples, 0.95).toFixed(2))
  };
}

describe('editor hot path perf harness', () => {
  let currentDocumentStore;
  let filterStore;
  let contextStore;
  let itemSearch;

  beforeEach(() => {
    setActivePinia(createPinia());
    currentDocumentStore = useCurrentDocumentStore();
    filterStore = useFilterStore();
    contextStore = useEditorContextStore();
    itemSearch = useItemSearch();
  });

  it('profiles transform, tree filtering, search, and association lookup on a large synthetic framework', () => {
    const fixture = buildSyntheticFramework();
    const transformMetric = measureOperation('transformCASEItems', () =>
      currentDocumentStore.transformCASEItems(fixture.items, fixture.associations, fixture.docId)
    );

    const transformedTree = transformMetric.result;
    const selectedItemId = fixture.items[Math.floor(fixture.items.length / 2)].identifier;

    fixture.items.forEach((item) => {
      contextStore.itemRegistry.set(item.identifier, {
        item,
        frameworkId: fixture.docId
      });
    });
    fixture.associations.forEach((association) => {
      contextStore.associationRegistry.set(association.identifier, {
        association,
        frameworkId: fixture.docId
      });
    });

    const filterMetric = measureOperation('filterItemsRecursively', () =>
      filterStore.filterItemsRecursively(
        transformedTree,
        'synthetic item item-1',
        filterStore.selectedFilters,
        'group-a'
      )
    );

    const searchMetric = measureOperation('useItemSearch.countMatches', () =>
      itemSearch.countMatches(transformedTree, 'synthetic item item-1')
    );

    const associationMetric = measureOperation('editorContextStore.getAssociations', () =>
      contextStore.getAssociations(selectedItemId, `https://example.org/items/${selectedItemId}`)
    );

    console.table([
      {
        operation: transformMetric.label,
        meanMs: transformMetric.meanMs,
        p95Ms: transformMetric.p95Ms,
        items: fixture.stats.itemCount,
        associations: fixture.stats.associationCount
      },
      {
        operation: filterMetric.label,
        meanMs: filterMetric.meanMs,
        p95Ms: filterMetric.p95Ms,
        items: fixture.stats.itemCount,
        associations: fixture.stats.associationCount
      },
      {
        operation: searchMetric.label,
        meanMs: searchMetric.meanMs,
        p95Ms: searchMetric.p95Ms,
        items: fixture.stats.itemCount,
        associations: fixture.stats.associationCount
      },
      {
        operation: associationMetric.label,
        meanMs: associationMetric.meanMs,
        p95Ms: associationMetric.p95Ms,
        items: fixture.stats.itemCount,
        associations: fixture.stats.associationCount
      }
    ]);

    expect(Array.isArray(transformedTree)).toBe(true);
    expect(transformedTree.length).toBeGreaterThan(0);
    expect(searchMetric.result).toBeGreaterThan(0);
    expect(Array.isArray(filterMetric.result)).toBe(true);
    expect(associationMetric.result.length).toBeGreaterThan(0);
  });
});
