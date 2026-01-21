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

        <!-- Text Search -->
        <div class="filter-section mb-4">
          <label class="form-label fw-bold small text-uppercase text-muted mb-2 letter-spacing-1">Search</label>
          <div class="input-group input-group-sm shadow-sm border rounded">
            <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
            <input
              v-model="searchFilter"
              type="text"
              class="form-control border-0 ps-0"
              placeholder="Search associations..."
              aria-label="Search associations"
            >
            <button v-if="searchFilter" class="btn btn-link btn-sm text-secondary border-0" @click="searchFilter = ''">
              <i class="bi bi-x-circle-fill"></i>
            </button>
          </div>
        </div>

        <!-- Association Types Filter -->
        <div class="filter-section mb-5">
          <label class="form-label fw-bold small text-uppercase text-muted mb-3 letter-spacing-1">Association Types</label>
          <div class="form-check mb-2">
            <input
              class="form-check-input"
              type="checkbox"
              id="allTypes"
              :checked="selectedTypes.length === 0"
              @change="selectedTypes = []"
            >
            <label class="form-check-label" for="allTypes">All Types</label>
          </div>
          <div v-for="type in availableTypes" :key="type" class="form-check mb-2">
            <input
              class="form-check-input"
              type="checkbox"
              :id="`type-${type}`"
              :value="type"
              v-model="selectedTypes"
            >
            <label class="form-check-label" :for="`type-${type}`">{{ type }}</label>
          </div>
        </div>

        <!-- Association Groups Filter -->
        <div class="filter-section">
          <label class="form-label fw-bold small text-uppercase text-muted mb-3 letter-spacing-1">Association Groups</label>
          <div v-for="group in associationGroups" :key="group.id" class="form-check mb-2">
            <input
              class="form-check-input"
              type="checkbox"
              :id="`group-${group.id}`"
              :value="group.id"
              v-model="selectedGroups"
            >
            <label class="form-check-label" :for="`group-${group.id}`">{{ group.title }}</label>
          </div>
        </div>
      </aside>

      <!-- Association Table Area -->
      <main class="flex-grow-1 d-flex flex-column overflow-hidden" style="min-height: 0;">
        <div class="p-4 flex-grow-1 d-flex flex-column overflow-hidden" style="min-height: 0;">
          <div class="mb-4 d-flex justify-content-between align-items-center flex-shrink-0">
            <h2 class="h4 mb-0 fw-bold">Associations ({{ filteredAssociations.length }})</h2>
            <div class="stats-pills d-flex gap-2">
              <span class="badge rounded-pill bg-white text-dark border px-3 py-2 shadow-sm">
                {{ availableTypes.length }} Types
              </span>
              <span class="badge rounded-pill bg-white text-dark border px-3 py-2 shadow-sm">
                {{ associationGroups.length - 2 }} Custom Groups
              </span>
            </div>
          </div>

          <div class="table-responsive border rounded-3 bg-white shadow-sm flex-grow-1 overflow-auto association-table-wrapper" style="min-height: 0;">
            <table class="table table-hover align-middle mb-0 border-0">
              <thead class="table-light sticky-top shadow-sm z-index-1">
                <tr>
                  <th scope="col" class="ps-4 py-3 border-0 text-muted small text-uppercase font-weight-bold">Origin</th>
                  <th scope="col" class="py-3 border-0 text-muted small text-uppercase font-weight-bold">Type</th>
                  <th scope="col" class="py-3 border-0 text-muted small text-uppercase font-weight-bold">Destination</th>
                  <th scope="col" class="py-3 border-0 text-muted small text-uppercase font-weight-bold">Annotation</th>
                  <th scope="col" class="py-3 border-0 text-muted small text-uppercase font-weight-bold">Seq</th>
                  <th scope="col" class="py-3 border-0 text-muted small text-uppercase font-weight-bold">Group</th>
                  <th scope="col" class="pe-4 py-3 border-0 text-muted small text-uppercase font-weight-bold text-end">Actions</th>
                </tr>
              </thead>
              <tbody class="border-0">
                <tr v-if="filteredAssociations.length === 0">
                  <td colspan="7" class="text-center py-5 text-muted border-0">
                    <div class="py-4">
                      <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                      <p class="mb-0">No associations found matching the current filters.</p>
                      <button @click="resetFilters" class="btn btn-link btn-sm mt-2">Reset Filters</button>
                    </div>
                  </td>
                </tr>
                <tr v-for="assoc in paginatedAssociations" :key="assoc.identifier" class="association-row border-bottom transition-all">
                  <td class="ps-4 py-3">
                    <div class="item-link d-inline-block text-truncate" style="max-width: 300px;" @click="goToItem(assoc.originNodeURI?.identifier)" :title="getItemFullTitle(assoc.originNodeURI?.identifier)">
                      <span v-if="getItemCodingScheme(assoc.originNodeURI?.identifier)" class="fw-bold me-1">{{ getItemCodingScheme(assoc.originNodeURI?.identifier) }}</span>
                      {{ getItemTitle(assoc.originNodeURI?.identifier) }}
                    </div>
                  </td>
                  <td class="py-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-medium association-type-badge">
                      {{ assoc.associationType }}
                    </span>
                  </td>
                  <td class="py-3">
                    <div class="item-link d-inline-block text-truncate" style="max-width: 300px;" @click="goToItem(assoc.destinationNodeURI?.identifier)" :title="getItemFullTitle(assoc.destinationNodeURI?.identifier)">
                      <span v-if="getItemCodingScheme(assoc.destinationNodeURI?.identifier)" class="fw-bold me-1">{{ getItemCodingScheme(assoc.destinationNodeURI?.identifier) }}</span>
                      {{ getItemTitle(assoc.destinationNodeURI?.identifier) }}
                    </div>
                  </td>
                  <td class="py-3">
                    <div v-if="assoc.notes" class="text-muted small text-truncate" style="max-width: 250px;" :title="assoc.notes">
                      {{ assoc.notes }}
                    </div>
                    <span v-else class="text-secondary opacity-25">&mdash;</span>
                  </td>
                  <td class="py-3 text-muted small">
                    {{ assoc.sequenceNumber }}
                  </td>
                  <td class="py-3">
                    <span class="badge bg-light text-secondary border fw-normal px-2 py-1">
                      {{ getGroupTitle(assoc.groupId) }}
                    </span>
                  </td>
                  <td class="pe-4 py-3 text-end">
                    <div class="btn-group btn-group-sm rounded-pill overflow-hidden shadow-sm border border-light p-1 bg-white">
                      <!-- Edit/Delete will be connected once actions are available -->
                      <button v-if="assoc.associationType !== 'isChildOf'" class="btn btn-link text-secondary p-1 px-2 border-0" @click="editAssoc(assoc)" title="Edit">
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button v-if="assoc.associationType !== 'isChildOf'" class="btn btn-link text-danger p-1 px-2 border-0" @click="deleteAssoc(assoc)" title="Delete">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Controls -->
          <div v-if="totalPages > 1" class="d-flex justify-content-between align-items-center mt-3 bg-white p-3 border rounded shadow-sm flex-shrink-0">
            <div class="text-muted small">
              Showing {{ startItem + 1 }} to {{ endItem }} of {{ filteredAssociations.length }} associations
            </div>
            <nav aria-label="Association pagination">
              <ul class="pagination pagination-sm mb-0">
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                  <button class="page-link" @click="currentPage--" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                  </button>
                </li>
                
                <li v-for="pageNum in displayedPages" :key="pageNum" class="page-item" :class="{ active: currentPage === pageNum, disabled: pageNum === '...' }">
                  <button v-if="pageNum !== '...'" class="page-link" @click="currentPage = pageNum">{{ pageNum }}</button>
                  <span v-else class="page-link border-0">...</span>
                </li>

                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                  <button class="page-link" @click="currentPage++" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                  </button>
                </li>
              </ul>
            </nav>
            <div class="d-flex align-items-center gap-2">
              <label class="small text-muted mb-0">Per page:</label>
              <select v-model="itemsPerPage" class="form-select form-select-sm" style="width: auto;">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useDocumentStore } from '../../stores/documentStore';
