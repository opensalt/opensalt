<template>
  <div class="association-group mb-3">
    <div class="association-group-header d-flex justify-content-between align-items-center mb-0">
      <h6 class="mb-0 text-capitalize">
        <i :class="getAssociationIcon(associationType)" class="me-2"></i>
        {{ formatAssociationType(associationType) }}
        <span v-if="associationType.match(/^ext:/i)" class="badge bg-warning text-dark ms-2" title="This is an extended association type">Extended</span>
        <span v-if="direction === 'reversed'" class="badge bg-warning text-dark ms-2" title="Reversed (item is destination)">Reversed</span>
        <span class="badge ms-2 count-badge">{{ associations.length }}</span>
      </h6>
    </div>

    <div class="association-group-items">
      <AssociationItem
        v-for="assoc in associations"
        :key="assoc.identifier || assoc.id"
        :association="assoc"
        :association-groups="associationGroups"
        :direction="direction"
        :item-identifier="itemIdentifier"
        :is-read-only="isReadOnly"
        @edit="!isReadOnly ? $emit('edit-association', $event) : null"
        @delete="!isReadOnly ? $emit('delete-association', $event) : null"
      />
    </div>
  </div>
</template>

<script setup>
import AssociationItem from './AssociationItem.vue';
import { formatAssociationType, getAssociationIcon } from '../../utils/associationHelpers.js';

const props = defineProps({
  associationType: {
    type: String,
    required: true
  },
  associations: {
    type: Array,
    required: true
  },
  associationGroups: {
    type: Array,
    default: () => []
  },
  direction: {
    type: String,
    default: 'normal'
  },
  itemIdentifier: {
    type: [String, null],
    default: null
  },
  isReadOnly: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits([
  'edit-association',
  'delete-association'
]);


</script>

<style scoped>
.association-group {
  border: 1px solid #e9ecef;
  border-radius: 0.375rem;
  background-color: #f8f9fa;
}

.association-group-header {
  padding: 0.75rem 1rem;
  background-color: #e9ecef;
  border-bottom: 1px solid #dee2e6;
  border-radius: 0.375rem 0.375rem 0 0;
}

.association-group-header h6 {
  font-size: 0.875rem;
  font-weight: 600;
  color: #495057;
}

.association-group-items {
  padding: 0.5rem;
  max-height: 300px;
  overflow-y: auto;
}

.association-group-items:deep(.association-item) {
  margin-bottom: 0.5rem;
}

.association-group-items:deep(.association-item:last-child) {
  margin-bottom: 0;
}

/* WCAG 2.1 AA compliant count badge */
.count-badge {
  background-color: #495057;
  color: #ffffff;
}
</style>
