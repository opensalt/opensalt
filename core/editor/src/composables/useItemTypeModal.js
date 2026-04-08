import { ref, computed, watch, onUnmounted } from 'vue';
import { logger } from '../utils/logger.js';

export function useItemTypeModal(props, emit, options = {}) {
  const loading = ref(false);
  const error = ref('');
  const saving = ref(false);
  const isEdit = computed(() => !!props.item);

  function resetState() {
    error.value = '';
    saving.value = false;
  }

  function saveItem(fields) {
    if (saving.value) return;
    
    saving.value = true;
    error.value = '';

    try {
      const timestamp = new Date().toISOString();
      
      if (isEdit.value) {
        const updatedItem = {
          ...props.item,
          ...fields,
          extensions: {
            ...(props.item?.extensions || {}),
            'salt:type': options.typeName || props.itemType || 'general'
          },
          updated: timestamp
        };
        emit('updated', updatedItem);
      } else {
        const newItem = {
          ...fields,
          extensions: {
            'salt:type': options.typeName || props.itemType || 'general'
          },
          parentId: props.parentItem?.identifier || null,
          created: timestamp,
          children: []
        };
        emit('created', newItem);
      }
    } catch (e) {
      error.value = 'Failed to save: ' + e.message;
      logger.error('Item save failed:', e);
    } finally {
      saving.value = false;
    }
  }

  function closeModal() {
    resetState();
    emit('hidden');
  }

  watch(() => props.show, (newVal) => {
    if (newVal) {
      resetState();
    }
  });

  return {
    loading,
    error,
    saving,
    isEdit,
    resetState,
    saveItem,
    closeModal
  };
}