import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';
import { useItemStore } from '../../stores/itemStore';

const router = useRouter();
const documentStore = useDocumentStore();
const currentDocumentStore = useCurrentDocumentStore();
const itemStore = useItemStore();

const loading = computed(() => documentStore.loading);
const error = computed(() => documentStore.error);
const currentDocument = computed(() => currentDocumentStore.currentDocument);
const associationGroups = computed(() => currentDocumentStore.associationGroups);

const selectedTypes = ref([]);
const selectedGroups = ref(['all']);
const searchFilter = ref('');

// Pagination state
const currentPage = ref(1);
const itemsPerPage = ref(25);

const associations = computed(() => {
  if (!currentDocument.value) return [];
  return currentDocumentStore.currentDocumentAssociations || [];
});

const availableTypes = computed(() => {
  const types = new Set();
  associations.value.forEach(assoc => {
    if (assoc.associationType) types.add(assoc.associationType);
  });
  return Array.from(types).sort();
});

const flatMap = computed(() => {
  const map = new Map();
  if (!currentDocument.value || !currentDocument.value.items) return map;

  const flatten = (items) => {
    items.forEach(item => {
      map.set(item.identifier, item);
      if (item.children) {
        flatten(item.children);
      }
    });
  };

  flatten(currentDocument.value.items);
  return map;
});

