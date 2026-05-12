<template>
  <div class="association-view h-100 d-flex flex-column bg-light">
    <div
      v-if="loading"
      class="d-flex justify-content-center align-items-center flex-grow-1"
    >
      <div
        class="spinner-border text-primary"
        role="status"
      >
        <span class="visually-hidden">Loading associations...</span>
      </div>
    </div>
    <div
      v-else-if="error"
      class="alert alert-danger m-4"
      role="alert"
    >
      {{ error }}
    </div>
    <div
      v-else-if="!currentDocument"
      class="alert alert-info m-4"
      role="alert"
    >
      Please select a document to view associations.
    </div>
    <div
      v-else
      class="d-flex flex-grow-1 overflow-hidden"
      style="min-height: 0;"
    >
      <!-- Filters Sidebar -->
      <aside class="col-md-3 col-lg-2 border-end p-4 bg-white overflow-auto shadow-sm filter-panel flex-shrink-0">
        <h5 class="mb-4 d-flex align-items-center text-secondary">
          <i class="bi bi-filter-right me-2 fs-4" /> Filters
        </h5>

        <div class="mb-3">
          <label class="form-label">Search</label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-search" />
            </span>
            <input
              v-model="searchFilter"
              type="text"
              class="form-control"
              placeholder="Search associations..."
            >
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Association Types</label>
          <div
            v-if="allAssociationTypes.length === 0"
            class="text-muted small"
          >
            No association types available. Load a document to see available types.
          </div>
          <div
            v-else
            class="type-checkboxes assocViewTableTypeFilters"
          >
            <div
              v-for="type in allAssociationTypes"
              :key="type"
              class="form-check avTypeFilter"
            >
              <input
                :id="'type-' + type"
                v-model="selectedTypes"
                type="checkbox"
                :value="type"
                class="form-check-input"
              >
              <label
                :for="'type-' + type"
                class="form-check-label"
              >
                {{ type }}
              </label>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Association Group</label>
          <select
            v-model="selectedGroup"
            class="form-select"
          >
            <option value="">
              All Groups
            </option>
            <option
              v-for="group in associationGroups"
              :key="group.id"
              :value="group.id"
            >
              {{ group.title }}
            </option>
          </select>
        </div>

        <div class="mt-4">
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm w-100"
            @click="clearFilters"
          >
            Clear Filters
          </button>
        </div>
      </aside>

      <!-- Main Content -->
      <main
        class="col-md-9 col-lg-10 p-4 d-flex flex-column"
        style="min-height: 0;"
      >
        <div
          v-if="associationsLoading"
          class="d-flex justify-content-center align-items-center flex-grow-1"
        >
          <div class="text-center text-muted">
            <div
              class="spinner-border text-primary mb-3"
              role="status"
            >
              <span class="visually-hidden">Loading associations...</span>
            </div>
            <p>Loading associations...</p>
          </div>
        </div>
        <div
          v-else-if="filteredAssociations.length === 0"
          class="text-center py-5 text-muted"
        >
          <i class="bi bi-inbox fs-1 mb-3" />
          <p>No associations found matching your filters.</p>
        </div>

        <template v-else>
          <AssociationTableView
            :associations="paginatedAssociations"
            :association-groups="associationGroups"
            :is-read-only="associationActionsReadOnly"
            @edit-association="editAssoc"
            @delete-association="deleteAssoc"
          />

          <!-- Pagination -->
          <div
            v-if="totalPages > 1"
            class="mt-4 flex-shrink-0"
          >
            <nav aria-label="Association pagination">
              <ul class="pagination justify-content-center">
                <li :class="{ disabled: currentPage === 1 }">
                  <button
                    class="page-link"
                    :disabled="currentPage === 1"
                    @click="goToPage(1)"
                  >
                    &laquo; First
                  </button>
                </li>
                <li :class="{ disabled: currentPage === 1 }">
                  <button
                    class="page-link"
                    :disabled="currentPage === 1"
                    @click="goToPage(currentPage - 1)"
                  >
                    &lsaquo; Previous
                  </button>
                </li>
                <li
                  v-for="page in displayedPages"
                  :key="page"
                  :class="{ active: page === currentPage, disabled: page === '...' }"
                >
                  <button
                    v-if="page !== '...'"
                    class="page-link"
                    :class="{ active: page === currentPage }"
                    @click="goToPage(page)"
                  >
                    {{ page }}
                  </button>
                  <span
                    v-else
                    class="page-link border-0"
                  >...</span>
                </li>
                <li :class="{ disabled: currentPage === totalPages }">
                  <button
                    class="page-link"
                    :disabled="currentPage === totalPages"
                    @click="goToPage(currentPage + 1)"
                  >
                    Next &rsaquo;
                  </button>
                </li>
                <li :class="{ disabled: currentPage === totalPages }">
                  <button
                    class="page-link"
                    :disabled="currentPage === totalPages"
                    @click="goToPage(totalPages)"
                  >
                    Last &raquo;
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </template>
      </main>
    </div>

    <!-- Edit Association Modal -->
    <EditAssociationModal
      v-if="editingAssociation"
      :association="editingAssociation"
      :available-groups="associationGroups"
      :show="showEditAssociationModal"
      :selected-item-identifier="selectedItemIdentifier"
      @updated="handleAssociationUpdated"
      @hidden="handleModalHidden"
    />

    <!-- Delete Association Modal -->
    <DeleteAssociationModal
      v-model:show="showDeleteModal"
      :association="associationToDelete"
      @confirmed="handleDeleteConfirmed"
      @hidden="handleDeleteModalHidden"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useDocumentStore } from '../../stores/documentStore';
