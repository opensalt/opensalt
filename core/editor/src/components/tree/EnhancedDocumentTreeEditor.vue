<template>
  <div class="h-100 d-flex flex-column">
    <!-- Dual Framework Header -->
    <DualFrameworkHeader
      v-if="currentDoc"
      :current-doc="currentDoc"
      :is-viewing-different-framework="isViewingDifferentFramework"
      :viewed-doc="viewedDoc"
    />

    <!-- Page-wide spinner: only during initial load (no document yet) -->
    <div
      v-if="loading && !currentDoc"
      class="d-flex justify-content-center align-items-center"
      style="height: 100%;"
      role="status"
      aria-live="polite"
    >
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading document...</span>
      </div>
    </div>
    <div v-else-if="error && !currentDoc" class="alert alert-danger my-4" role="alert" aria-live="assertive">
      {{ error }}
    </div>

    <main v-else class="row g-0 flex-grow-1" style="min-height: 0;">
      <!-- Tree panel -->
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

      <!-- Details / side-by-side panel -->
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
          @add-child="showAddModal"
          @add-exemplar="onAddExemplar"
          @add-association="onAddAssociation"
          @edit-association="onEditAssociation"
          @delete-association="onDeleteAssociation"
          @edit-document="onEditDocument"
          @delete-document="onDeleteDocument"
          @add-root-item="handleAddRootItem"
          @manage-association-groups="onManageAssociationGroups"
          @update-item="onItemUpdate"
          @update-framework="showUpdateFrameworkModal = true"
          @export-document="showExportModal = true"
          @clone-framework="onCloneFramework"
          @side-document-select="onSideDocumentSelect"
          @external-document-requested="onExternalDocumentRequested"
          @side-select="onSideSelect"
          @action="onExternalAction"
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
          @action="onExternalAction"
        />
      </section>
    </main>

    <!-- Modals -->
    <ModalManager
      :current-doc="currentDoc"
      :selected-item="selectedItem"
      :selected-id="selectedId"
      :association-groups="associationGroups"
      :show-edit-doc-modal="showEditDocModal"
      :show-edit-association-modal="showEditAssociationModal"
      :show-delete-modal="showDeleteModal"
      :show-exemplar-modal="showExemplarModal"
      :show-assoc-group-modal="showAssocGroupModal"
      :show-cross-tree-modal="showCrossTreeModal"
      :show-load-external-modal="showLoadExternalModal"
      :show-update-framework-modal="showUpdateFrameworkModal"
      :show-export-modal="showExportModal"
      :show-clone-framework-modal="showCloneFrameworkModal"
      :show-delete-association-modal="showDeleteAssociationModal"
      :association-to-delete="associationToDelete"
      :clone-framework-title="cloneFrameworkTitle"
      :editing-association="editingAssociation"
      :items-to-delete="itemsToDelete"
      :delete-type="deleteType"
      :adding-association="addingAssociation"
      :adding-association-type="addingAssociationType"
      :adding-association-origin="addingAssociationOrigin"
      :adding-association-destination="addingAssociationDestination"
      :cross-tree-source="crossTreeSource"
      :cross-tree-target="crossTreeTarget"
      :is-edit-modal-visible="isEditModalVisible"
      :editing-item="editingItem"
      :modal-parent-item="modalParentItem"
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
      @edit-association-modal-hidden="onEditAssociationModalHidden"
      @delete-modal-hidden="showDeleteModal = false"
      @exemplar-modal-hidden="showExemplarModal = false"
      @assoc-group-modal-hidden="showAssocGroupModal = false"
      @load-external-modal-hidden="showLoadExternalModal = false"
      @update-framework-imported="onUpdateFrameworkImported"
      @update-framework-modal-hidden="showUpdateFrameworkModal = false"
      @export-modal-hidden="showExportModal = false"
      @clone-framework-confirmed="onCloneFrameworkConfirmed"
      @clone-framework-modal-hidden="showCloneFrameworkModal = false"
      @delete-association-confirmed="onDeleteAssociationConfirmed"
      @delete-association-modal-hidden="showDeleteAssociationModal = false"
      @dynamic-edit-updated="handleUpdated"
      @dynamic-edit-created="onDynamicEditCreated"
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
import { useViewedDoc } from '../../composables/useViewedDoc';
import { useTreeNavigation } from '../../composables/useTreeNavigation.js';
import { useAnnouncer } from '../../composables/useAnnouncer.js';
import { useDynamicEditModal } from '../../composables/useDynamicEditModal.js';
import { useModalState } from '../../composables/useModalState.js';
import { useSideDocument } from '../../composables/useSideDocument.js';
import { useMercureNotifications } from '../../composables/useMercureNotifications.js';
import { useDocumentLoader } from '../../composables/useDocumentLoader.js';
import { useFrameworkSearch } from '../../composables/useFrameworkSearch.js';
import { useTreeEditorHandlers } from '../../composables/useTreeEditorHandlers.js';
import { logger } from '../../utils/logger.js';
import { findItem, findItemPath } from '../../utils/tree.js';

