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

        <!-- Search and Filter -->
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

        <!-- Tree View -->
        <div class="mt-3 flex-grow-1 overflow-auto">
          <TreeView :doc="filteredDoc" @select="onSelect" :search="searchQuery" />
        </div>
      </section>

      <!-- Details/info panel -->
      <section class="col-7 details-panel d-flex flex-column">
        <RightSidePanel
          :current-document="currentDoc"
          :association-groups="associationGroups"
          :selected-item="selectedItem"
          :initial-mode="rightPanelMode"
          @mode-changed="onRightPanelModeChanged"
          @edit-item="onEditItem"
          @delete-item="onDeleteItem"
          @add-child="onAddChild"
          @add-exemplar="onAddExemplar"
          @add-association="onAddAssociation"
          @edit-association="onEditAssociation"
          @delete-association="onDeleteAssociation"
          @edit-document="onEditDocument"
          @add-root-item="onAddRootItem"
          @manage-association-groups="onManageAssociationGroups"
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

    <AddNewChildModal
      :parent-item="selectedItem"
      :item-type="newItemType"
      :show="showAddChildModal"
      @created="onChildCreated"
      @hidden="showAddChildModal = false"
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useFrameworkStore } from '../../stores/frameworkStore';
import TreeView from './TreeView.vue';
import RightSidePanel from '../shared/panels/RightSidePanel.vue';
import DocumentSelector from '../shared/common/DocumentSelector.vue';
import SearchFilter from '../shared/common/SearchFilter.vue';
import AssociationGroupSelector from '../shared/common/AssociationGroupSelector.vue';
import EditDocModal from '../shared/modals/EditDocModal.vue';
import AddNewChildModal from '../shared/modals/AddNewChildModal.vue';
import AssociateModal from '../association/AssociateModal.vue';
import EditAssociationModal from '../association/EditAssociationModal.vue';
import DeleteItemsModal from '../shared/modals/DeleteItemsModal.vue';
import ExemplarModal from '../shared/modals/ExemplarModal.vue';
import AssociationGroupModal from '../association/AssociationGroupModal.vue';
import ViewSwitcher from '../shared/common/ViewSwitcher.vue';

// Use the Pinia store
const frameworkStore = useFrameworkStore();

// Use store state and computed properties
const doc = computed(() => frameworkStore.currentDocument || { title: '', status: '', items: [] });
const loading = computed(() => frameworkStore.loading);
const error = computed(() => frameworkStore.error);
const searchQuery = computed(() => frameworkStore.searchQuery);
const selectedId = ref(null);
const selectedItem = computed(() => findItem(doc.value.items || [], selectedId.value));
const filteredDoc = computed(() => ({
  ...doc.value,
  items: frameworkStore.filteredItems
}));

// Modal states
const showEditDocModal = ref(false);
const showAddChildModal = ref(false);
const showAssociateModal = ref(false);
const showEditAssociationModal = ref(false);
const showDeleteModal = ref(false);
const showExemplarModal = ref(false);
const showAssocGroupModal = ref(false);

// Modal data
const newItemType = ref('');
const associationOrigin = ref(null);
const associationDestination = ref(null);
const editingAssociation = ref(null);
const itemsToDelete = ref([]);
const deleteType = ref('single');

// Panel modes
const rightPanelMode = ref('itemDetails');

// Use store data
const availableDocuments = computed(() => frameworkStore.documents);
const availableSubjects = computed(() => frameworkStore.availableSubjects);
const associationGroups = computed(() => frameworkStore.associationGroups);
const selectedAssociationGroupValue = computed({
  get: () => frameworkStore.selectedAssociationGroup,
  set: (value) => frameworkStore.setSelectedAssociationGroup(value)
});

const currentDoc = computed(() => frameworkStore.currentDocument);

// Initialize data on mount
onMounted(async () => {
  try {
    console.log('[DEBUG] EnhancedDocumentTreeEditor onMounted - currentDocument state:', {
      currentDocument: frameworkStore.currentDocument,
      isNull: frameworkStore.currentDocument === null,
      isUndefined: frameworkStore.currentDocument === undefined,
      isEmptyObject: frameworkStore.currentDocument && Object.keys(frameworkStore.currentDocument).length === 0,
      hasItems: frameworkStore.currentDocument?.items?.length > 0,
      documentKeys: frameworkStore.currentDocument ? Object.keys(frameworkStore.currentDocument) : []
    });

    if (!frameworkStore.currentDocument || Object.keys(frameworkStore.currentDocument).length === 0) {
      console.log('[DEBUG] No current document found, fetching documents...');
      // Fetch the list of available documents
      await frameworkStore.fetchDocuments();

      // If there are documents available, load the first one as an example
      if (frameworkStore.documents.length > 0) {
        const firstDoc = frameworkStore.documents[0];
        console.log('[DEBUG] Loading first document:', firstDoc.id);
        await frameworkStore.fetchDocument(firstDoc.id);
      }
    } else {
      console.log('[DEBUG] Current document already exists, skipping fetch');
    }
  } catch (e) {
    console.error('Error initializing data:', e);
  }
});

// Event handlers
function onSelect(id) {
  selectedId.value = id;
}

async function onDocumentChanged({ side, documentId }) {
  try {
    if (documentId) {
      await frameworkStore.fetchDocument(documentId);
    }
  } catch (error) {
    console.error('Error loading document:', error);
  }
}

function onExternalDocumentRequested({ url }) {
  // Handle external document request
  console.log('External document requested:', url);
}

function onSearch({ query, filters }) {
  frameworkStore.setSearchQuery(query);
  if (filters) {
    frameworkStore.setFilters(filters);
  }
}

function onFilter(filters) {
  frameworkStore.setFilters(filters);
}

function onClearSearch() {
  frameworkStore.setSearchQuery('');
  frameworkStore.clearFilters();
  selectedAssociationGroupValue.value = 'all';
}



// Modal event handlers
function onEditItem(item) {
  showEditDocModal.value = true;
}

function onDeleteItem(item) {
  itemsToDelete.value = [item];
  deleteType.value = 'single';
  showDeleteModal.value = true;
}

function onAddChild(parentItem) {
  newItemType.value = '';
  showAddChildModal.value = true;
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
  console.log('Delete association:', association);
}

function onRightPanelModeChanged(mode) {
  rightPanelMode.value = mode;
}

// Placeholder handlers for modal events
function onDocSaved(data) {
  console.log('Document saved:', data);
}

function onChildCreated(child) {
  console.log('Child created:', child);
}

function onAssociationCreated(association) {
  console.log('Association created:', association);
}

function onAssociationUpdated(association) {
  console.log('Association updated:', association);
}

function onItemsDeleted({ items, deleteType }) {
  console.log('Items deleted:', items, deleteType);
}

function onExemplarAdded(exemplar) {
  console.log('Exemplar added:', exemplar);
}

function onAssocGroupSaved(group) {
  console.log('Association group saved:', group);
}

function onAssocGroupDeleted(group) {
  console.log('Association group deleted:', group);
}

function onEditDocument() {
  showEditDocModal.value = true;
}

function onAddRootItem() {
  // Handle adding root item - could open a modal or navigate to item creation
  console.log('Add root item requested');
}

function onManageAssociationGroups() {
  showAssocGroupModal.value = true;
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
