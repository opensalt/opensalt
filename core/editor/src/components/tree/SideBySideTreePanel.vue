<template>
  <div class="side-by-side-panel side-tree-panel h-100 d-flex flex-column">
    <!-- Mode Tabs -->
    <div class="mode-tabs mb-2">
      <div
        class="btn-group w-100"
        role="group"
        aria-label="Panel mode selection"
      >
        <button
          type="button"
          class="btn btn-sm"
          :class="{ 'btn-primary': mode === 'itemDetails', 'btn-outline-primary': mode !== 'itemDetails' }"
          @click="$emit('mode-changed', 'itemDetails')"
        >
          <i class="bi bi-info-circle me-1" />
          Item Details
        </button>
        <button
          type="button"
          class="btn btn-sm"
          :class="{ 'btn-primary': mode === 'externalDocument', 'btn-outline-primary': mode !== 'externalDocument' }"
          @click="$emit('mode-changed', 'externalDocument')"
        >
          <i class="bi bi-box-arrow-in-right me-1" />
          Copy / Associate
        </button>
      </div>
    </div>

    <!-- Content Area -->
    <div class="panel-content flex-grow-1 overflow-hidden">
      <!-- Item Details Mode -->
      <slot
        v-if="mode === 'itemDetails'"
        name="item-details"
      />

      <!-- Copy Items or Create Associations Mode -->
      <div
        v-else
        class="side-tree-container h-100 d-flex flex-column"
      >
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
        <div
          v-if="!selectedDocumentId"
          class="instructions alert alert-info py-2 mb-2"
        >
          <small>
            <i class="bi bi-info-circle me-1" />
            <span>
              Select an external document above to view and act on its items.
            </span>
          </small>
        </div>

        <!-- Action Bar -->
        <div
          v-if="sideDocument"
          class="action-bar mb-2 p-2 border rounded bg-light d-flex flex-column align-items-center"
        >
          <small class="text-muted mb-2">
            <span v-if="!sideSelectedId">Select an item below to act on it.</span>
            <span v-else>Item selected. Switch to main tree to select target.</span>
          </small>
          <div class="d-flex gap-2 ms-auto">
            <button
              type="button"
              class="btn btn-outline-primary"
              :disabled="!sideSelectedId"
              @click="$emit('action', { type: 'associate', itemId: sideSelectedId })"
            >
              <i class="bi bi-link-45deg" /> Associate
            </button>
            <div class="btn-group">
              <button
                id="copyDropdownBtnSide"
                type="button"
                class="btn btn-outline-primary dropdown-toggle"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                :disabled="!sideSelectedId"
              >
                <i class="bi bi-copy" /> Copy...
              </button>
              <ul
                class="dropdown-menu shadow-sm"
                aria-labelledby="copyDropdownBtnSide"
              >
                <li>
                  <button
                    class="dropdown-item py-2"
                    @click="$emit('action', { type: 'copy', position: 'before', itemId: sideSelectedId })"
                  >
                    <i class="bi bi-arrow-bar-up text-muted me-2" /> Before Target
                  </button>
                </li>
                <li>
                  <button
                    class="dropdown-item py-2"
                    @click="$emit('action', { type: 'copy', position: 'after', itemId: sideSelectedId })"
                  >
                    <i class="bi bi-arrow-bar-down text-muted me-2" /> After Target
                  </button>
                </li>
                <li>
                  <button
                    class="dropdown-item py-2"
                    @click="$emit('action', { type: 'copy', position: 'inside', itemId: sideSelectedId })"
                  >
                    <i class="bi bi-arrow-bar-right text-muted me-2" /> As Child
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Side Tree -->
        <!-- Show spinner when loading, regardless of sideDocument state -->
        <div
          v-if="selectedDocumentId && loadingSideDoc"
          class="side-tree flex-grow-1 d-flex justify-content-center align-items-center border rounded p-2"
        >
          <div
            class="spinner-border text-primary"
            role="status"
          >
            <span class="visually-hidden">Loading document...</span>
          </div>
        </div>
        <!-- Show error if there's an error -->
        <div
          v-else-if="selectedDocumentId && sideDocError"
          class="side-tree flex-grow-1 d-flex align-items-center justify-content-center border rounded p-2"
        >
          <div class="alert alert-danger py-2 w-100">
            {{ sideDocError }}
          </div>
        </div>
        <!-- Show tree when document is loaded AND the ID matches the selected document -->
        <!-- This prevents showing stale content when re-selecting a different document -->
        <div
          v-else-if="selectedDocumentId && sideDocument && sideDocument.id === selectedDocumentId"
          class="side-tree flex-grow-1 d-flex flex-column overflow-hidden border rounded p-2"
        >
          <TreeView
            :doc="sideDocument"
            :selected-id="sideSelectedId"
            :disable-drop="true"
            :disable-drag="true"
            @select="onSideSelect"
            @tree-change="$emit('tree-change', $event)"
          />
        </div>
        <!-- Show placeholder when no document is selected -->
        <div
          v-else-if="selectedDocumentId && !sideDocument"
          class="side-tree flex-grow-1 d-flex align-items-center justify-content-center border rounded"
        >
          <div class="text-muted text-center">
            <i class="bi bi-file-earmark-text fs-1 d-block mb-2" />
            <span>Select a document to view</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, inject } from 'vue';
import TreeView from './TreeView.vue';
import DocumentSelector from '../shared/common/DocumentSelector.vue';
import { logger } from '@/utils/logger.js';
import { useEditorContextStore } from '@/stores/editorContextStore';
import { useSideTreePanel } from '../../composables/useSideTreePanel';

const props = defineProps({
  mode: {
    type: String,
    default: 'itemDetails', // 'itemDetails', 'externalDocument'
    validator: (value) => ['itemDetails', 'externalDocument'].includes(value)
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
  'mode-changed',
  'document-select',
  'external-document-requested',
  'side-select',
  'tree-change',
  'action'
]);

const editorContextStore = useEditorContextStore();
const { selectedDocumentId, currentDocForSelector, onDocumentSelected } = useSideTreePanel(props);
const sideSelectedId = ref(null);

// Inject tree navigation context for expansion state management
const navigation = inject('treeNavigation', {
  expandItem: () => {},
  collapseItem: () => {},
  isItemExpanded: () => false
});

function onDocumentChanged(event) {
  const { side: _side, documentId } = event;
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