import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';
import { useSessionStore } from '../../stores/sessionStore';
import { logger } from '../../utils/logger.js';
import AssociationTableView from './AssociationTableView.vue';
import EditAssociationModal from './EditAssociationModal.vue';
import DeleteAssociationModal from './DeleteAssociationModal.vue';

const documentStore = useDocumentStore();
const currentDocumentStore = useCurrentDocumentStore();
const sessionStore = useSessionStore();

// Modal state
const showEditAssociationModal = ref(false);
const editingAssociation = ref(null);
const selectedItemIdentifier = ref(null);

// Delete association modal state
const showDeleteModal = ref(false);
const associationToDelete = ref(null);

const loading = computed(() => documentStore.loading);
const error = computed(() => documentStore.error);
const currentDocument = computed(() => currentDocumentStore.currentDocument);
const associationGroups = computed(() => currentDocumentStore.associationGroups);
const dbAssociations = ref([]);
const associationsLoading = ref(true);
const associationActionsReadOnly = computed(() => !sessionStore.isAuthenticated);

async function refreshDbAssociations() {
  const currentDocId = currentDocumentStore.currentDocument?.identifier;
  if (!currentDocId) {
    dbAssociations.value = [];
    associationsLoading.value = false;
    return;
  }

  associationsLoading.value = true;
  try {
    const data = await currentDocumentStore.fetchFrameworkAssociations(currentDocId);
    dbAssociations.value = data.map((assoc) => ({
      ...assoc,
      _sourceFrameworkId: assoc.associationDocumentIdentifier || null,
    }));
  } catch (err) {
    logger.error('[AssociationView] Failed to fetch associations for framework:', currentDocId, err);
    dbAssociations.value = [];
  } finally {
    associationsLoading.value = false;
  }
}

watch(
  () => currentDocumentStore.currentDocument?.identifier,
  () => {
    void refreshDbAssociations();
  },
  { immediate: true }
);

const associations = computed(() => dbAssociations.value);