// Components
import DualFrameworkHeader from './DualFrameworkHeader.vue';
import TreePanelSection from './TreePanelSection.vue';
import ModalManager from './ModalManager.vue';
import RightSidePanel from '../shared/panels/RightSidePanel.vue';
import SideBySideTreePanel from './SideBySideTreePanel.vue';

// ---------------------------------------------------------------------------
// Scroll timeout refs (per-instance isolation)
// ---------------------------------------------------------------------------
const scrollTimeoutId = ref(null);
const docChangeTimeoutId = ref(null);

// ---------------------------------------------------------------------------
// Stores
// ---------------------------------------------------------------------------
const documentStore = useDocumentStore();
const currentDocumentStore = useCurrentDocumentStore();
const filterStore = useFilterStore();
const itemStore = useItemStore();
const viewStore = useViewStore();
const contextStore = useEditorContextStore();
const route = useRoute();
const router = useRouter();
const announcer = useAnnouncer();

// ---------------------------------------------------------------------------
// Core state
// ---------------------------------------------------------------------------
const doc = computed(() => currentDocumentStore.currentDocument || { title: '', status: '', items: [] });
const loading = computed(() => documentStore.loading);
const error = computed(() => documentStore.error);
const searchQuery = computed(() => filterStore.searchQuery);
const selectedId = ref(route.params.itemId || null);
const currentDoc = computed(() => currentDocumentStore.currentDocument);
const rightPanelMode = ref('itemDetails');

const { viewedDoc, isViewingDifferentFramework } = useViewedDoc({ transformItems: true });

// ---------------------------------------------------------------------------
// Item selection
// ---------------------------------------------------------------------------
const selectedItem = computed(() => {
  const id = selectedId.value;
  if (!id) return null;
  const editedDocItem = findItem(doc.value.items || [], id);
  if (editedDocItem) return editedDocItem;
  if (isViewingDifferentFramework.value && viewedDoc.value?.items) {
    return findItem(viewedDoc.value.items, id) || null;
  }
  return null;
});

// ---------------------------------------------------------------------------
// Filtered doc + tree items
// ---------------------------------------------------------------------------
const filteredDoc = computed(() => ({
  ...doc.value,
  items: filterStore.filterItemsRecursively(
    doc.value.items || [],
    searchQuery.value,
    filterStore.selectedFilters,
    filterStore.selectedAssociationGroup
  ),
}));

const treeItems = computed(() => {
  if (!filteredDoc.value) return [];
  return [{
    identifier: filteredDoc.value.id || 'document-root',
    title: filteredDoc.value.title || 'Document Root',
    abbreviatedStatement: filteredDoc.value.title || 'Document Root',
    humanCodingScheme: '',
    children: filteredDoc.value.items || [],
    itemType: 'document',
    isDocumentRoot: true,
  }];
});

