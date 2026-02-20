import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useViewStore = defineStore('view', () => {
  // State
  const currentView = ref('tree'); // 'tree', 'association', 'log'
  const currentItem = ref(null);

  // Actions
  function setCurrentView(view) {
    currentView.value = view;
  }

  function setCurrentItem(item) {
    currentItem.value = item;
  }

  return {
    currentView,
    currentItem,
    setCurrentView,
    setCurrentItem
  };
});