// Dynamically derive association types from actual data
const allAssociationTypes = computed(() => {
  if (associations.value.length === 0) {
    return [];
  }

  // Extract unique association types from all associations
  const typeSet = new Set();
  associations.value.forEach(assoc => {
    if (assoc.associationType) {
      typeSet.add(assoc.associationType);
    }
  });

  // Sort types: standard CASE types first (in a defined order), then ext:* types alphabetically
  const standardOrder = [
    'isChildOf',
    'isPeerOf',
    'isPartOf',
    'exactMatchOf',
    'precedes',
    'relatesTo',
    'hasSkillLevel',
    'isSkillLevelOf'
  ];

  const types = Array.from(typeSet);

  // Separate standard types and extension types
  const standardTypes = [];
  const extensionTypes = [];

  types.forEach(type => {
    if (type.startsWith('ext:')) {
      extensionTypes.push(type);
    } else {
      standardTypes.push(type);
    }
  });

  // Sort standard types according to the defined order
  standardTypes.sort((a, b) => {
    const indexA = standardOrder.indexOf(a);
    const indexB = standardOrder.indexOf(b);
    // If both are in the standard order, sort by that order
    if (indexA !== -1 && indexB !== -1) {
      return indexA - indexB;
    }
    // If only one is in the standard order, it comes first
    if (indexA !== -1) return -1;
    if (indexB !== -1) return 1;
    // Otherwise, sort alphabetically
    return a.localeCompare(b);
  });

  // Sort extension types alphabetically
  extensionTypes.sort((a, b) => a.localeCompare(b));

  // Return standard types first, then extension types
  return [...standardTypes, ...extensionTypes];
});

// Track if we've initialized the selected types
const hasInitializedTypes = ref(false);

// Selected types for filtering - initialize empty, will be set by watcher
const selectedTypes = ref([]);
const selectedGroup = ref('');
const searchFilter = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(25);

// Initialize selected types when association types become available
// Default: all types except isChildOf
watch(
  allAssociationTypes,
  (newTypes) => {
    // Only initialize once when types first become available
    if (newTypes.length > 0 && !hasInitializedTypes.value) {
      selectedTypes.value = newTypes.filter(type => type !== 'isChildOf');
      hasInitializedTypes.value = true;
    }
  },
  { immediate: true }
);

// Reset to first page when association type filters change
watch(
  selectedTypes,
  () => {
    currentPage.value = 1;
  }
);



const filteredAssociations = computed(() => {
  return associations.value.filter(assoc => {
    // Filter by type - only show if type is in selected types array
    if (selectedTypes.value.length > 0 && !selectedTypes.value.includes(assoc.associationType)) {
      return false;
    }
    // Filter by group
    if (selectedGroup.value) {
      const assocGroupId = assoc.groupId ||
                           assoc.CFAssociationGroupingURI?.identifier ||
                           (typeof assoc.CFAssociationGroupingURI === 'string' ? assoc.CFAssociationGroupingURI : null);
      if (assocGroupId !== selectedGroup.value) {
        return false;
      }
    }
    // Filter by search
    if (searchFilter.value) {
      const search = searchFilter.value.toLowerCase();
      const originTitle = getItemTitle(assoc, 'origin').toLowerCase();
      const destTitle = getItemTitle(assoc, 'dest').toLowerCase();
      const assocType = (assoc.associationType || '').toLowerCase();
      return originTitle.includes(search) || destTitle.includes(search) || assocType.includes(search);
    }
    return true;
  });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredAssociations.value.length / itemsPerPage.value)));

const displayedPages = computed(() => {
  const delta = 2; // Number of pages to show before and after current page
  const left = currentPage.value - delta;
  const right = currentPage.value + delta + 1;
  const range = [];
  const rangeWithDots = [];
  let l;

  for (let i = 1; i <= totalPages.value; i++) {
    if (i === 1 || i === totalPages.value || (i >= left && i < right)) {
      range.push(i);
    }
  }

  for (const i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1);
      } else if (i - l !== 1) {
        rangeWithDots.push('...');
      }
    }
    rangeWithDots.push(i);
    l = i;
  }

  return rangeWithDots;
});

// Ensure currentPage is valid if totalPages decreases
watch(totalPages, (newTotalPages) => {
  if (currentPage.value > newTotalPages) {
    currentPage.value = Math.max(1, newTotalPages);
  }
});

const paginatedAssociations = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredAssociations.value.slice(start, end);
});

/**
 * Get a human-readable title for the origin or destination of an association.
 * Mirrors the logic from the old jQuery avGetItemCell function.
 */
