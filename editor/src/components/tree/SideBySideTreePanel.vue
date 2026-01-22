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
                {{ doc.title }}{{ doc.id === currentDocumentId ? ' (Current Document)' : '' }}
              </option>
              <optgroup label="External Documents">
                <option value="__external__">Load external document by URL...</option>
              </optgroup>
            </select>
            <button
              class="btn btn-outline-secondary"
              type="button"
              @click="onChangeDocument"
              title="Change document"
            >
              <i class="bi bi-arrow-repeat"></i>
            </button>
          </div>
        </div>

        <!-- Instructions -->
        <div v-if="!selectedDocumentId" class="instructions alert alert-info py-2 mb-2">
          <small>
            <i class="bi bi-info-circle me-1"></i>
            <span v-if="mode === 'copyItems'">
              Select a document above to copy items from it to the current document.
            </span>
            <span v-else>
              Select a document above to create associations between its items and items in the current document.
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
import { ref, computed, watch } from 'vue';
import TreeView from './TreeView.vue';

const props = defineProps({
  mode: {
    type: String,
    default: 'itemDetails', // 'itemDetails', 'copyItems', 'createAssociations'
    validator: (value) => ['itemDetails', 'copyItems', 'createAssociations'].includes(value)
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

function onDocumentChange(event) {
  const value = event.target.value;
  if (value === '__external__') {
    emit('external-document-requested');
    selectedDocumentId.value = '';
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

.document-selector select {
  flex: 1;
}
</style>
