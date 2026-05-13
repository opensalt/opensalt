import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useFilterStore } from '@/stores/filterStore.js';

describe('FilterStore', () => {
  let filterStore;

  beforeEach(() => {
    setActivePinia(createPinia());
    filterStore = useFilterStore();
  });

  it('initializes selectedAssociationGroup to "default"', () => {
    expect(filterStore.selectedAssociationGroup).toBe('default');
  });

  it('selects default on framework switch when at least one node has null group', () => {
    filterStore.setSelectedAssociationGroup('group-1');

    filterStore.syncSelectedAssociationGroup({
      frameworkId: 'framework-1',
      definedGroupIds: ['group-1'],
      treeNodes: [
        { identifier: 'item-1', associationGroupIdentifier: null }
      ]
    });

    expect(filterStore.selectedAssociationGroup).toBe('default');
  });

  it('selects the sole real group when there are no default-group associations', () => {
    filterStore.syncSelectedAssociationGroup({
      frameworkId: 'framework-1',
      definedGroupIds: ['group-1'],
      treeNodes: [
        { identifier: 'item-1', associationGroupIdentifier: 'group-1' }
      ]
    });

    expect(filterStore.selectedAssociationGroup).toBe('group-1');
  });

  it('keeps default when no default-group associations and multiple real groups exist', () => {
    filterStore.syncSelectedAssociationGroup({
      frameworkId: 'framework-1',
      definedGroupIds: ['group-1', 'group-2'],
      treeNodes: [
        { identifier: 'item-1', associationGroupIdentifier: 'group-1' }
      ]
    });

    expect(filterStore.selectedAssociationGroup).toBe('default');
  });

  it('preserves current selection on same-framework reload when still valid', () => {
    filterStore.syncSelectedAssociationGroup({
      frameworkId: 'framework-1',
      definedGroupIds: ['group-1', 'group-2'],
      treeNodes: []
    });

    filterStore.setSelectedAssociationGroup('group-2');

    filterStore.syncSelectedAssociationGroup({
      frameworkId: 'framework-1',
      definedGroupIds: ['group-1', 'group-2'],
      treeNodes: []
    });

    expect(filterStore.selectedAssociationGroup).toBe('group-2');
  });
});

describe('filterItemsRecursively', () => {
  let filterStore;

  beforeEach(() => {
    setActivePinia(createPinia());
    filterStore = useFilterStore();
  });

  /** Helper: collect all identifiers from a tree (depth-first). */
  function collectIds(items) {
    const ids = [];
    for (const item of items) {
      ids.push(item.identifier);
      if (item.children) ids.push(...collectIds(item.children));
    }
    return ids;
  }

  it('returns all items when no filters are active', () => {
    const items = [
      { identifier: 'a', title: 'Alpha', children: [] },
      { identifier: 'b', title: 'Bravo', children: [] },
    ];

    const result = filterStore.filterItemsRecursively(items, '', {});

    expect(result).toBe(items); // same reference – nothing filtered
  });

  it('keeps items that match the search query', () => {
    const items = [
      { identifier: 'a', title: 'Alpha', children: [] },
      { identifier: 'b', title: 'Bravo', children: [] },
    ];

    const result = filterStore.filterItemsRecursively(items, 'alpha', {});

    expect(collectIds(result)).toEqual(['a']);
  });

  it('hides items that do not match and have no matching descendants', () => {
    const items = [
      { identifier: 'a', title: 'Alpha', children: [] },
      { identifier: 'b', title: 'Bravo', children: [] },
    ];

    const result = filterStore.filterItemsRecursively(items, 'alpha', {});

    expect(collectIds(result)).not.toContain('b');
  });

  it('keeps parent that does not match but has a matching child', () => {
    const items = [
      {
        identifier: 'parent',
        title: 'Parent',
        children: [
          { identifier: 'child-match', title: 'Alpha Child', children: [] },
          { identifier: 'child-no-match', title: 'Bravo Child', children: [] },
        ],
      },
    ];

    const result = filterStore.filterItemsRecursively(items, 'alpha', {});

    const ids = collectIds(result);
    expect(ids).toContain('parent');       // kept because child matches
    expect(ids).toContain('child-match');   // matches directly
    expect(ids).not.toContain('child-no-match'); // does not match
  });

  it('keeps grandparent when only a grandchild matches', () => {
    const items = [
      {
        identifier: 'grandparent',
        title: 'Grandparent',
        children: [
          {
            identifier: 'parent',
            title: 'Parent',
            children: [
              { identifier: 'grandchild', title: 'Alpha Deep', children: [] },
            ],
          },
        ],
      },
    ];

    const result = filterStore.filterItemsRecursively(items, 'alpha', {});

    const ids = collectIds(result);
    expect(ids).toContain('grandparent');
    expect(ids).toContain('parent');
    expect(ids).toContain('grandchild');
  });

  it('removes entire branch when nothing in it matches', () => {
    const items = [
      {
        identifier: 'branch-a',
        title: 'Alpha Branch',
        children: [
          { identifier: 'a1', title: 'Alpha One', children: [] },
        ],
      },
      {
        identifier: 'branch-b',
        title: 'Branch B',
        children: [
          { identifier: 'b1', title: 'B One', children: [] },
        ],
      },
    ];

    const result = filterStore.filterItemsRecursively(items, 'alpha', {});

    const ids = collectIds(result);
    expect(ids).toContain('branch-a');
    expect(ids).toContain('a1');
    expect(ids).not.toContain('branch-b');
    expect(ids).not.toContain('b1');
  });

  it('removes all items when nothing matches the search', () => {
    const items = [
      { identifier: 'a', title: 'Alpha', children: [] },
      { identifier: 'b', title: 'Bravo', children: [] },
    ];

    const result = filterStore.filterItemsRecursively(items, 'charlie', {});

    expect(result).toEqual([]);
  });

  it('matches against humanCodingScheme', () => {
    const items = [
      { identifier: 'a', title: 'Item', humanCodingScheme: 'MAT.1', children: [] },
      { identifier: 'b', title: 'Item', humanCodingScheme: 'ENG.1', children: [] },
    ];

    const result = filterStore.filterItemsRecursively(items, 'mat', {});

    expect(collectIds(result)).toEqual(['a']);
  });

  it('matches against fullStatement', () => {
    const items = [
      { identifier: 'a', title: 'Item', fullStatement: 'The quick brown fox', children: [] },
      { identifier: 'b', title: 'Item', fullStatement: 'Lazy dog', children: [] },
    ];

    const result = filterStore.filterItemsRecursively(items, 'fox', {});

    expect(collectIds(result)).toEqual(['a']);
  });

  it('applies itemType filter', () => {
    const items = [
      { identifier: 'a', title: 'Alpha', itemType: 'assessment', children: [] },
      { identifier: 'b', title: 'Bravo', itemType: 'item', children: [] },
    ];

    const result = filterStore.filterItemsRecursively(items, '', { itemType: 'assessment' });

    expect(collectIds(result)).toEqual(['a']);
  });

  it('keeps ancestor of itemType-filtered item', () => {
    const items = [
      {
        identifier: 'parent',
        title: 'Parent',
        itemType: 'item',
        children: [
          { identifier: 'child', title: 'Child', itemType: 'assessment', children: [] },
        ],
      },
    ];

    const result = filterStore.filterItemsRecursively(items, '', { itemType: 'assessment' });

    const ids = collectIds(result);
    expect(ids).toContain('parent');
    expect(ids).toContain('child');
  });
});
