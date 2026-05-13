import { ref, nextTick, watch } from 'vue';

/**
 * Tree Navigation Composable
 * Implements WAI-ARIA Tree View Pattern with roving tabindex
 * and keyboard navigation for accessible tree components.
 *
 * @param {Object} options - Configuration options
 * @param {Array} options.items - Reactive array of tree items (hierarchical)
 * @param {String} options.selectedId - Currently selected item ID
 * @param {Function} options.onSelect - Callback when item is selected
 * @returns {Object} Navigation state and methods
 */
export function useTreeNavigation(options = {}) {
  const {
    items = ref([]),
    selectedId = ref(null),
    onSelect = () => { },
    // NEW: Optional external state from viewStore
    externalFocusedItemId = null,
    externalExpandedState = null
  } = options;

  // Focus state - use external if provided, otherwise local ref
  const focusedItemId = externalFocusedItemId || ref(null);
  const treeRef = ref(null);

  // Centralized expanded state - use external if provided, otherwise local ref
  const expandedState = externalExpandedState || ref({});

  // Helper to check if an item is expanded
  const isItemExpanded = (itemId) => {
    // If using externalExpandedState (which might be a Map), handle it correctly
    if (expandedState.value instanceof Map) {
      return expandedState.value.get(itemId)?.expanded === true;
    }
    return expandedState.value[itemId] === true;
  };

  // Helper to set expanded state
  const setItemExpanded = (itemId, expanded) => {
    if (expandedState.value instanceof Map) {
      const state = expandedState.value.get(itemId) || { expanded: false, selected: false, loading: false };
      expandedState.value.set(itemId, { ...state, expanded });
      return;
    }
    expandedState.value = { ...expandedState.value, [itemId]: expanded };
  };

  // Toggle expanded state
  const toggleExpanded = (itemId) => {
    const current = isItemExpanded(itemId);
    setItemExpanded(itemId, !current);
  };

  // Expand an item
  const expandItem = (itemId) => {
    if (!isItemExpanded(itemId)) {
      setItemExpanded(itemId, true);
    }
  };

  // Collapse an item
  const collapseItem = (itemId) => {
    if (isItemExpanded(itemId)) {
      setItemExpanded(itemId, false);
    }
  };

  // Build a flat list of VISIBLE nodes following depth-first traversal
  // This respects the expanded/collapsed state of each node
  const getVisibleNodes = () => {
    const visibleNodes = [];

    const traverseVisible = (itemList, level = 0) => {
      for (const item of itemList) {
        // Add current item to visible list
        visibleNodes.push({
          ...item,
          level,
          isExpanded: isItemExpanded(item.identifier)
        });

        // Only traverse children if the item is expanded
        if (item.children && item.children.length > 0 && isItemExpanded(item.identifier)) {
          traverseVisible(item.children, level + 1);
        }
      }
    };

    if (items.value && items.value.length > 0) {
      traverseVisible(items.value);
    }

    return visibleNodes;
  };

  // Set focus to a specific node
  const setFocus = (nodeId) => {
    const visibleNodes = getVisibleNodes();
    const index = visibleNodes.findIndex(item => item.identifier === nodeId);

    if (index !== -1) {
      focusedItemId.value = nodeId;

      // Focus DOM element - use multiple nextTick calls to ensure DOM is ready
      // after expand/collapse operations
      nextTick(() => {
        const element = document.querySelector(`[data-tree-node-id="${nodeId}"]`);
        if (element) {
          element.focus();
        } else {
          // Element not found yet - try again after another tick
          // This handles cases where expand/collapse hasn't finished rendering
          nextTick(() => {
            const retryElement = document.querySelector(`[data-tree-node-id="${nodeId}"]`);
            if (retryElement) {
              retryElement.focus();
            }
          });
        }
      });
    }
  };

  // Move focus to next visible node
  const focusNext = () => {
    const visibleNodes = getVisibleNodes();
    const currentIndex = visibleNodes.findIndex(item => item.identifier === focusedItemId.value);
    const nextIndex = currentIndex + 1;

    if (nextIndex < visibleNodes.length) {
      const nextItem = visibleNodes[nextIndex];
      setFocus(nextItem.identifier);
    }
  };

  // Move focus to previous visible node
  const focusPrevious = () => {
    const visibleNodes = getVisibleNodes();
    const currentIndex = visibleNodes.findIndex(item => item.identifier === focusedItemId.value);
    const prevIndex = currentIndex - 1;

    if (prevIndex >= 0) {
      const prevItem = visibleNodes[prevIndex];
      setFocus(prevItem.identifier);
    }
  };

  // Move focus to first visible node
  const focusFirst = () => {
    const visibleNodes = getVisibleNodes();
    if (visibleNodes.length > 0) {
      setFocus(visibleNodes[0].identifier);
    }
  };

  // Move focus to last visible node
  const focusLast = () => {
    const visibleNodes = getVisibleNodes();
    if (visibleNodes.length > 0) {
      setFocus(visibleNodes[visibleNodes.length - 1].identifier);
    }
  };

  // Move focus one page down (10 nodes or to last)
  const focusPageDown = () => {
    const visibleNodes = getVisibleNodes();
    const currentIndex = visibleNodes.findIndex(item => item.identifier === focusedItemId.value);
    const pageSize = 10;
    const nextIndex = Math.min(currentIndex + pageSize, visibleNodes.length - 1);

    if (nextIndex >= 0 && nextIndex < visibleNodes.length) {
      setFocus(visibleNodes[nextIndex].identifier);
    }
  };

  // Move focus one page up (10 nodes or to first)
  const focusPageUp = () => {
    const visibleNodes = getVisibleNodes();
    const currentIndex = visibleNodes.findIndex(item => item.identifier === focusedItemId.value);
    const pageSize = 10;
    const prevIndex = Math.max(currentIndex - pageSize, 0);

    if (prevIndex >= 0 && prevIndex < visibleNodes.length) {
      setFocus(visibleNodes[prevIndex].identifier);
    }
  };

  // Handle Arrow Right key - expand or move to first child
  const onArrowRight = (item) => {
    const hasChildren = item.children && item.children.length > 0;
    const isExpanded = isItemExpanded(item.identifier);

    if (hasChildren && !isExpanded) {
      // Expand the item
      expandItem(item.identifier);
    } else if (hasChildren && isExpanded) {
      // Move to first child
      const visibleNodes = getVisibleNodes();
      const currentIndex = visibleNodes.findIndex(i => i.identifier === item.identifier);
      if (currentIndex !== -1 && currentIndex + 1 < visibleNodes.length) {
        const nextItem = visibleNodes[currentIndex + 1];
        // Verify the next item is actually a child (level should be deeper)
        if (nextItem.level > visibleNodes[currentIndex].level) {
          setFocus(nextItem.identifier);
        }
      }
    }
  };

  // Handle Arrow Left key - collapse or move to parent
  const onArrowLeft = (item) => {
    const hasChildren = item.children && item.children.length > 0;
    const isExpanded = isItemExpanded(item.identifier);

    if (hasChildren && isExpanded) {
      // Collapse the item
      collapseItem(item.identifier);
    } else {
      // Move to parent
      const visibleNodes = getVisibleNodes();
      const currentIndex = visibleNodes.findIndex(i => i.identifier === item.identifier);

      if (currentIndex > 0) {
        const currentLevel = visibleNodes[currentIndex].level;
        // Find parent by going backwards until we find an item with lower level
        for (let i = currentIndex - 1; i >= 0; i--) {
          if (visibleNodes[i].level < currentLevel) {
            setFocus(visibleNodes[i].identifier);
            return;
          }
        }
      }
    }
  };

  // Handle Enter key - select item
  const onEnter = (item) => {
    onSelect(item.identifier);
  };

  // Handle Space key - toggle selection
  const onSpace = (event, item) => {
    event.preventDefault();
    onSelect(item.identifier);
  };

  // Expand all ancestors to make an item visible
  const expandToItem = (itemId) => {
    if (!itemId) return;

    function findPath(currItems, targetId, path = []) {
      for (const item of currItems) {
        if (item.identifier === targetId) return path;
        if (item.children && item.children.length > 0) {
          const foundPath = findPath(item.children, targetId, [...path, item.identifier]);
          if (foundPath) return foundPath;
        }
      }
      return null;
    }

    const path = findPath(items.value, itemId);
    if (path) {
      path.forEach(id => expandItem(id));
    }
  };

  // Handle Asterisk (*) key - expand all siblings
  const onAsterisk = (item) => {
    const visibleNodes = getVisibleNodes();
    const currentItemLevel = item.level || 0;

    // Expand all siblings at the same level
    visibleNodes.forEach((node) => {
      const nodeLevel = node.level || 0;
      if (nodeLevel === currentItemLevel && node.children && node.children.length > 0) {
        expandItem(node.identifier);
      }
    });
  };

  // Main keyboard handler
  const handleKeyDown = (event, item) => {
    if (!item) return;

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        focusNext();
        break;
      case 'ArrowUp':
        event.preventDefault();
        focusPrevious();
        break;
      case 'ArrowRight':
        event.preventDefault();
        onArrowRight(item);
        break;
      case 'ArrowLeft':
        event.preventDefault();
        onArrowLeft(item);
        break;
      case 'Home':
        event.preventDefault();
        focusFirst();
        break;
      case 'End':
        event.preventDefault();
        focusLast();
        break;
      case 'PageDown':
        event.preventDefault();
        focusPageDown();
        break;
      case 'PageUp':
        event.preventDefault();
        focusPageUp();
        break;
      case 'Enter':
        event.preventDefault();
        onEnter(item);
        break;
      case ' ':
        event.preventDefault();
        onSpace(event, item);
        break;
      case '*':
        event.preventDefault();
        onAsterisk(item);
        break;
    }
  };

  // Initialize focus on first visible node
  const initializeFocus = () => {
    const visibleNodes = getVisibleNodes();
    if (visibleNodes.length > 0 && !focusedItemId.value) {
      setFocus(visibleNodes[0].identifier);
    }
  };

  // NEW: Watch selectedId and expand to it
  watch(() => selectedId.value, (newId) => {
    if (newId) expandToItem(newId);
  }, { immediate: true });

  return {
    // State
    focusedItemId,
    treeRef,
    expandedState,

    // Methods
    getVisibleNodes,
    setFocus,
    handleKeyDown,
    focusNext,
    focusPrevious,
    focusFirst,
    focusLast,
    focusPageDown,
    focusPageUp,
    initializeFocus,

    // Expanded state helpers
    isItemExpanded,
    setItemExpanded,
    expandItem,
    collapseItem,
    toggleExpanded,
    expandToItem,

    // Keyboard handlers
    onArrowRight,
    onArrowLeft,
    onEnter,
    onSpace,
    onAsterisk
  };
}

export default useTreeNavigation;
