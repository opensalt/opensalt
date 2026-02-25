<template>
  <div class="h-100 d-flex flex-column">
    <!-- Page-wide spinner only shows during initial page load (no document loaded yet) -->
    <div v-if="loading && !currentDoc" class="d-flex justify-content-center align-items-center" style="height: 100%;" role="status" aria-live="polite">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading document...</span>
      </div>
    </div>
    <div v-else-if="error && !currentDoc" class="alert alert-danger my-4" role="alert" aria-live="assertive">{{ error }}</div>
    <main v-else class="row g-0" style="height: 100%; min-height: 0;">
      <!-- Tree panel (extracted to TreePanelSection component) -->
      <TreePanelSection
        class="col-5"
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
        @document-changed="onDocumentChanged"
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
import { ref, computed, onMounted, watch, provide } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useDocumentStore } from '../../stores/documentStore';
import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';
import { useFilterStore } from '../../stores/filterStore';
import { useItemStore } from '../../stores/itemStore';
import { useViewStore } from '../../stores/viewStore';
import { useTreeNavigation } from '../../composables/useTreeNavigation.js';
import { useAnnouncer } from '../../composables/useAnnouncer.js';
import { useDynamicEditModal } from '../../composables/useDynamicEditModal.js';
import { useModalState } from '../../composables/useModalState.js';
import { useSideDocument } from '../../composables/useSideDocument.js';
import { useCrossTreeOperations } from '../../composables/useCrossTreeOperations.js';

// Components
import TreePanelSection from './TreePanelSection.vue';
import ModalManager from './ModalManager.vue';
import RightSidePanel from '../shared/panels/RightSidePanel.vue';
import SideBySideTreePanel from './SideBySideTreePanel.vue';

// Initialize stores (must be initialized before computed properties that use them)
const documentStore = useDocumentStore();
const currentDocumentStore = useCurrentDocumentStore();
const filterStore = useFilterStore();
const itemStore = useItemStore();
const viewStore = useViewStore();
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
const selectedItem = computed(() => findItem(doc.value.items || [], selectedId.value));
const currentDoc = computed(() => currentDocumentStore.currentDocument);

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
  selectedId,
  onSelect: (id) => onSelect(id)
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
  sideSelectedId,
  loadingSideDoc,
  sideDocError,
  onSideDocumentSelect,
  onSideSelect
} = useSideDocument();

// Panel modes
const rightPanelMode = ref('itemDetails');

// Tree filter state
const treeSearchQuery = ref('');

// Count matching items for the match count badge
const matchCount = computed(() => {
  if (!treeSearchQuery.value) return null;
  const query = treeSearchQuery.value.toLowerCase();
  return countMatches(doc.value.items || [], query);
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

  findMatches(doc.value.items || []);
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
  // Store the selected item with document context for view switching
  if (newItemId && currentDoc.value?.id) {
    viewStore.setLastSelectedItem(currentDoc.value.id, newItemId);
  }
}, { immediate: true });

// Initialize focus and expand document root when document loads
watch(() => doc.value?.id, (newDocId) => {
  if (newDocId) {
    // Expand the document root by default
    expandItem(newDocId);
    // Initialize focus on the document root
    initializeFocus();
  }
}, { immediate: true });

