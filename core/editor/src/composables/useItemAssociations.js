import { ref } from 'vue';
import { useAssociationList } from './useAssociationList.js';

/**
 * Composable that manages item association display state for ItemDetails.
 *
 * @param {Object} options
 * @param {import('vue').ComputedRef} options.item
 * @param {import('vue').Ref} options.displayItem
 */
export function useItemAssociations({ item, displayItem }) {
  const {
    mergedAssociations,
    isProcessingAssociations,
  } = useAssociationList({
    mode: 'item',
    item,
    displayItem,
  });

  // Keep delete-modal handlers for compatibility with existing call sites.
  const showDeleteModal = ref(false);
  const associationToDelete = ref(null);

  /**
   * @param {Object} association
   * @param {boolean} isCrossFrameworkItem
   * @param {boolean} canEditItem
   */
  function handleDeleteAssociationRequest(association, isCrossFrameworkItem, canEditItem) {
    if (!canEditItem) return;

    if (isCrossFrameworkItem) {
      const associationType = association.associationType || association.type;
      if (associationType !== 'isChildOf') return;
    }

    associationToDelete.value = association;
    showDeleteModal.value = true;
  }

  function handleDeleteConfirmed(association, emit) {
    emit('delete-association', association);
    showDeleteModal.value = false;
  }

  function handleDeleteModalHidden() {
    associationToDelete.value = null;
  }

  return {
    mergedAssociations,
    isProcessingAssociations,
    showDeleteModal,
    associationToDelete,
    handleDeleteAssociationRequest,
    handleDeleteConfirmed,
    handleDeleteModalHidden,
  };
}
