<template>
  <div class="side-by-side-panel h-100 d-flex flex-column">
    <!-- Mode Tabs -->
    <div class="mode-tabs mb-2">
      <div class="btn-group w-100" role="group" aria-label="Panel mode selection">
        <button
          type="button"
          class="btn btn-sm"
          :class="{ 'btn-primary': mode === 'itemDetails', 'btn-outline-primary': mode !== 'itemDetails' }"
          @click="$emit('mode-change', 'itemDetails')"
        >
          <i class="bi bi-info-circle me-1"></i>
          Item Details
        </button>
        <button
          type="button"
          class="btn btn-sm"
          :class="{ 'btn-primary': mode === 'copyItems', 'btn-outline-primary': mode !== 'copyItems' }"
          @click="$emit('mode-change', 'copyItems')"
        >
          <i class="bi bi-copy me-1"></i>
          Copy Items
        </button>
        <button
          type="button"
          class="btn btn-sm"
          :class="{ 'btn-primary': mode === 'createAssociations', 'btn-outline-primary': mode !== 'createAssociations' }"
          @click="$emit('mode-change', 'createAssociations')"
        >
          <i class="bi bi-link-45deg me-1"></i>
          Create Associations
        </button>
      </div>
    </div>

    <!-- Content Area -->
    <div class="panel-content flex-grow-1 overflow-hidden">
      <!-- Item Details Mode -->
      <slot v-if="mode === 'itemDetails'" name="item-details"></slot>

      <!-- Copy Items or Create Associations Mode -->
      <div v-else class="side-tree-container h-100 d-flex flex-column">
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
              Select a document above to copy items from it to current document.
            </span>
            <span v-else>
              Select a document above to create associations between its items and items in current document.
            </span>
          </small>
        </div>

        <!-- Side Tree -->
        <div v-if="selectedDocumentId && sideDocument" class="side-tree flex-grow-1 overflow-auto border rounded p-2">
          <div v-if="loadingSideDoc" class="d-flex justify-content-center align-items-center h-100">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
          <div v-else-if="sideDocError" class="alert alert-danger py-2">
            {{ sideDocError }}
          </div>
          <TreeView
            v-else
            :doc="sideDocument"
            :selected-id="sideSelectedId"
            @select="onSideSelect"
            @tree-change="$emit('tree-change', $event)"
          />
        </div>
        <div v-else-if="selectedDocumentId && !sideDocument && !loadingSideDoc" class="side-tree flex-grow-1 d-flex align-items-center justify-content-center border rounded">
          <div class="text-muted text-center">
            <i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>
            <span>Loading document...</span>
          </div>
        </div>

        <!-- Drag Instructions -->
        <div v-if="sideDocument" class="drag-instructions mt-2 alert alert-secondary py-2">
          <small>
            <i class="bi bi-grip-vertical me-1"></i>
            <span v-if="mode === 'copyItems'">
              Drag items from here to the left tree to copy them.
            </span>
            <span v-else>
              Drag items from here to items in the left tree to create associations.
            </span>
          </small>
        </div>
      </div>
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
    default: 'itemDetails', // 'itemDetails', 'copyItems', 'createAssociations'
    validator: (value) => ['itemDetails', 'copyItems', 'createAssociations'].includes(value)
  },
  currentDocument: {
    type: Object,
    default: null
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
  'mode-change',
  'document-select',
  'external-document-requested',
  'side-select',
  'tree-change',
  'copy-item',
  'create-association'
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
.side-by-side-panel {
  background: white;
  border-radius: 8px;
  padding: 0.75rem;
}

.mode-tabs .btn {
  font-size: 0.8rem;
  padding: 0.375rem 0.5rem;
}

.mode-tabs .btn i {
  font-size: 0.9em;
}

.side-tree-container {
  min-height: 0;
}

.side-tree {
  background-color: #f8f9fa;
  min-height: 200px;
}

.instructions {
  font-size: 0.85rem;
}

.drag-instructions {
  font-size: 0.8rem;
}
</style>
