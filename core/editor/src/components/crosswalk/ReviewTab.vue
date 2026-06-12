<template>
  <div class="review-tab h-100 d-flex flex-column">
    <!-- Header with stats -->
    <div class="mb-3">
      <h5>Review Crosswalk Associations</h5>
      <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-secondary">Total: {{ stats.total }}</span>
        <span
          v-if="stats.pending > 0"
          class="badge bg-primary"
        >Pending: {{ stats.pending }}</span>
        <span class="badge bg-success">Approved: {{ stats.approved }}</span>
        <span class="badge bg-danger">Rejected: {{ stats.rejected }}</span>
        <span
          v-if="stats.modified > 0"
          class="badge bg-info"
        >Modified: {{ stats.modified }}</span>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="row g-2 mb-3">
      <div class="col-md-4">
        <input
          v-model="filters.search"
          type="text"
          class="form-control form-control-sm"
          placeholder="Search..."
        >
      </div>
      <div class="col-md-3">
        <select
          v-model="filters.status"
          class="form-select form-select-sm"
        >
          <option value="all">
            All Statuses
          </option>
          <option value="pending">
            Pending
          </option>
          <option value="approved">
            Approved
          </option>
          <option value="rejected">
            Rejected
          </option>
          <option value="modified">
            Modified
          </option>
        </select>
      </div>
      <div class="col-md-3">
        <select
          v-model="filters.associationType"
          class="form-select form-select-sm"
        >
          <option value="all">
            All Types
          </option>
          <option value="exactMatchOf">
            exactMatchOf
          </option>
          <option value="isRelatedTo">
            isRelatedTo
          </option>
        </select>
      </div>
      <div class="col-md-2">
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
      class="mb-2 d-flex gap-2"
    >
      <span class="text-muted">{{ selectedIds.size }} selected</span>
      <button
        type="button"
        class="btn btn-sm btn-success"
        @click="bulkApprove"
      >
        Approve
      </button>
      <button
        type="button"
        class="btn btn-sm btn-danger"
        @click="bulkReject"
      >
        Reject
      </button>
    </div>

    <!-- Table -->
    <CrosswalkReviewTable
      :associations="filteredAssociations"
      :selected-ids="selectedIds"
      class="flex-grow-1 overflow-hidden"
      @select="onSelect"
      @approve="onApprove"
      @reject="onReject"
      @update-type="onUpdateType"
      @update-subtype="onUpdateSubtype"
    />
  </div>
</template>

<script setup>
import CrosswalkReviewTable from './CrosswalkReviewTable.vue';
import { useCrosswalkReview } from '@/composables/useCrosswalkReview.js';

const {
  filteredAssociations,
  filters,
  stats,
  selectedIds,
  approveAssociation,
  rejectAssociation,
  bulkApprove,
  bulkReject,
  clearFilters,
} = useCrosswalkReview();

function onSelect(assoc) {
  const id = assoc.identifier || assoc.id;
  if (selectedIds.value.has(id)) {
    selectedIds.value.delete(id);
  } else {
    selectedIds.value.add(id);
  }
}

function onApprove(assoc) {
  approveAssociation(assoc.identifier || assoc.id);
}

function onReject(assoc) {
  rejectAssociation(assoc.identifier || assoc.id);
}

function onUpdateType(assoc, type) {
  assoc.associationType = type;
  assoc.extensions['crosswalk:status'] = 'modified';
}

function onUpdateSubtype(assoc, subtype) {
  assoc.extensions['crosswalk:subtype'] = subtype;
  assoc.extensions['crosswalk:status'] = 'modified';
}
</script>
