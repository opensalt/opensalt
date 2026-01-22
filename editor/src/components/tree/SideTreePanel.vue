<template>
  <div class="side-tree-panel h-100 d-flex flex-column">
    <!-- Document Selector -->
    <div class="document-selector mb-2">
      <div class="input-group input-group-sm">
        <label class="input-group-text" for="side-doc-select">
          <i class="bi bi-file-earmark-text"></i>
        </label>
        <select
          id="side-doc-select"
          class="form-select form-select-sm"
          :value="selectedDocumentId"
          @change="onDocumentChange"
        >
          <option value="">Select a document...</option>
          <option
            v-for="doc in availableDocuments"
            :key="doc.id"
            :value="doc.id"
            :disabled="doc.id === currentDocumentId"
          >
            {{ doc.title }}{{ doc.id === currentDocumentId ? ' (Current)' : '' }}
          </option>
          <optgroup label="External">
            <option value="__external__">Load external document...</option>
          </optgroup>
        </select>
        <button
          v-if="selectedDocumentId"
          class="btn btn-outline-secondary"
          type="button"
          @click="onChangeDocument"
          title="Change document"
        >
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    </div>

    <!-- Instructions -->
    <div v-if="!selectedDocumentId" class="instructions alert alert-info py-2 mb-2">
      <small>
        <i class="bi bi-info-circle me-1"></i>
        <span v-if="mode === 'copyItems'">
          Select a document to copy items from.
        </span>
        <span v-else>
          Select a document to create associations with.
        </span>
      </small>
    </div>

    <!-- Side Tree -->
    <div v-if="selectedDocumentId" class="side-tree flex-grow-1 overflow-auto border rounded p-2">
      <div v-if="loadingSideDoc" class="d-flex justify-content-center align-items-center h-100">
        <div class="spinner-border spinner-border-sm text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <div v-else-if="sideDocError" class="alert alert-danger py-2">
        {{ sideDocError }}
      </div>
      <div v-else-if="sideDocument" class="side-tree-content">
        <TreeView
          :doc="sideDocument"
          :selected-id="sideSelectedId"
          @select="onSideSelect"
        />
      </div>
      <div v-else class="d-flex justify-content-center align-items-center h-100 text-muted">
        <span>Select a document above</span>
      </div>
    </div>

    <!-- Drag Instructions -->
    <div v-if="sideDocument" class="drag-instructions mt-2 alert alert-secondary py-1">
      <small>
        <i class="bi bi-grip-vertical me-1"></i>
        <span v-if="mode === 'copyItems'">
          Drag items to the left tree to copy them.
        </span>
        <span v-else>
          Drag items to create associations.
        </span>
      </small>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import TreeView from './TreeView.vue';

const props = defineProps({
  mode: {
    type: String,
    default: 'copyItems'
  },
  currentDocumentId: {
    type: String,
    default: ''
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
  'document-select',
  'external-document-requested',
  'side-select'
]);

const selectedDocumentId = ref('');
const sideSelectedId = ref(null);

function onDocumentChange(event) {
  const value = event.target.value;
  if (value === '__external__') {
    emit('external-document-requested');
    event.target.value = selectedDocumentId.value; // Reset select
  } else {
    selectedDocumentId.value = value;
    emit('document-select', value);
  }
}

function onChangeDocument() {
  selectedDocumentId.value = '';
  emit('document-select', '');
}

function onSideSelect(id) {
  sideSelectedId.value = id;
  emit('side-select', id);
}

// Reset selection when document changes
watch(() => props.sideDocument, () => {
  sideSelectedId.value = null;
});
</script>

<style scoped>
.side-tree-panel {
  min-height: 0;
}

.side-tree {
  background-color: #f8f9fa;
  min-height: 200px;
}

.side-tree-content {
  min-height: 100%;
}

.instructions {
  font-size: 0.85rem;
}

.drag-instructions {
  font-size: 0.8rem;
}

.document-selector select {
  flex: 1;
}
</style>
