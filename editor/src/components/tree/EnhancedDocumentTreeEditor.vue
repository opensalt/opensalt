<template>
  <div>
    <div v-if="loading" class="d-flex justify-content-center align-items-center" style="height: 100%;" role="status" aria-live="polite">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading document...</span>
      </div>
    </div>
    <div v-else-if="error" class="alert alert-danger my-4" role="alert" aria-live="assertive">{{ error }}</div>
    <main v-else class="row g-0" style="height: 100%;">
      <!-- Tree panel -->
      <section class="col-5 tree-panel d-flex flex-column">
        <!-- Document Selector -->
        <DocumentSelector
          :current-doc1="currentDoc"
          :available-documents="availableDocuments"
          @document-changed="onDocumentChanged"
          @external-document-requested="onExternalDocumentRequested"
        />

        <!-- Tree Filter -->
        <TreeFilter
          v-model="treeSearchQuery"
          :match-count="matchCount"
          @clear="onClearTreeFilter"
          class="mx-2"
        />

        <!-- Search and Filter (legacy, hidden) -->
        <SearchFilter
          v-if="false"
          :available-subjects="availableSubjects"
          @search="onSearch"
          @filter="onFilter"
          @clear="onClearSearch"
        />

        <!-- Association Group Selector -->
        <AssociationGroupSelector
          v-model="selectedAssociationGroupValue"
          :association-groups="associationGroups"
        />

        <!-- Bulk Actions Toggle -->
        <div class="d-flex align-items-center mb-2 gap-2">
          <button
            class="btn btn-sm"
            :class="viewStore.bulkMode ? 'btn-warning' : 'btn-outline-secondary'"
            @click="viewStore.toggleBulkMode()"
          >
            <i class="bi" :class="viewStore.bulkMode ? 'bi-check-square' : 'bi-square'"></i>
            {{ viewStore.bulkMode ? 'Exit Bulk Mode' : 'Bulk Select' }}
          </button>

          <!-- Bulk Actions Dropdown -->
          <div v-if="viewStore.bulkMode" class="dropdown">
            <button
              class="btn btn-sm btn-outline-primary dropdown-toggle"
              type="button"
              id="bulkActionsDropdown"
              data-bs-toggle="dropdown"
              aria-expanded="false"
              :disabled="viewStore.selectedItems.size === 0"
            >
              Bulk Actions ({{ viewStore.selectedItems.size }})
            </button>
            <ul class="dropdown-menu" aria-labelledby="bulkActionsDropdown">
              <li>
                <button class="dropdown-item" @click="handleBulkDelete">
                  <i class="bi bi-trash me-2"></i>Delete Selected
                </button>
              </li>
              <li>
                <button class="dropdown-item" @click="handleMakeItemsParents">
                  <i class="bi bi-arrow-up-circle me-2"></i>Make Items Parents
                </button>
              </li>
            </ul>
          </div>
        </div>

        <!-- Tree View -->
        <div class="mt-3 flex-grow-1 overflow-auto">
          <TreeView
            :doc="filteredDoc"
            :selected-id="selectedId"
            @select="onSelect"
            @dblclick="onDblClick"
            :search-query="treeSearchQuery"
            @tree-change="onTreeChange"
          />
        </div>
      </section>

      <!-- Details/info panel -->
      <section class="col-7 details-panel d-flex flex-column">
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
          :current-document-id="currentDoc?.id"
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

    <!-- Modals -->
    <EditDocModal
      :document="currentDoc"
      :show="showEditDocModal"
      @saved="onDocSaved"
      @hidden="showEditDocModal = false"
    />


    <AssociateModal
      :origin-item="associationOrigin"
      :destination-item="associationDestination"
      :available-groups="associationGroups"
      :show="showAssociateModal"
      @created="onAssociationCreated"
      @hidden="showAssociateModal = false"
    />

    <EditAssociationModal
      :association="editingAssociation"
      :available-groups="associationGroups"
      :show="showEditAssociationModal"
      @updated="onAssociationUpdated"
      @hidden="showEditAssociationModal = false"
    />

    <DeleteItemsModal
      :items="itemsToDelete"
      :delete-type="deleteType"
      :show="showDeleteModal"
      @confirmed="onItemsDeleted"
      @hidden="showDeleteModal = false"
    />

    <ExemplarModal
      :current-item="selectedItem"
      :show="showExemplarModal"
      @added="onExemplarAdded"
      @hidden="showExemplarModal = false"
    />

    <AssociationGroupModal
      :show="showAssocGroupModal"
      :association-groups="associationGroups"
      @saved="onAssocGroupSaved"
      @deleted="onAssocGroupDeleted"
      @hidden="showAssocGroupModal = false"
    />

    <CrossTreeDropModal
      :show="showCrossTreeModal"
      :source-item="crossTreeSource"
      :target-item="crossTreeTarget"
      @close="onCrossTreeClose"
      @copy="onCrossTreeCopy"
      @associate="onCrossTreeAssociate"
    />

    <EditItemModal
      :item="selectedItem"
      :show="showEditItemModal"
      @saved="onItemSaved"
      @hidden="showEditItemModal = false"
    />

    <LoadExternalDocumentModal
      :show="showLoadExternalModal"
      @load="onExternalDocumentUrlLoaded"
      @hidden="showLoadExternalModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useDocumentStore } from '../../stores/documentStore';
