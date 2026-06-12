<template>
  <div class="review-tab h-100 d-flex flex-column">
    <!-- Header with stats -->
    <div class="mb-3">
      <h5>Review Crosswalk Associations</h5>
      <div class="d-flex gap-2 flex-wrap">
        <span class="badge bg-primary">Origin Items: {{ stats.originTotal }}</span>
        <span class="badge bg-info">Destination Items: {{ stats.destinationTotal }}</span>
        <span class="badge bg-success">Matched: {{ stats.matched }}</span>
        <span class="badge bg-warning text-dark">Unmatched Origin: {{ stats.unmatchedOrigin }}</span>
        <span class="badge bg-secondary">Unmatched Destination: {{ stats.unmatchedDestination }}</span>
        <span v-if="stats.pending > 0" class="badge bg-primary">Pending: {{ stats.pending }}</span>
        <span v-if="stats.approved > 0" class="badge bg-success">Approved: {{ stats.approved }}</span>
        <span v-if="stats.rejected > 0" class="badge bg-danger">Rejected: {{ stats.rejected }}</span>
        <span v-if="stats.modified > 0" class="badge bg-info">Modified: {{ stats.modified }}</span>
      </div>
    </div>

    <div class="alert alert-secondary small mb-3">
      <i class="bi bi-info-circle me-1" />
      <strong>Read-only review.</strong> Status changes are applied in-memory.
      Persistence and inline editing will be available in a future update.
    </div>

    <!-- Loading state -->
    <div v-if="loadingOriginItems || loadingDestinationItems" class="alert alert-info">
      Loading framework items...
    </div>

    <!-- Filter bar -->
    <div class="row g-2 mb-3">
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
          <option value="all">All Items</option>
          <option value="matched">Matched Only</option>
          <option value="unmatched-origin">Unmatched Origin</option>
          <option value="unmatched-destination">Unmatched Destination</option>
        </select>
      </div>
      <div class="col-md-3">
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
      class="mb-2 d-flex gap-2 align-items-center"
    >
      <span class="text-muted">{{ selectedIds.size }} selected</span>
      <button
        type="button"
        class="btn btn-sm btn-success"
        disabled
        title="Bulk approve will persist changes in a future update"
      >
        Approve
      </button>
      <button
        type="button"
        class="btn btn-sm btn-danger"
        disabled
        title="Bulk reject will persist changes in a future update"
      >
        Reject
      </button>
    </div>

    <!-- Side-by-side table -->
    <CrosswalkReviewTable
      :matched-pairs="filteredPairs"
      :unmatched-origin="filteredUnmatchedOrigin"
      :unmatched-destination="filteredUnmatchedDestination"
      :selected-ids="selectedIds"
      :loading="loadingOriginItems || loadingDestinationItems"
      class="flex-grow-1 overflow-hidden"
      @select="onSelect"
      @approve="onApprove"
      @reject="onReject"
    />
  </div>
</template>

<script setup>
import CrosswalkReviewTable from './CrosswalkReviewTable.vue';
import { useCrosswalkReview } from '@/composables/useCrosswalkReview.js';
import { onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const {
  filteredPairs,
  filteredUnmatchedOrigin,
  filteredUnmatchedDestination,
  filters,
  stats,
  selectedIds,
  bulkApprove,
  bulkReject,
  clearFilters,
  approveAssociation,
  rejectAssociation,
  loadOriginAndDestinationItems,
  loadingOriginItems,
  loadingDestinationItems,
} = useCrosswalkReview();

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

function onReject(id) {
  rejectAssociation(id);
}
</script>
