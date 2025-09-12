<template>
  <div class="item-details-panel">
    <!-- Document Details (when no item selected) -->
    <DocumentDetailsPanel
      v-if="!selectedItem && currentDocument"
      :document="currentDocument"
      :association-groups="associationGroups"
      @edit-document="$emit('edit-document')"
      @add-root-item="$emit('add-root-item', $event)"
      @manage-association-groups="$emit('manage-association-groups')"
    />

    <!-- No Document Loaded -->
    <div v-else-if="!selectedItem && !currentDocument" class="text-center text-muted p-4">
      <i class="bi bi-file-earmark fs-1 mb-3"></i>
      <p>No document loaded</p>
    </div>

    <!-- Item Details (when item selected) -->
    <ItemDetails
      v-else
      :item="selectedItem"
      :current-document="currentDocument"
      :association-groups="associationGroups"
      @edit-item="$emit('edit-item', $event)"
      @delete-item="$emit('delete-item', $event)"
      @add-child="$emit('add-child', $event)"
      @add-exemplar="$emit('add-exemplar', $event)"
      @add-association="$emit('add-association', $event)"
      @edit-association="$emit('edit-association', $event)"
      @delete-association="$emit('delete-association', $event)"
      @update-item="$emit('update-item', $event)"
    />
  </div>
</template>

<script setup>
import DocumentDetailsPanel from './DocumentDetailsPanel.vue';
import ItemDetails from './ItemDetails.vue';

const props = defineProps({
  selectedItem: Object,
  currentDocument: Object,
  associationGroups: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits([
  'edit-item',
  'delete-item',
  'add-child',
  'add-exemplar',
  'add-association',
  'edit-association',
  'delete-association',
  'update-item',
  'edit-document',
  'add-root-item',
  'manage-association-groups'
]);
</script>

<style scoped>
/* ItemDetailsPanel specific styles can be added here if needed */
</style>