// ---------------------------------------------------------------------------
// Tree navigation
// ---------------------------------------------------------------------------
const availableTypes = ['general', 'assessment', 'course', 'credential', 'job', 'organization', 'public_key', 'identifier'];

const {
  focusedItemId,
  setFocus,
  handleKeyDown: navigationHandleKeyDown,
  isItemExpanded,
  expandItem,
  collapseItem,
  toggleExpanded,
  expandToItem,
  initializeFocus,
} = useTreeNavigation({
  items: treeItems,
  selectedId: computed(() => viewStore.currentItem?.identifier),
  onSelect: (id) => onSelect(id),
  externalFocusedItemId: computed({
    get: () => viewStore.focusedItemId,
    set: (val) => viewStore.setFocusedItemId(val),
  }),
  externalExpandedState: computed(() => viewStore.itemViewState),
});

async function navigateToItem(itemId) {
  if (!itemId) return;
  const frameworkId = currentDoc.value?.id;
  if (!frameworkId) return;

  const item =
    findItem(doc.value?.items || [], itemId) ||
    (viewedDoc.value ? findItem(viewedDoc.value.items, itemId) : null);
  if (!item) return;

  expandToItem(itemId);
  viewStore.setCurrentItem(item);
  router.push(`/${frameworkId}/${itemId}`);
  viewStore.setLastSelectedItem(frameworkId, itemId);

  await nextTick();
  const el = document.querySelector(`[data-tree-node-id="${itemId}"]`);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
}

provide('treeNavigation', {
  focusedItemId,
  setFocus,
  handleKeyDown: navigationHandleKeyDown,
  isItemExpanded,
  expandItem,
  collapseItem,
  toggleExpanded,
  navigateToItem,
});


// ---------------------------------------------------------------------------
// Dynamic edit modal
// ---------------------------------------------------------------------------
const onItemUpdate = async (updatedItem) => {
  logger.debug('onItemUpdate triggered with:', updatedItem);
  try {
    if (updatedItem && updatedItem.identifier) {
      logger.debug('Sending update to backend for:', updatedItem.identifier);
      const result = await currentDocumentStore.updateItem(updatedItem.identifier, updatedItem);
      logger.debug('Backend response:', result);
      // Local update
      itemStore.updateItem(currentDoc.value, updatedItem);
      // Force revalidation to update cached data
      if (currentDoc.value?.id) {
        await documentStore.revalidatePackage(currentDoc.value.id, true);
      }
      currentDocumentStore.reloadActiveDocument();
    } else {
      logger.warn('updatedItem is missing identifier:', updatedItem);
    }
  } catch (error) {
    logger.error('Failed to update item:', error);
    logger.error('Update item error detail:', error);
  }
};

const {
  showEditModal,
  showAddModal,
  isEditModalVisible,
  editingItem,
  modalParentItem,
  editModalComponent,
  handleUpdated,
  handleCreated,
  handleEditHidden,
} = useDynamicEditModal(
  onItemUpdate,
  null, // break circular dependency, don't pass handleAddChild here
  availableTypes
);
const modalState = useModalState();
const {
  showEditDocModal,
  showEditAssociationModal,
  showDeleteModal,
  showExemplarModal,
  showAssocGroupModal,
  showLoadExternalModal,
  showCrossTreeModal,
  editingAssociation,
  itemsToDelete,
  deleteType,
  addingAssociation,
  addingAssociationType,
  addingAssociationOrigin,
  addingAssociationDestination,
  crossTreeSource,
  crossTreeTarget,
  openCrossTreeModal,
  closeCrossTreeModal,
  closeEditAssociationModal,
  showUpdateFrameworkModal,
  showExportModal,
  showDeleteAssociationModal,
  associationToDelete,
  openDeleteAssociationModal,
  closeDeleteAssociationModal,
} = modalState;

