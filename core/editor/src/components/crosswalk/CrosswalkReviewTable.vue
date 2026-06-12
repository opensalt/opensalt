<template>
  <div class="crosswalk-review-table flex-grow-1 overflow-auto">
    <table class="table table-hover table-sm mb-0">
      <thead class="table-light sticky-top">
        <tr>
          <th style="width: 40px;">
            <input
              type="checkbox"
              class="form-check-input"
              :checked="allSelected"
              @change="toggleSelectAll"
            >
          </th>
          <th class="col-origin">Origin Item</th>
          <th style="width: 60px;"></th>
          <th style="width: 80px;">Confidence</th>
          <th style="width: 100px;">Type</th>
          <th class="col-destination">Destination Item</th>
          <th style="width: 100px;">Status</th>
          <th style="width: 100px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <template v-if="loading">
          <tr>
            <td
              colspan="8"
              class="text-center text-muted py-4"
            >
              Loading items...
            </td>
          </tr>
        </template>
        <template v-else>
          <CrosswalkReviewRow
            v-for="pair in matchedPairs"
            :key="pair.id"
            :pair="pair"
            :is-selected="isSelected(pair.id)"
            @select="onSelect"
            @approve="onApprove"
            @reject="onReject"
          />
          <CrosswalkReviewRow
            v-for="item in unmatchedOrigin"
            :key="'unmatched-origin-' + item.identifier"
            :unmatched-origin-item="item"
            @select="onSelectUnmatchedOrigin"
          />
          <CrosswalkReviewRow
            v-for="item in unmatchedDestination"
            :key="'unmatched-dest-' + item.identifier"
            :unmatched-destination-item="item"
            @select="onSelectUnmatchedDestination"
          />
        </template>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import CrosswalkReviewRow from './CrosswalkReviewRow.vue';

const props = defineProps({
  matchedPairs: { type: Array, default: () => [] },
  unmatchedOrigin: { type: Array, default: () => [] },
  unmatchedDestination: { type: Array, default: () => [] },
  selectedIds: { type: Set, required: true },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'approve', 'reject']);

const allSelected = computed(() => {
  const total = props.matchedPairs.length + props.unmatchedOrigin.length + props.unmatchedDestination.length;
  if (total === 0) return false;
  return props.matchedPairs.every(p => props.selectedIds.has(p.id)) &&
         props.unmatchedOrigin.every(i => props.selectedIds.has('unmatched-origin-' + i.identifier)) &&
         props.unmatchedDestination.every(i => props.selectedIds.has('unmatched-dest-' + i.identifier));
});

function isSelected(id) {
  return props.selectedIds.has(id);
}

function onSelect(id) {
  emit('select', id);
}

function onApprove(id) {
  emit('approve', id);
}

function onReject(id) {
  emit('reject', id);
}

function onSelectUnmatchedOrigin(item) {
  emit('select', 'unmatched-origin-' + item.identifier);
}

function onSelectUnmatchedDestination(item) {
  emit('select', 'unmatched-dest-' + item.identifier);
}

function toggleSelectAll() {
  if (allSelected.value) {
    props.selectedIds.clear();
  } else {
    props.matchedPairs.forEach(p => props.selectedIds.add(p.id));
    props.unmatchedOrigin.forEach(i => props.selectedIds.add('unmatched-origin-' + i.identifier));
    props.unmatchedDestination.forEach(i => props.selectedIds.add('unmatched-dest-' + i.identifier));
  }
}
</script>
