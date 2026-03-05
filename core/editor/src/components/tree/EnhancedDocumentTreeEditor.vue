<template>
  <div class="h-100 d-flex flex-column">
    <!-- NEW: Dual Framework Header -->
    <header v-if="currentDoc" class="dual-framework-header p-2 border-bottom bg-light">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="edited-framework">
          <span class="badge bg-primary">
            <i class="bi bi-pencil-square me-1"></i>Editing
          </span>
          <span class="ms-2 fw-bold">{{ currentDoc.title || 'Untitled' }}</span>
        </div>
        <div v-if="isViewingDifferentFramework" class="viewed-framework">
          <span class="badge bg-secondary">
            <i class="bi bi-eye me-1"></i>Viewing
          </span>
          <span class="text-muted"> from {{ viewedDoc?.title || 'external framework' }}</span>
        </div>
      </div>
    </header>

    <!-- Page-wide spinner only shows during initial page load (no document loaded yet) -->
    <div v-if="loading && !currentDoc" class="d-flex justify-content-center align-items-center" style="height: 100%;" role="status" aria-live="polite">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading document...</span>
      </div>
    </div>
    <div v-else-if="error && !currentDoc" class="alert alert-danger my-4" role="alert" aria-live="assertive">{{ error }}</div>
    <main v-else class="row g-0 flex-grow-1" style="min-height: 0;">
      <!-- Tree panel (extracted to TreePanelSection component) -->
      <TreePanelSection
        :class="['col-5', { 'viewing-different-framework': isViewingDifferentFramework }]"
        :current-doc="currentDoc"
        :filtered-doc="filteredDoc"
        :available-documents="availableDocuments"
        :selected-id="selectedId"
        :tree-search-query="treeSearchQuery"
        :match-count="matchCount"
        :matching-item-ids="matchingItemIds"
        :association-groups="associationGroups"
        :selected-association-group="selectedAssociationGroupValue"
        :available-subjects="availableSubjects"
        @viewed-document-changed="onViewedDocumentChanged"
        @external-document-requested="onExternalDocumentRequested"
        @select="onSelect"
        @dblclick="onDblClick"
        @tree-change="onTreeChange"
        @focus="onTreeFocus"
        @update:tree-search-query="treeSearchQuery = $event"
        @update:selected-association-group="selectedAssociationGroupValue = $event"
        @search="onSearch"
        @filter="onFilter"
        @clear-search="onClearSearch"
      />

      <!-- Details/info panel -->
      <section class="col-7 details-panel d-flex flex-column h-100 overflow-hidden">
        <RightSidePanel
          v-if="rightPanelMode === 'itemDetails'"
          :current-document="currentDoc"
          :association-groups="associationGroups"
          :selected-item="selectedItem"
          :initial-mode="rightPanelMode"
          :available-documents="availableDocuments"
          :side-document="sideDocument"
          :loading-side-doc="loadingSideDoc"
          :side-doc-error="sideDocError"
          @mode-changed="onRightPanelModeChanged"
          @edit-item="onEditItem"
          @delete-item="onDeleteItem"
          @add-child="handleAddChild"
          @add-exemplar="onAddExemplar"
          @add-association="onAddAssociation"
          @edit-association="onEditAssociation"
          @delete-association="onDeleteAssociation"
          @edit-document="onEditDocument"
          @add-root-item="handleAddRootItem"
          @manage-association-groups="onManageAssociationGroups"
          @side-document-select="onSideDocumentSelect"
          @external-document-requested="onExternalDocumentRequested"
          @side-select="onSideSelect"
        />
        <SideBySideTreePanel
          v-else
          :mode="rightPanelMode"
          :current-document="currentDoc"
          :available-documents="availableDocuments"
          :side-document="sideDocument"
          :loading-side-doc="loadingSideDoc"
          :side-doc-error="sideDocError"
          @mode-changed="onRightPanelModeChanged"
          @document-select="onSideDocumentSelect"
          @external-document-requested="onExternalDocumentRequested"
          @side-select="onSideSelect"
          @tree-change="onTreeChange"
        />
      </section>
    </main>

    <!-- Modals (managed by ModalManager component) -->
    <ModalManager
      :current-doc="currentDoc"
      :selected-item="selectedItem"
      :selected-id="selectedId"
      :association-groups="associationGroups"
      :show-edit-doc-modal="showEditDocModal"
      :show-associate-modal="showAssociateModal"
      :show-edit-association-modal="showEditAssociationModal"
      :show-delete-modal="showDeleteModal"
      :show-exemplar-modal="showExemplarModal"
      :show-assoc-group-modal="showAssocGroupModal"
      :show-cross-tree-modal="showCrossTreeModal"
      :show-load-external-modal="showLoadExternalModal"
      :association-origin="associationOrigin"
      :association-destination="associationDestination"
      :editing-association="editingAssociation"
      :items-to-delete="itemsToDelete"
      :delete-type="deleteType"
      :adding-association="addingAssociation"
      :adding-association-type="addingAssociationType"
      :adding-association-origin="addingAssociationOrigin"
      :cross-tree-source="crossTreeSource"
      :cross-tree-target="crossTreeTarget"
      :is-edit-modal-visible="isEditModalVisible"
      :editing-item="editingItem"
      :edit-modal-component="editModalComponent"
      @doc-saved="onDocSaved"
      @association-created="onAssociationCreated"
      @association-updated="onAssociationUpdated"
      @items-deleted="onItemsDeleted"
      @exemplar-added="onExemplarAdded"
      @assoc-group-saved="onAssocGroupSaved"
      @assoc-group-deleted="onAssocGroupDeleted"
      @cross-tree-close="onCrossTreeClose"
      @cross-tree-copy="onCrossTreeCopy"
      @cross-tree-associate="onCrossTreeAssociate"
      @external-document-load="onExternalDocumentUrlLoaded"
      @edit-doc-modal-hidden="showEditDocModal = false"
      @associate-modal-hidden="showAssociateModal = false"
      @edit-association-modal-hidden="onEditAssociationModalHidden"
      @delete-modal-hidden="showDeleteModal = false"
      @exemplar-modal-hidden="showExemplarModal = false"
      @assoc-group-modal-hidden="showAssocGroupModal = false"
      @load-external-modal-hidden="showLoadExternalModal = false"
      @dynamic-edit-updated="handleUpdated"
      @dynamic-edit-hidden="handleEditHidden"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, provide, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useDocumentStore } from '../../stores/documentStore';
