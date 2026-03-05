/**
 * useCrossTreeOperations Composable
 *
 * Handles cross-tree drag/drop logic for the EnhancedDocumentTreeEditor.
 * Manages operations that involve copying or associating items between
 * different documents/trees.
 */
import { ref } from 'vue';
import { useDocumentStore } from '../stores/documentStore';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';

/**
 * @param {Object} options - Configuration options
 * @param {import('vue').Ref} options.currentDoc - Reference to the current document
 * @param {import('vue').Ref} options.rightPanelMode - Reference to the current panel mode
 * @param {import('vue').Ref} options.associationOrigin - Reference to association origin
 * @param {import('vue').Ref} options.associationDestination - Reference to association destination
 * @param {import('vue').Ref} options.showAssociateModal - Reference to show associate modal
 * @param {Function} options.onCopyComplete - Callback after copy operation completes
 * @param {Function} options.onAssociateComplete - Callback after associate operation completes
 * @returns {Object} Cross-tree operations state and methods
 */
export function useCrossTreeOperations(options = {}) {
  const {
    currentDoc,
    rightPanelMode,
    associationOrigin,
    associationDestination,
    showAssociateModal,
    onCopyComplete,
    onAssociateComplete
  } = options;

  const documentStore = useDocumentStore();
  const currentDocumentStore = useCurrentDocumentStore();

  // Cross-tree drop state
  const showCrossTreeModal = ref(false);
  const crossTreeSource = ref(null);
  const crossTreeTarget = ref(null);
  const crossTreePosition = ref(null);

  /**
   * Handle tree change events (move, copy, associate)
   * @param {Object} event - The tree change event
   * @param {string} event.type - The type of change ('move')
   * @param {Object} event.draggedItem - The item being dragged
   * @param {Object} event.targetItem - The target item
   * @param {string} event.position - The drop position
   */
  async function handleTreeChange(event) {
    if (event.type === 'move') {
      const { draggedItem, targetItem, position } = event;

      // Check for drop on same item
      if (draggedItem.identifier === targetItem.identifier) return;

      // NEW: Check mode first to determine operation type
      if (rightPanelMode?.value === 'createAssociations') {
        // Always create association when in createAssociations mode
        if (associationOrigin) {
          associationOrigin.value = draggedItem;
        }
        if (associationDestination) {
          associationDestination.value = targetItem;
        }
        if (showAssociateModal) {
          showAssociateModal.value = true;
        }
        return { isInternal: false, action: 'associate' };
      } else if (rightPanelMode?.value === 'copyItems') {
        // Always copy when in copyItems mode
        crossTreeSource.value = draggedItem;
        crossTreeTarget.value = targetItem;
        crossTreePosition.value = position;
        showCrossTreeModal.value = true;
        return { isInternal: false, action: 'copy' };
      }

      // Only check document IDs for default behavior (Item Details mode)
      const draggedDocId = draggedItem.CFDocumentURI?.identifier || draggedItem.documentId;
      const targetDocId = currentDoc?.value?.id;

      if (draggedDocId === targetDocId) {
        // Internal move - not handled by this composable
        return { isInternal: true, draggedItem, targetItem, position };
      } else {
        // Cross-tree move (Item Details mode) - prompt for action
        crossTreeSource.value = draggedItem;
        crossTreeTarget.value = targetItem;
        crossTreePosition.value = position;
        showCrossTreeModal.value = true;
        return { isInternal: false, action: 'prompt' };
      }
    }
    return null;
  }

  /**
   * Close the cross-tree modal and reset state
   */
  function onCrossTreeClose() {
    showCrossTreeModal.value = false;
    crossTreeSource.value = null;
    crossTreeTarget.value = null;
    crossTreePosition.value = null;
  }

  /**
   * Execute cross-tree copy operation
   * @returns {Promise<boolean>} True if copy was successful
   */
  async function onCrossTreeCopy() {
    if (!crossTreeSource.value || !crossTreeTarget.value) return false;

    try {
      const documentId = currentDoc?.value?.id;
      const targetParentId = crossTreeTarget.value.identifier === documentId
        ? null
        : crossTreeTarget.value.identifier;

      await currentDocumentStore.copyItem(documentId, crossTreeSource.value, targetParentId);

      // Refresh the document to show the new item
      // In a more optimized version, we would add the item locally, but re-fetch is safer for now
      if (documentId) {
        const docData = await documentStore.fetchDocument(documentId);
        const items = currentDocumentStore.transformCASEItems(
          docData.CFItems || [],
          docData.CFAssociations || [],
          documentId
        );
        // Update store
        currentDocumentStore.currentDocument.items = items;
      }

      onCrossTreeClose();

      if (onCopyComplete) {
        onCopyComplete(crossTreeSource.value, crossTreeTarget.value);
      }

      return true;
    } catch (error) {
      console.error('Failed to copy item:', error);
      // TODO: Show error toast
      return false;
    }
  }

  /**
   * Execute cross-tree associate operation
   * Opens the association modal with the cross-tree items
   */
  function onCrossTreeAssociate() {
    if (!crossTreeSource.value || !crossTreeTarget.value) return;

    // We want to create the association IN the current document (target), pointing TO the side item (source)
    // So the Main Tree Item is the Origin, and Side Tree Item is the Destination
    if (associationOrigin) {
      associationOrigin.value = crossTreeTarget.value;
    }
    if (associationDestination) {
      associationDestination.value = crossTreeSource.value;
    }

    if (showAssociateModal) {
      showAssociateModal.value = true;
    }

    onCrossTreeClose();

    if (onAssociateComplete) {
      onAssociateComplete(crossTreeSource.value, crossTreeTarget.value);
    }
  }

  /**
   * Set cross-tree state directly
   * @param {Object} source - The source item
   * @param {Object} target - The target item
   * @param {string} position - The drop position
   */
  function setCrossTreeState(source, target, position) {
    crossTreeSource.value = source;
    crossTreeTarget.value = target;
    crossTreePosition.value = position;
  }

  /**
   * Open the cross-tree modal
   */
  function openCrossTreeModal() {
    showCrossTreeModal.value = true;
  }

  /**
   * Check if a drag operation is cross-tree
   * @param {Object} draggedItem - The item being dragged
   * @param {Object} targetItem - The target item
   * @returns {boolean} True if this is a cross-tree operation
   */
  function isCrossTreeOperation(draggedItem, targetItem) {
    const draggedDocId = draggedItem.CFDocumentURI?.identifier || draggedItem.documentId;
    const targetDocId = currentDoc?.value?.id;
    return draggedDocId !== targetDocId;
  }

  return {
    // State
    showCrossTreeModal,
    crossTreeSource,
    crossTreeTarget,
    crossTreePosition,

    // Methods
    handleTreeChange,
    onCrossTreeClose,
    onCrossTreeCopy,
    onCrossTreeAssociate,
    setCrossTreeState,
    openCrossTreeModal,
    isCrossTreeOperation
  };
}
