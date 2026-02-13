<template>
  <div class="association-view h-100 d-flex flex-column bg-light">
    <div v-if="loading" class="d-flex justify-content-center align-items-center flex-grow-1">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading associations...</span>
      </div>
    </div>
    <div v-else-if="error" class="alert alert-danger m-4" role="alert">
      {{ error }}
    </div>
    <div v-else-if="!currentDocument" class="alert alert-info m-4" role="alert">
      Please select a document to view associations.
    </div>
    <div v-else class="d-flex flex-grow-1 overflow-hidden" style="min-height: 0;">
      <!-- Filters Sidebar -->
      <aside class="col-md-3 col-lg-2 border-end p-4 bg-white overflow-auto shadow-sm filter-panel flex-shrink-0">
        <h5 class="mb-4 d-flex align-items-center text-secondary">
          <i class="bi bi-filter-right me-2 fs-4"></i> Filters
        </h5>

        <div class="mb-3">
          <label class="form-label">Search</label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-search"></i>
            </span>
            <input
              type="text"
              class="form-control"
              v-model="searchFilter"
              placeholder="Search associations..."
            >
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Association Type</label>
          <select class="form-select" v-model="selectedType">
            <option value="">All Types</option>
            <option v-for="type in availableTypes" :key="type" :value="type">
              {{ type }}
            </option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Association Group</label>
          <select class="form-select" v-model="selectedGroup">
            <option value="">All Groups</option>
            <option v-for="group in associationGroups" :key="group.id" :value="group.id">
              {{ group.title }}
            </option>
          </select>
        </div>

        <div class="mt-4">
          <button type="button" class="btn btn-outline-secondary btn-sm w-100" @click="clearFilters">
            Clear Filters
          </button>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="col-md-9 col-lg-10 p-4 overflow-auto">
        <div v-if="filteredAssociations.length === 0" class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 mb-3"></i>
          <p>No associations found matching your filters.</p>
        </div>

        <div v-else class="associations-grid">
          <div
            v-for="assoc in paginatedAssociations"
            :key="assoc.id || assoc.identifier"
            class="association-card card mb-3"
          >
            <div class="card-header d-flex justify-content-between align-items-center">
              <span class="badge bg-primary">{{ assoc.associationType }}</span>
              <div class="dropdown">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary dropdown-toggle"
                  data-bs-toggle="dropdown"
                >
                  <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu">
                  <li>
                    <button class="dropdown-item" @click="editAssoc(assoc)">
                      <i class="bi bi-pencil me-2"></i>Edit
                    </button>
                  </li>
                  <li>
                    <button class="dropdown-item text-danger" @click="deleteAssoc(assoc)">
                      <i class="bi bi-trash me-2"></i>Delete
                    </button>
                  </li>
                </ul>
              </div>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <strong>Origin:</strong>
                <p>{{ getItemTitle(assoc, 'origin') }}</p>
              </div>
              <div class="mb-2">
                <strong>Destination:</strong>
                <p>{{ getItemTitle(assoc, 'dest') }}</p>
              </div>
              <div v-if="assoc.sequenceNumber !== undefined">
                <strong>Sequence:</strong>
                <span>{{ assoc.sequenceNumber }}</span>
              </div>
              <div v-if="assoc.lastChangeDateTime" class="text-muted small mt-2">
                Last modified: {{ formatDate(assoc.lastChangeDateTime) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="mt-4">
          <nav aria-label="Association pagination">
            <ul class="pagination justify-content-center">
              <li :class="{ disabled: currentPage === 1 }">
                <button class="page-link" @click="goToPage(1)" :disabled="currentPage === 1">
                  &laquo; First
                </button>
              </li>
              <li :class="{ disabled: currentPage === 1 }">
                <button class="page-link" @click="goToPage(currentPage - 1)" :disabled="currentPage === 1">
                  &lsaquo; Previous
                </button>
              </li>
              <li v-for="page in totalPages" :key="page">
                <button class="page-link" :class="{ active: page === currentPage }" @click="goToPage(page)">
                  {{ page }}
                </button>
              </li>
              <li :class="{ disabled: currentPage === totalPages }">
                <button class="page-link" @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages">
                  Next &rsaquo;
                </button>
              </li>
              <li :class="{ disabled: currentPage === totalPages }">
                <button class="page-link" @click="goToPage(totalPages)" :disabled="currentPage === totalPages">
                  Last &raquo;
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useDocumentStore } from '../../stores/documentStore';
import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';
import { useItemStore } from '../../stores/itemStore';
import { logger } from '../../utils/logger.js';

const router = useRouter();
const documentStore = useDocumentStore();
const currentDocumentStore = useCurrentDocumentStore();
const itemStore = useItemStore();

const loading = computed(() => documentStore.loading);
const error = computed(() => documentStore.error);
const currentDocument = computed(() => currentDocumentStore.currentDocument);
const associationGroups = computed(() => currentDocumentStore.associationGroups);

const selectedType = ref('');
const selectedGroup = ref('');
const searchFilter = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(25);

const availableTypes = computed(() => {
  const types = new Set();
  associations.value.forEach(assoc => {
    if (assoc.associationType) types.add(assoc.associationType);
  });
  return Array.from(types);
});

const associations = computed(() => {
  return currentDocumentStore.currentDocumentAssociations || [];
});

const filteredAssociations = computed(() => {
  return associations.value.filter(assoc => {
    // Filter by type
    if (selectedType.value && assoc.associationType !== selectedType.value) {
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

function goToItem(identifier) {
  if (!identifier || !currentDocument.value) return;
  router.push({
    name: 'TreeView',
    params: {
      frameworkId: currentDocument.value.id || currentDocument.value.identifier,
      itemId: identifier
    }
  });
}

function editAssoc(assoc) {
  logger.debug('Edit association:', assoc);
  // Implementation will depend on how modals are triggered in the Vue app
}

async function deleteAssoc(assoc) {
  if (!confirm('Are you sure you want to delete this association?')) {
    return;
  }
  try {
    await currentDocumentStore.removeAssociation(assoc.id);
    logger.debug('Association deleted successfully:', assoc.id);
  } catch (e) {
    console.error('Failed to delete association', e);
  }
}

function clearFilters() {
  selectedType.value = '';
  selectedGroup.value = '';
  searchFilter.value = '';
  currentPage.value = 1;
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString();
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
