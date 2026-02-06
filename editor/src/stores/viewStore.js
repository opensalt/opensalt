import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useViewStore = defineStore('view', () => {
  // State
  const currentView = ref('tree'); // 'tree', 'association', 'log'
  const currentItem = ref(null);
  const bulkMode = ref(false);
  const selectedItemsSet = ref(new Set());

  // Computed property to convert Set to Array for components
  const selectedItems = computed(() => Array.from(selectedItemsSet.value));

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
      selectedItemsSet.value.clear();
    }
  }

  function toggleBulkMode() {
    setBulkMode(!bulkMode.value);
  }

  function selectItem(itemId) {
    selectedItemsSet.value.add(itemId);
  }

  function deselectItem(itemId) {
    selectedItemsSet.value.delete(itemId);
  }

  function toggleItemSelection(itemId) {
    if (selectedItemsSet.value.has(itemId)) {
      deselectItem(itemId);
    } else {
      selectItem(itemId);
    }
  }

  function clearSelection() {
    selectedItemsSet.value.clear();
  }

  function isItemSelected(itemId) {
    return selectedItemsSet.value.has(itemId);
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
