<template>
  <tr
    v-if="pair"
    class="crosswalk-review-row"
    :class="rowClass"
  >
    <td>
      <input
        type="checkbox"
        :checked="isSelected"
        class="form-check-input"
        @change="toggleSelect"
      >
    </td>
    <td class="col-origin">
      <div class="d-flex flex-column">
        <span
          v-if="pair.originItem?.humanCodingScheme"
          class="text-muted small"
        >{{ pair.originItem.humanCodingScheme }}</span>
        <span>{{ truncatedOriginTitle }}</span>
      </div>
    </td>
    <td class="text-center">
      <span class="text-muted">&leftrightarrow;</span>
    </td>
    <td>
      <span
        class="badge"
        :class="confidenceBadgeClass"
      >{{ confidencePercentage }}%</span>
    </td>
    <td>
      <span
        class="badge"
        :class="pair.type === 'exactMatchOf' ? 'bg-success' : 'bg-info'"
      >{{ pair.type === 'exactMatchOf' ? 'Exact' : 'Related' }}</span>
    </td>
    <td class="col-destination">
      <div class="d-flex flex-column">
        <span
          v-if="pair.destinationItem?.humanCodingScheme"
          class="text-muted small"
        >{{ pair.destinationItem.humanCodingScheme }}</span>
        <span>{{ truncatedDestTitle }}</span>
      </div>
    </td>
    <td>
      <span
        class="badge"
        :class="statusBadgeClass"
      >{{ pair.status }}</span>
    </td>
    <td>
      <div class="btn-group btn-group-sm">
        <button
          type="button"
          class="btn btn-outline-success"
          title="Approve"
          @click="$emit('approve', pair.id)"
        >
          <i class="bi bi-check" />
        </button>
        <button
          type="button"
          class="btn btn-outline-danger"
          title="Reject"
          @click="$emit('reject', pair.id)"
        >
          <i class="bi bi-x" />
        </button>
      </div>
    </td>
  </tr>
  <tr
    v-else-if="unmatchedOriginItem"
    class="crosswalk-review-row table-warning"
  >
    <td>
      <input
        type="checkbox"
        :checked="isSelectedUnmatched"
        class="form-check-input"
        @change="toggleSelectUnmatchedOrigin"
      >
    </td>
    <td class="col-origin">
      <div class="d-flex flex-column">
        <span
          v-if="unmatchedOriginItem.humanCodingScheme"
          class="text-muted small"
        >{{ unmatchedOriginItem.humanCodingScheme }}</span>
        <span>{{ truncatedUnmatchedOriginTitle }}</span>
      </div>
    </td>
    <td class="text-center text-muted">&mdash;</td>
    <td class="text-muted">—</td>
    <td class="text-muted">—</td>
    <td class="col-destination text-muted">
      <em>No match found</em>
    </td>
    <td>
      <span class="badge bg-warning text-dark">unmatched</span>
    </td>
    <td>
      <button
        type="button"
        class="btn btn-sm btn-outline-primary"
        disabled
        title="Find match will be available in a future update"
      >
        <i class="bi bi-search me-1" />
        Find Match
      </button>
    </td>
  </tr>
  <tr
    v-else-if="unmatchedDestinationItem"
    class="crosswalk-review-row"
  >
    <td>
      <input
        type="checkbox"
        :checked="isSelectedUnmatched"
        class="form-check-input"
        @change="toggleSelectUnmatchedDest"
      >
    </td>
    <td class="col-origin text-muted">
      <em>No match from origin</em>
    </td>
    <td class="text-center text-muted">&mdash;</td>
    <td class="text-muted">—</td>
    <td class="text-muted">—</td>
    <td class="col-destination">
      <div class="d-flex flex-column">
        <span
          v-if="unmatchedDestinationItem.humanCodingScheme"
          class="text-muted small"
        >{{ unmatchedDestinationItem.humanCodingScheme }}</span>
        <span>{{ truncatedUnmatchedDestTitle }}</span>
      </div>
    </td>
    <td>
      <span class="badge bg-secondary">unmatched</span>
    </td>
    <td></td>
  </tr>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  pair: { type: Object, default: null },
  unmatchedOriginItem: { type: Object, default: null },
  unmatchedDestinationItem: { type: Object, default: null },
  isSelected: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'approve', 'reject']);

const confidence = computed(() => props.pair?.confidence || 0);
const confidencePercentage = computed(() => Math.round(confidence.value * 100));

const status = computed(() => props.pair?.status || 'pending');

const confidenceBadgeClass = computed(() => {
  if (confidencePercentage.value >= 90) return 'bg-success';
  if (confidencePercentage.value >= 75) return 'bg-warning text-dark';
  return 'bg-danger';
});

const statusBadgeClass = computed(() => {
  const map = { pending: 'bg-secondary', approved: 'bg-success', rejected: 'bg-danger', modified: 'bg-info' };
  return map[status.value] || 'bg-secondary';
});

const rowClass = computed(() => {
  if (status.value === 'rejected') return 'table-danger opacity-50';
  if (status.value === 'approved') return 'table-success opacity-75';
  return '';
});

const truncatedOriginTitle = computed(() => {
  const t = props.pair?.originItem?.fullStatement || props.pair?.originItem?.title || 'Unknown';
  return t.length > 80 ? t.substring(0, 80) + '...' : t;
});

const truncatedDestTitle = computed(() => {
  const t = props.pair?.destinationItem?.fullStatement || props.pair?.destinationItem?.title || 'Unknown';
  return t.length > 80 ? t.substring(0, 80) + '...' : t;
});

const truncatedUnmatchedOriginTitle = computed(() => {
  const t = props.unmatchedOriginItem?.fullStatement || props.unmatchedOriginItem?.title || 'Unknown';
  return t.length > 80 ? t.substring(0, 80) + '...' : t;
});

const truncatedUnmatchedDestTitle = computed(() => {
  const t = props.unmatchedDestinationItem?.fullStatement || props.unmatchedDestinationItem?.title || 'Unknown';
  return t.length > 80 ? t.substring(0, 80) + '...' : t;
});

const isSelectedUnmatched = computed(() => {
  if (props.unmatchedOriginItem) {
    return props.isSelected;
  }
  if (props.unmatchedDestinationItem) {
    return props.isSelected;
  }
  return false;
});

function toggleSelect() {
  emit('select', props.pair.id);
}

function toggleSelectUnmatchedOrigin() {
  emit('select', props.unmatchedOriginItem);
}

function toggleSelectUnmatchedDest() {
  emit('select', props.unmatchedDestinationItem);
}
</script>

<style scoped>
.col-origin {
  width: 35%;
}
.col-destination {
  width: 35%;
}
</style>
