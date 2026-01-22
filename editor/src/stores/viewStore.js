import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useViewStore = defineStore('view', () => {
  // State
  const currentView = ref('tree'); // 'tree', 'association', 'log'
  const currentItem = ref(null);
  const bulkMode = ref(false);
  const selectedItems = ref(new Set());

  // Actions
  function setCurrentView(view) {
    currentView.value = view;
  }

  function setCurrentItem(item) {
    currentItem.value = item;
  }

  function setBulkMode(enabled) {
    bulkMode.value = enabled;
    if (!enabled) {
      selectedItems.value.clear();
    }
  }

  function toggleBulkMode() {
    setBulkMode(!bulkMode.value);
  }

  function selectItem(itemId) {
    selectedItems.value.add(itemId);
  }

  function deselectItem(itemId) {
    selectedItems.value.delete(itemId);
  }

  function toggleItemSelection(itemId) {
    if (selectedItems.value.has(itemId)) {
      deselectItem(itemId);
    } else {
      selectItem(itemId);
    }
  }

  function clearSelection() {
    selectedItems.value.clear();
  }

  function isItemSelected(itemId) {
    return selectedItems.value.has(itemId);
  }

  return {
    currentView,
    currentItem,
    bulkMode,
    selectedItems,
    setCurrentView,
    setCurrentItem,
    setBulkMode,
    toggleBulkMode,
    selectItem,
    deselectItem,
    toggleItemSelection,
    clearSelection,
    isItemSelected
  };
});
