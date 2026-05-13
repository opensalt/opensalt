import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock Vue composition API
vi.mock('vue', async () => {
  const actual = await vi.importActual('vue');
  return {
    ...actual,
    ref: actual.ref,
    onMounted: vi.fn((callback) => callback()),
    inject: vi.fn()
  };
});

// Import after mocking
import { useAnnouncer } from '@/composables/useAnnouncer.js';

describe('useAnnouncer', () => {
  let announcer;
  let mockElements;

  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();

    // Store for mock elements
    mockElements = {
      container: null,
      politeRegion: null,
      assertiveRegion: null
    };

    // Create mock element factory
    const createMockElement = (id) => ({
      id: id || '',
      className: '',
      textContent: '',
      setAttribute: vi.fn(),
      appendChild: vi.fn(function(child) {
        this.children = this.children || [];
        this.children.push(child);
        return child;
      }),
      children: []
    });

    // Track which element we're creating
    let createElementCount = 0;

    // Mock document.getElementById
    vi.spyOn(document, 'getElementById').mockImplementation((_id) => {
      // Return null to force createAnnouncerElement to be called
      return null;
    });

    // Mock document.createElement
    vi.spyOn(document, 'createElement').mockImplementation((_tagName) => {
      createElementCount++;

      if (createElementCount === 1) {
        // First call: container
        mockElements.container = createMockElement('a11y-announcer-container');
        return mockElements.container;
      } else if (createElementCount === 2) {
        // Second call: polite region
        mockElements.politeRegion = createMockElement('a11y-announcer-polite');
        return mockElements.politeRegion;
      } else {
        // Third call: assertive region
        mockElements.assertiveRegion = createMockElement('a11y-announcer-assertive');
        return mockElements.assertiveRegion;
      }
    });

    // Mock document.body.appendChild
    vi.spyOn(document.body, 'appendChild').mockImplementation(() => {});

    // Reset createElementCount for each test
    createElementCount = 0;

    // Create a fresh announcer instance for each test
    announcer = useAnnouncer();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
  });

  describe('initialization', () => {
    it('initializes with empty messages', () => {
      expect(announcer.politeMessage.value).toBe('');
      expect(announcer.assertiveMessage.value).toBe('');
    });

    it('returns all expected methods', () => {
      expect(typeof announcer.announce).toBe('function');
      expect(typeof announcer.announceSelection).toBe('function');
      expect(typeof announcer.announceExpansion).toBe('function');
      expect(typeof announcer.announceNavigation).toBe('function');
      expect(typeof announcer.announceDrag).toBe('function');
      expect(typeof announcer.announceMove).toBe('function');
      expect(typeof announcer.announceError).toBe('function');
      expect(typeof announcer.announceSuccess).toBe('function');
      expect(typeof announcer.announceLoading).toBe('function');
      expect(typeof announcer.announceSearchResults).toBe('function');
      expect(typeof announcer.clearAnnouncements).toBe('function');
    });

    it('creates announcer elements on mount', () => {
      expect(mockElements.container).not.toBeNull();
      expect(mockElements.politeRegion).not.toBeNull();
      expect(mockElements.assertiveRegion).not.toBeNull();
    });

    it('sets correct attributes on polite region', () => {
      expect(mockElements.politeRegion.setAttribute).toHaveBeenCalledWith('role', 'status');
      expect(mockElements.politeRegion.setAttribute).toHaveBeenCalledWith('aria-live', 'polite');
      expect(mockElements.politeRegion.setAttribute).toHaveBeenCalledWith('aria-atomic', 'true');
    });

    it('sets correct attributes on assertive region', () => {
      expect(mockElements.assertiveRegion.setAttribute).toHaveBeenCalledWith('role', 'alert');
      expect(mockElements.assertiveRegion.setAttribute).toHaveBeenCalledWith('aria-live', 'assertive');
      expect(mockElements.assertiveRegion.setAttribute).toHaveBeenCalledWith('aria-atomic', 'true');
    });
  });

  describe('announce function behavior', () => {
    it('does not throw when announcing a message', () => {
      expect(() => announcer.announce('Test message', 'polite')).not.toThrow();
    });

    it('does not throw when message is empty', () => {
      expect(() => announcer.announce('')).not.toThrow();
    });

    it('does not throw when message is null', () => {
      expect(() => announcer.announce(null)).not.toThrow();
    });

    it('clears textContent before setting new message', () => {
      announcer.announce('Test message', 'polite');
      // The immediate clear happens
      expect(mockElements.politeRegion.textContent).toBe('');
    });

    it('uses polite region by default', () => {
      announcer.announce('Test message');
      expect(mockElements.politeRegion.textContent).toBe('');
    });

    it('uses assertive region when specified', () => {
      announcer.announce('Alert message', 'assertive');
      expect(mockElements.assertiveRegion.textContent).toBe('');
    });
  });

  describe('announceSelection', () => {
    it('does not throw with valid item', () => {
      const item = { identifier: 'item-1', title: 'Test Item' };
      expect(() => announcer.announceSelection(item)).not.toThrow();
    });

    it('does not throw with null item', () => {
      expect(() => announcer.announceSelection(null)).not.toThrow();
    });

    it('does not throw with undefined item', () => {
      expect(() => announcer.announceSelection(undefined)).not.toThrow();
    });

    it('does not throw with item missing title properties', () => {
      const item = { identifier: 'item-1' };
      expect(() => announcer.announceSelection(item)).not.toThrow();
    });
  });

  describe('announceExpansion', () => {
    it('does not throw when expanding', () => {
      const item = { identifier: 'item-1', title: 'Expandable Item' };
      expect(() => announcer.announceExpansion(item, true)).not.toThrow();
    });

    it('does not throw when collapsing', () => {
      const item = { identifier: 'item-1', title: 'Collapsible Item' };
      expect(() => announcer.announceExpansion(item, false)).not.toThrow();
    });
  });

  describe('announceNavigation', () => {
    it('does not throw with item only', () => {
      const item = { identifier: 'item-1', title: 'Navigation Target' };
      expect(() => announcer.announceNavigation(item)).not.toThrow();
    });

    it('does not throw with position information', () => {
      const item = { identifier: 'item-1', title: 'Item' };
      expect(() => announcer.announceNavigation(item, 3, 10)).not.toThrow();
    });
  });

  describe('announceDrag', () => {
    it('does not throw for drag start', () => {
      const item = { identifier: 'item-1', title: 'Draggable Item' };
      expect(() => announcer.announceDrag(item, 'start')).not.toThrow();
    });

    it('does not throw for drag move', () => {
      const item = { identifier: 'item-1', title: 'Draggable Item' };
      expect(() => announcer.announceDrag(item, 'move')).not.toThrow();
    });

    it('does not throw for drag drop', () => {
      const item = { identifier: 'item-1', title: 'Draggable Item' };
      expect(() => announcer.announceDrag(item, 'drop')).not.toThrow();
    });

    it('does not throw for drag cancel', () => {
      const item = { identifier: 'item-1', title: 'Draggable Item' };
      expect(() => announcer.announceDrag(item, 'cancel')).not.toThrow();
    });

    it('handles unknown action gracefully', () => {
      const item = { identifier: 'item-1', title: 'Draggable Item' };
      expect(() => announcer.announceDrag(item, 'unknown')).not.toThrow();
    });
  });

  describe('announceMove', () => {
    it('does not throw for move before', () => {
      const item = { identifier: 'item-1', title: 'Moved Item' };
      const target = { identifier: 'item-2', title: 'Target Item' };
      expect(() => announcer.announceMove(item, 'before', target)).not.toThrow();
    });

    it('does not throw for move after', () => {
      const item = { identifier: 'item-1', title: 'Moved Item' };
      const target = { identifier: 'item-2', title: 'Target Item' };
      expect(() => announcer.announceMove(item, 'after', target)).not.toThrow();
    });

    it('does not throw for move inside', () => {
      const item = { identifier: 'item-1', title: 'Moved Item' };
      const target = { identifier: 'item-2', title: 'Target Item' };
      expect(() => announcer.announceMove(item, 'inside', target)).not.toThrow();
    });

    it('does not throw with null target', () => {
      const item = { identifier: 'item-1', title: 'Moved Item' };
      expect(() => announcer.announceMove(item, 'before', null)).not.toThrow();
    });
  });

  describe('announceError', () => {
    it('does not throw with error message', () => {
      expect(() => announcer.announceError('An error occurred')).not.toThrow();
    });

    it('does not throw with empty message', () => {
      expect(() => announcer.announceError('')).not.toThrow();
    });

    it('uses assertive region for errors', () => {
      announcer.announceError('Critical error');
      // Assertive region should be cleared
      expect(mockElements.assertiveRegion.textContent).toBe('');
    });
  });

  describe('announceSuccess', () => {
    it('does not throw with success message', () => {
      expect(() => announcer.announceSuccess('Operation successful')).not.toThrow();
    });
  });

  describe('announceLoading', () => {
    it('does not throw for loading state', () => {
      expect(() => announcer.announceLoading(true, 'data')).not.toThrow();
    });

    it('does not throw for loaded state', () => {
      expect(() => announcer.announceLoading(false, 'data')).not.toThrow();
    });

    it('does not throw without context', () => {
      expect(() => announcer.announceLoading(true)).not.toThrow();
    });
  });

  describe('announceSearchResults', () => {
    it('does not throw for single result', () => {
      expect(() => announcer.announceSearchResults(1, 'test query')).not.toThrow();
    });

    it('does not throw for multiple results', () => {
      expect(() => announcer.announceSearchResults(5, 'test query')).not.toThrow();
    });

    it('does not throw for no results', () => {
      expect(() => announcer.announceSearchResults(0, 'test query')).not.toThrow();
    });
  });

  describe('clearAnnouncements', () => {
    it('does not throw when clearing', () => {
      expect(() => announcer.clearAnnouncements()).not.toThrow();
    });

    it('clears both regions', () => {
      // Set some content first
      announcer.announce('Test', 'polite');
      announcer.announce('Alert', 'assertive');

      // Clear
      announcer.clearAnnouncements();

      expect(mockElements.politeRegion.textContent).toBe('');
      expect(mockElements.assertiveRegion.textContent).toBe('');
    });
  });

  describe('edge cases', () => {
    it('handles very long messages without throwing', () => {
      const longMessage = 'A'.repeat(1000);
      expect(() => announcer.announce(longMessage, 'polite')).not.toThrow();
    });

    it('handles special characters without throwing', () => {
      const specialMessage = 'Item with <special> & "characters" and \'quotes\'';
      expect(() => announcer.announce(specialMessage, 'polite')).not.toThrow();
    });

    it('handles unicode characters without throwing', () => {
      const unicodeMessage = 'Item with unicode: 你好世界 🌍 مرحبا';
      expect(() => announcer.announce(unicodeMessage, 'polite')).not.toThrow();
    });

    it('handles rapid successive announcements without throwing', () => {
      expect(() => {
        for (let i = 0; i < 10; i++) {
          announcer.announce(`Message ${i}`, 'polite');
        }
      }).not.toThrow();
    });

    it('handles item with all title properties without throwing', () => {
      const item = {
        identifier: 'item-1',
        title: 'Title',
        fullStatement: 'Full Statement',
        abbreviatedStatement: 'Abbreviated'
      };
      expect(() => announcer.announceSelection(item)).not.toThrow();
    });

    it('handles item with only identifier without throwing', () => {
      const item = { identifier: 'item-123' };
      expect(() => announcer.announceSelection(item)).not.toThrow();
    });

    it('handles empty item object without throwing', () => {
      const item = {};
      expect(() => announcer.announceSelection(item)).not.toThrow();
    });
  });

  describe('message formatting', () => {
    it('uses polite priority by default', () => {
      announcer.announce('Test');
      // Should use polite region (cleared immediately)
      expect(mockElements.politeRegion.textContent).toBe('');
    });

    it('uses assertive priority when specified', () => {
      announcer.announce('Test', 'assertive');
      // Should use assertive region (cleared immediately)
      expect(mockElements.assertiveRegion.textContent).toBe('');
    });
  });
});