import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';
import { useFilterStore } from '../../stores/filterStore';
import { useItemStore } from '../../stores/itemStore';
import { useAssociationStore } from '../../stores/associationStore';
import { useViewStore } from '../../stores/viewStore';
import TreeView from './TreeView.vue';
import RightSidePanel from '../shared/panels/RightSidePanel.vue';
import DocumentSelector from '../shared/common/DocumentSelector.vue';
import SearchFilter from '../shared/common/SearchFilter.vue';
import AssociationGroupSelector from '../shared/common/AssociationGroupSelector.vue';
import EditDocModal from '../shared/modals/EditDocModal.vue';
import EditItemModal from '../shared/modals/EditItemModal.vue';
import AssociateModal from '../association/AssociateModal.vue';
import EditAssociationModal from '../association/EditAssociationModal.vue';
import DeleteItemsModal from '../shared/modals/DeleteItemsModal.vue';
import ExemplarModal from '../shared/modals/ExemplarModal.vue';
import AssociationGroupModal from '../association/AssociationGroupModal.vue';
import ViewSwitcher from '../shared/common/ViewSwitcher.vue';
import TreeFilter from './TreeFilter.vue';
import CrossTreeDropModal from './CrossTreeDropModal.vue';
import LoadExternalDocumentModal from '../shared/modals/LoadExternalDocumentModal.vue';
import SideBySideTreePanel from './SideBySideTreePanel.vue';

// Use the Pinia stores
const documentStore = useDocumentStore();
const currentDocumentStore = useCurrentDocumentStore();
const filterStore = useFilterStore();
const itemStore = useItemStore();
const associationStore = useAssociationStore();
const viewStore = useViewStore();
const route = useRoute();
const router = useRouter();

// Use store state and computed properties
const doc = computed(() => currentDocumentStore.currentDocument || { title: '', status: '', items: [] });
const loading = computed(() => documentStore.loading);
const error = computed(() => documentStore.error);
const searchQuery = computed(() => filterStore.searchQuery);
const selectedId = ref(route.params.itemId || null);
const selectedItem = computed(() => findItem(doc.value.items || [], selectedId.value));

// Watch for route changes to update selected item
watch(() => route.params.itemId, (newItemId) => {
  selectedId.value = newItemId || null;
}, { immediate: true });
const filteredDoc = computed(() => ({
  ...doc.value,
  items: filterStore.filterItemsRecursively(doc.value.items || [], searchQuery.value, filterStore.selectedFilters, filterStore.selectedAssociationGroup)
}));

// Modal states
const showEditDocModal = ref(false);
const showEditItemModal = ref(false);
const showAssociateModal = ref(false);
const showEditAssociationModal = ref(false);
const showDeleteModal = ref(false);
const showExemplarModal = ref(false);
const showAssocGroupModal = ref(false);
const showLoadExternalModal = ref(false);

