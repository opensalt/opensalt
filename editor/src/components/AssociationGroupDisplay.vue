<template>
  <div class="association-group mb-3">
    <div class="association-group-header d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0 text-capitalize">
        <i :class="getAssociationIcon(associationType)" class="me-2"></i>
        {{ formatAssociationType(associationType) }}
        <span class="badge bg-secondary ms-2">{{ associations.length }}</span>
      </h6>
    </div>

    <div class="association-group-items">
      <AssociationItem
        v-for="assoc in associations"
        :key="assoc.identifier || assoc.id"
        :association="assoc"
        :association-groups="associationGroups"
        @edit="$emit('edit-association', $event)"
        @delete="$emit('delete-association', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import AssociationItem from './AssociationItem.vue';

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
  }
});

const emit = defineEmits([
  'edit-association',
  'delete-association'
]);

function formatAssociationType(type) {
  if (!type) return 'Unknown';

  // Convert camelCase to readable format
  return type
    .replace(/([A-Z])/g, ' $1') // Add space before capital letters
    .replace(/^./, str => str.toUpperCase()) // Capitalize first letter
    .trim();
}

function getAssociationIcon(type) {
  const iconMap = {
    'isChildOf': 'bi bi-diagram-3',
    'isPeerOf': 'bi bi-share',
    'isPartOf': 'bi bi-puzzle',
    'exactMatchOf': 'bi bi-check-circle',
    'precedes': 'bi bi-arrow-right',
    'isRelatedTo': 'bi bi-link',
    'replacedBy': 'bi bi-arrow-clockwise',
    'exemplar': 'bi bi-star',
    'hasSkillLevel': 'bi bi-bar-chart',
    'isTranslationOf': 'bi bi-translate'
  };

  return iconMap[type] || 'bi bi-link-45deg';
}
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
</style>
