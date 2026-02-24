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

  return {
    currentView,
    currentItem,
    lastSelectedItem,
    setCurrentView,
    setCurrentItem,
    setLastSelectedItem,
    clearLastSelectedItem,
    getLastItemIdForDocument
  };
});
