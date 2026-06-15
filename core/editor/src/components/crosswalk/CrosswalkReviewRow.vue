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
        <a
          v-if="originItemLink"
          :href="originItemLink"
          target="_blank"
          class="text-decoration-none"
        >{{ truncatedOriginTitle }}<i
          v-if="originHasChildren"
          class="bi bi-diagram-3 text-muted ms-1 cw-has-children"
          title="Has child items"
        /></a>
        <span v-else>{{ truncatedOriginTitle }}<i
          v-if="originHasChildren"
          class="bi bi-diagram-3 text-muted ms-1 cw-has-children"
          title="Has child items"
        /></span>
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
        <a
          v-if="destItemLink"
          :href="destItemLink"
          target="_blank"
          class="text-decoration-none"
        >{{ truncatedDestTitle }}<i
          v-if="destHasChildren"
          class="bi bi-diagram-3 text-muted ms-1 cw-has-children"
          title="Has child items"
        /></a>
        <span v-else>{{ truncatedDestTitle }}<i
          v-if="destHasChildren"
          class="bi bi-diagram-3 text-muted ms-1 cw-has-children"
          title="Has child items"
        /></span>
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
          class="btn btn-outline-secondary"
          title="Edit"
          @click="$emit('edit', pair.association)"
        >
          <i class="bi bi-pencil" />
        </button>
        <template v-if="!isApproved">
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
        </template>
        <button
          v-else
          type="button"
          class="btn btn-outline-danger"
          title="Delete"
          @click="$emit('delete', pair.association)"
        >
          <i class="bi bi-trash" />
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
        @change="toggleSelect"
      >
    </td>
    <td class="col-origin">
      <div class="d-flex flex-column">
        <span
          v-if="unmatchedOriginItem.humanCodingScheme"
          class="text-muted small"
        >{{ unmatchedOriginItem.humanCodingScheme }}</span>
        <a
          v-if="originItemLink"
          :href="originItemLink"
          target="_blank"
          class="text-decoration-none"
        >{{ truncatedUnmatchedOriginTitle }}<i
          v-if="unmatchedOriginHasChildren"
          class="bi bi-diagram-3 text-muted ms-1 cw-has-children"
          title="Has child items"
        /></a>
        <span v-else>{{ truncatedUnmatchedOriginTitle }}<i
          v-if="unmatchedOriginHasChildren"
          class="bi bi-diagram-3 text-muted ms-1 cw-has-children"
          title="Has child items"
        /></span>
      </div>
    </td>
    <td class="text-center text-muted">
      &mdash;
    </td>
    <td class="text-muted">
      —
    </td>
    <td class="text-muted">
      —
    </td>
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
        title="Find a match in the destination framework"
        @click="onFindMatch"
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
        @change="toggleSelect"
      >
    </td>
    <td class="col-origin text-muted">
      <em>No match from origin</em>
    </td>
    <td class="text-center text-muted">
      &mdash;
    </td>
    <td class="text-muted">
      —
    </td>
    <td class="text-muted">
      —
    </td>
    <td class="col-destination">
      <div class="d-flex flex-column">
        <span
          v-if="unmatchedDestinationItem.humanCodingScheme"
          class="text-muted small"
        >{{ unmatchedDestinationItem.humanCodingScheme }}</span>
        <a
          v-if="destItemLink"
          :href="destItemLink"
          target="_blank"
          class="text-decoration-none"
        >{{ truncatedUnmatchedDestTitle }}<i
          v-if="unmatchedDestHasChildren"
          class="bi bi-diagram-3 text-muted ms-1 cw-has-children"
          title="Has child items"
        /></a>
        <span v-else>{{ truncatedUnmatchedDestTitle }}<i
          v-if="unmatchedDestHasChildren"
          class="bi bi-diagram-3 text-muted ms-1 cw-has-children"
          title="Has child items"
        /></span>
      </div>
    </td>
    <td>
      <span class="badge bg-secondary">unmatched</span>
    </td>
    <td>
      <button
        type="button"
        class="btn btn-sm btn-outline-primary"
        title="Find a match in the origin framework"
        @click="onFindMatch"
      >
        <i class="bi bi-search me-1" />
        Find Match
      </button>
    </td>
  </tr>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  row: { type: Object, required: true },
  isSelected: { type: Boolean, default: false },
  originFrameworkId: { type: String, default: null },
  destinationFrameworkId: { type: String, default: null },
});

const emit = defineEmits(['select', 'approve', 'reject', 'edit', 'delete', 'find-match']);

const pair = computed(() => (props.row.type === 'matched' ? props.row.pair : null));
const unmatchedOriginItem = computed(() => (props.row.type === 'unmatched-origin' ? props.row.item : null));
const unmatchedDestinationItem = computed(() => (props.row.type === 'unmatched-destination' ? props.row.item : null));

const confidence = computed(() => pair.value?.confidence || 0);
const confidencePercentage = computed(() => Math.round(confidence.value * 100));

const status = computed(() => pair.value?.status || 'pending');
const isApproved = computed(() => status.value === 'approved');

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
  const t = pair.value?.originItem?.fullStatement || pair.value?.originItem?.title || 'Unknown';
  return t.length > 80 ? t.substring(0, 80) + '...' : t;
});

const truncatedDestTitle = computed(() => {
  const t = pair.value?.destinationItem?.fullStatement || pair.value?.destinationItem?.title || 'Unknown';
  return t.length > 80 ? t.substring(0, 80) + '...' : t;
});

const truncatedUnmatchedOriginTitle = computed(() => {
  const t = unmatchedOriginItem.value?.fullStatement || unmatchedOriginItem.value?.title || 'Unknown';
  return t.length > 80 ? t.substring(0, 80) + '...' : t;
});

const truncatedUnmatchedDestTitle = computed(() => {
  const t = unmatchedDestinationItem.value?.fullStatement || unmatchedDestinationItem.value?.title || 'Unknown';
  return t.length > 80 ? t.substring(0, 80) + '...' : t;
});

const isSelectedUnmatched = computed(() => Boolean(unmatchedOriginItem.value || unmatchedDestinationItem.value) && props.isSelected);

function hasChildren(item) {
  return Boolean(item?.children?.length);
}

const originHasChildren = computed(() => hasChildren(pair.value?.originItem));
const destHasChildren = computed(() => hasChildren(pair.value?.destinationItem));
const unmatchedOriginHasChildren = computed(() => hasChildren(unmatchedOriginItem.value));
const unmatchedDestHasChildren = computed(() => hasChildren(unmatchedDestinationItem.value));

const originItemLink = computed(() => {
  const item = pair.value?.originItem || unmatchedOriginItem.value;
  if (!item?.identifier || !props.originFrameworkId) return null;
  return `/editor/${props.originFrameworkId}/${item.identifier}`;
});

const destItemLink = computed(() => {
  const item = pair.value?.destinationItem || unmatchedDestinationItem.value;
  if (!item?.identifier || !props.destinationFrameworkId) return null;
  return `/editor/${props.destinationFrameworkId}/${item.identifier}`;
});

function toggleSelect() {
  emit('select', props.row.id);
}

function onFindMatch() {
  emit('find-match', props.row);
}
</script>

<style scoped>
.col-origin {
  width: 35%;
}
.col-destination {
  width: 35%;
}
.cw-has-children {
  font-size: 0.7rem;
  opacity: 0.5;
}
</style>