import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';
import { useFilterStore } from '../../stores/filterStore';
import { useItemStore } from '../../stores/itemStore';
import { useViewStore } from '../../stores/viewStore';
import { useEditorContextStore } from '@/stores/editorContextStore';
import { useTreeNavigation } from '../../composables/useTreeNavigation.js';
import { useAnnouncer } from '../../composables/useAnnouncer.js';
import { useDynamicEditModal } from '../../composables/useDynamicEditModal.js';
import { useModalState } from '../../composables/useModalState.js';
import { useSideDocument } from '../../composables/useSideDocument.js';

import { useDocumentLoader } from '../../composables/useDocumentLoader.js';
import { logger } from '../../utils/logger.js';

// Components
import TreePanelSection from './TreePanelSection.vue';
import ModalManager from './ModalManager.vue';
import RightSidePanel from '../shared/panels/RightSidePanel.vue';
import SideBySideTreePanel from './SideBySideTreePanel.vue';

// Track scroll timeout for cleanup (using refs for per-instance isolation)
const scrollTimeoutId = ref(null);
const docChangeTimeoutId = ref(null);

// Initialize stores (must be initialized before computed properties that use them)
const documentStore = useDocumentStore();
const currentDocumentStore = useCurrentDocumentStore();
const filterStore = useFilterStore();
const itemStore = useItemStore();
const viewStore = useViewStore();
const contextStore = useEditorContextStore();
const route = useRoute();
const router = useRouter();

// Initialize screen reader announcer
const announcer = useAnnouncer();

