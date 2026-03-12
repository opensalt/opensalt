<template>
  <div class="side-tree-panel h-100 d-flex flex-column">
    <!-- Document Selector -->
    <DocumentSelector
      :current-doc="currentDocForSelector"
      :available-documents="availableDocuments"
      :label="mode === 'copyItems' ? 'Source Document' : 'Target Document'"
      side="right"
      @document-changed="onDocumentChanged"
      @external-document-requested="onExternalDocumentRequested"
    />

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
    <div v-if="selectedDocumentId" class="side-tree flex-grow-1 d-flex flex-column overflow-hidden border rounded p-2">
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
        <span>Loading document...</span>
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
import { ref, watch, computed } from 'vue';
import TreeView from './TreeView.vue';
import DocumentSelector from '../shared/common/DocumentSelector.vue';

const props = defineProps({
  mode: {
    type: String,
    default: 'copyItems'
  },
  currentDocument: {
    type: Object,
    default: null
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

// Track current document for DocumentSelector
const currentDocForSelector = computed(() => {
  // If sideDocument is set, it's the current selected document for this panel
  if (selectedDocumentId.value && props.availableDocuments) {
    return props.availableDocuments.find(doc => doc.id === selectedDocumentId.value) || props.currentDocument;
  }
  return props.currentDocument;
});

function onDocumentChanged(event) {
  const { side, documentId } = event;
  if (documentId) {
    selectedDocumentId.value = documentId;
    emit('document-select', documentId);
  }
}

function onExternalDocumentRequested(event) {
  emit('external-document-requested');
}

function onSideSelect(id) {
  sideSelectedId.value = id;
  emit('side-select', id);
}

// Reset selection when document changes
watch(() => props.sideDocument, () => {
  sideSelectedId.value = null;
});

// Sync selectedDocumentId with sideDocument
watch(() => props.sideDocument, (newDoc) => {
  if (newDoc) {
    selectedDocumentId.value = newDoc.id;
  }
}, { immediate: true });
</script>

<style scoped>
.side-tree-panel {
  min-height: 0;
}

.side-tree {
  background-color: #f8f9fa;
  min-height: 0;
}

.side-tree-content {
  min-height: 0;
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
}

.side-tree-content :deep(.tree-view) {
  min-height: 0;
}

.instructions {
  font-size: 0.85rem;
}

.drag-instructions {
  font-size: 0.8rem;
}
</style>
