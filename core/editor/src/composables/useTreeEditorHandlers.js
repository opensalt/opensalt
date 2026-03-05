import { nextTick } from 'vue';
import { logger } from '../utils/logger.js';

/**
 * Composable that owns all event-handler functions for EnhancedDocumentTreeEditor.
 *
 * This exists purely to reduce the size of the parent component; all handlers
 * receive the necessary reactive state and stores via the options bag instead of
 * closing over them (which would prevent tree-shaking and makes testing harder).
 *
 * @param {Object} ctx - Context object with refs, stores, and utility functions
 */
export function useTreeEditorHandlers({
    // Stores
    documentStore,
    currentDocumentStore,
    filterStore,
    itemStore,
    viewStore,
    contextStore,
    // Router
    router,
    route,
    // Reactive state
    currentDoc,
    doc,
    viewedDoc,
    selectedId,
    isViewingDifferentFramework,
    rightPanelMode,
    filteredDoc,
    // Modal state (from useModalState)
    showEditDocModal,
    showAssociateModal,
    showEditAssociationModal,
    showDeleteModal,
    showExemplarModal,
    associationOrigin,
    associationDestination,
    editingAssociation,
    itemsToDelete,
    deleteType,
    addingAssociation,
    addingAssociationType,
    addingAssociationOrigin,
    closeEditAssociationModal,
    openCrossTreeModal,
    closeCrossTreeModal,
    crossTreeSource,
    crossTreeTarget,
    showAssocGroupModal,
    showLoadExternalModal,
    // Dynamic edit modal
    showEditModal,
    // Document loader composable fns
    documentLoaderOnExternalDocumentRequested,
    documentLoaderOnExternalDocumentUrlLoaded,
    // Side document
    sideDocument,
    // Navigation helpers
    expandItem,
    initializeFocus,
    setFocus,
    scrollToSelectedItem,
    // Announcer
    announcer,
    // Mercure
    connectMercure,
}) {
    // ---------------------------------------------------------------------------
    // Item lookup helper
    // ---------------------------------------------------------------------------
    function findItem(items, id) {
        for (const item of items) {
            if (item.identifier === id) return item;
            if (item.children) {
                const found = findItem(item.children, id);
                if (found) return found;
            }
        }
        return null;
    }

    // ---------------------------------------------------------------------------
    // Selection
    // ---------------------------------------------------------------------------
    function onSelect(id) {
        const frameworkId = currentDocumentStore.currentDocument?.id;
        if (frameworkId) {
            if (id) {
                const item =
                    findItem(currentDoc.value?.items || [], id) ||
                    (viewedDoc.value ? findItem(viewedDoc.value.items, id) : null);
                viewStore.setCurrentItem(item);
                router.push(`/${frameworkId}/${id}`);
                viewStore.setLastSelectedItem(frameworkId, id);
            } else {
                viewStore.setCurrentItem(null);
                router.push(`/${frameworkId}`);
            }
        } else {
            const item =
                findItem(currentDoc.value?.items || [], id) ||
                (viewedDoc.value ? findItem(viewedDoc.value.items, id) : null);
            viewStore.setCurrentItem(item);
        }
    }

    function onDblClick(id) {
        onSelect(id);
        const isDocumentNode = id === currentDoc.value?.id;
        if (isDocumentNode) {
            showEditDocModal.value = true;
        } else {
            // selectedItem is derived from viewStore in the parent
            const item = viewStore.currentItem;
            showEditModal(item);
        }
    }

    // ---------------------------------------------------------------------------
    // Tree change (drag-drop)
    // ---------------------------------------------------------------------------
    async function onTreeChange(event) {
        if (event.type !== 'move') return;
        const { draggedItem, targetItem, position } = event;

        const draggedDocId = draggedItem.CFDocumentURI?.identifier || draggedItem.documentId;
        const targetDocId = currentDoc.value?.id;

        if (draggedItem.identifier === targetItem.identifier) return;

        if (draggedDocId === targetDocId) {
            await itemStore.moveItem(currentDoc.value, { draggedItem, targetItem, position });
        } else {
            if (rightPanelMode.value === 'copyItems') {
                openCrossTreeModal(draggedItem, targetItem, position);
            } else if (rightPanelMode.value === 'createAssociations') {
                associationOrigin.value = draggedItem;
                associationDestination.value = targetItem;
                showAssociateModal.value = true;
            } else {
                openCrossTreeModal(draggedItem, targetItem, position);
            }
        }
    }

    // ---------------------------------------------------------------------------
    // Cross-tree
    // ---------------------------------------------------------------------------
    async function onCrossTreeCopy() {
        if (!crossTreeSource.value || !crossTreeTarget.value) return;
        try {
            const documentId = currentDoc.value?.id;
            const targetParentId =
                crossTreeTarget.value.identifier === documentId ? null : crossTreeTarget.value.identifier;
            await currentDocumentStore.copyItem(documentId, crossTreeSource.value, targetParentId);
            if (documentId) {
                const docData = await documentStore.fetchDocument(documentId);
                const items = currentDocumentStore.transformCASEItems(
                    docData.CFItems || [],
                    docData.CFAssociations || [],
                    documentId
                );
                currentDocumentStore.currentDocument.items = items;
            }
            closeCrossTreeModal();
        } catch (error) {
            logger.error('Failed to copy item:', error);
        }
    }

    function onCrossTreeClose() {
        closeCrossTreeModal();
    }

    function onCrossTreeAssociate() {
        if (!crossTreeSource.value || !crossTreeTarget.value) return;
        associationOrigin.value = crossTreeTarget.value;
        associationDestination.value = crossTreeSource.value;
        showAssociateModal.value = true;
        closeCrossTreeModal();
    }

    // ---------------------------------------------------------------------------
    // Viewed document
    // ---------------------------------------------------------------------------
    async function onViewedDocumentChanged(id) {
        const documentId = id && typeof id === 'object' ? id.documentId : id;

        if (!documentId) {
            contextStore.viewedDocumentId = null;
            return;
        }

        if (documentId === currentDoc.value?.identifier) {
            contextStore.viewedDocumentId = null;
            return;
        }

        try {
            const pkg = await documentStore.loadPackage(documentId);
            if (pkg?.CFDocument) {
                contextStore.viewedDocumentId = documentId;
            }
        } catch (err) {
            console.error('Failed to switch viewed document:', err);
        }
    }

    // ---------------------------------------------------------------------------
    // External document
    // ---------------------------------------------------------------------------
    function onExternalDocumentRequested() {
        const shouldShowModal = documentLoaderOnExternalDocumentRequested();
        if (shouldShowModal) showLoadExternalModal.value = true;
    }

    async function onExternalDocumentUrlLoaded(url) {
        showLoadExternalModal.value = false;
        const result = await documentLoaderOnExternalDocumentUrlLoaded(url);
        if (result && sideDocument) sideDocument.value = result;
    }

    // ---------------------------------------------------------------------------
    // Search / filter
    // ---------------------------------------------------------------------------
    function onSearch({ query, filters }) {
        filterStore.setSearchQuery(query);
        if (filters) filterStore.setFilters(filters);
    }

    function onFilter(filters) {
        filterStore.setFilters(filters);
    }

    function onClearSearch() {
        filterStore.setSearchQuery('');
        filterStore.clearFilters();
        filterStore.setSelectedAssociationGroup('all');
    }

    // ---------------------------------------------------------------------------
    // Item actions
    // ---------------------------------------------------------------------------
    function onEditItem(item) {
        showEditModal(item);
    }

    function onDeleteItem(item) {
        itemsToDelete.value = [item];
        deleteType.value = 'single';
        showDeleteModal.value = true;
    }

    async function handleAddChild(newItem, parentItem) {
        if (newItem && parentItem?.identifier) {
            const success = itemStore.addItem(currentDoc.value, newItem, parentItem.identifier);
            if (!success) logger.error('Failed to add child item');
        }
    }

    function onAddExemplar(item) {
        addingAssociation.value = true;
        addingAssociationType.value = 'exemplar';
        addingAssociationOrigin.value = item || viewStore.currentItem;
        showEditAssociationModal.value = true;
    }

    function onAddAssociation(item) {
        associationOrigin.value = item;
        associationDestination.value = null;
        showAssociateModal.value = true;
    }

    function onEditAssociation(association) {
        addingAssociation.value = false;
        addingAssociationType.value = '';
        addingAssociationOrigin.value = null;
        editingAssociation.value = association;
        showEditAssociationModal.value = true;
    }

    function onDeleteAssociation() {
        // Handled inside ItemDetails via DeleteAssociationModal
    }

    function onRightPanelModeChanged(mode) {
        rightPanelMode.value = mode;
    }

    // ---------------------------------------------------------------------------
    // Modal event callbacks
    // ---------------------------------------------------------------------------
    function onDocSaved() { }

    function onAssociationCreated(association) {
        addingAssociation.value = false;
        addingAssociationType.value = '';
        addingAssociationOrigin.value = null;
        logger.debug('Association created:', association);
    }

    function onAssociationUpdated() {
        addingAssociation.value = false;
        addingAssociationType.value = '';
        addingAssociationOrigin.value = null;
    }

    function onEditAssociationModalHidden() {
        closeEditAssociationModal();
    }

    function onExemplarAdded() { }
    function onItemsDeleted() { logger.debug('Items deleted'); }
    function onAssocGroupSaved() { logger.debug('Association group saved'); }
    function onAssocGroupDeleted() { }

    function onEditDocument() {
        showEditDocModal.value = true;
    }

    async function handleAddRootItem(newItem) {
        if (newItem && currentDoc.value) {
            const success = itemStore.addItem(currentDoc.value, newItem, null);
            if (!success) logger.error('Failed to add root item');
        }
    }

    function onManageAssociationGroups() {
        showAssocGroupModal.value = true;
    }

    // ---------------------------------------------------------------------------
    // Tree focus
    // ---------------------------------------------------------------------------
    function onTreeFocus(itemId) {
        const item = findItem(filteredDoc.value.items || [], itemId);
        if (item) announcer.announceNavigation(item);
        viewStore.setFocusedItemId(itemId);
    }

    // ---------------------------------------------------------------------------
    // Document init & scroll
    // ---------------------------------------------------------------------------
    function findItemPath(items, targetId, path = []) {
        for (const item of items) {
            if (item.identifier === targetId) return [...path, item.identifier];
            if (item.children?.length) {
                const childPath = findItemPath(item.children, targetId, [...path, item.identifier]);
                if (childPath) return childPath;
            }
        }
        return null;
    }

    // Exposed for the parent component to call
    function getScrollTarget() {
        return { findItemPath, findItem };
    }

    return {
        findItem,
        onSelect,
        onDblClick,
        onTreeChange,
        onCrossTreeCopy,
        onCrossTreeClose,
        onCrossTreeAssociate,
        onViewedDocumentChanged,
        onExternalDocumentRequested,
        onExternalDocumentUrlLoaded,
        onSearch,
        onFilter,
        onClearSearch,
        onEditItem,
        onDeleteItem,
        handleAddChild,
        onAddExemplar,
        onAddAssociation,
        onEditAssociation,
        onDeleteAssociation,
        onRightPanelModeChanged,
        onDocSaved,
        onAssociationCreated,
        onAssociationUpdated,
        onEditAssociationModalHidden,
        onExemplarAdded,
        onItemsDeleted,
        onAssocGroupSaved,
        onAssocGroupDeleted,
        onEditDocument,
        handleAddRootItem,
        onManageAssociationGroups,
        onTreeFocus,
        getScrollTarget,
    };
}
