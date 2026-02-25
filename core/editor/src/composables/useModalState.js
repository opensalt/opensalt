/**
 * useModalState Composable
 *
 * Centralized modal state management for the EnhancedDocumentTreeEditor.
 * Handles all modal visibility states and associated data.
 */
import { ref } from 'vue';

/**
 * @returns {Object} Modal state and methods
 */
export function useModalState() {
  // Modal visibility states
  const showEditDocModal = ref(false);
  const showAssociateModal = ref(false);
  const showEditAssociationModal = ref(false);
  const showDeleteModal = ref(false);
  const showExemplarModal = ref(false);
  const showAssocGroupModal = ref(false);
  const showLoadExternalModal = ref(false);
  const showCrossTreeModal = ref(false);

  // Modal data states
  const associationOrigin = ref(null);
  const associationDestination = ref(null);
  const editingAssociation = ref(null);
  const itemsToDelete = ref([]);
  const deleteType = ref('single'); // 'single' or 'bulk'

  // Add mode state for EditAssociationModal
  const addingAssociation = ref(false);
  const addingAssociationType = ref('');
  const addingAssociationOrigin = ref(null);

  // Cross-tree drop state
  const crossTreeSource = ref(null);
  const crossTreeTarget = ref(null);
  const crossTreePosition = ref(null);

  /**
   * Reset all modal states to their default values
   */
  function resetAllModals() {
    showEditDocModal.value = false;
    showAssociateModal.value = false;
    showEditAssociationModal.value = false;
    showDeleteModal.value = false;
    showExemplarModal.value = false;
    showAssocGroupModal.value = false;
    showLoadExternalModal.value = false;
    showCrossTreeModal.value = false;

    resetModalData();
  }

  /**
   * Reset modal data states
   */
  function resetModalData() {
    associationOrigin.value = null;
    associationDestination.value = null;
    editingAssociation.value = null;
    itemsToDelete.value = [];
    deleteType.value = 'single';
    addingAssociation.value = false;
    addingAssociationType.value = '';
    addingAssociationOrigin.value = null;
    crossTreeSource.value = null;
    crossTreeTarget.value = null;
    crossTreePosition.value = null;
  }

  /**
   * Open the edit document modal
   */
  function openEditDocModal() {
    showEditDocModal.value = true;
  }

  /**
   * Close the edit document modal
   */
  function closeEditDocModal() {
    showEditDocModal.value = false;
  }

  /**
   * Open the associate modal with origin and destination items
   * @param {Object} origin - The origin item for the association
   * @param {Object|null} destination - The destination item for the association
   */
  function openAssociateModal(origin, destination = null) {
    associationOrigin.value = origin;
    associationDestination.value = destination;
    showAssociateModal.value = true;
  }

  /**
   * Close the associate modal
   */
  function closeAssociateModal() {
    showAssociateModal.value = false;
    associationOrigin.value = null;
    associationDestination.value = null;
  }

  /**
   * Open the edit association modal for editing an existing association
   * @param {Object} association - The association to edit
   */
  function openEditAssociationModal(association) {
    // Reset add mode state when editing
    addingAssociation.value = false;
    addingAssociationType.value = '';
    addingAssociationOrigin.value = null;
    editingAssociation.value = association;
    showEditAssociationModal.value = true;
  }

  /**
   * Open the edit association modal for adding a new association
   * @param {Object} origin - The origin item
   * @param {string} type - The association type (e.g., 'exemplar')
   */
  function openAddAssociationModal(origin, type = '') {
    addingAssociation.value = true;
    addingAssociationType.value = type;
    addingAssociationOrigin.value = origin;
    editingAssociation.value = null;
    showEditAssociationModal.value = true;
  }

  /**
   * Close the edit association modal and reset state
   */
  function closeEditAssociationModal() {
    showEditAssociationModal.value = false;
    addingAssociation.value = false;
    addingAssociationType.value = '';
    addingAssociationOrigin.value = null;
    editingAssociation.value = null;
  }

  /**
   * Open the delete modal for one or more items
   * @param {Array|Object} items - Item(s) to delete
   * @param {string} type - 'single' or 'bulk'
   */
  function openDeleteModal(items, type = 'single') {
    itemsToDelete.value = Array.isArray(items) ? items : [items];
    deleteType.value = type;
    showDeleteModal.value = true;
  }

  /**
   * Close the delete modal
   */
  function closeDeleteModal() {
    showDeleteModal.value = false;
    itemsToDelete.value = [];
    deleteType.value = 'single';
  }

  /**
   * Open the exemplar modal
   */
  function openExemplarModal() {
    showExemplarModal.value = true;
  }

  /**
   * Close the exemplar modal
   */
  function closeExemplarModal() {
    showExemplarModal.value = false;
  }

  /**
   * Open the association group modal
   */
  function openAssocGroupModal() {
    showAssocGroupModal.value = true;
  }

  /**
   * Close the association group modal
   */
  function closeAssocGroupModal() {
    showAssocGroupModal.value = false;
  }

  /**
   * Open the load external document modal
   */
  function openLoadExternalModal() {
    showLoadExternalModal.value = true;
  }

  /**
   * Close the load external document modal
   */
  function closeLoadExternalModal() {
    showLoadExternalModal.value = false;
  }

  /**
   * Open the cross-tree drop modal
   * @param {Object} source - The source item being dragged
   * @param {Object} target - The target item for the drop
   * @param {string} position - The drop position ('before', 'after', 'inside')
   */
  function openCrossTreeModal(source, target, position) {
    crossTreeSource.value = source;
    crossTreeTarget.value = target;
    crossTreePosition.value = position;
    showCrossTreeModal.value = true;
  }

  /**
   * Close the cross-tree drop modal
   */
  function closeCrossTreeModal() {
    showCrossTreeModal.value = false;
    crossTreeSource.value = null;
    crossTreeTarget.value = null;
    crossTreePosition.value = null;
  }

  return {
    // Modal visibility states
    showEditDocModal,
    showAssociateModal,
    showEditAssociationModal,
    showDeleteModal,
    showExemplarModal,
    showAssocGroupModal,
    showLoadExternalModal,
    showCrossTreeModal,

    // Modal data states
    associationOrigin,
    associationDestination,
    editingAssociation,
    itemsToDelete,
    deleteType,
    addingAssociation,
    addingAssociationType,
    addingAssociationOrigin,
    crossTreeSource,
    crossTreeTarget,
    crossTreePosition,

    // Reset methods
    resetAllModals,
    resetModalData,

    // Modal open/close methods
    openEditDocModal,
    closeEditDocModal,
    openAssociateModal,
    closeAssociateModal,
    openEditAssociationModal,
    openAddAssociationModal,
    closeEditAssociationModal,
    openDeleteModal,
    closeDeleteModal,
    openExemplarModal,
    closeExemplarModal,
    openAssocGroupModal,
    closeAssocGroupModal,
    openLoadExternalModal,
    closeLoadExternalModal,
    openCrossTreeModal,
    closeCrossTreeModal
  };
}
