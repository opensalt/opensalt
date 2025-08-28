<template>
  <div class="right-side-panel">
    <!-- Control Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
      <div class="btn-group" role="group" aria-label="Right side mode">
        <button
          type="button"
          class="btn"
          :class="mode === 'itemDetails' ? 'btn-primary' : 'btn-outline-primary'"
          @click="setMode('itemDetails')"
        >
          <i class="bi bi-info-circle"></i> Item Details
        </button>
        <button
          type="button"
          class="btn"
          :class="mode === 'copyItem' ? 'btn-primary' : 'btn-outline-primary'"
          @click="setMode('copyItem')"
        >
          <i class="bi bi-copy"></i> Copy Items
        </button>
        <button
          type="button"
          class="btn"
          :class="mode === 'addAssociation' ? 'btn-primary' : 'btn-outline-primary'"
          @click="setMode('addAssociation')"
        >
          <i class="bi bi-link"></i> Associations
        </button>
      </div>
    </div>

    <!-- Item Details Mode -->
    <ItemDetailsPanel
      v-if="mode === 'itemDetails'"
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

    <!-- Copy Items Mode -->
    <CopyItemsPanel v-else-if="mode === 'copyItem'" />

    <!-- Add Association Mode -->
    <AssociationPanel v-else-if="mode === 'addAssociation'" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import ItemDetailsPanel from './ItemDetailsPanel.vue';
import CopyItemsPanel from './CopyItemsPanel.vue';
import AssociationPanel from './AssociationPanel.vue';

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
  'mode-changed',
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

const mode = ref(props.initialMode);

watch(() => props.initialMode, (newMode) => {
  mode.value = newMode;
});

function setMode(newMode) {
  mode.value = newMode;
  emit('mode-changed', newMode);
}
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
