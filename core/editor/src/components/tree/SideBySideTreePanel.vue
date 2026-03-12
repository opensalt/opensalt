<template>
  <div class="side-by-side-panel h-100 d-flex flex-column">
    <!-- Mode Tabs -->
    <div class="mode-tabs mb-2">
      <div class="btn-group w-100" role="group" aria-label="Panel mode selection">
        <button
          type="button"
          class="btn btn-sm"
          :class="{ 'btn-primary': mode === 'itemDetails', 'btn-outline-primary': mode !== 'itemDetails' }"
          @click="$emit('mode-changed', 'itemDetails')"
        >
          <i class="bi bi-info-circle me-1"></i>
          Item Details
        </button>
        <button
          type="button"
          class="btn btn-sm"
          :class="{ 'btn-primary': mode === 'copyItems', 'btn-outline-primary': mode !== 'copyItems' }"
          @click="$emit('mode-changed', 'copyItems')"
        >
          <i class="bi bi-copy me-1"></i>
          Copy Items
        </button>
        <button
          type="button"
          class="btn btn-sm"
          :class="{ 'btn-primary': mode === 'createAssociations', 'btn-outline-primary': mode !== 'createAssociations' }"
          @click="$emit('mode-changed', 'createAssociations')"
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
          @viewed-document-changed="onDocumentChanged"
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
        <!-- Show spinner when loading, regardless of sideDocument state -->
        <div v-if="selectedDocumentId && loadingSideDoc" class="side-tree flex-grow-1 d-flex justify-content-center align-items-center border rounded p-2">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading document...</span>
          </div>
        </div>
        <!-- Show error if there's an error -->
        <div v-else-if="selectedDocumentId && sideDocError" class="side-tree flex-grow-1 d-flex align-items-center justify-content-center border rounded p-2">
          <div class="alert alert-danger py-2 w-100">
            {{ sideDocError }}
          </div>
        </div>
        <!-- Show tree when document is loaded AND the ID matches the selected document -->
        <!-- This prevents showing stale content when re-selecting a different document -->
        <div v-else-if="selectedDocumentId && sideDocument && sideDocument.id === selectedDocumentId" class="side-tree flex-grow-1 d-flex flex-column overflow-hidden border rounded p-2">
          <TreeView
            :doc="sideDocument"
            :selected-id="sideSelectedId"
            :disable-drop="true"
            @select="onSideSelect"
            @tree-change="$emit('tree-change', $event)"
          />
        </div>
        <!-- Show placeholder when no document is selected -->
        <div v-else-if="selectedDocumentId && !sideDocument" class="side-tree flex-grow-1 d-flex align-items-center justify-content-center border rounded">
          <div class="text-muted text-center">
            <i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>
            <span>Select a document to view</span>
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
import { ref, watch, computed, onMounted, inject } from 'vue';
import TreeView from './TreeView.vue';
import DocumentSelector from '../shared/common/DocumentSelector.vue';
import { logger } from '@/utils/logger.js';

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

// Debug: Log prop changes
watch(() => props.loadingSideDoc, (newVal) => {
  console.log('[SideBySideTreePanel] loadingSideDoc changed to:', newVal);
});

watch(() => props.sideDocument, (newVal) => {
  console.log('[SideBySideTreePanel] sideDocument changed to:', newVal?.id || null);
}, { immediate: true });

const emit = defineEmits([
  'mode-changed',
  'document-select',
  'external-document-requested',
  'side-select',
  'tree-change',
  'copy-item',
  'create-association'
]);

const selectedDocumentId = ref('');
const sideSelectedId = ref(null);

// Inject tree navigation context for expansion state management
const navigation = inject('treeNavigation', {
  expandItem: () => {},
  collapseItem: () => {},
  isItemExpanded: () => false
});

// Initialize selectedDocumentId from currentDocument when component mounts
onMounted(() => {
  if (props.currentDocument?.identifier && !selectedDocumentId.value) {
    selectedDocumentId.value = props.currentDocument.identifier;
    emit('document-select', props.currentDocument.identifier);
  }
});

// Watch for currentDocument changes and initialize selectedDocumentId when it becomes available
watch(() => props.currentDocument, (newDoc) => {
  if (newDoc?.identifier && !selectedDocumentId.value) {
    selectedDocumentId.value = newDoc.identifier;
    emit('document-select', newDoc.identifier);
  }
}, { immediate: false });

// Track current document for DocumentSelector
const currentDocForSelector = computed(() => {
  // If sideDocument is set, it's the current selected document for this panel
  if (selectedDocumentId.value && props.availableDocuments) {
    return props.availableDocuments.find(doc => doc.identifier === selectedDocumentId.value) || props.currentDocument;
  }
  return props.currentDocument;
});

function onDocumentChanged(event) {
  const { side, documentId } = event;
  console.log('[SideBySideTreePanel] onDocumentChanged called with documentId:', documentId);
  if (documentId) {
    selectedDocumentId.value = documentId;
    console.log('[SideBySideTreePanel] Emitting document-select event');
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

// Sync selectedDocumentId with sideDocument when it changes
// This ensures selectedDocumentId matches the loaded document's ID
watch(() => props.sideDocument, (newDoc) => {
  if (newDoc && newDoc.id) {
    selectedDocumentId.value = newDoc.id;
  }
});

// Watch for sideDocument changes to expand the tree one level
// This ensures the root document is expanded when a new document is loaded
watch(() => props.sideDocument?.id, (newDocId, oldDocId) => {
  if (newDocId && newDocId !== oldDocId) {
    // Expand the document root by default (one level expansion)
    navigation.expandItem(newDocId);
    logger.debug('[SideBySideTreePanel] Expanded side document root:', newDocId);
  }
}, { immediate: false });
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
  min-height: 0;
}

.side-tree :deep(.tree-view) {
  min-height: 0;
}

.instructions {
  font-size: 0.85rem;
}

.drag-instructions {
  font-size: 0.8rem;
}
</style>
