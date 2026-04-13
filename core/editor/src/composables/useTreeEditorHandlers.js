import { nextTick } from 'vue';
import { findItem, findItemPath } from '../utils/tree.js';
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
    showEditAssociationModal,
    showDeleteModal,
    showExemplarModal,
    editingAssociation,
    itemsToDelete,
    deleteType,
    addingAssociation,
    addingAssociationType,
    addingAssociationOrigin,
    addingAssociationDestination,
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
    onSideDocumentSelect,
    // Navigation helpers
    expandItem,
    initializeFocus,
    setFocus,
    scrollToSelectedItem,
    // Announcer
    announcer,
    showDeleteAssociationModal,
    associationToDelete,
    openDeleteAssociationModal,
    closeDeleteAssociationModal,
    // Mercure
    connectMercure,
}) {
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

        // Check for drop on same item
        if (draggedItem.identifier === targetItem.identifier) return;

        // Verify it's an internal move (safety check)
        const draggedDocId = draggedItem.CFDocumentURI?.identifier || draggedItem.documentId;
        const targetDocId = currentDoc.value?.id;

        if (draggedDocId === targetDocId) {
            // Internal move
            await itemStore.moveItem(currentDoc.value, { draggedItem, targetItem, position });
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
                crossTreeTarget.value.identifier === documentId ? documentId : crossTreeTarget.value.identifier;
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
        addingAssociation.value = true;
        addingAssociationType.value = '';
        addingAssociationOrigin.value = crossTreeTarget.value;
        addingAssociationDestination.value = crossTreeSource.value;
        showEditAssociationModal.value = true;
        closeCrossTreeModal();
    }

    // ---------------------------------------------------------------------------
    // External Button Actions
    // ---------------------------------------------------------------------------
    async function onExternalAction(event) {
        const { type, position, itemId } = event;
        // Find source item from right panel
        const itemsToSearch = sideDocument.value?.items || sideDocument.value?.children || [];
        let sourceItem = findItem(itemsToSearch, itemId);

        // If the item selected is not in the items tree, must be the document root
        if (!sourceItem && sideDocument.value) {
            sourceItem = {
                identifier: sideDocument.value.identifier || sideDocument.value.id || 'document-root',
                title: sideDocument.value.title || 'Document Root',
                itemType: 'document',
                ...sideDocument.value
            };
        }

        if (!sourceItem) return;

        // Target item is current selection in main tree
        let targetItem = viewStore.currentItem;

        // If nothing is selected, check if we can use the document root
        if (!targetItem && currentDoc.value) {
            targetItem = {
                identifier: currentDoc.value.identifier || currentDoc.value.id || 'document-root',
                title: currentDoc.value.title || 'Document Root',
                itemType: 'document',
                ...currentDoc.value
            };
        }

        if (!targetItem) return;

        if (type === 'associate') {
            addingAssociation.value = true;
            addingAssociationType.value = '';
            addingAssociationOrigin.value = targetItem;
            addingAssociationDestination.value = sourceItem;
            showEditAssociationModal.value = true;
        } else if (type === 'copy') {
            const documentId = currentDoc.value?.id;
            let targetParentId = null;

            if (position === 'inside') {
                targetParentId = targetItem.identifier === documentId ? documentId : targetItem.identifier;
            } else if (position === 'before' || position === 'after') {
                // Find parent of targetItem
                const path = findItemPath(currentDoc.value?.items || [], targetItem.identifier);
                if (path && path.length >= 2) {
                    targetParentId = path[path.length - 2];
                } else {
                    targetParentId = documentId; // Fallback to root
                }
            }

            try {
                await currentDocumentStore.copyItem(documentId, sourceItem, targetParentId);
                if (documentId) {
                    await documentStore.revalidatePackage(documentId, true);
                }
                currentDocumentStore.reloadActiveDocument();
            } catch (error) {
                logger.error('Failed to copy external item:', error);
            }
        }
    }

    // ---------------------------------------------------------------------------
    // Viewed document
    // ---------------------------------------------------------------------------
    async function onViewedDocumentChanged(id) {
        const documentId = id && typeof id === 'object' ? id.documentId : id;

        if (!documentId) {
            contextStore.viewedDocumentId = null;
            contextStore.setFrameworkSelection('treeView', null);
            return;
        }

        if (documentId === currentDoc.value?.identifier) {
            contextStore.viewedDocumentId = null;
            contextStore.setFrameworkSelection('treeView', null);
            return;
        }

        try {
            const response = await documentStore.fetchTree(documentId);
            if (response?.document) {
                contextStore.viewedDocumentId = documentId;
                // Save framework selection for treeView mode
                contextStore.setFrameworkSelection('treeView', documentId);
            }
        } catch (err) {
            logger.error('Failed to switch viewed document:', err);
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
            try {
                await currentDocumentStore.createItem(parentItem.identifier, newItem);
                if (currentDoc.value?.id) {
                    await documentStore.revalidatePackage(currentDoc.value.id, true);
                }
                currentDocumentStore.reloadActiveDocument();
            } catch (error) {
                logger.error('Failed to add child item:', error);
            }
        }
    }

    function onAddExemplar(item) {
        addingAssociation.value = true;
        addingAssociationType.value = 'exemplar';
        addingAssociationOrigin.value = item || viewStore.currentItem;
        showEditAssociationModal.value = true;
    }

    function onAddAssociation(item) {
        addingAssociation.value = true;
        addingAssociationType.value = '';
        addingAssociationOrigin.value = item;
        addingAssociationDestination.value = null;
        showEditAssociationModal.value = true;
    }

    function onEditAssociation(association) {
        addingAssociation.value = false;
        addingAssociationType.value = '';
        addingAssociationOrigin.value = null;
        editingAssociation.value = association;
        showEditAssociationModal.value = true;
    }

    function onDeleteAssociation(association) {
        if (!association?.identifier) return;
        openDeleteAssociationModal(association);
    }

    async function onDeleteAssociationConfirmed(association) {
        if (!association?.identifier) return;
        try {
            await currentDocumentStore.removeAssociation(association.identifier);
            if (currentDoc.value?.id) {
                await documentStore.revalidatePackage(currentDoc.value.id, true);
            }
            currentDocumentStore.reloadActiveDocument();
            closeDeleteAssociationModal();
        } catch (error) {
            logger.error('Failed to delete association:', error);
        }
    }

    async function onRightPanelModeChanged(mode) {
        rightPanelMode.value = mode;

        // Restore framework selection for the new mode
        if (mode === 'externalDocument') {
            const selection = contextStore.getFrameworkSelection(mode);
            if (selection?.documentId) {
                // Trigger side document loading
                await onSideDocumentSelect(selection.documentId);
            }
        }
    }

    // ---------------------------------------------------------------------------
    // Modal event callbacks
    // ---------------------------------------------------------------------------
    async function onDocSaved(data) {
        const documentId = currentDoc.value?.id;
        if (!documentId) return;
        try {
            await currentDocumentStore.updateDocument(documentId, data);
            if (currentDoc.value?.id) {
                await documentStore.revalidatePackage(currentDoc.value.id, true);
            }
            currentDocumentStore.reloadActiveDocument();
        } catch (error) {
            logger.error('Failed to save document:', error);
        }
    }

    async function onAssociationCreated(association) {
        const documentId = currentDoc.value?.id;
        if (!documentId) return;
        try {
            await currentDocumentStore.addAssociation(documentId, association);
            addingAssociation.value = false;
            addingAssociationType.value = '';
            addingAssociationOrigin.value = null;
            if (currentDoc.value?.id) {
                await documentStore.revalidatePackage(currentDoc.value.id, true);
            }
            currentDocumentStore.reloadActiveDocument();
        } catch (error) {
            logger.error('Failed to create association:', error);
        }
    }

    async function onAssociationUpdated(association) {
        if (!association?.identifier) return;
        try {
            await currentDocumentStore.updateAssociation(association.identifier, association);
            addingAssociation.value = false;
            addingAssociationType.value = '';
            addingAssociationOrigin.value = null;
            if (currentDoc.value?.id) {
                await documentStore.revalidatePackage(currentDoc.value.id, true);
            }
            currentDocumentStore.reloadActiveDocument();
        } catch (error) {
            logger.error('Failed to update association:', error);
        }
    }

    function onEditAssociationModalHidden() {
        closeEditAssociationModal();
    }

    function onDeleteDocument() {
        if (!currentDoc.value || isViewingDifferentFramework.value) return;

        itemsToDelete.value = [currentDoc.value];
        deleteType.value = 'framework';
        showDeleteModal.value = true;
    }

    async function onExemplarAdded(exemplar) {
        const documentId = currentDoc.value?.identifier || currentDoc.value?.id;
        if (!documentId) return;
        try {
            await currentDocumentStore.addAssociation(documentId, exemplar);
            if (currentDoc.value?.id) {
                await documentStore.revalidatePackage(currentDoc.value.id, true);
            }
            currentDocumentStore.reloadActiveDocument();
        } catch (error) {
            logger.error('Failed to add exemplar:', error);
        }
    }

    async function onItemsDeleted({ items, deleteType }) {
        try {
            if (deleteType === 'framework') {
                const framework = Array.isArray(items) ? items[0] : null;
                const documentId = framework?.identifier || framework?.id;
                if (!documentId) {
                    logger.error('Failed to delete framework: missing document identifier', { items });
                    return;
                }
                await currentDocumentStore.deleteDocument(documentId);
                window.location.href = '/';
                return;
            }

            for (const item of items) {
                await currentDocumentStore.deleteItem(item.identifier);
            }
            if (currentDoc.value?.id) {
                await documentStore.revalidatePackage(currentDoc.value.id, true);
            }
            currentDocumentStore.reloadActiveDocument();
        } catch (error) {
            logger.error('Failed to delete items:', error);
        }
    }

    async function onAssocGroupSaved(group) {
        const documentId = currentDoc.value?.id;
        if (!documentId) return;
        try {
            if (group.identifier) {
                await currentDocumentStore.updateAssociationGroup(group.identifier, group);
            } else {
                await currentDocumentStore.createAssociationGroup(documentId, group);
            }
            if (currentDoc.value?.id) {
                await documentStore.revalidatePackage(currentDoc.value.id, true);
            }
            currentDocumentStore.reloadActiveDocument();
        } catch (error) {
            logger.error('Failed to save association group:', error);
        }
    }

    async function onAssocGroupDeleted(group) {
        if (!group?.identifier) return;
        try {
            await currentDocumentStore.deleteAssociationGroup(group.identifier);
            if (currentDoc.value?.id) {
                await documentStore.revalidatePackage(currentDoc.value.id, true);
            }
            currentDocumentStore.reloadActiveDocument();
        } catch (error) {
            logger.error('Failed to delete association group:', error);
        }
    }

    function onEditDocument() {
        showEditDocModal.value = true;
    }

    async function handleAddRootItem(newItem) {
        if (newItem && currentDoc.value) {
            try {
                await currentDocumentStore.createItem(currentDoc.value.id, newItem);
                if (currentDoc.value?.id) {
                    await documentStore.revalidatePackage(currentDoc.value.id, true);
                }
                currentDocumentStore.reloadActiveDocument();
            } catch (error) {
                logger.error('Failed to add root item:', error);
            }
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
        onExternalAction,
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
        onDeleteAssociationConfirmed,
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
        onDeleteDocument,
        handleAddRootItem,
        onManageAssociationGroups,
        onTreeFocus,
        getScrollTarget,
    };
}