// ---------------------------------------------------------------------------
// Side document
// ---------------------------------------------------------------------------
const { sideDocument, loadingSideDoc, sideDocError, onSideDocumentSelect, onSideSelect } =
  useSideDocument({
    onDocumentLoaded: (sideDoc, docData) => {
      // Save framework selection when document is loaded
      if (sideDoc?.id) {
        contextStore.setFrameworkSelection(rightPanelMode.value, sideDoc.id);
      }
    }
  });

// ---------------------------------------------------------------------------
// Document loader
// ---------------------------------------------------------------------------
const {
  onDocumentChanged: documentLoaderOnDocumentChanged,
  onExternalDocumentRequested: documentLoaderOnExternalDocumentRequested,
  onExternalDocumentUrlLoaded: documentLoaderOnExternalDocumentUrlLoaded,
  initializeDocument,
} = useDocumentLoader({ sideDocument, onDocumentLoaded: () => {} });

// Mercure
const { connect: connectMercure } = useMercureNotifications();

// ---------------------------------------------------------------------------
// Panel / search state
// ---------------------------------------------------------------------------
const treeSearchQuery = ref('');

// ---------------------------------------------------------------------------
// Clone Framework modal state
// ---------------------------------------------------------------------------
const showCloneFrameworkModal = ref(false);
const cloneFrameworkTitle = ref('');

// Use extracted search composable
const { matchCount, matchingItemIds } = useFrameworkSearch({
  treeSearchQuery,
  doc,
  viewedDoc,
  isViewingDifferentFramework,
});

// ---------------------------------------------------------------------------
// Store-derived computed
// ---------------------------------------------------------------------------
const availableDocuments = computed(() => documentStore.documents);
const availableSubjects = computed(() => filterStore.availableSubjects);
const associationGroups = computed(() => currentDocumentStore.associationGroups);
const selectedAssociationGroupValue = computed({
  get: () => filterStore.selectedAssociationGroup,
  set: (value) => filterStore.setSelectedAssociationGroup(value),
});

// ---------------------------------------------------------------------------
// Event handlers (from composable)
// ---------------------------------------------------------------------------
const {
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
} = useTreeEditorHandlers({
  documentStore,
  currentDocumentStore,
  filterStore,
  itemStore,
  viewStore,
  contextStore,
  router,
  route,
  currentDoc,
  doc,
  viewedDoc,
  selectedId,
  isViewingDifferentFramework,
  rightPanelMode,
  filteredDoc,
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
  showEditModal,
  documentLoaderOnExternalDocumentRequested,
  documentLoaderOnExternalDocumentUrlLoaded,
  sideDocument,
  onSideDocumentSelect,
  expandItem,
  initializeFocus,
  setFocus,
  scrollToSelectedItem,
  // Announcer
  announcer,
  // Modal state for association deletion
  showDeleteAssociationModal,
  associationToDelete,
  openDeleteAssociationModal,
  closeDeleteAssociationModal,
  // Mercure
  connectMercure,
});

function onDynamicEditCreated(newItem) {
  // Capture parent before clearing modal state
  const parent = modalParentItem.value;
  // Hide modal and clean up state
  handleCreated(newItem);
  // Actually create and add the item
  handleAddChild(newItem, parent);
}

// ---------------------------------------------------------------------------
// Import modal handlers
// ---------------------------------------------------------------------------
function onUpdateFrameworkImported() {
  // The modal itself triggers a page reload after success
  logger.info('Framework update import completed');
}

// ---------------------------------------------------------------------------
// Clone Framework modal handlers
// ---------------------------------------------------------------------------
function onCloneFramework() {
  // Show the clone framework modal with the current document's title
  showCloneFrameworkModal.value = true;
  cloneFrameworkTitle.value = currentDoc.value?.title || '';
}

