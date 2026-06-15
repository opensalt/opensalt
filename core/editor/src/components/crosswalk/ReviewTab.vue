<template>
  <div class="review-tab h-100 d-flex flex-column">
    <!-- Header with stats -->
    <div class="mb-3 flex-shrink-0">
      <h5>Review Crosswalk Associations</h5>
      <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-primary">Origin Items: {{ stats.originTotal }}</span>
        <span class="badge bg-info">Destination Items: {{ stats.destinationTotal }}</span>
        <span class="badge bg-success">Matched: {{ stats.matched }}</span>
        <span class="badge bg-warning text-dark">Unmatched Origin: {{ stats.unmatchedOrigin }}</span>
        <span class="badge bg-secondary">Unmatched Destination: {{ stats.unmatchedDestination }}</span>
        <span
          v-if="stats.pending > 0"
          class="badge bg-primary"
        >Pending: {{ stats.pending }}</span>
        <span
          v-if="stats.approved > 0"
          class="badge bg-success"
        >Approved: {{ stats.approved }}</span>
        <span
          v-if="stats.rejected > 0"
          class="badge bg-danger"
        >Rejected: {{ stats.rejected }}</span>
        <span
          v-if="stats.modified > 0"
          class="badge bg-info"
        >Modified: {{ stats.modified }}</span>
      </div>
    </div>

    <div class="alert alert-secondary small mb-3 flex-shrink-0">
      <i class="bi bi-info-circle me-1" />
      <strong>Approve</strong> persists the status on the association.
      <strong>Reject</strong> permanently removes it from the framework.
    </div>

    <!-- Filter bar -->
    <div class="row g-2 mb-3 flex-shrink-0 align-items-end">
      <div class="col-md-4">
        <input
          v-model="filters.search"
          type="text"
          class="form-control form-control-sm"
          placeholder="Search items..."
        >
      </div>
      <div class="col-md-3">
        <select
          v-model="filters.matchStatus"
          class="form-select form-select-sm"
        >
          <option value="all">
            All Items
          </option>
          <option value="matched">
            Matched Only
          </option>
          <option value="unmatched-origin">
            Unmatched Origin
          </option>
          <option value="unmatched-destination">
            Unmatched Destination
          </option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-center">
        <div class="form-check">
          <input
            id="hideApproved"
            v-model="filters.hideApproved"
            type="checkbox"
            class="form-check-input"
          >
          <label
            for="hideApproved"
            class="form-check-label small"
          >Hide approved</label>
        </div>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-0">Min Confidence: {{ (filters.confidenceMin * 100).toFixed(0) }}%</label>
        <input
          v-model.number="filters.confidenceMin"
          type="range"
          min="0"
          max="1"
          step="0.05"
          class="form-range"
        >
      </div>
      <div class="col-md-1">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary w-100"
          @click="clearFilters"
        >
          Clear
        </button>
      </div>
    </div>

    <!-- Bulk actions -->
    <div
      v-if="selectedIds.size > 0"
      class="mb-2 d-flex gap-2 align-items-center flex-shrink-0"
    >
      <span class="text-muted">{{ selectedIds.size }} selected</span>
      <button
        type="button"
        class="btn btn-sm btn-success"
        @click="onBulkApprove"
      >
        <i class="bi bi-check me-1" />Approve
      </button>
      <button
        type="button"
        class="btn btn-sm btn-danger"
        @click="onBulkReject"
      >
        <i class="bi bi-x me-1" />Reject
      </button>
    </div>

    <!-- Scrollable content region (mirrors Association View layout) -->
    <div
      class="flex-grow-1 overflow-hidden d-flex flex-column"
      style="min-height: 0;"
    >
      <div
        v-if="loadingAssociations || loadingOriginItems || loadingDestinationItems"
        class="d-flex justify-content-center align-items-center flex-grow-1"
      >
        <div class="text-center text-muted">
          <div
            class="spinner-border text-primary mb-3"
            role="status"
          >
            <span class="visually-hidden">Loading...</span>
          </div>
          <p>Loading crosswalk data...</p>
        </div>
      </div>
      <div
        v-else-if="totalRows === 0"
        class="text-center py-5 text-muted flex-grow-1 d-flex flex-column justify-content-center"
      >
        <i class="bi bi-inbox fs-1 mb-3" />
        <p>No items match the current filters.</p>
      </div>

      <template v-else>
        <!-- Scrollable table area -->
        <div
          class="flex-grow-1 overflow-auto"
          style="min-height: 0;"
        >
          <!-- Side-by-side table -->
          <CrosswalkReviewTable
            :rows="pagedRows"
            :selected-ids="selectedIds"
            :loading="loadingAssociations || loadingOriginItems || loadingDestinationItems"
            :origin-framework-id="originFrameworkId"
            :destination-framework-id="destinationFrameworkId"
            @select="onSelect"
            @approve="onApprove"
            @reject="onReject"
            @edit="onEdit"
            @delete="onDelete"
            @find-match="onFindMatch"
          />
        </div>

        <!-- Pagination -->
        <div
          v-if="totalPages > 1"
          class="mt-3 flex-shrink-0"
        >
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-muted">
              Showing {{ pageStart + 1 }}–{{ pageEnd }} of {{ totalRows }}
            </small>
            <div class="d-flex align-items-center gap-2">
              <label class="small text-muted mb-0">Per page:</label>
              <select
                v-model.number="itemsPerPage"
                class="form-select form-select-sm"
                style="width: auto;"
                @change="goToPage(1)"
              >
                <option :value="25">
                  25
                </option>
                <option :value="50">
                  50
                </option>
                <option :value="100">
                  100
                </option>
              </select>
            </div>
          </div>
          <nav aria-label="Crosswalk review pagination">
            <ul class="pagination justify-content-center mb-0">
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
                  &lsaquo; Prev
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
    </div>

    <!-- Find Match modal -->
    <FindMatchModal
      :show="findMatch.show"
      :loading="findMatch.loading"
      :item-title="findMatch.itemTitle"
      :item-human-coding-scheme="findMatch.itemHumanCodingScheme"
      :candidates="findMatch.candidates"
      :error="findMatch.error"
      @close="closeFindMatch"
      @pick="onPickCandidate"
    />

    <!-- Reject confirmation modal -->
    <RejectMatchModal
      v-model:show="rejectModal.show"
      :pair="rejectModal.pair"
      :bulk-count="rejectModal.bulkIds.length"
      :deleting="rejectModal.deleting"
      :error="rejectModal.error"
      @confirm="onConfirmReject"
    />

    <!-- Edit association modal -->
    <EditAssociationModal
      v-if="editModal.association"
      :association="editModal.association"
      :available-groups="associationGroups"
      :show="editModal.show"
      @updated="onAssociationUpdated"
      @hidden="onEditModalHidden"
    />

    <!-- Delete association modal -->
    <DeleteAssociationModal
      v-model:show="deleteModal.show"
      :association="deleteModal.association"
      @confirmed="onConfirmDelete"
      @hidden="onDeleteModalHidden"
    />
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue';
import CrosswalkReviewTable from './CrosswalkReviewTable.vue';
import FindMatchModal from './FindMatchModal.vue';
import RejectMatchModal from './RejectMatchModal.vue';
import EditAssociationModal from '../association/EditAssociationModal.vue';
import DeleteAssociationModal from '../association/DeleteAssociationModal.vue';
import { useCrosswalkReview } from '@/composables/useCrosswalkReview.js';
import { useCurrentDocumentStore } from '@/stores/currentDocumentStore.ts';
import { onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const currentDocumentStore = useCurrentDocumentStore();

const {
  pagedRows,
  matchedPairs,
  currentPage,
  itemsPerPage,
  totalRows,
  totalPages,
  goToPage,
  filters,
  stats,
  selectedIds,
  clearFilters,
  approveAssociation,
  editAssociation,
  deleteAssociation,
  bulkApprove,
  bulkReject,
  findMatches,
  createMatch,
  originFrameworkId,
  destinationFrameworkId,
  loadOriginAndDestinationItems,
  loadingAssociations,
  loadingOriginItems,
  loadingDestinationItems,
} = useCrosswalkReview();

const rejectModal = reactive({
  show: false,
  pair: null,
  bulkIds: [],
  deleting: false,
  error: '',
});

const findMatch = reactive({
  show: false,
  loading: false,
  error: null,
  candidates: [],
  row: null,
  itemTitle: '',
  itemHumanCodingScheme: '',
  targetFramework: null,
});

const editModal = reactive({
  show: false,
  association: null,
});

const deleteModal = reactive({
  show: false,
  association: null,
});

const associationGroups = computed(() => currentDocumentStore.associationGroups || []);

function itemDisplayTitle(item) {
  return item?.fullStatement || item?.title || item?.abbreviatedStatement || '';
}

async function onFindMatch(row) {
  const isOrigin = row.type === 'unmatched-origin';
  const targetFramework = isOrigin ? destinationFrameworkId.value : originFrameworkId.value;
  if (!targetFramework || !row.item?.identifier) {
    return;
  }

  findMatch.row = row;
  findMatch.targetFramework = targetFramework;
  findMatch.itemTitle = itemDisplayTitle(row.item);
  findMatch.itemHumanCodingScheme = row.item?.humanCodingScheme || '';
  findMatch.candidates = [];
  findMatch.error = null;
  findMatch.loading = true;
  findMatch.show = true;

  try {
    findMatch.candidates = await findMatches(row.item.identifier, targetFramework);
  } catch (err) {
    findMatch.error = err?.message || 'Failed to search for matches.';
  } finally {
    findMatch.loading = false;
  }
}

function closeFindMatch() {
  findMatch.show = false;
  findMatch.row = null;
  findMatch.candidates = [];
  findMatch.error = null;
}

async function onPickCandidate(candidate) {
  const row = findMatch.row;
  if (!row || !candidate?.item_identifier) {
    return;
  }

  const isOrigin = row.type === 'unmatched-origin';
  const originIdentifier = isOrigin ? row.item.identifier : candidate.item_identifier;
  const destinationIdentifier = isOrigin ? candidate.item_identifier : row.item.identifier;

  try {
    await createMatch({
      originIdentifier,
      destinationIdentifier,
      similarity: candidate.relevance,
    });
    closeFindMatch();
  } catch (err) {
    findMatch.error = err?.message || 'Failed to create the match.';
  }
}

const pageStart = computed(() => (currentPage.value - 1) * itemsPerPage.value);
const pageEnd = computed(() => Math.min(currentPage.value * itemsPerPage.value, totalRows.value));

const displayedPages = computed(() => {
  const delta = 2;
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

onMounted(() => {
  loadOriginAndDestinationItems();
});

watch(() => route.query.tab, (newTab) => {
  if (newTab === 'review') {
    loadOriginAndDestinationItems();
  }
});

function onSelect(id) {
  if (selectedIds.value.has(id)) {
    selectedIds.value.delete(id);
  } else {
    selectedIds.value.add(id);
  }
}

function onApprove(id) {
  approveAssociation(id);
}

function onEdit(assoc) {
  editModal.association = assoc;
  editModal.show = true;
}

async function onAssociationUpdated(updatedAssoc) {
  editModal.show = false;
  await editAssociation(updatedAssoc);
  editModal.association = null;
}

function onEditModalHidden() {
  editModal.show = false;
  editModal.association = null;
}

function onDelete(assoc) {
  deleteModal.association = assoc;
  deleteModal.show = true;
}

async function onConfirmDelete(assoc) {
  const id = assoc.id || assoc.identifier;
  if (id) {
    await deleteAssociation(id);
  }
  deleteModal.show = false;
}

function onDeleteModalHidden() {
  deleteModal.association = null;
}

/**
 * Filter the current selection down to IDs that correspond to matched pairs
 * (association identifiers). Unmatched rows have IDs prefixed with 'unmatched-'.
 */
function selectedMatchedIds() {
  const pairIds = new Set(matchedPairs.value.map(p => p.id));
  return [...selectedIds.value].filter(id => pairIds.has(id));
}

async function onBulkApprove() {
  const ids = selectedMatchedIds();
  if (ids.length === 0) {
    return;
  }
  await bulkApprove(new Set(ids));
  selectedIds.value.clear();
}

function onReject(id) {
  const pair = matchedPairs.value.find(p => p.id === id);
  if (!pair) {
    return;
  }
  rejectModal.pair = pair;
  rejectModal.bulkIds = [];
  rejectModal.error = '';
  rejectModal.deleting = false;
  rejectModal.show = true;
}

function onBulkReject() {
  const ids = selectedMatchedIds();
  if (ids.length === 0) {
    return;
  }
  rejectModal.pair = null;
  rejectModal.bulkIds = ids;
  rejectModal.error = '';
  rejectModal.deleting = false;
  rejectModal.show = true;
}

async function onConfirmReject() {
  rejectModal.deleting = true;
  rejectModal.error = '';
  try {
    if (rejectModal.pair) {
      await deleteAssociation(rejectModal.pair.id);
    } else if (rejectModal.bulkIds.length > 0) {
      await bulkReject(new Set(rejectModal.bulkIds));
      selectedIds.value.clear();
    }
    rejectModal.show = false;
    rejectModal.pair = null;
    rejectModal.bulkIds = [];
  } catch (err) {
    rejectModal.error = err?.message || 'Failed to remove the association(s).';
  } finally {
    rejectModal.deleting = false;
  }
}
</script>