function getItemTitle(assoc, key) {
  const nodeURI = key === 'origin' ? assoc.originNodeURI : assoc.destinationNodeURI;
  const nodeIdentifier = key === 'origin' ? assoc.originNodeIdentifier : assoc.destinationNodeIdentifier;

  if (!nodeURI && !nodeIdentifier) return 'N/A';

  const identifier = nodeURI?.identifier || nodeIdentifier;
  const title = nodeURI?.title || '';

  // If we have an abbreviatedTitle or title from the URI, use that
  if (title) return title;

  // Try to find the item in the current document's items
  if (identifier && currentDocument.value?.items) {
    const item = currentDocument.value.items.find(
      i => i.id === identifier || i.identifier === identifier
    );
    if (item) {
      return item.abbreviatedStatement || item.humanCodingScheme || item.fullStatement || identifier;
    }
  }

  // Fall back to the URI or identifier
  return nodeURI?.uri || identifier || 'N/A';
}

function editAssoc(assoc) {
  logger.debug('Edit association:', assoc);
  editingAssociation.value = assoc;
  showEditAssociationModal.value = true;
}

function handleAssociationUpdated(updatedAssoc) {
  logger.debug('Association updated:', updatedAssoc);
  // Update the association in the current document's associations list
  const index = currentDocumentStore.currentDocumentAssociations.findIndex(
    a => (a.id || a.identifier) === (updatedAssoc.id || updatedAssoc.identifier)
  );
  if (index !== -1) {
    currentDocumentStore.currentDocumentAssociations[index] = {
      ...currentDocumentStore.currentDocumentAssociations[index],
      ...updatedAssoc
    };
  }
  // Close the modal
  showEditAssociationModal.value = false;
  editingAssociation.value = null;
}

function handleModalHidden() {
  showEditAssociationModal.value = false;
  editingAssociation.value = null;
}

function deleteAssoc(assoc) {
  associationToDelete.value = assoc;
  showDeleteModal.value = true;
}

async function handleDeleteConfirmed(assoc) {
  // Use the correct ID property - associations can have either id or identifier
  const associationId = assoc.id || assoc.identifier;
  if (!associationId) {
    logger.error('Association has no id or identifier:', assoc);
    return;
  }
  try {
    await currentDocumentStore.removeAssociation(associationId);
    logger.debug('Association deleted successfully:', associationId);
    showDeleteModal.value = false;
  } catch (e) {
    logger.error('Failed to delete association', e);
  }
}

function handleDeleteModalHidden() {
  associationToDelete.value = null;
}

function clearFilters() {
  // Reset to default: all types except isChildOf
  selectedTypes.value = allAssociationTypes.value.filter(type => type !== 'isChildOf');
  selectedGroup.value = '';
  searchFilter.value = '';
  currentPage.value = 1;
}

function goToPage(page) {
  currentPage.value = page;
}

onMounted(() => {
  // Auto-load documents if not already loaded
  if (documentStore.documents.length === 0) {
    documentStore.fetchDocuments();
  }
});
</script>

<style scoped>
.association-view {
  min-height: 0;
}

.type-checkboxes {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  padding: 0.5rem;
}

.type-checkboxes .form-check {
  margin-bottom: 0.25rem;
}

.type-checkboxes .form-check:last-child {
  margin-bottom: 0;
}

.association-card {
  transition: transform 0.2s, opacity 0.2s;
}

.association-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.pagination {
  display: flex;
  list-style: none;
  padding: 0;
}

.pagination li {
  margin: 0 0.25rem;
}

.page-link {
  border: 1px solid #dee2e6;
  padding: 0.375rem 0.75rem;
  margin: 0 0.125rem;
  border-radius: 0.375rem;
  background-color: #fff;
  color: #007bff;
}

.page-link:hover:not(:disabled) {
  background-color: #0056b3;
  border-color: #0056b3;
}

.page-link.active {
  background-color: #007bff;
  color: #fff;
  border-color: #007bff;
}

.page-link:disabled {
  background-color: #e9ecef;
  border-color: #dee2e6;
  color: #6c757d;
  cursor: not-allowed;
}
</style>