async function onCloneFrameworkConfirmed() {
  const frameworkIdentifier = currentDoc.value?.identifier;
  if (!frameworkIdentifier) {
    logger.error('No framework identifier available for cloning');
    showCloneFrameworkModal.value = false;
    return;
  }

  try {
    // Make a POST request to the clone endpoint
    const response = await fetch(`/clone/framework/${frameworkIdentifier}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      throw new Error(`Failed to clone framework: ${response.statusText} (${response.status})`);
    }

    // The backend returns a 302 redirect to /editor/{newFrameworkIdentifier}
    // Since fetch follows redirects automatically, response.url will be the final URL
    // Navigate to the new framework using browser navigation
    window.location.href = response.url;

  } catch (error) {
    logger.error('Failed to clone framework:', error);
    // TODO: Show error message to user
  } finally {
    showCloneFrameworkModal.value = false;
  }
}

function onCloneFrameworkModalHidden() {
  // Hide the clone framework modal
  showCloneFrameworkModal.value = false;
}

// ---------------------------------------------------------------------------
// Scroll to selected item
// ---------------------------------------------------------------------------
async function scrollToSelectedItem() {
  if (!selectedId.value || !doc.value?.items) return;
  const path = findItemPath(doc.value.items, selectedId.value);
  if (!path?.length) return;

  for (let i = 0; i < path.length - 1; i++) expandItem(path[i]);
  await nextTick();

  if (scrollTimeoutId.value) clearTimeout(scrollTimeoutId.value);
  scrollTimeoutId.value = setTimeout(() => {
    const identifier = viewStore.currentItem?.identifier || route.params.itemId;
    if (!identifier) return;
    const selectedElement = document.querySelector(`[data-tree-node-id="${identifier}"]`);
    if (selectedElement) {
      selectedElement.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
      setFocus(identifier);
    }
    scrollTimeoutId.value = null;
  }, 100);
}

// ---------------------------------------------------------------------------
// Watchers
// ---------------------------------------------------------------------------
watch(() => route.params.itemId, (newItemId) => {
  selectedId.value = newItemId || null;
  if (newItemId && currentDoc.value?.id) {
    viewStore.setLastSelectedItem(currentDoc.value.id, newItemId);
  }
}, { immediate: true });

watch(() => viewStore.currentItem, (newItem) => {
  if (newItem && newItem.identifier !== selectedId.value) {
    selectedId.value = newItem.identifier;
  }
});

watch(() => doc.value?.id, (newDocId, oldDocId) => {
  if (newDocId) {
    expandItem(newDocId);
    initializeFocus();
    connectMercure(newDocId);

    if (newDocId !== oldDocId && (viewStore.currentItem?.identifier || route.params.itemId)) {
      if (docChangeTimeoutId.value) clearTimeout(docChangeTimeoutId.value);
      docChangeTimeoutId.value = setTimeout(() => {
        scrollToSelectedItem();
        docChangeTimeoutId.value = null;
      }, 200);
    }
  }
}, { immediate: true });

watch(() => viewedDoc.value?.id, (newViewedDocId, oldViewedDocId) => {
  if (newViewedDocId && newViewedDocId !== oldViewedDocId) {
    expandItem(newViewedDocId);
    logger.debug('Expanded viewed document root:', newViewedDocId);
  }
}, { immediate: false });

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------
onMounted(async () => {
  await initializeDocument();
  if (selectedId.value) scrollToSelectedItem();
});

onUnmounted(() => {
  if (scrollTimeoutId.value) clearTimeout(scrollTimeoutId.value);
  if (docChangeTimeoutId.value) clearTimeout(docChangeTimeoutId.value);
});
</script>

<style scoped>
.details-panel {
  min-height: 0;
}

/* Visual distinction for tree panel when viewing different framework */
:deep(.viewing-different-framework) {
  background-color: #f8f9fa;
  border-right: 3px solid #adb5bd;
}
</style>
