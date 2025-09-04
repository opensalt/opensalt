<template>
  <div class="association-item d-flex justify-content-between align-items-center p-3 border-bottom">
    <div class="association-info flex-grow-1">
      <div class="d-flex align-items-center mb-2">
        <span class="badge bg-primary me-2">{{ associationType }}</span>
        <span v-if="groupTitle" class="badge bg-secondary">{{ groupTitle }}</span>
      </div>

      <div class="association-details">
        <div class="mb-1">
          <strong>Destination:</strong>
          <span class="ms-2">{{ destinationTitle }}</span>
        </div>

        <div v-if="notes" class="mb-1">
          <strong>Notes:</strong>
          <span class="ms-2 text-muted">{{ notes }}</span>
        </div>

        <div v-if="lastChangeDateTime" class="mb-1">
          <small class="text-muted">
            <strong>Last changed:</strong> {{ formatDate(lastChangeDateTime) }}
          </small>
        </div>
      </div>
    </div>

    <div class="association-actions btn-group btn-group-sm ms-3">
      <button
        type="button"
        class="btn btn-outline-primary"
        @click="$emit('edit', association)"
        title="Edit association"
      >
        <i class="bi bi-pencil"></i>
      </button>
      <button
        type="button"
        class="btn btn-outline-danger"
        @click="$emit('delete', association)"
        title="Delete association"
      >
        <i class="bi bi-trash"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  association: {
    type: Object,
    required: true
  },
  associationGroups: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['edit', 'delete']);

const associationType = computed(() => {
  return props.association.associationType || props.association.type || 'Unknown';
});

const destinationTitle = computed(() => {
  const dest = props.association.destinationNodeURI || props.association.destination;
  return dest?.title || dest?.identifier || 'Unknown';
});

const notes = computed(() => {
  return props.association.notes || '';
});

const lastChangeDateTime = computed(() => {
  return props.association.lastChangeDateTime || '';
});

const groupTitle = computed(() => {
  if (!props.association.CFAssociationGroupingURI) return '';

  const groupId = props.association.CFAssociationGroupingURI.identifier;
  const group = props.associationGroups.find(g => g.id === groupId);
  return group?.title || '';
});

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}
</script>

<style scoped>
.association-item {
  transition: background-color 0.2s ease;
}

.association-item:hover {
  background-color: #f8f9fa;
}

.association-info {
  min-width: 0; /* Allow text to wrap */
}

.association-details {
  font-size: 0.875rem;
}

.badge {
  font-size: 0.75em;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}
</style>