// Use store state and computed properties (must be declared before use in other composables)
const doc = computed(() => currentDocumentStore.currentDocument || { title: '', status: '', items: [] });
const loading = computed(() => documentStore.loading);
const error = computed(() => documentStore.error);
const searchQuery = computed(() => filterStore.searchQuery);
const selectedId = ref(route.params.itemId || null);

// Enhanced selectedItem computed that searches both edited and viewed documents
const selectedItem = computed(() => {
  const id = selectedId.value;
  if (!id) return null;

  // First try to find in edited document
  const editedDocItem = findItem(doc.value.items || [], id);
  if (editedDocItem) {
    return editedDocItem;
  }

  // If viewing different framework, also search in viewed document items
  if (isViewingDifferentFramework.value && viewedDoc.value?.items) {
    const viewedDocItem = findItem(viewedDoc.value.items, id);
    if (viewedDocItem) {
      return viewedDocItem;
    }
  }

  return null;
});

const currentDoc = computed(() => currentDocumentStore.currentDocument);

// NEW: Viewed document state for dual framework edit/view separation
// Combine viewed document metadata with its items for tree display
const viewedDoc = computed(() => {
  const id = contextStore.viewedDocumentId;
  if (!id) return null;
  
  const doc = contextStore.documentRegistry.get(id);
  if (!doc) return null;
  
  // Get items from centralized registries
  const pkg = contextStore.loadedPackages.get(id);
  let items = [];
  if (pkg && pkg.CFItems) {
    const transformed = currentDocumentStore.transformCASEItems(pkg.CFItems, pkg.CFAssociations || [], id);
    items = transformed.items || transformed;
  }
  
  return {
    ...doc,
    id: doc.identifier,
    items: items
  };
});
const isViewingDifferentFramework = computed(() => contextStore.isViewingDifferentFramework);

// filteredDoc must be declared before treeItems since treeItems depends on it
const filteredDoc = computed(() => ({
  ...doc.value,
  items: filterStore.filterItemsRecursively(doc.value.items || [], searchQuery.value, filterStore.selectedFilters, filterStore.selectedAssociationGroup)
}));

// Initialize tree navigation (depends on doc and selectedId declared above)
// Create a tree that includes the document root as the first item
// IMPORTANT: Use filteredDoc since that's what TreeView renders
const treeItems = computed(() => {
  if (!filteredDoc.value) return [];

  // Create a document root node that wraps all items
  const rootItem = {
    identifier: filteredDoc.value.id || 'document-root',
    title: filteredDoc.value.title || 'Document Root',
    abbreviatedStatement: filteredDoc.value.title || 'Document Root',
    humanCodingScheme: '',
    children: filteredDoc.value.items || [],
    itemType: 'document',
    isDocumentRoot: true
  };

  return [rootItem];
});

const {
  focusedItemId,
  setFocus,
  handleKeyDown: navigationHandleKeyDown,
  isItemExpanded,
  expandItem,
  collapseItem,
  toggleExpanded,
  initializeFocus
} = useTreeNavigation({
  items: treeItems,
  selectedId: computed(() => viewStore.currentItem?.identifier),
  onSelect: (id) => onSelect(id),
  // Pass centralized state from viewStore
  externalFocusedItemId: computed({
    get: () => viewStore.focusedItemId,
    set: (val) => viewStore.setFocusedItemId(val)
  }),
  externalExpandedState: computed(() => viewStore.itemViewState)
});

// Provide navigation context to child components (TreeNode, TreeView)
provide('treeNavigation', {
  focusedItemId,
  setFocus,
  handleKeyDown: navigationHandleKeyDown,
  isItemExpanded,
  expandItem,
  collapseItem,
  toggleExpanded
});

// Initialize useDynamicEditModal composable for type-specific edit modals
const availableTypes = ['general', 'assessment', 'course', 'credential', 'job', 'organization', 'public_key', 'identifier'];
const {
  showEditModal,
  isEditModalVisible,
  editingItem,
  editModalComponent,
  handleUpdated,
  handleEditHidden
} = useDynamicEditModal(
  (updatedItem) => {
    itemStore.updateItem(currentDoc.value, updatedItem);
  },
  availableTypes
);

