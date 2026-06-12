<template>
  <tr
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
    <td>
      <div class="d-flex flex-column">
        <span
          v-if="association.originNodeURI?.humanCodingScheme"
          class="text-muted small"
        >{{ association.originNodeURI.humanCodingScheme }}</span>
        <span>{{ truncatedOriginTitle }}</span>
      </div>
    </td>
    <td>
      <span
        class="badge"
        :class="confidenceBadgeClass"
      >{{ confidencePercentage }}%</span>
    </td>
    <td>
      <div class="d-flex flex-column">
        <span
          v-if="association.destinationNodeURI?.humanCodingScheme"
          class="text-muted small"
        >{{ association.destinationNodeURI.humanCodingScheme }}</span>
        <span>{{ truncatedDestTitle }}</span>
      </div>
    </td>
    <td>
      <select
        :value="association.associationType"
        class="form-select form-select-sm"
        @change="$emit('update-type', association, $event.target.value)"
      >
        <option value="exactMatchOf">
          exactMatchOf
        </option>
        <option value="isRelatedTo">
          isRelatedTo
        </option>
      </select>
    </td>
    <td>
      <select
        :value="association.extensions?.['crosswalk:subtype'] || 'related'"
        class="form-select form-select-sm"
        @change="$emit('update-subtype', association, $event.target.value)"
      >
        <option value="exact">
          exact
        </option>
        <option value="related">
          related
        </option>
        <option value="broaderThan">
          broaderThan
        </option>
        <option value="narrowerThan">
          narrowerThan
        </option>
      </select>
    </td>
    <td>
      <span
        class="badge"
        :class="statusBadgeClass"
      >{{ status }}</span>
    </td>
    <td>
      <div class="btn-group btn-group-sm">
        <button
          type="button"
          class="btn btn-outline-success"
          title="Approve"
          @click="$emit('approve', association)"
        >
          <i class="bi bi-check" />
        </button>
        <button
          type="button"
          class="btn btn-outline-danger"
          title="Reject"
          @click="$emit('reject', association)"
        >
          <i class="bi bi-x" />
        </button>
      </div>
    </td>
  </tr>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  association: { type: Object, required: true },
  isSelected: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'approve', 'reject', 'update-type', 'update-subtype']);

const confidence = computed(() => props.association.extensions?.['crosswalk:confidence'] || 0);
const confidencePercentage = computed(() => Math.round(confidence.value * 100));
const status = computed(() => props.association.extensions?.['crosswalk:status'] || 'pending');

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
  const t = props.association.originNodeURI?.title || 'Unknown';
  return t.length > 60 ? t.substring(0, 60) + '...' : t;
});

const truncatedDestTitle = computed(() => {
  const t = props.association.destinationNodeURI?.title || 'Unknown';
  return t.length > 60 ? t.substring(0, 60) + '...' : t;
});

function toggleSelect() {
  emit('select', props.association);
}
</script>
