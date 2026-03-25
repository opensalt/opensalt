<template>
  <div class="item-details-panel">
    <!-- Document Details (when no item selected) -->
    <!-- Show viewed document details when viewing a different framework, otherwise show current document -->
    <DocumentDetailsPanel
      v-if="!selectedItem && displayDocument"
      :document="displayDocument"
      :association-groups="associationGroups"
      :is-viewing-different-framework="contextStore.isViewingDifferentFramework"
      @edit-document="$emit('edit-document')"
      @delete-document="$emit('delete-document')"
      @add-root-item="$emit('add-root-item', $event)"
      @manage-association-groups="$emit('manage-association-groups')"
      @update-framework="$emit('update-framework')"
      @export-document="$emit('export-document')"
      @clone-framework="$emit('clone-framework')"
      @edit-association="$emit('edit-association', $event)"
      @delete-association="$emit('delete-association', $event)"
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
      @add-child="(...args) => $emit('add-child', ...args)"
      @add-exemplar="$emit('add-exemplar', $event)"
      @add-association="$emit('add-association', $event)"
      @edit-association="$emit('edit-association', $event)"
      @delete-association="$emit('delete-association', $event)"
      @update-item="$emit('update-item', $event)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useEditorContextStore } from '@/stores/editorContextStore';
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
  'delete-document',
  'add-root-item',
  'manage-association-groups',
  'update-framework',
  'export-document',
  'clone-framework'
]);

const contextStore = useEditorContextStore();

/**
 * Computed property to determine which document to display in the details panel.
 * When viewing a different framework and no item is selected, show the viewed document.
 * Otherwise, show the current (edited) document.
 */
const displayDocument = computed(() => {
  if (contextStore.isViewingDifferentFramework && contextStore.viewedDocumentId) {
    return contextStore.documentRegistry.get(contextStore.viewedDocumentId);
  }
  return props.currentDocument;
});
</script>

<style scoped>
.item-details-panel {
  flex: 1;
  min-height: 0;
}
</style>

<style scoped>
.item-details-panel {
  flex: 1;
  min-height: 0;
}
</style>