// Use composables for modal state and side document management
const modalState = useModalState();
const {
  showEditDocModal,
  showAssociateModal,
  showEditAssociationModal,
  showDeleteModal,
  showExemplarModal,
  showAssocGroupModal,
  showLoadExternalModal,
  showCrossTreeModal,
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
  closeEditAssociationModal,
  openCrossTreeModal,
  closeCrossTreeModal
} = modalState;

// Use side document composable
const {
  sideDocument,
  loadingSideDoc,
  sideDocError,
  onSideDocumentSelect,
  onSideSelect
} = useSideDocument();

// Initialize document loader composable
const {
  onDocumentChanged: documentLoaderOnDocumentChanged,
  onExternalDocumentRequested: documentLoaderOnExternalDocumentRequested,
  onExternalDocumentUrlLoaded: documentLoaderOnExternalDocumentUrlLoaded,
  initializeDocument
} = useDocumentLoader({
  sideDocument,
  onDocumentLoaded: () => {
    // Optional callback when document is loaded
  }
});

// Panel modes
const rightPanelMode = ref('itemDetails');

// Tree filter state
const treeSearchQuery = ref('');

// Count matching items for match count badge
// Uses viewed document items when in view mode, edited document items otherwise
const matchCount = computed(() => {
  if (!treeSearchQuery.value) return null;
  const query = treeSearchQuery.value.toLowerCase();
  // Use viewed document items when in view mode, otherwise use edited document items
  const itemsToSearch = isViewingDifferentFramework.value
    ? (viewedDoc.value?.items || [])
    : (doc.value.items || []);
  return countMatches(itemsToSearch, query);
});

function countMatches(items, query) {
  let count = 0;
  for (const item of items) {
    const searchableText = [
      item.humanCodingScheme,
      item.abbreviatedStatement,
      item.fullStatement,
      item.title,
      item.identifier
    ].filter(Boolean).join(' ').toLowerCase();

    if (searchableText.includes(query)) {
      count++;
    }
    if (item.children) {
      count += countMatches(item.children, query);
    }
  }
  return count;
}

// Pre-compute matching item IDs for visibility filtering
// Uses viewed document items when in view mode, edited document items otherwise
const matchingItemIds = computed(() => {
  if (!treeSearchQuery.value) return new Set();

  const query = treeSearchQuery.value.toLowerCase();
  const matches = new Set();

  function findMatches(items) {
    for (const item of items) {
      const searchableText = [
        item.humanCodingScheme,
        item.abbreviatedStatement,
        item.fullStatement,
        item.title,
        item.identifier
      ].filter(Boolean).join(' ').toLowerCase();

      if (searchableText.includes(query)) {
        matches.add(item.identifier);
      }
      if (item.children) {
        findMatches(item.children);
      }
    }
  }

  // Use viewed document items when in view mode, otherwise use edited document items
  const itemsToSearch = isViewingDifferentFramework.value
    ? (viewedDoc.value?.items || [])
    : (doc.value.items || []);

  findMatches(itemsToSearch);
  return matches;
});

// Use store data
const availableDocuments = computed(() => documentStore.documents);
const availableSubjects = computed(() => filterStore.availableSubjects);
const associationGroups = computed(() => currentDocumentStore.associationGroups);
const selectedAssociationGroupValue = computed({
  get: () => filterStore.selectedAssociationGroup,
  set: (value) => filterStore.setSelectedAssociationGroup(value)
});

// Watch for route changes to update selected item
watch(() => route.params.itemId, (newItemId) => {
  selectedId.value = newItemId || null;
  
  // If we have an ID in the route, try to sync viewStore.currentItem
  // But don't do it if it's already set to prevent infinite loops
  if (newItemId && viewStore.currentItem?.identifier !== newItemId) {
    // Note: Here we might not have the item object yet if it's external,
    // so we rely on the component that loads it to update the store.
  }
  
  // Store the selected item with document context for view switching
  if (newItemId && currentDoc.value?.id) {
    viewStore.setLastSelectedItem(currentDoc.value.id, newItemId);
  }
}, { immediate: true });