const filteredAssociations = computed(() => {
  const query = searchFilter.value.toLowerCase().trim();
  
  return associations.value.filter(assoc => {
    // Type match
    const typeMatch = selectedTypes.value.length === 0 || selectedTypes.value.includes(assoc.associationType);
    
    // Group match
    const groupId = assoc.groupId || 'default';
    const groupMatch = selectedGroups.value.includes('all') || selectedGroups.value.includes(groupId);
    
    if (!typeMatch || !groupMatch) return false;

    // Text search match
    if (query) {
      const originTitle = getItemTitle(assoc.originNodeURI?.identifier).toLowerCase();
      const originScheme = getItemCodingScheme(assoc.originNodeURI?.identifier).toLowerCase();
      const destTitle = getItemTitle(assoc.destinationNodeURI?.identifier).toLowerCase();
      const destScheme = getItemCodingScheme(assoc.destinationNodeURI?.identifier).toLowerCase();
      const notes = (assoc.notes || '').toLowerCase();
      const type = (assoc.associationType || '').toLowerCase();
      
      return originTitle.includes(query) ||
        originScheme.includes(query) ||
        destTitle.includes(query) ||
        destScheme.includes(query) ||
        notes.includes(query);
    }

    return true;
  });
});

const totalPages = computed(() => Math.ceil(filteredAssociations.value.length / itemsPerPage.value));

const startItem = computed(() => (currentPage.value - 1) * itemsPerPage.value);
const endItem = computed(() => Math.min(startItem.value + itemsPerPage.value, filteredAssociations.value.length));

const paginatedAssociations = computed(() => {
  return filteredAssociations.value.slice(startItem.value, endItem.value);
});

const displayedPages = computed(() => {
  const pages = [];
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

// Watch for filter changes to reset pagination
import { watch } from 'vue';
watch([selectedTypes, selectedGroups, itemsPerPage, searchFilter], () => {
  currentPage.value = 1;
});

function getItemTitle(identifier) {
  // Check if it's an item in the current document
  const item = flatMap.value.get(identifier);
  if (item) {
    return item.abbreviatedTitle || item.title || 'Untitled';
  }

  // Check if it's the current document itself
  if (currentDocument.value?.id === identifier || currentDocument.value?.identifier === identifier) {
    return currentDocument.value.title || 'Current Document';
  }

  // Check if it's an associated document
  const assocDoc = currentDocumentStore.getAssociatedDocument(identifier);
  if (assocDoc) {
    return assocDoc.title || 'Associated Document';
  }

  return identifier || 'Unknown';
}

function getItemCodingScheme(identifier) {
  const item = flatMap.value.get(identifier);
  return item?.humanCodingScheme || '';
}

function getItemFullTitle(identifier) {
  // Check items first
  const item = flatMap.value.get(identifier);
  if (item) {
    const scheme = item.humanCodingScheme ? `${item.humanCodingScheme}: ` : '';
    return `${scheme}${item.title || item.abbreviatedTitle || 'Untitled'}`;
  }

  // Check current document
  if (currentDocument.value?.id === identifier || currentDocument.value?.identifier === identifier) {
    return currentDocument.value.title || 'Current Document';
  }

  // Check associated documents
  const assocDoc = currentDocumentStore.getAssociatedDocument(identifier);
  if (assocDoc) {
    return assocDoc.title || 'Associated Document';
  }

  return identifier || 'Unknown';
}

function getGroupTitle(groupId) {
  const group = associationGroups.value.find(g => g.id === (groupId || 'default'));
  return group ? group.title : (groupId || 'Default');
}

function resetFilters() {
  selectedTypes.value = [];
  selectedGroups.value = ['all'];
  searchFilter.value = '';
}

function goToItem(identifier) {
  if (!identifier || !currentDocument.value) return;
  router.push({
    name: 'TreeView',
    params: {
      frameworkId: currentDocument.value.identifier,
      itemId: identifier
    }
  });
}

function editAssoc(assoc) {
  console.log('Edit association:', assoc);
  // Implementation will depend on how modals are triggered in the Vue app
}

async function deleteAssoc(assoc) {
  if (confirm('Are you sure you want to delete this association?')) {
    try {
      await currentDocumentStore.removeAssociation(assoc.id);
      // The store should update currentDocumentAssociations automatically if it's reactive
    } catch (e) {
      console.error('Failed to delete association', e);
    }
  }
}
</script>

<style scoped>
.association-view {
  height: 100%;
}

.item-link {
  color: #0d6efd;
  cursor: pointer;
  text-decoration: none;
  font-weight: 500;
}

.item-link:hover {
  text-decoration: underline;
  color: #0a58ca;
}

.filter-panel {
  z-index: 10;
}

.letter-spacing-1 {
  letter-spacing: 0.05rem;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}

.association-row:hover {
  background-color: #f8f9fa !important;
}

.association-table-wrapper::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.association-table-wrapper::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.association-table-wrapper::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 10px;
}

.association-table-wrapper::-webkit-scrollbar-thumb:hover {
  background: #bbb;
}

.association-type-badge {
  font-size: 0.75rem;
}

.z-index-1 {
  z-index: 1;
}
</style>