// Initialize data on mount
onMounted(async () => {
  try {
    const frameworkId = route.params.frameworkId;

    if (frameworkId) {
      const docData = await documentStore.fetchDocument(frameworkId);
      const cfDoc = docData.CFDocument || {};
      const items = currentDocumentStore.transformCASEItems(docData.CFItems || [], docData.CFAssociations || [], cfDoc.identifier);

      currentDocumentStore.selectDocument({
        id: cfDoc.identifier,
        uri: cfDoc.uri || '',
        title: cfDoc.title || 'Untitled',
        description: cfDoc.description || null,
        creator: cfDoc.creator || '',
        subject: cfDoc.subject || null,
        subjectURI: cfDoc.subjectURI || [],
        status: cfDoc.adoptionStatus || 'Draft',
        statusStartDate: cfDoc.statusStartDate || null,
        statusEndDate: cfDoc.statusEndDate || null,
        lastModified: cfDoc.lastChangeDateTime || '',
        language: cfDoc.language || null,
        version: cfDoc.version || null,
        officialSourceURL: cfDoc.officialSourceURL || null,
        publisher: cfDoc.publisher || null,
        licenseURI: cfDoc.licenseURI || null,
        notes: cfDoc.notes || null,
        frameworkType: cfDoc.frameworkType || null,
        caseVersion: cfDoc.caseVersion || null,
        extensions: cfDoc.extensions || null,
        CFPackageURI: cfDoc.CFPackageURI || null,
        items: items
      }, docData.CFAssociationGroupings || [], docData.CFAssociations || [], docData.CFDefinitions || null);
    } else if (!currentDocumentStore.currentDocument || Object.keys(currentDocumentStore.currentDocument).length === 0) {
      await documentStore.fetchDocuments();

      if (documentStore.documents.length > 0) {
        const firstDoc = documentStore.documents[0];
        const docData = await documentStore.fetchDocument(firstDoc.id);
        const cfDoc = docData.CFDocument || {};
        const items = currentDocumentStore.transformCASEItems(docData.CFItems || [], docData.CFAssociations || [], cfDoc.identifier);

        currentDocumentStore.selectDocument({
          id: cfDoc.identifier,
          uri: cfDoc.uri || '',
          title: cfDoc.title || 'Untitled',
          description: cfDoc.description || null,
          creator: cfDoc.creator || '',
          subject: cfDoc.subject || null,
          subjectURI: cfDoc.subjectURI || [],
          status: cfDoc.adoptionStatus || 'Draft',
          statusStartDate: cfDoc.statusStartDate || null,
          statusEndDate: cfDoc.statusEndDate || null,
          lastModified: cfDoc.lastChangeDateTime || '',
          language: cfDoc.language || null,
          version: cfDoc.version || null,
          officialSourceURL: cfDoc.officialSourceURL || null,
          publisher: cfDoc.publisher || null,
          licenseURI: cfDoc.licenseURI || null,
          notes: cfDoc.notes || null,
          frameworkType: cfDoc.frameworkType || null,
          caseVersion: cfDoc.caseVersion || null,
          extensions: cfDoc.extensions || null,
          CFPackageURI: cfDoc.CFPackageURI || null,
          items: items
        }, docData.CFAssociationGroupings || [], docData.CFAssociations || [], docData.CFDefinitions || null);
      }
    }
  } catch (e) {
    console.error('Error initializing data:', e);
  }
});

// Event handlers
function onSelect(id) {
  const frameworkId = currentDocumentStore.currentDocument?.id;
  if (frameworkId) {
    if (id) {
      router.push(`/${frameworkId}/${id}`);
      // Store the selected item with document context for view switching
      viewStore.setLastSelectedItem(frameworkId, id);
    } else {
      router.push(`/${frameworkId}`);
    }
  } else {
    selectedId.value = id;
  }
}

function onDblClick(id) {
  // First select the item
  onSelect(id);

  // Determine if this is the document node or an item
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
    console.error('Failed to copy item:', error);
  }
}

function onCrossTreeClose() {
  closeCrossTreeModal();
}

function onCrossTreeAssociate() {
  if (!crossTreeSource.value || !crossTreeTarget.value) return;

  // We want to create the association IN the current document (target), pointing TO the side item (source)
  // So the Main Tree Item is the Origin, and Side Tree Item is the Destination
  associationOrigin.value = crossTreeTarget.value;
  associationDestination.value = crossTreeSource.value;

  showAssociateModal.value = true;
  closeCrossTreeModal();
}

