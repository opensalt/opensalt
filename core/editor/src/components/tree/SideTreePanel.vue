<template>
  <div class="side-tree-panel h-100 d-flex flex-column">
    <!-- Document Selector -->
    <DocumentSelector
      :current-doc="currentDocForSelector"
      :available-documents="availableDocuments"
      :label="'External Document'"
      side="right"
      @viewed-document-changed="onDocumentChanged"
      @external-document-requested="onExternalDocumentRequested"
    />

    <!-- Instructions -->
    <div v-if="!selectedDocumentId" class="instructions alert alert-info py-2 mb-2">
      <small>
        <i class="bi bi-info-circle me-1"></i>
        <span>
          Select a document to act on its items.
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

    <!-- Action Bar -->
    <div v-if="sideDocument" class="mt-2 p-2 border rounded bg-light d-flex flex-column align-items-center">
      <small class="text-muted mb-2">
        <span v-if="!sideSelectedId">Select an item above to act on it.</span>
        <span v-else>Item selected. Switch to main tree to select target.</span>
      </small>
      <div class="btn-group w-100" role="group">
        <button
          class="btn btn-outline-primary btn-sm"
          :disabled="!sideSelectedId"
          @click="emit('action', { type: 'associate', itemId: sideSelectedId })"
        >
          <i class="bi bi-link-45deg"></i> Associate
        </button>
        <div class="btn-group w-100" role="group">
          <button
            id="copyDropdownBtn"
            type="button"
            class="btn btn-outline-primary btn-sm dropdown-toggle"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            :disabled="!sideSelectedId"
          >
            <i class="bi bi-copy"></i> Copy...
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="copyDropdownBtn">
            <li>
              <button class="dropdown-item py-2" @click="emit('action', { type: 'copy', position: 'before', itemId: sideSelectedId })">
                <i class="bi bi-arrow-bar-up text-muted me-2"></i> Before Target
              </button>
            </li>
            <li>
              <button class="dropdown-item py-2" @click="emit('action', { type: 'copy', position: 'after', itemId: sideSelectedId })">
                <i class="bi bi-arrow-bar-down text-muted me-2"></i> After Target
              </button>
            </li>
            <li>
              <button class="dropdown-item py-2" @click="emit('action', { type: 'copy', position: 'inside', itemId: sideSelectedId })">
                <i class="bi bi-arrow-bar-right text-muted me-2"></i> As Child
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import TreeView from './TreeView.vue';
import DocumentSelector from '../shared/common/DocumentSelector.vue';
import { useEditorContextStore } from '../../stores/editorContextStore';
import { useSideTreePanel } from '../../composables/useSideTreePanel';

const editorContextStore = useEditorContextStore();

const props = defineProps({
  mode: {
    type: String,
    default: 'externalDocument'
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
  'side-select',
  'action'
]);

const { selectedDocumentId, currentDocForSelector, onDocumentSelected } = useSideTreePanel(props);
const sideSelectedId = ref(null);

function onDocumentChanged(event) {
  const { documentId } = event;
  if (documentId) {
    onDocumentSelected(documentId);
    emit('document-select', documentId);

    // Save framework selection to centralized state
    if (props.mode === 'externalDocument') {
      editorContextStore.setFrameworkSelection(props.mode, documentId);
    }
  }
}

function onExternalDocumentRequested() {
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
