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
          <th>Origin Item</th>
          <th style="width: 80px;">
            Confidence
          </th>
          <th>Destination Item</th>
          <th style="width: 120px;">
            Type
          </th>
          <th style="width: 120px;">
            Subtype
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
        <template
          v-for="assoc in associations"
          :key="assoc.identifier || assoc.id"
        >
          <CrosswalkReviewRow
            :association="assoc"
            :is-selected="isSelected(assoc)"
            @select="onSelect"
            @approve="onApprove"
            @reject="onReject"
            @update-type="onUpdateType"
            @update-subtype="onUpdateSubtype"
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
  associations: { type: Array, required: true },
  selectedIds: { type: Set, required: true },
});

const emit = defineEmits(['select', 'approve', 'reject', 'bulk-approve', 'bulk-reject', 'update-type', 'update-subtype']);

const allSelected = computed(() => {
  return props.associations.length > 0 && props.associations.every(a => isSelected(a));
});

function isSelected(assoc) {
  return props.selectedIds.has(assoc.identifier || assoc.id);
}

function onSelect(assoc) {
  emit('select', assoc);
}

function onApprove(assoc) {
  emit('approve', assoc);
}

function onReject(assoc) {
  emit('reject', assoc);
}

function onUpdateType(assoc, type) {
  emit('update-type', assoc, type);
}

function onUpdateSubtype(assoc, subtype) {
  emit('update-subtype', assoc, subtype);
}

function toggleSelectAll() {
  if (allSelected.value) {
    props.selectedIds.clear();
  } else {
    props.associations.forEach(a => props.selectedIds.add(a.identifier || a.id));
  }
}
</script>
