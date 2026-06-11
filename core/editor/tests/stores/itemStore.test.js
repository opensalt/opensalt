import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useItemStore } from '@/stores/itemStore.js';
import { api } from '@/services/api.js';

vi.mock('@/services/api.js', () => ({
  api: {
    post: vi.fn()
  }
}));

describe('ItemStore', () => {
  let itemStore;

  beforeEach(() => {
    setActivePinia(createPinia());
    itemStore = useItemStore();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('getItemByIdentifierFast', () => {
    it('returns item from lookup map after building', () => {
      const items = [{ identifier: 'item-1', title: 'Item 1' }];
      itemStore.buildItemLookupMap(items);

      const result = itemStore.getItemByIdentifierFast('item-1');
      expect(result).toEqual({ identifier: 'item-1', title: 'Item 1' });
    });

    it('returns null for non-existent identifier', () => {
      itemStore.buildItemLookupMap([{ identifier: 'item-1' }]);
      const result = itemStore.getItemByIdentifierFast('non-existent');
      expect(result).toBeNull();
    });

    it('returns null before map is built', () => {
      const result = itemStore.getItemByIdentifierFast('item-1');
      expect(result).toBeNull();
    });
  });

  describe('buildItemLookupMap', () => {
    it('builds lookup map from flat items array', () => {
      const items = [
        { identifier: 'item-1', title: 'Item 1' },
        { identifier: 'item-2', title: 'Item 2' }
      ];

      itemStore.buildItemLookupMap(items);

      expect(itemStore.getItemByIdentifierFast('item-1')).toEqual({ identifier: 'item-1', title: 'Item 1' });
      expect(itemStore.getItemByIdentifierFast('item-2')).toEqual({ identifier: 'item-2', title: 'Item 2' });
    });

    it('builds lookup map from nested items', () => {
      const items = [
        {
          identifier: 'item-1',
          title: 'Parent',
          children: [
            { identifier: 'item-1-1', title: 'Child 1' },
            { identifier: 'item-1-2', title: 'Child 2' }
          ]
        }
      ];

      itemStore.buildItemLookupMap(items);

      expect(itemStore.getItemByIdentifierFast('item-1')).toBeTruthy();
      expect(itemStore.getItemByIdentifierFast('item-1-1').title).toBe('Child 1');
      expect(itemStore.getItemByIdentifierFast('item-1-2').title).toBe('Child 2');
    });

    it('handles empty array', () => {
      itemStore.buildItemLookupMap([]);
      expect(itemStore.getItemByIdentifierFast('any')).toBeNull();
    });

    it('handles null/undefined input', () => {
      itemStore.buildItemLookupMap(null);
      expect(itemStore.getItemByIdentifierFast('any')).toBeNull();

      itemStore.buildItemLookupMap(undefined);
      expect(itemStore.getItemByIdentifierFast('any')).toBeNull();
    });
  });

  describe('findItemByIdentifier', () => {
    it('finds item at root level', () => {
      const items = [
        { identifier: 'item-1', title: 'Item 1' },
        { identifier: 'item-2', title: 'Item 2' }
      ];

      const result = itemStore.findItemByIdentifier(items, 'item-2');
      expect(result).toEqual({ identifier: 'item-2', title: 'Item 2' });
    });

    it('finds item in nested structure', () => {
      const items = [
        {
          identifier: 'item-1',
          title: 'Parent',
          children: [
            { identifier: 'item-1-1', title: 'Child' }
          ]
        }
      ];

      const result = itemStore.findItemByIdentifier(items, 'item-1-1');
      expect(result).toEqual({ identifier: 'item-1-1', title: 'Child' });
    });

    it('returns null for non-existent item', () => {
      const items = [{ identifier: 'item-1', title: 'Item 1' }];
      const result = itemStore.findItemByIdentifier(items, 'non-existent');
      expect(result).toBeNull();
    });

    it('handles empty array', () => {
      const result = itemStore.findItemByIdentifier([], 'item-1');
      expect(result).toBeNull();
    });
  });

  describe('updateItem', () => {
    it('updates item at root level', () => {
      const currentDocument = {
        items: [
          { identifier: 'item-1', title: 'Old Title', children: [], associations: [] }
        ]
      };
      const updatedItem = { identifier: 'item-1', title: 'New Title' };

      const result = itemStore.updateItem(currentDocument, updatedItem);

      expect(result).toBe(true);
      expect(currentDocument.items[0].title).toBe('New Title');
    });

    it('updates item in nested structure', () => {
      const currentDocument = {
        items: [
          {
            identifier: 'parent',
            title: 'Parent',
            children: [
              { identifier: 'child-1', title: 'Old Title', children: [], associations: [] }
            ],
            associations: []
          }
        ]
      };
      const updatedItem = { identifier: 'child-1', title: 'New Title' };

      const result = itemStore.updateItem(currentDocument, updatedItem);

      expect(result).toBe(true);
      expect(currentDocument.items[0].children[0].title).toBe('New Title');
    });

    it('returns false when item not found', () => {
      const currentDocument = {
        items: [{ identifier: 'item-1', title: 'Item 1' }]
      };
      const updatedItem = { identifier: 'non-existent', title: 'New Title' };

      const result = itemStore.updateItem(currentDocument, updatedItem);

      expect(result).toBe(false);
    });

    it('returns false when document is null', () => {
      const result = itemStore.updateItem(null, { identifier: 'item-1' });
      expect(result).toBe(false);
    });

    it('returns false when item is null', () => {
      const result = itemStore.updateItem({ items: [] }, null);
      expect(result).toBe(false);
    });

    it('preserves children and associations if not overwritten', () => {
      const currentDocument = {
        items: [{
          identifier: 'item-1',
          title: 'Old',
          children: [{ identifier: 'child' }],
          associations: [{ identifier: 'assoc' }]
        }]
      };
      const updatedItem = { identifier: 'item-1', title: 'New' };

      itemStore.updateItem(currentDocument, updatedItem);

      expect(currentDocument.items[0].children).toEqual([{ identifier: 'child' }]);
      expect(currentDocument.items[0].associations).toEqual([{ identifier: 'assoc' }]);
    });
  });

  describe('addItem', () => {
    it('adds item at root level', () => {
      const currentDocument = {
        id: 'doc-1',
        items: [{ identifier: 'item-1', sequenceNumber: 10 }]
      };
      const newItem = { identifier: 'item-2', title: 'New Item' };

      const result = itemStore.addItem(currentDocument, newItem, null);

      expect(result).toBe(true);
      expect(currentDocument.items).toHaveLength(2);
      expect(newItem.sequenceNumber).toBe(11);
    });

    it('adds item as child of parent', () => {
      const currentDocument = {
        id: 'doc-1',
        items: [{
          identifier: 'parent',
          sequenceNumber: 10,
          children: [{ identifier: 'child-1', sequenceNumber: 10 }]
        }]
      };
      const newItem = { identifier: 'child-2', title: 'New Child' };

      const result = itemStore.addItem(currentDocument, newItem, 'parent');

      expect(result).toBe(true);
      expect(currentDocument.items[0].children).toHaveLength(2);
      expect(newItem.sequenceNumber).toBe(11);
    });

    it('returns false when parent not found', () => {
      const currentDocument = {
        id: 'doc-1',
        items: []
      };
      const newItem = { identifier: 'item-1' };

      const result = itemStore.addItem(currentDocument, newItem, 'non-existent');

      expect(result).toBe(false);
    });

    it('returns false when document is null', () => {
      const result = itemStore.addItem(null, { identifier: 'item-1' }, null);
      expect(result).toBe(false);
    });

    it('sorts items by sequence number after adding', () => {
      const currentDocument = {
        id: 'doc-1',
        items: [
          { identifier: 'item-1', sequenceNumber: 30 },
          { identifier: 'item-2', sequenceNumber: 10 }
        ]
      };
      const newItem = { identifier: 'item-3', title: 'New' };

      itemStore.addItem(currentDocument, newItem, null);

      expect(currentDocument.items[0].identifier).toBe('item-2');
      expect(currentDocument.items[1].identifier).toBe('item-1');
      expect(currentDocument.items[2].identifier).toBe('item-3');
    });
  });

  describe('getMaxSequence', () => {
    it('returns max sequence number', () => {
      const items = [
        { sequenceNumber: 10 },
        { sequenceNumber: 50 },
        { sequenceNumber: 30 }
      ];

      expect(itemStore.getMaxSequence(items)).toBe(50);
    });

    it('returns 0 for empty array', () => {
      expect(itemStore.getMaxSequence([])).toBe(0);
    });

    it('handles items without sequenceNumber', () => {
      const items = [
        { identifier: 'a' },
        { sequenceNumber: 20 }
      ];

      expect(itemStore.getMaxSequence(items)).toBe(20);
    });
  });

  describe('moveItem', () => {
    it('moves item before target', async () => {
      api.post.mockResolvedValueOnce({
        siblingSequenceNumbers: { 'item-2': 1, 'item-1': 2 }
      });
      const currentDocument = {
        id: 'doc-1',
        items: [
          { identifier: 'item-1', sequenceNumber: 10 },
          { identifier: 'item-2', sequenceNumber: 20 }
        ]
      };

      const result = await itemStore.moveItem(currentDocument, {
        draggedItem: { identifier: 'item-2' },
        targetItem: { identifier: 'item-1' },
        position: 'before'
      });

      expect(result).toBe(true);
      expect(currentDocument.items[0].identifier).toBe('item-2');
      expect(currentDocument.items[1].identifier).toBe('item-1');
      expect(currentDocument.items[0].sequenceNumber).toBe(1);
      expect(currentDocument.items[1].sequenceNumber).toBe(2);
    });

    it('moves item after target', async () => {
      api.post.mockResolvedValueOnce({
        siblingSequenceNumbers: { 'item-1': 1, 'item-3': 2, 'item-2': 3 }
      });
      const currentDocument = {
        id: 'doc-1',
        items: [
          { identifier: 'item-1', sequenceNumber: 10 },
          { identifier: 'item-2', sequenceNumber: 20 },
          { identifier: 'item-3', sequenceNumber: 30 }
        ]
      };

      const result = await itemStore.moveItem(currentDocument, {
        draggedItem: { identifier: 'item-3' },
        targetItem: { identifier: 'item-1' },
        position: 'after'
      });

      expect(result).toBe(true);
      expect(currentDocument.items[1].identifier).toBe('item-3');
      expect(currentDocument.items[0].sequenceNumber).toBe(1);
      expect(currentDocument.items[1].sequenceNumber).toBe(2);
      expect(currentDocument.items[2].sequenceNumber).toBe(3);
    });

    it('moves item inside target (as child)', async () => {
      api.post.mockResolvedValueOnce({
        childOfAssociationIdentifier: 'new-assoc-id',
        sequenceNumber: 1,
        siblingSequenceNumbers: { 'item-2': 1 }
      });
      const currentDocument = {
        id: 'doc-1',
        items: [
          {
            identifier: 'parent',
            sequenceNumber: 10,
            children: []
          },
          { identifier: 'item-2', sequenceNumber: 20, children: [] }
        ]
      };

      const result = await itemStore.moveItem(currentDocument, {
        draggedItem: { identifier: 'item-2' },
        targetItem: currentDocument.items[0],
        position: 'inside'
      });

      expect(result).toBe(true);
      expect(currentDocument.items[0].children).toHaveLength(1);
      expect(currentDocument.items[0].children[0].identifier).toBe('item-2');
      expect(currentDocument.items[0].children[0].sequenceNumber).toBe(1);
    });

    it('returns true when dragged item not found in local tree but API succeeds', async () => {
      api.post.mockResolvedValueOnce({});
      const currentDocument = {
        id: 'doc-1',
        items: [{ identifier: 'item-1' }]
      };

      const result = await itemStore.moveItem(currentDocument, {
        draggedItem: { identifier: 'non-existent' },
        targetItem: { identifier: 'item-1' },
        position: 'before'
      });

      expect(result).toBe(true);
    });

    it('calls API to persist move', async () => {
      api.post.mockResolvedValueOnce({});
      const currentDocument = {
        id: 'doc-1',
        items: [
          { identifier: 'item-1', sequenceNumber: 10 },
          { identifier: 'item-2', sequenceNumber: 20 }
        ]
      };

      await itemStore.moveItem(currentDocument, {
        draggedItem: { identifier: 'item-2' },
        targetItem: { identifier: 'item-1' },
        position: 'before'
      });

      expect(api.post).toHaveBeenCalledWith(
        '/framework/editor/item/item-2/move',
        expect.objectContaining({
          newParentIdentifier: 'doc-1',
          targetItemIdentifier: 'item-1',
          position: 'before',
        })
      );
    });

    it('falls back to response.sequenceNumber when no siblingSequenceNumbers', async () => {
      api.post.mockResolvedValueOnce({
        sequenceNumber: 5
      });
      const currentDocument = {
        id: 'doc-1',
        items: [
          { identifier: 'item-1', sequenceNumber: 10 },
          { identifier: 'item-2', sequenceNumber: 20 }
        ]
      };

      await itemStore.moveItem(currentDocument, {
        draggedItem: { identifier: 'item-2' },
        targetItem: { identifier: 'item-1' },
        position: 'before'
      });

      expect(currentDocument.items[0].sequenceNumber).toBe(5);
    });

    it('throws when API call fails', async () => {
      const apiError = new Error('API error');
      api.post.mockRejectedValueOnce(apiError);

      const currentDocument = {
        id: 'doc-1',
        items: [
          { identifier: 'item-1', sequenceNumber: 10 },
          { identifier: 'item-2', sequenceNumber: 20 }
        ]
      };

      await expect(itemStore.moveItem(currentDocument, {
        draggedItem: { identifier: 'item-2' },
        targetItem: { identifier: 'item-1' },
        position: 'before'
      })).rejects.toThrow('API error');
    });
  });

  describe('invalidateCache', () => {
    it('clears the lookup map', () => {
      itemStore.buildItemLookupMap([{ identifier: 'item-1' }]);
      expect(itemStore.getItemByIdentifierFast('item-1')).toBeTruthy();

      itemStore.invalidateCache();
      expect(itemStore.getItemByIdentifierFast('item-1')).toBeNull();
    });
  });
});
