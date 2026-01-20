<template>
  <div class="right-side-panel ms-3">
    <!-- Item Details Panel -->
    <ItemDetailsPanel
      :selected-item="selectedItem"
      :current-document="currentDocument"
      :association-groups="associationGroups"
      @edit-item="$emit('edit-item', $event)"
      @delete-item="$emit('delete-item', $event)"
      @add-child="$emit('add-child', $event)"
      @add-exemplar="$emit('add-exemplar', $event)"
      @add-association="$emit('add-association', $event)"
      @edit-association="$emit('edit-association', $event)"
      @delete-association="$emit('delete-association', $event)"
      @edit-document="$emit('edit-document')"
      @add-root-item="$emit('add-root-item')"
      @manage-association-groups="$emit('manage-association-groups')"
    />
  </div>
</template>

<script setup>
import ItemDetailsPanel from './ItemDetailsPanel.vue';

const props = defineProps({
  selectedItem: Object,
  currentDocument: Object,
  initialMode: {
    type: String,
    default: 'itemDetails'
  },
  associationGroups: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits([
  'mode-changed', // deprecated but kept to avoid breaking parent listeners yet
  'edit-item',
  'delete-item',
  'add-child',
  'add-exemplar',
  'add-association',
  'edit-association',
  'delete-association',
  'edit-document',
  'add-root-item',
  'manage-association-groups'
]);
</script>

<style scoped>
.right-side-panel {
  height: 100%;
  overflow-y: auto;
}

.btn-group .btn {
  font-size: 0.875rem;
}

.card-header {
  padding: 0.5rem 1rem;
  background-color: #f8f9fa;
}

.card-body {
  padding: 1rem;
}

.list-group-item {
  padding: 0.75rem 1rem;
}

.badge {
  font-size: 0.75em;
}

.alert {
  padding: 0.75rem 1rem;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.associations-list {
  max-height: 300px;
  overflow-y: auto;
}
</style>
