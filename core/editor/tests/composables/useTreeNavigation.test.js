import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { ref, nextTick } from 'vue';

// Mock Vue composition API
vi.mock('vue', async () => {
  const actual = await vi.importActual('vue');
  return {
    ...actual,
    onMounted: vi.fn((callback) => callback()),
    nextTick: vi.fn((callback) => {
      if (callback) return Promise.resolve().then(callback);
      return Promise.resolve();
    })
  };
});

// Import after mocking
import { useTreeNavigation } from '@/composables/useTreeNavigation.js';

describe('useTreeNavigation', () => {
  let mockItems;
  let mockSelectedId;
  let mockOnSelect;
  let navigation;

  // Helper to create mock tree items (hierarchical structure)
  const createMockItems = (count = 3, withChildren = false) => {
    const items = [];
    for (let i = 1; i <= count; i++) {
      const item = {
        identifier: `item-${i}`,
        title: `Item ${i}`,
        children: []
      };
      if (withChildren && i === 1) {
        item.children = [
          { identifier: `item-${i}-1`, title: `Child 1`, children: [] },
          { identifier: `item-${i}-2`, title: `Child 2`, children: [] }
        ];
      }
      items.push(item);
    }
    return items;
  };

  beforeEach(() => {
    vi.clearAllMocks();

    // Mock document.querySelector for focus management
    vi.spyOn(document, 'querySelector').mockImplementation((selector) => ({
      focus: vi.fn(),
      dataset: { treeNodeId: selector.match(/data-tree-node-id="(.+)"/)?.[1] }
    }));

    mockItems = ref(createMockItems(5));
    mockSelectedId = ref(null);
    mockOnSelect = vi.fn();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('initialization', () => {
    it('initializes with default empty state when no options provided', () => {
      navigation = useTreeNavigation();

      expect(navigation.focusedItemId.value).toBeNull();
      expect(navigation.treeRef.value).toBeNull();
    });

    it('initializes with provided options', () => {
      navigation = useTreeNavigation({
        items: mockItems,
        selectedId: mockSelectedId,
        onSelect: mockOnSelect
      });

      // Focus is not automatically set - must call initializeFocus()
      expect(navigation.focusedItemId.value).toBeNull();
    });

    it('provides navigation context to child components', () => {
      navigation = useTreeNavigation({
        items: mockItems
      });

      // Verify the navigation object has all expected properties
      expect(navigation.focusedItemId).toBeDefined();
      expect(navigation.setFocus).toBeDefined();
      expect(navigation.handleKeyDown).toBeDefined();
      expect(navigation.isItemExpanded).toBeDefined();
      expect(navigation.expandItem).toBeDefined();
      expect(navigation.collapseItem).toBeDefined();
    });
  });

  describe('getVisibleNodes', () => {
    beforeEach(() => {
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('returns all items when none are expanded', () => {
      const visibleNodes = navigation.getVisibleNodes();
      // Only root level items are visible when none are expanded
      expect(visibleNodes).toHaveLength(5);
    });

    it('includes children when parent is expanded', () => {
      mockItems.value = createMockItems(3, true);
      navigation = useTreeNavigation({ items: mockItems });

      // Expand the first item which has children
      navigation.expandItem('item-1');

      const visibleNodes = navigation.getVisibleNodes();
      // Should include 3 root items + 2 children of item-1
      expect(visibleNodes).toHaveLength(5);
      expect(visibleNodes.map(i => i.identifier)).toEqual([
        'item-1', 'item-1-1', 'item-1-2', 'item-2', 'item-3'
      ]);
    });

    it('returns empty array when no items', () => {
      mockItems.value = [];
      navigation = useTreeNavigation({ items: mockItems });

      const visibleNodes = navigation.getVisibleNodes();
      expect(visibleNodes).toHaveLength(0);
    });
  });

  describe('setFocus', () => {
    beforeEach(() => {
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('sets focus to specified item', async () => {
      navigation.setFocus('item-3');

      expect(navigation.focusedItemId.value).toBe('item-3');
    });

    it('does not change focus for non-existent item', () => {
      const originalFocus = navigation.focusedItemId.value;
      navigation.setFocus('non-existent');

      expect(navigation.focusedItemId.value).toBe(originalFocus);
    });

    it('attempts to focus DOM element after setting focus', async () => {
      const mockElement = { focus: vi.fn() };
      vi.spyOn(document, 'querySelector').mockReturnValue(mockElement);

      navigation.setFocus('item-2');
      await nextTick();

      expect(document.querySelector).toHaveBeenCalledWith('[data-tree-node-id="item-2"]');
    });
  });

  describe('focusNext', () => {
    beforeEach(() => {
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('moves focus to next visible item', () => {
      navigation.setFocus('item-1');
      navigation.focusNext();

      expect(navigation.focusedItemId.value).toBe('item-2');
    });

    it('does not move focus past last item', () => {
      navigation.setFocus('item-5');
      navigation.focusNext();

      expect(navigation.focusedItemId.value).toBe('item-5');
    });
  });

  describe('focusPrevious', () => {
    beforeEach(() => {
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('moves focus to previous visible item', () => {
      navigation.setFocus('item-3');
      navigation.focusPrevious();

      expect(navigation.focusedItemId.value).toBe('item-2');
    });

    it('does not move focus before first item', () => {
      navigation.setFocus('item-1');
      navigation.focusPrevious();

      expect(navigation.focusedItemId.value).toBe('item-1');
    });
  });

  describe('focusFirst', () => {
    beforeEach(() => {
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('moves focus to first visible item', () => {
      navigation.setFocus('item-3');
      navigation.focusFirst();

      expect(navigation.focusedItemId.value).toBe('item-1');
    });
  });

  describe('focusLast', () => {
    beforeEach(() => {
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('moves focus to last visible item', () => {
      navigation.setFocus('item-1');
      navigation.focusLast();

      expect(navigation.focusedItemId.value).toBe('item-5');
    });
  });

  describe('focusPageDown', () => {
    beforeEach(() => {
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('moves focus down by 10 items or to last item', () => {
      navigation.setFocus('item-1');
      navigation.focusPageDown();

      expect(navigation.focusedItemId.value).toBe('item-5');
    });

    it('moves focus by exactly 10 items when available', () => {
      // Create 15 items
      mockItems.value = createMockItems(15);
      navigation = useTreeNavigation({ items: mockItems });
      navigation.setFocus('item-1');
      navigation.focusPageDown();

      expect(navigation.focusedItemId.value).toBe('item-11');
    });
  });

  describe('focusPageUp', () => {
    beforeEach(() => {
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('moves focus up by 10 items or to first item', () => {
      navigation.setFocus('item-5');
      navigation.focusPageUp();

      expect(navigation.focusedItemId.value).toBe('item-1');
    });

    it('moves focus by exactly 10 items when available', () => {
      // Create 15 items
      mockItems.value = createMockItems(15);
      navigation = useTreeNavigation({ items: mockItems });
      navigation.setFocus('item-15');
      navigation.focusPageUp();

      expect(navigation.focusedItemId.value).toBe('item-5');
    });
  });

  describe('expandItem / collapseItem', () => {
    beforeEach(() => {
      mockItems.value = createMockItems(3, true);
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('expands an item', () => {
      navigation.expandItem('item-1');
      expect(navigation.isItemExpanded('item-1')).toBe(true);
    });

    it('collapses an item', () => {
      navigation.expandItem('item-1');
      navigation.collapseItem('item-1');
      expect(navigation.isItemExpanded('item-1')).toBe(false);
    });

    it('toggleExpanded toggles item state', () => {
      expect(navigation.isItemExpanded('item-1')).toBe(false);
      navigation.toggleExpanded('item-1');
      expect(navigation.isItemExpanded('item-1')).toBe(true);
      navigation.toggleExpanded('item-1');
      expect(navigation.isItemExpanded('item-1')).toBe(false);
    });
  });

  describe('onArrowRight', () => {
    beforeEach(() => {
      mockItems.value = createMockItems(3, true);
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('expands collapsed item with children', () => {
      const item = mockItems.value[0];
      expect(navigation.isItemExpanded(item.identifier)).toBe(false);

      navigation.onArrowRight(item);

      expect(navigation.isItemExpanded(item.identifier)).toBe(true);
    });

    it('moves to first child when item is already expanded', async () => {
      const item = mockItems.value[0];
      navigation.expandItem(item.identifier);

      navigation.setFocus(item.identifier);
      navigation.onArrowRight(item);
      await nextTick();

      expect(navigation.focusedItemId.value).toBe('item-1-1');
    });

    it('does nothing for item without children', () => {
      const item = mockItems.value[1]; // item-2 has no children
      navigation.setFocus('item-2');
      navigation.onArrowRight(item);

      // Should not expand (no children) and focus should remain
      expect(navigation.focusedItemId.value).toBe('item-2');
    });
  });

  describe('onArrowLeft', () => {
    beforeEach(() => {
      mockItems.value = createMockItems(3, true);
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('collapses expanded item with children', () => {
      const item = mockItems.value[0];
      navigation.expandItem(item.identifier);

      navigation.onArrowLeft(item);

      expect(navigation.isItemExpanded(item.identifier)).toBe(false);
    });

    it('moves to parent when item is collapsed', () => {
      // Set up a child item scenario
      mockItems.value = createMockItems(1, true);
      navigation = useTreeNavigation({ items: mockItems });

      // Expand parent and focus child
      navigation.expandItem('item-1');
      navigation.setFocus('item-1-1');

      navigation.onArrowLeft({ identifier: 'item-1-1', children: [] });

      // Should move to parent
      expect(navigation.focusedItemId.value).toBe('item-1');
    });
  });

  describe('onEnter', () => {
    beforeEach(() => {
      navigation = useTreeNavigation({
        items: mockItems,
        onSelect: mockOnSelect
      });
    });

    it('calls onSelect with item identifier', () => {
      const item = mockItems.value[2];
      navigation.onEnter(item);

      expect(mockOnSelect).toHaveBeenCalledWith('item-3');
    });
  });

  describe('onSpace', () => {
    beforeEach(() => {
      navigation = useTreeNavigation({
        items: mockItems,
        onSelect: mockOnSelect
      });
    });

    it('prevents default and calls onSelect', () => {
      const mockEvent = { preventDefault: vi.fn() };
      const item = mockItems.value[1];

      navigation.onSpace(mockEvent, item);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(mockOnSelect).toHaveBeenCalledWith('item-2');
    });
  });

  describe('onAsterisk', () => {
    beforeEach(() => {
      mockItems.value = [
        { identifier: 'item-1', title: 'Item 1', children: [{ identifier: 'child-1' }] },
        { identifier: 'item-2', title: 'Item 2', children: [{ identifier: 'child-2' }] },
        { identifier: 'item-3', title: 'Item 3', children: [{ identifier: 'child-3' }] }
      ];
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('expands all sibling items at the same level', () => {
      navigation.onAsterisk({ identifier: 'item-1', level: 0, children: mockItems.value[0].children });

      // All items at level 0 should be expanded
      expect(navigation.isItemExpanded('item-1')).toBe(true);
      expect(navigation.isItemExpanded('item-2')).toBe(true);
      expect(navigation.isItemExpanded('item-3')).toBe(true);
    });
  });

  describe('handleKeyDown', () => {
    let mockEvent;

    beforeEach(() => {
      navigation = useTreeNavigation({
        items: mockItems,
        onSelect: mockOnSelect
      });
      mockEvent = { preventDefault: vi.fn() };
    });

    it('handles ArrowDown key', () => {
      navigation.setFocus('item-1');
      mockEvent.key = 'ArrowDown';

      navigation.handleKeyDown(mockEvent, mockItems.value[0]);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(navigation.focusedItemId.value).toBe('item-2');
    });

    it('handles ArrowUp key', () => {
      navigation.setFocus('item-3');
      mockEvent.key = 'ArrowUp';

      navigation.handleKeyDown(mockEvent, mockItems.value[2]);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(navigation.focusedItemId.value).toBe('item-2');
    });

    it('handles Home key', () => {
      navigation.setFocus('item-4');
      mockEvent.key = 'Home';

      navigation.handleKeyDown(mockEvent, mockItems.value[3]);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(navigation.focusedItemId.value).toBe('item-1');
    });

    it('handles End key', () => {
      navigation.setFocus('item-1');
      mockEvent.key = 'End';

      navigation.handleKeyDown(mockEvent, mockItems.value[0]);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(navigation.focusedItemId.value).toBe('item-5');
    });

    it('handles PageDown key', () => {
      mockEvent.key = 'PageDown';
      navigation.setFocus('item-1');

      navigation.handleKeyDown(mockEvent, mockItems.value[0]);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(navigation.focusedItemId.value).toBe('item-5');
    });

    it('handles PageUp key', () => {
      mockEvent.key = 'PageUp';
      navigation.setFocus('item-5');

      navigation.handleKeyDown(mockEvent, mockItems.value[4]);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(navigation.focusedItemId.value).toBe('item-1');
    });

    it('handles Enter key', () => {
      mockEvent.key = 'Enter';

      navigation.handleKeyDown(mockEvent, mockItems.value[2]);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(mockOnSelect).toHaveBeenCalledWith('item-3');
    });

    it('handles Space key', () => {
      mockEvent.key = ' ';

      navigation.handleKeyDown(mockEvent, mockItems.value[1]);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(mockOnSelect).toHaveBeenCalledWith('item-2');
    });

    it('handles Asterisk key', () => {
      mockItems.value[0].children = [{ identifier: 'child' }];
      mockEvent.key = '*';

      navigation.handleKeyDown(mockEvent, mockItems.value[0]);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(navigation.isItemExpanded('item-1')).toBe(true);
    });

    it('does nothing when item is null', () => {
      mockEvent.key = 'ArrowDown';

      navigation.handleKeyDown(mockEvent, null);

      expect(mockEvent.preventDefault).not.toHaveBeenCalled();
    });

    it('ignores unhandled keys', () => {
      mockEvent.key = 'Escape';
      navigation.setFocus('item-1');
      const originalFocus = navigation.focusedItemId.value;

      navigation.handleKeyDown(mockEvent, mockItems.value[0]);

      expect(mockEvent.preventDefault).not.toHaveBeenCalled();
      expect(navigation.focusedItemId.value).toBe(originalFocus);
    });
  });

  describe('roving tabindex pattern', () => {
    beforeEach(() => {
      navigation = useTreeNavigation({
        items: mockItems
      });
    });

    it('maintains only one focused item at a time', () => {
      navigation.setFocus('item-1');
      expect(navigation.focusedItemId.value).toBe('item-1');

      navigation.setFocus('item-3');
      expect(navigation.focusedItemId.value).toBe('item-3');

      navigation.setFocus('item-5');
      expect(navigation.focusedItemId.value).toBe('item-5');
    });
  });

  describe('edge cases', () => {
    it('handles empty items array', () => {
      mockItems.value = [];
      navigation = useTreeNavigation({
        items: mockItems
      });

      expect(navigation.focusedItemId.value).toBeNull();
      expect(navigation.getVisibleNodes()).toHaveLength(0);
    });

    it('handles single item', () => {
      mockItems.value = [createMockItems(1)[0]];
      navigation = useTreeNavigation({
        items: mockItems
      });

      navigation.initializeFocus();
      expect(navigation.focusedItemId.value).toBe('item-1');

      navigation.focusNext();
      expect(navigation.focusedItemId.value).toBe('item-1');

      navigation.focusPrevious();
      expect(navigation.focusedItemId.value).toBe('item-1');
    });

    it('handles items without children property', () => {
      mockItems.value = [
        { identifier: 'item-1', title: 'Item 1' },
        { identifier: 'item-2', title: 'Item 2' }
      ];
      navigation = useTreeNavigation({
        items: mockItems
      });

      navigation.setFocus('item-2');
      expect(navigation.focusedItemId.value).toBe('item-2');
    });

    it('handles deeply nested items', () => {
      mockItems.value = [
        {
          identifier: 'root',
          title: 'Root',
          children: [
            {
              identifier: 'level-2',
              title: 'Level 2',
              children: [
                {
                  identifier: 'level-3',
                  title: 'Level 3',
                  children: [
                    { identifier: 'level-4', title: 'Level 4', children: [] }
                  ]
                }
              ]
            }
          ]
        }
      ];
      navigation = useTreeNavigation({ items: mockItems });

      // Expand all levels
      navigation.expandItem('root');
      navigation.expandItem('level-2');
      navigation.expandItem('level-3');

      navigation.setFocus('level-4');
      expect(navigation.focusedItemId.value).toBe('level-4');
    });
  });

  describe('initializeFocus', () => {
    it('sets focus to first visible item', () => {
      navigation = useTreeNavigation({ items: mockItems });
      expect(navigation.focusedItemId.value).toBeNull();

      navigation.initializeFocus();

      expect(navigation.focusedItemId.value).toBe('item-1');
    });

    it('does not change focus if already set', () => {
      navigation = useTreeNavigation({ items: mockItems });
      navigation.setFocus('item-3');

      navigation.initializeFocus();

      // Should not override existing focus
      expect(navigation.focusedItemId.value).toBe('item-3');
    });
  });
});
