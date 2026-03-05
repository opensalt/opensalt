import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useViewStore = defineStore('view', () => {
  // State
  const currentView = ref('tree'); // 'tree', 'association', 'log'
  const currentItem = ref(null);

  // Document-aware storage for last selected item
  // Stores { documentId, itemId } to prevent cross-document issues
  const lastSelectedItem = ref({
    documentId: null,
    itemId: null
  });

  const focusedItemId = ref(null);
  const draggedItem = ref(null);

  function setFocusedItemId(id) {
    focusedItemId.value = id;
  }

  function setDraggedItem(item) {
    draggedItem.value = item;
  }

  // Actions
  function setCurrentView(view) {
    currentView.value = view;
  }

  function setCurrentItem(item) {
    currentItem.value = item;
  }

  // Set the last selected item with document context
  function setLastSelectedItem(documentId, itemId) {
    lastSelectedItem.value = { documentId, itemId };
  }

  // Clear the last selected item
  function clearLastSelectedItem() {
    lastSelectedItem.value = { documentId: null, itemId: null };
  }

  // Get the last selected item ID for a specific document
  // Returns null if the stored item belongs to a different document
  function getLastItemIdForDocument(documentId) {
    if (lastSelectedItem.value.documentId === documentId) {
      return lastSelectedItem.value.itemId;
    }
    return null;
  }

  // itemViewState stores transient UI state for item nodes
  // Maps itemIdentifier -> { expanded, selected, loading }
  const itemViewState = ref(new Map());

  function getViewState(identifier) {
    if (!itemViewState.value.has(identifier)) {
      itemViewState.value.set(identifier, {
        expanded: false,
        selected: false,
        loading: false
      });
    }
    return itemViewState.value.get(identifier);
  }

  function setExpanded(identifier, expanded) {
    const state = getViewState(identifier);
    state.expanded = expanded;
  }

  function toggleExpanded(identifier) {
    const state = getViewState(identifier);
    state.expanded = !state.expanded;
  }

  function setSelected(identifier, selected) {
    const state = getViewState(identifier);
    state.selected = selected;
  }

  function setLoading(identifier, loading) {
    const state = getViewState(identifier);
    state.loading = loading;
  }

  function clearViewState() {
    itemViewState.value.clear();
  }

  return {
    currentView,
    currentItem,
    lastSelectedItem,
    setCurrentView,
    setCurrentItem,
    setLastSelectedItem,
    clearLastSelectedItem,
    getLastItemIdForDocument,
    itemViewState,
    getViewState,
    setExpanded,
    toggleExpanded,
    setSelected,
    setLoading,
    clearViewState,
    focusedItemId,
    setFocusedItemId,
    draggedItem,
    setDraggedItem
  };
});
