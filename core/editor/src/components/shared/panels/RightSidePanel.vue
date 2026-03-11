<template>
  <div class="right-side-panel ms-3 h-100 d-flex flex-column">
    <!-- Mode Tabs -->
    <div class="mode-tabs mb-2 flex-shrink-0">
      <div class="btn-group w-100" role="group" aria-label="Panel mode selection">
        <button
          type="button"
          class="btn btn-sm"
          :class="{ 'btn-primary': currentMode === 'itemDetails', 'btn-outline-primary': currentMode !== 'itemDetails' }"
          @click="setMode('itemDetails')"
        >
          <i class="bi bi-info-circle me-1"></i>
          Item Details
        </button>
        <button
          v-if="sessionStore.isAuthenticated"
          type="button"
          class="btn btn-sm"
          :class="{ 'btn-primary': currentMode === 'copyItems', 'btn-outline-primary': currentMode !== 'copyItems' }"
          @click="setMode('copyItems')"
        >
          <i class="bi bi-copy me-1"></i>
          Copy Items
        </button>
        <button
          v-if="sessionStore.isAuthenticated"
          type="button"
          class="btn btn-sm"
          :class="{ 'btn-primary': currentMode === 'createAssociations', 'btn-outline-primary': currentMode !== 'createAssociations' }"
          @click="setMode('createAssociations')"
        >
          <i class="bi bi-link-45deg me-1"></i>
          Create Associations
        </button>
      </div>
    </div>

    <!-- Panel Content -->
    <div class="panel-content flex-grow-1 overflow-y-auto">
      <!-- Item Details Mode -->
      <ItemDetailsPanel
        v-if="currentMode === 'itemDetails'"
        :selected-item="selectedItem"
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
        @edit-document="$emit('edit-document')"
        @add-root-item="$emit('add-root-item')"
        @manage-association-groups="$emit('manage-association-groups')"
        @update-framework="$emit('update-framework')"
        @export-document="$emit('export-document')"
        @clone-framework="$emit('clone-framework')"
      />

      <!-- Copy Items or Create Associations Mode -->
      <SideTreePanel
        v-else
        :mode="currentMode"
        :current-document-id="currentDocument?.id"
        :available-documents="availableDocuments"
        :side-document="sideDocument"
        :loading-side-doc="loadingSideDoc"
        :side-doc-error="sideDocError"
        @document-select="$emit('side-document-select', $event)"
        @external-document-requested="$emit('external-document-requested')"
        @side-select="$emit('side-select', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import ItemDetailsPanel from './ItemDetailsPanel.vue';
import SideTreePanel from '../../tree/SideTreePanel.vue';
import { useSessionStore } from '../../../stores/sessionStore';

const sessionStore = useSessionStore();

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
  },
  availableDocuments: {
    type: Array,
    default: () => []
  },
  sideDocument: {
    type: Object,
    default: null
  },
  loadingSideDoc: {
    type: Boolean,
    default: false
  },
  sideDocError: {
    type: String,
    default: ''
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
  'update-item',
  'edit-document',
  'add-root-item',
  'manage-association-groups',
  'update-framework',
  'export-document',
  'clone-framework',
  'side-document-select',
  'external-document-requested',
  'side-select'
]);

const currentMode = ref(props.initialMode);

function setMode(mode) {
  currentMode.value = mode;
  emit('mode-changed', mode);
}

// Sync with prop changes
watch(() => props.initialMode, (newMode) => {
  currentMode.value = newMode;
});

watch(() => sessionStore.isAuthenticated, (auth) => {
  if (!auth && currentMode.value !== 'itemDetails') {
    setMode('itemDetails');
  }
});
</script>

<style scoped>
.right-side-panel {
  height: 100%;
  overflow: hidden;
}

.mode-tabs .btn {
  font-size: 0.8rem;
  padding: 0.375rem 0.5rem;
}

.mode-tabs .btn i {
  font-size: 0.9em;
}

.panel-content {
  min-height: 0;
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
