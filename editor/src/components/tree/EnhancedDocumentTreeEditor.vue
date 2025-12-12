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
          <TreeView :doc="filteredDoc" :selected-id="selectedId" @select="onSelect" :search="searchQuery" />
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
          @add-child="handleAddChild"
          @add-exemplar="onAddExemplar"
          @add-association="onAddAssociation"
          @edit-association="onEditAssociation"
          @delete-association="onDeleteAssociation"
          @edit-document="onEditDocument"
          @add-root-item="handleAddRootItem"
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

    <EditItemModal
      :item="selectedItem"
      :show="showEditItemModal"
      @saved="onItemSaved"
      @hidden="showEditItemModal = false"
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

// Use the Pinia stores
const documentStore = useDocumentStore();
const currentDocumentStore = useCurrentDocumentStore();
const filterStore = useFilterStore();
const itemStore = useItemStore();
const associationStore = useAssociationStore();
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

// Modal data
const associationOrigin = ref(null);
const associationDestination = ref(null);
const editingAssociation = ref(null);
const itemsToDelete = ref([]);
const deleteType = ref('single');

// Panel modes
const rightPanelMode = ref('itemDetails');

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
    console.log('[DEBUG] EnhancedDocumentTreeEditor onMounted - currentDocument state:', {
      currentDocument: currentDocumentStore.currentDocument,
      isNull: currentDocumentStore.currentDocument === null,
      isUndefined: currentDocumentStore.currentDocument === undefined,
      isEmptyObject: currentDocumentStore.currentDocument && Object.keys(currentDocumentStore.currentDocument).length === 0,
      hasItems: currentDocumentStore.currentDocument?.items?.length > 0,
      documentKeys: currentDocumentStore.currentDocument ? Object.keys(currentDocumentStore.currentDocument) : []
    });

    if (!currentDocumentStore.currentDocument || Object.keys(currentDocumentStore.currentDocument).length === 0) {
      console.log('[DEBUG] No current document found, fetching documents...');
      // Fetch the list of available documents
      await documentStore.fetchDocuments();

      // If there are documents available, load the first one as an example
      if (documentStore.documents.length > 0) {
        const firstDoc = documentStore.documents[0];
        console.log('[DEBUG] Loading first document:', firstDoc.id);
        const docData = await documentStore.fetchDocument(firstDoc.id);

        // Transform and set the current document
        const cfDoc = docData.CFDocument || {};
        const items = currentDocumentStore.transformCASEItems(docData.CFItems || [], docData.CFAssociations || []);

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
        });
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

async function onDocumentChanged({ side, documentId }) {
  try {
    if (documentId) {
      const docData = await documentStore.fetchDocument(documentId);

      // Transform and set the current document
      const cfDoc = docData.CFDocument || {};
      const items = currentDocumentStore.transformCASEItems(docData.CFItems || [], docData.CFAssociations || []);

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
      });
    }
  } catch (error) {
    console.error('Error loading document:', error);
  }
}

async function onExternalDocumentRequested({ url }) {
  // Handle external document request
  console.log('External document requested:', url);
  try {
    const { data, finalUrl } = await documentStore.loadExternalDocument(url);

    // Transform and set the current document
    const cfDoc = data.CFDocument || {};
    const items = currentDocumentStore.transformCASEItems(data.CFItems || [], data.CFAssociations || []);

    currentDocumentStore.selectDocument({
      id: cfDoc.identifier || 'external-' + Date.now(),
      uri: cfDoc.uri || finalUrl,
      title: cfDoc.title || 'External Document',
      description: cfDoc.description || null,
      creator: cfDoc.creator || '',
      subject: cfDoc.subject || null,
      subjectURI: cfDoc.subjectURI || [],
      status: cfDoc.adoptionStatus || 'Draft',
      statusStartDate: cfDoc.statusStartDate || null,
      statusEndDate: cfDoc.statusEndDate || null,
      lastModified: cfDoc.lastChangeDateTime || new Date().toISOString(),
      language: cfDoc.language || null,
      version: cfDoc.version || null,
      officialSourceURL: cfDoc.officialSourceURL || finalUrl,
      publisher: cfDoc.publisher || null,
      licenseURI: cfDoc.licenseURI || null,
      notes: cfDoc.notes || null,
      frameworkType: cfDoc.frameworkType || null,
      caseVersion: cfDoc.caseVersion || null,
      extensions: cfDoc.extensions || null,
      CFPackageURI: cfDoc.CFPackageURI || finalUrl,
      items: items,
      isReadOnly: true
    });
  } catch (error) {
    console.error('Error loading external document:', error);
    // The store's error state will be displayed in the template
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
      console.log('Child item added successfully');
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
  console.log('Delete association:', association);
}

function onRightPanelModeChanged(mode) {
  rightPanelMode.value = mode;
}

// Placeholder handlers for modal events
function onDocSaved(data) {
  console.log('Document saved:', data);
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

async function handleAddRootItem(newItem) {
  if (newItem && currentDocument.value) {
    const success = itemStore.addItem(currentDocument.value, newItem, null);
    if (success) {
      console.log('Root item added successfully');
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
