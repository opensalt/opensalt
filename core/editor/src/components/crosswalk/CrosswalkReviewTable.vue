<template>
  <div class="crosswalk-review-table border rounded bg-white shadow-sm">
    <table class="table table-hover table-sm mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 40px;">
            <input
              type="checkbox"
              class="form-check-input"
              :checked="allSelected"
              @change="toggleSelectAll"
            >
          </th>
          <th class="col-origin">
            Origin Item
          </th>
          <th style="width: 60px;" />
          <th style="width: 80px;">
            Confidence
          </th>
          <th style="width: 100px;">
            Type
          </th>
          <th class="col-destination">
            Destination Item
          </th>
          <th style="width: 100px;">
            Status
          </th>
          <th style="width: 100px;">
            Actions
          </th>
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
        <template v-else-if="rows.length === 0">
          <tr>
            <td
              colspan="8"
              class="text-center text-muted py-4"
            >
              No items match the current filters.
            </td>
          </tr>
        </template>
        <template v-else>
          <CrosswalkReviewRow
            v-for="row in rows"
            :key="row.id"
            :row="row"
            :is-selected="selectedIds.has(row.id)"
            :origin-framework-id="originFrameworkId"
            :destination-framework-id="destinationFrameworkId"
            @select="onSelect"
            @approve="onApprove"
            @reject="onReject"
            @edit="onEdit"
            @delete="onDelete"
            @find-match="onFindMatch"
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
  rows: { type: Array, default: () => [] },
  selectedIds: { type: Set, required: true },
  loading: { type: Boolean, default: false },
  originFrameworkId: { type: String, default: null },
  destinationFrameworkId: { type: String, default: null },
});

const emit = defineEmits(['select', 'approve', 'reject', 'edit', 'delete', 'find-match']);

const allSelected = computed(() => {
  if (props.rows.length === 0) return false;
  return props.rows.every(row => props.selectedIds.has(row.id));
});

function onSelect(id) {
  emit('select', id);
}

function onApprove(id) {
  emit('approve', id);
}

function onReject(id) {
  emit('reject', id);
}

function onEdit(assoc) {
  emit('edit', assoc);
}

function onDelete(assoc) {
  emit('delete', assoc);
}

function onFindMatch(row) {
  emit('find-match', row);
}

function toggleSelectAll() {
  if (allSelected.value) {
    props.selectedIds.clear();
  } else {
    props.rows.forEach(row => props.selectedIds.add(row.id));
  }
}
</script>

<style scoped>
.crosswalk-review-table {
  overflow-x: visible;
}

.crosswalk-review-table thead th {
  position: sticky;
  top: 0;
  z-index: 10;
}
</style>