// Sync selectedId with viewStore.currentItem changes
watch(() => viewStore.currentItem, (newItem) => {
  if (newItem && newItem.identifier !== selectedId.value) {
    selectedId.value = newItem.identifier;
  }
});

// Initialize data on mount
onMounted(async () => {
  await initializeDocument();

  // After document loads, scroll to selected item if one exists
  if (selectedId.value) {
    scrollToSelectedItem();
  }
});

/**
 * Find the path to an item in the tree (list of ancestor identifiers)
 * @param {Array} items - Tree items to search
 * @param {String} targetId - ID of the item to find
 * @param {Array} path - Current path (used recursively)
 * @returns {Array|null} - Array of identifiers leading to the target, or null if not found
 */
function findItemPath(items, targetId, path = []) {
  for (const item of items) {
    if (item.identifier === targetId) {
      return [...path, item.identifier];
    }
    if (item.children && item.children.length > 0) {
      const childPath = findItemPath(item.children, targetId, [...path, item.identifier]);
      if (childPath) {
        return childPath;
      }
    }
  }
  return null;
}

/**
 * Scroll the selected item into view
 * Expands parent nodes and smoothly scrolls to the selected item
 */
async function scrollToSelectedItem() {
  if (!selectedId.value || !doc.value?.items) return;

  // Find the path to the selected item
  const path = findItemPath(doc.value.items, selectedId.value);

  if (!path || path.length === 0) return;

  // Expand all parent nodes (all items in the path except the selected item itself)
  // The tree root is handled separately, so we only expand intermediate parents
  for (let i = 0; i < path.length - 1; i++) {
    expandItem(path[i]);
  }

  // Wait for DOM to update after expanding parents
  await nextTick();

  // Clear any existing scroll timeout to prevent memory leaks
  if (scrollTimeoutId.value) clearTimeout(scrollTimeoutId.value);

  // Find the selected element and scroll it into view
// Use a small delay to ensure the DOM is fully rendered
scrollTimeoutId.value = setTimeout(() => {
  const identifier = viewStore.currentItem?.identifier || route.params.itemId;
  if (!identifier) return;

  const selectedElement = document.querySelector(`[data-tree-node-id="${identifier}"]`);
  if (selectedElement) {
    selectedElement.scrollIntoView({
      behavior: 'smooth',
      block: 'center',
      inline: 'nearest'
    });

    // Also set focus to the selected item for accessibility
    setFocus(identifier);
  }
  scrollTimeoutId.value = null;
}, 100);
}

// Watch for document changes to handle initialization and scrolling
watch(() => doc.value?.id, (newDocId, oldDocId) => {
  if (newDocId) {
    // Expand the document root by default
    expandItem(newDocId);
    // Initialize focus on the document root
    initializeFocus();

  // Handle scrolling when document is loaded and we have a selected item
  if (newDocId !== oldDocId && (viewStore.currentItem?.identifier || route.params.itemId)) {
    // Clear any existing timeout to prevent memory leaks on rapid changes
    if (docChangeTimeoutId.value) clearTimeout(docChangeTimeoutId.value);
    // Delay slightly to ensure tree is rendered
    docChangeTimeoutId.value = setTimeout(() => {
      scrollToSelectedItem();
      docChangeTimeoutId.value = null;
    }, 200);
  }
  }
}, { immediate: true });

// Watch for viewed document changes to expand the root only
watch(() => viewedDoc.value?.id, (newViewedDocId, oldViewedDocId) => {
  if (newViewedDocId && newViewedDocId !== oldViewedDocId) {
    // Expand only the viewed document root by default
    // The root-level items (children) will remain collapsed
    expandItem(newViewedDocId);

    logger.debug('Expanded viewed document root:', newViewedDocId);
  }
}, { immediate: false });