// Modal data
const associationOrigin = ref(null);
const associationDestination = ref(null);
const editingAssociation = ref(null);
const itemsToDelete = ref([]);
const deleteType = ref('single'); // 'single' or 'bulk'
// Cross-tree drop state
const showCrossTreeModal = ref(false);
const crossTreeSource = ref(null);
const crossTreeTarget = ref(null);
const crossTreePosition = ref(null);

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

function onClearTreeFilter() {
  treeSearchQuery.value = '';
}

// Side document state (for Copy Items / Create Associations modes)
const sideDocument = ref(null);
const loadingSideDoc = ref(false);
const sideDocError = ref('');
const sideSelectedId = ref(null);

async function onSideDocumentSelect(documentId) {
  if (!documentId) {
    sideDocument.value = null;
    sideDocError.value = '';
    return;
  }

  loadingSideDoc.value = true;
  sideDocError.value = '';

  try {
    const docData = await documentStore.fetchDocument(documentId);
    const cfDoc = docData.CFDocument || {};
    const items = currentDocumentStore.transformCASEItems(
      docData.CFItems || [],
      docData.CFAssociations || [],
      cfDoc.identifier
    );

    sideDocument.value = {
      id: cfDoc.identifier,
      title: cfDoc.title || 'Untitled',
      items: items
    };
  } catch (error) {
    console.error('Error loading side document:', error);
    sideDocError.value = 'Failed to load document';
    sideDocument.value = null;
  } finally {
    loadingSideDoc.value = false;
  }
}

function onSideSelect(id) {
  sideSelectedId.value = id;
}

// Use store data
const availableDocuments = computed(() => documentStore.documents);
const availableSubjects = computed(() => filterStore.availableSubjects);
const associationGroups = computed(() => currentDocumentStore.associationGroups);
const selectedAssociationGroupValue = computed({
  get: () => filterStore.selectedAssociationGroup,
  set: (value) => filterStore.setSelectedAssociationGroup(value)
});

const currentDoc = computed(() => currentDocumentStore.currentDocument);

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
      }, docData.CFAssociationGroupings || [], docData.CFAssociations || []);
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
        }, docData.CFAssociationGroupings || [], docData.CFAssociations || []);
      }
    } else {
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
    // Open edit item modal
    showEditItemModal.value = true;
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
        // Direct copy or prompt?
        // jQuery logic: prompt if needed, or just copy.
        // Let's use the modal for now to confirm action or differentiate if ambiguous
        crossTreeSource.value = draggedItem;
        crossTreeTarget.value = targetItem;
        crossTreePosition.value = position;
        showCrossTreeModal.value = true;
      } else if (rightPanelMode.value === 'createAssociations') {
        // Prepare association modal directly
        associationOrigin.value = draggedItem; // The dragged item is the origin (from side tree) -> wait, depends on direction.
        // Usually dragging FROM side tree TO main tree.
        // If draggedItem is external, it's the origin.
        // If targetItem is local, it's the destination.
        // Or vice versa?
        // jQuery: "Create Associations" tab implies we are creating associations FROM the side tree TO the main tree?
        // Let's assume dragged item = origin, target item = destination for now.
        associationOrigin.value = draggedItem;
        associationDestination.value = targetItem;
        showAssociateModal.value = true;
      } else {
        // Default behavior (Item Details mode) - maybe prompt?
        crossTreeSource.value = draggedItem;
        crossTreeTarget.value = targetItem;
        crossTreePosition.value = position;
        showCrossTreeModal.value = true;
      }
    }
  }
}

// Cross-tree handlers
function onCrossTreeClose() {
  showCrossTreeModal.value = false;
  crossTreeSource.value = null;
  crossTreeTarget.value = null;
  crossTreePosition.value = null;
}

async function onCrossTreeCopy() {
  if (!crossTreeSource.value || !crossTreeTarget.value) return;

  try {
    const documentId = currentDoc.value?.id;
    const targetParentId = crossTreeTarget.value.identifier === documentId ? null : crossTreeTarget.value.identifier;


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
  } catch (error) {
    console.error('Failed to copy item:', error);
    // TODO: Show error toast
  }
}