async function onDocumentChanged({ documentId }) {
  try {
    if (documentId) {
      const docData = await documentStore.fetchDocument(documentId);

      // Transform and set the current document
      const cfDoc = docData.CFDocument || {};
      const items = currentDocumentStore.transformCASEItems(docData.CFItems || [], docData.CFAssociations || [], cfDoc.identifier);

      currentDocumentStore.selectDocument({
        id: cfDoc.identifier,
        uri: cfDoc.uri || '',
        title: cfDoc.title || 'Untitled',
        description: cfDoc.description || null,
        creator: cfDoc.creator || '',
        subject: cfDoc.subject || null,
        subjectURI: cfDoc.subjectURI || [],
        status: cfDoc.adoptionStatus || 'Draft',
        statusStartDate: cfDoc.statusStartDate || null,
        statusEndDate: cfDoc.statusEndDate || null,
        lastModified: cfDoc.lastChangeDateTime || '',
        language: cfDoc.language || null,
        version: cfDoc.version || null,
        officialSourceURL: cfDoc.officialSourceURL || null,
        publisher: cfDoc.publisher || null,
        licenseURI: cfDoc.licenseURI || null,
        notes: cfDoc.notes || null,
        frameworkType: cfDoc.frameworkType || null,
        caseVersion: cfDoc.caseVersion || null,
        extensions: cfDoc.extensions || null,
        CFPackageURI: cfDoc.CFPackageURI || null,
        items: items
      }, docData.CFAssociationGroupings || [], docData.CFAssociations || [], docData.CFDefinitions || null);
    }
  } catch (error) {
    console.error('Error loading document:', error);
  }
}

function onExternalDocumentRequested() {
  showLoadExternalModal.value = true;
}

async function onExternalDocumentUrlLoaded(url) {
  showLoadExternalModal.value = false;

  if (!url) return;

  // Clear any previous error
  documentStore.clearSideDocError();

  try {
    const { data, finalUrl } = await documentStore.loadExternalDocument(url);

    // Transform and set the side document
    const cfDoc = data.CFDocument || {};
    // Use a unique ID for external docs if identifier is missing or clashes
    const externalId = cfDoc.identifier || 'external-' + Date.now();
    const items = currentDocumentStore.transformCASEItems(data.CFItems || [], data.CFAssociations || [], externalId);

    sideDocument.value = {
      id: externalId,
      uri: cfDoc.uri || finalUrl,
      title: cfDoc.title || 'External Document',
      description: cfDoc.description || null,
      items: items
    };
  } catch (error) {
    console.error('Error loading external document:', error);
    sideDocument.value = null;
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
    if (success) {
    } else {
      console.error('Failed to add child item');
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

function onDeleteAssociation(association) {
  // Handle association deletion
}

function onRightPanelModeChanged(mode) {
  rightPanelMode.value = mode;
}

// Placeholder handlers for modal events
function onDocSaved(data) {
}

function onAssociationCreated(association) {
  // Handle the created association from add mode
  // Reset add mode state
  addingAssociation.value = false;
  addingAssociationType.value = '';
  addingAssociationOrigin.value = null;

  // TODO: Call the association store to persist the new association
  console.log('Association created:', association);
}

function onAssociationUpdated(association) {
  // Reset add mode state
  addingAssociation.value = false;
  addingAssociationType.value = '';
  addingAssociationOrigin.value = null;
}

function onEditAssociationModalHidden() {
  closeEditAssociationModal();
}

function onItemsDeleted({ items, deleteType }) {
}

function onExemplarAdded(exemplar) {
}

function onAssocGroupSaved(group) {
}

function onAssocGroupDeleted(group) {
}

function onEditDocument() {
  showEditDocModal.value = true;
}

async function handleAddRootItem(newItem) {
  if (newItem && currentDoc.value) {
    const success = itemStore.addItem(currentDoc.value, newItem, null);
    if (success) {
    } else {
      console.error('Failed to add root item');
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

const docTitle = computed(() => doc.value.title);
const docStatus = computed(() => doc.value.status || 'Draft');
</script>

<style scoped>
/* Component-specific styles can be added here */
.details-panel {
  min-height: 0;
}
</style>