// Event handlers
function onSelect(id) {
  const frameworkId = currentDocumentStore.currentDocument?.id;
  if (frameworkId) {
    if (id) {
      // Find the item object to store in viewStore
      const item = findItem(currentDoc.value?.items || [], id) || (viewedDoc.value ? findItem(viewedDoc.value.items, id) : null);
      viewStore.setCurrentItem(item);

      router.push(`/${frameworkId}/${id}`);
      // Store the selected item with document context for view switching
      viewStore.setLastSelectedItem(frameworkId, id);
    } else {
      viewStore.setCurrentItem(null);
      router.push(`/${frameworkId}`);
    }
  } else {
    // If no document context, just find the item anyway
    const item = findItem(currentDoc.value?.items || [], id) || (viewedDoc.value ? findItem(viewedDoc.value.items, id) : null);
    viewStore.setCurrentItem(item);
  }
}

function onDblClick(id) {
  // First select the item
  onSelect(id);

  // Determine if this is a document node or an item
  const isDocumentNode = id === currentDoc.value?.id;

  if (isDocumentNode) {
    // Open edit document modal
    showEditDocModal.value = true;
  } else {
    // Open type-specific edit modal for the selected item
    showEditModal(selectedItem.value);
  }
}

async function onTreeChange(event) {
  if (event.type === 'move') {
    const { draggedItem, targetItem, position } = event;

    // Check if it's internal or cross-tree
    const draggedDocId = draggedItem.CFDocumentURI?.identifier || draggedItem.documentId;
    const targetDocId = currentDoc.value?.id;

    // Check for drop on same item type
    if (draggedItem.identifier === targetItem.identifier) return;

    if (draggedDocId === targetDocId) {
      // Internal move
      await itemStore.moveItem(currentDoc.value, { draggedItem, targetItem, position });
    } else {
      // Cross-tree move (Copy/Associate)
      // Determine action based on current mode
      if (rightPanelMode.value === 'copyItems') {
        openCrossTreeModal(draggedItem, targetItem, position);
      } else if (rightPanelMode.value === 'createAssociations') {
        // Prepare association modal directly
        associationOrigin.value = draggedItem;
        associationDestination.value = targetItem;
        showAssociateModal.value = true;
      } else {
        // Default behavior (Item Details mode) - prompt?
        openCrossTreeModal(draggedItem, targetItem, position);
      }
    }
  }
}