function onCrossTreeAssociate() {
  if (!crossTreeSource.value || !crossTreeTarget.value) return;

  // We want to create the association IN the current document (target), pointing TO the side item (source)
  // So the Main Tree Item is the Origin, and Side Tree Item is the Destination
  associationOrigin.value = crossTreeTarget.value;
  associationDestination.value = crossTreeSource.value;

  showAssociateModal.value = true;
  onCrossTreeClose();
}

async function onDocumentChanged({ side, documentId }) {
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
      }, docData.CFAssociationGroupings || [], docData.CFAssociations || []);
    }
  } catch (error) {
    console.error('Error loading document:', error);
  }
}

async function onExternalDocumentRequested() {
  // If arguments passed (from document selector), it's empty or event object
  // If called from SidePanel, it expects us to show modal
  showLoadExternalModal.value = true;
}

async function onExternalDocumentUrlLoaded(url) {
  showLoadExternalModal.value = false;

  if (!url) return;

  // Assume if this was opened, it was likely for the side panel given current UI hooks.
  // Unless we add a way to load external doc as MAIN.
  // For now, let's load it into the side panel if the side panel requested it.
  // But wait, the main view has no "Load External" button yet?
  // documentStore can load it.

  loadingSideDoc.value = true;
  sideDocError.value = '';

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

    // Also add to available documents list so it stays in the dropdown?
    // documentStore.documents usually comes from server.
    // We might want to add a temporary entry to availableDocuments computed?
    // But availableDocuments is read-only from store.
    // For now, just setting sideDocument is enough for display.

  } catch (error) {
    console.error('Error loading external document:', error);
    sideDocError.value = `Failed to load document: ${error.message}`;
    sideDocument.value = null;
  } finally {
    loadingSideDoc.value = false;
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
  showEditItemModal.value = true;
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
  showExemplarModal.value = true;
}

function onAddAssociation(item) {
  associationOrigin.value = item;
  associationDestination.value = null;
  showAssociateModal.value = true;
}

function onEditAssociation(association) {
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
}

function onAssociationUpdated(association) {
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
  if (newItem && currentDocument.value) {
    const success = itemStore.addItem(currentDocument.value, newItem, null);
    if (success) {
    } else {
      console.error('Failed to add root item');
    }
  }
}

function onManageAssociationGroups() {
  showAssocGroupModal.value = true;
}

function onItemSaved(updatedItem) {
  itemStore.updateItem(currentDocument.value, updatedItem);
}

function handleBulkDelete() {
  const selectedItems = Array.from(viewStore.selectedItems);
  if (selectedItems.length === 0) return;

  // Set up the delete modal for multiple items
  itemsToDelete.value = selectedItems.map(id => findItem(doc.value.items || [], id)).filter(Boolean);
  deleteType.value = 'bulk';
  showDeleteModal.value = true;
}

function handleMakeItemsParents() {
  const selectedItems = Array.from(viewStore.selectedItems);
  if (selectedItems.length === 0) return;

  // For each selected item, if it doesn't have children, add a dummy child to make it a parent
  selectedItems.forEach(itemId => {
    const item = findItem(doc.value.items || [], itemId);
    if (item && (!item.children || item.children.length === 0)) {
      // Add a dummy child to make it a parent
      if (!item.children) item.children = [];
      const newChild = {
        identifier: `${itemId}-child-${Date.now()}`,
        title: 'New Child Item',
        abbreviatedStatement: 'New child item',
        fullStatement: 'This is a new child item created to make the parent a parent node.',
        itemType: 'item',
        lastChanged: new Date().toISOString(),
        children: []
      };
      item.children.push(newChild);
    }
  });

  // Clear selection and exit bulk mode
  viewStore.clearSelection();
  viewStore.setBulkMode(false);

  // Emit tree change to refresh the view
  emit('tree-change', { type: 'bulk-parent-creation', items: selectedItems });
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
</style>
