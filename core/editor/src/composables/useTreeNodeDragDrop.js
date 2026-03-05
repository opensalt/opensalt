import { ref } from 'vue';

/**
 * Composable that manages drag-and-drop for a single TreeNode.
 *
 * @param {Object} options
 * @param {import('vue').Ref}  options.item                - The current item object
 * @param {import('vue').Ref}  options.isViewMode          - Whether the tree is in read-only view mode
 * @param {import('vue').ComputedRef} options.isCrossFrameworkItem - Whether this is a cross-framework placeholder
 * @param {import('vue').Ref}  options.disableDrop         - When true, drop targets are disabled
 * @param {Object}             options.viewStore           - viewStore instance for dragged item state
 * @param {Function}           options.emit                - Component emit function
 */
export function useTreeNodeDragDrop({ item, isViewMode, isCrossFrameworkItem, disableDrop, viewStore, emit }) {
    const dropPosition = ref(null);

    function onDragStart(e) {
        if (isViewMode.value || isCrossFrameworkItem.value) {
            e.preventDefault();
            return;
        }
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData(
            'application/json',
            JSON.stringify({
                identifier: item.value.identifier,
                documentId: item.value.CFDocumentURI?.identifier || item.value.documentId,
            })
        );
        viewStore.setDraggedItem(item.value);
    }

    function onDragOver(e) {
        e.preventDefault();

        if (disableDrop.value) {
            dropPosition.value = 'disabled';
            return;
        }

        const rect = e.currentTarget.getBoundingClientRect();
        const y = e.clientY - rect.top;
        const height = rect.height;

        if (y < height * 0.25) {
            dropPosition.value = 'before';
        } else if (y > height * 0.75) {
            dropPosition.value = 'after';
        } else {
            dropPosition.value = 'inside';
        }
    }

    function onDragLeave() {
        dropPosition.value = null;
    }

    function onDrop(e) {
        e.preventDefault();

        if (disableDrop.value) {
            dropPosition.value = null;
            return;
        }

        const position = dropPosition.value;
        dropPosition.value = null;

        const draggedItem = viewStore.draggedItem;
        if (!draggedItem) return;

        // Don't drop on self
        if (draggedItem.identifier === item.value.identifier) return;

        emit('move', {
            draggedItem,
            targetItem: item.value,
            position,
        });
    }

    return {
        dropPosition,
        onDragStart,
        onDragOver,
        onDragLeave,
        onDrop,
    };
}