// Cross-tree handlers
async function onCrossTreeCopy() {
  if (!crossTreeSource.value || !crossTreeTarget.value) return;

  try {
    const documentId = currentDoc.value?.id;
    const targetParentId = crossTreeTarget.value.identifier === documentId ? null : crossTreeTarget.value.identifier;

    await currentDocumentStore.copyItem(documentId, crossTreeSource.value, targetParentId);

    // Refresh the document to show the new item
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

  // We want to create an association IN the current document (target), pointing TO the side item (source)
  // So Main Tree Item is the Origin, and Side Tree Item is the Destination
  associationOrigin.value = crossTreeTarget.value;
  associationDestination.value = crossTreeSource.value;

  showAssociateModal.value = true;
  closeCrossTreeModal();
}

// NEW: Changed to update contextStore.viewedDocumentId for dual framework edit/view separation
async function onViewedDocumentChanged(id) {
  // If id is an object (from event), extract documentId
  const documentId = (id && typeof id === 'object') ? id.documentId : id;

  if (!documentId) {
    contextStore.viewedDocumentId = null;
    return;
  }

  // If selecting the edited framework, clear viewed framework
  if (documentId === currentDoc.value?.identifier) {
    contextStore.viewedDocumentId = null;
    return;
  }

  try {
    const pkg = await documentStore.loadPackage(documentId);
    if (pkg && pkg.CFDocument) {
      contextStore.viewedDocumentId = documentId;
    }
  } catch (err) {
    console.error(`Failed to switch viewed document:`, err);
  }
}

function onExternalDocumentRequested() {
  const shouldShowModal = documentLoaderOnExternalDocumentRequested();
  if (shouldShowModal) {
    showLoadExternalModal.value = true;
  }
}

async function onExternalDocumentUrlLoaded(url) {
  showLoadExternalModal.value = false;

  const result = await documentLoaderOnExternalDocumentUrlLoaded(url);
  if (result && sideDocument) {
    sideDocument.value = result;
  }
}

function onSearch({ query, filters }) {
  filterStore.setSearchQuery(query);
  if (filters) {
    filterStore.setFilters(filters);
  }
}

function onFilter(filters) {
  filterStore.setFilters(filters);
}

function onClearSearch() {
  filterStore.setSearchQuery('');
  filterStore.clearFilters();
  selectedAssociationGroupValue.value = 'all';
}

// Modal event handlers
function onEditItem(item) {
  showEditModal(item);
}

function onDeleteItem(item) {
  itemsToDelete.value = [item];
  deleteType.value = 'single';
  showDeleteModal.value = true;
}

async function handleAddChild(newItem, parentItem) {
  if (newItem && parentItem && parentItem.identifier) {
    const success = itemStore.addItem(currentDoc.value, newItem, parentItem.identifier);
    if (!success) {
      logger.error('Failed to add child item');
    }
  }
}

function onAddExemplar(item) {
  // Use the dual-mode EditAssociationModal for adding exemplars
  addingAssociation.value = true;
  addingAssociationType.value = 'exemplar';
  addingAssociationOrigin.value = item || selectedItem.value;
  showEditAssociationModal.value = true;
}

function onAddAssociation(item) {
  associationOrigin.value = item;
  associationDestination.value = null;
  showAssociateModal.value = true;
}

function onEditAssociation(association) {
  // Reset add mode state when editing
  addingAssociation.value = false;
  addingAssociationType.value = '';
  addingAssociationOrigin.value = null;
  editingAssociation.value = association;
  showEditAssociationModal.value = true;
}

function onDeleteAssociation() {
  // Handle association deletion
}

function onRightPanelModeChanged(mode) {
  rightPanelMode.value = mode;
}

// Placeholder handlers for modal events
function onDocSaved() {
}

function onAssociationCreated(association) {
  // Handle the created association from add mode
  // Reset add mode state
  addingAssociation.value = false;
  addingAssociationType.value = '';
  addingAssociationOrigin.value = null;

  // TODO: Call association store to persist new association
  logger.debug('Association created:', association);
}

function onAssociationUpdated() {
  // Reset add mode state
  addingAssociation.value = false;
  addingAssociationType.value = '';
  addingAssociationOrigin.value = null;
}

function onEditAssociationModalHidden() {
  closeEditAssociationModal();
}

function onExemplarAdded() {
}

function onItemsDeleted() {
  // Handle items deleted from the document
  // The document should be refreshed to reflect the changes
  logger.debug('Items deleted');
}

function onAssocGroupSaved() {
  // Handle association group saved
  // The document should be refreshed to reflect the changes
  logger.debug('Association group saved');
}

function onAssocGroupDeleted() {
}

function onEditDocument() {
  showEditDocModal.value = true;
}

async function handleAddRootItem(newItem) {
  if (newItem && currentDoc.value) {
    const success = itemStore.addItem(currentDoc.value, newItem, null);
    if (!success) {
      logger.error('Failed to add root item');
    }
  }
}

function onManageAssociationGroups() {
  showAssocGroupModal.value = true;
}

function onTreeFocus(itemId) {
  // Handle tree focus events for accessibility
  // Announce navigation to screen reader
  const item = findItem(filteredDoc.value.items || [], itemId);
  if (item) {
    announcer.announceNavigation(item);
  }
  // Synchronize focused item with viewStore
  viewStore.setFocusedItemId(itemId);
}

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

// Cleanup on component unmount
onUnmounted(() => {
  if (scrollTimeoutId.value) clearTimeout(scrollTimeoutId.value);
  if (docChangeTimeoutId.value) clearTimeout(docChangeTimeoutId.value);
});

</script>

<style scoped>
/* Component-specific styles can be added here */
.details-panel {
  min-height: 0;
}

/* NEW: Dual framework header styles for edit/view separation */
.dual-framework-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  flex-shrink: 0;
}

.dual-framework-header .edited-framework {
  display: flex;
  align-items: center;
}

.dual-framework-header .viewed-framework {
  display: flex;
  align-items: center;
}

/* Visual distinction for tree panel when viewing different framework */
:deep(.viewing-different-framework) {
  background-color: #f8f9fa;
  border-right: 3px solid #adb5bd;
}
</style>
