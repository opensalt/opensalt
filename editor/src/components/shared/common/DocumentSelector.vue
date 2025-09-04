<template>
  <div class="document-selector">
    <div class="row">
      <div class="col-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Left Tree Document</h6>
            <button
              type="button"
              class="btn btn-sm btn-outline-primary"
              @click="changeDocument(1)"
              title="Change document"
            >
              <i class="bi bi-arrow-repeat"></i>
            </button>
          </div>
          <div class="card-body">
            <select
              class="form-select"
              v-model="selectedDoc1"
              @change="onDocumentChange(1)"
            >
              <option value="">Select a document...</option>
              <optgroup
                v-for="group in groupedDocuments"
                :key="group.creator"
                :label="group.creator"
              >
                <option
                  v-for="doc in group.documents"
                  :key="doc.id"
                  :value="doc.id"
                  :selected="doc.id === currentDoc1?.id"
                >
                  {{ doc.title }} ({{ doc.id }})
                </option>
              </optgroup>
              <optgroup label="External Documents">
                <option value="external">Load external document...</option>
              </optgroup>
            </select>
            <div v-if="currentDoc1" class="mt-2">
              <small class="text-muted">
                <strong>Status:</strong> {{ currentDoc1.status || 'Draft' }}<br>
                <strong>Items:</strong> {{ currentDoc1.itemCount || 0 }}
              </small>
            </div>
          </div>
        </div>
      </div>

      <div class="col-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Right Tree Document</h6>
            <button
              type="button"
              class="btn btn-sm btn-outline-primary"
              @click="changeDocument(2)"
              title="Change document"
            >
              <i class="bi bi-arrow-repeat"></i>
            </button>
          </div>
          <div class="card-body">
            <select
              class="form-select"
              v-model="selectedDoc2"
              @change="onDocumentChange(2)"
            >
              <option value="">Select a document...</option>
              <optgroup
                v-for="group in groupedDocuments"
                :key="group.creator"
                :label="group.creator"
              >
                <option
                  v-for="doc in group.documents"
                  :key="doc.id"
                  :value="doc.id"
                  :selected="doc.id === currentDoc2?.id"
                >
                  {{ doc.title }} ({{ doc.id }})
                </option>
              </optgroup>
              <optgroup label="External Documents">
                <option value="external">Load external document...</option>
              </optgroup>
            </select>
            <div v-if="currentDoc2" class="mt-2">
              <small class="text-muted">
                <strong>Status:</strong> {{ currentDoc2.status || 'Draft' }}<br>
                <strong>Items:</strong> {{ currentDoc2.itemCount || 0 }}
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- External Document Modal -->
    <div class="modal fade" id="loadExternalDocumentModal" tabindex="-1" role="dialog" aria-labelledby="loadExternalDocumentModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="loadExternalDocumentModalLabel">Load External Document</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label for="externalDocumentUrl" class="form-label">Document URL</label>
              <input
                type="url"
                id="externalDocumentUrl"
                class="form-control"
                v-model="externalUrl"
                placeholder="https://example.com/api/document.json"
              >
              <div class="form-text">
                Enter the URL of a CASE document to load
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="loadExternalDocument" :disabled="!externalUrl">
              Load Document
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Modal } from 'bootstrap';
import { useFrameworkStore } from '../../../stores/frameworkStore';

const props = defineProps({
  currentDoc1: Object,
  currentDoc2: Object,
  availableDocuments: Array
});

// Use the framework store
const frameworkStore = useFrameworkStore();

// Get grouped documents
const groupedDocuments = computed(() => frameworkStore.documentsGroupedByCreator);

const emit = defineEmits(['document-changed', 'external-document-requested']);

const selectedDoc1 = ref('');
const selectedDoc2 = ref('');
const externalUrl = ref('');
const externalModal = ref(null);

watch(() => props.currentDoc1, (newDoc) => {
  if (newDoc) {
    selectedDoc1.value = newDoc.id;
  }
}, { immediate: true });

watch(() => props.currentDoc2, (newDoc) => {
  if (newDoc) {
    selectedDoc2.value = newDoc.id;
  }
}, { immediate: true });

function onDocumentChange(side) {
  const selectedValue = side === 1 ? selectedDoc1.value : selectedDoc2.value;

  if (selectedValue === 'external') {
    // Show external document modal
    if (!externalModal.value) {
      externalModal.value = new Modal(document.getElementById('loadExternalDocumentModal'));
    }
    externalModal.value.show();
    // Reset selection
    if (side === 1) {
      selectedDoc1.value = props.currentDoc1?.id || '';
    } else {
      selectedDoc2.value = props.currentDoc2?.id || '';
    }
  } else if (selectedValue) {
    // Load selected document
    emit('document-changed', {
      side,
      documentId: selectedValue
    });
  }
}

function changeDocument(side) {
  // Reset selection to trigger change
  if (side === 1) {
    selectedDoc1.value = '';
  } else {
    selectedDoc2.value = '';
  }
}

function loadExternalDocument() {
  if (externalUrl.value) {
    emit('external-document-requested', {
      url: externalUrl.value
    });
    externalUrl.value = '';
    if (externalModal.value) {
      externalModal.value.hide();
    }
  }
}

// Handle modal hidden event
document.addEventListener('hidden.bs.modal', (event) => {
  if (event.target.id === 'loadExternalDocumentModal') {
    externalUrl.value = '';
  }
});
</script>

<style scoped>
.document-selector {
  margin-bottom: 1rem;
}

.card-header {
  padding: 0.5rem 1rem;
}

.card-body {
  padding: 1rem;
}

.form-select {
  font-size: 0.875rem;
}

.btn-outline-primary {
  border-color: #0d6efd;
  color: #0d6efd;
}

.btn-outline-primary:hover {
  background-color: #0d6efd;
  border-color: #0d6efd;
}
</style>
