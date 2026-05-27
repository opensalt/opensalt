<template>
  <div
    id="loadExternalDocumentModal"
    ref="modalElement"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="loadExternalDocumentModalLabel"
    aria-hidden="true"
  >
    <div
      class="modal-dialog"
      role="document"
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="loadExternalDocumentModalLabel"
            class="modal-title"
          >
            Load External Document
          </h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
            @click="hide"
          />
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label
              for="externalDocumentUrl"
              class="form-label"
            >Document URL</label>
            <input
              id="externalDocumentUrl"
              v-model="externalUrl"
              type="url"
              class="form-control"
              placeholder="https://example.com/api/document.json"
              :aria-invalid="urlError ? 'true' : undefined"
              :aria-describedby="urlDescribedBy"
              @keyup.enter="loadDocument"
            >
            <div
              id="help-externalDocumentUrl"
              class="form-text"
            >
              Enter the URL of a CASE document to load
            </div>
            <div
              v-if="urlError"
              id="error-externalDocumentUrl"
              class="invalid-feedback d-block"
              role="alert"
            >
              {{ urlError }}
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal"
            @click="hide"
          >
            Cancel
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="loadDocument"
          >
            Load Document
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import Modal from 'bootstrap/js/dist/modal';

const props = defineProps({
  show: Boolean
});

const emit = defineEmits(['load', 'hidden']);

const modalElement = ref(null);
const externalUrl = ref('');
const urlError = ref('');
let bsModal = null;

const urlDescribedBy = computed(() => {
  const ids = ['help-externalDocumentUrl'];
  if (urlError.value) ids.push('error-externalDocumentUrl');
  return ids.join(' ');
});

onMounted(() => {
  if (modalElement.value) {
    bsModal = new Modal(modalElement.value);
    modalElement.value.addEventListener('hidden.bs.modal', () => {
      emit('hidden');
      externalUrl.value = '';
    });
  }
});

// Watch for show prop changes
watch(() => props.show, (newValue) => {
  if (bsModal) {
    if (newValue) {
      bsModal.show();
    } else {
      bsModal.hide();
    }
  }
});

function hide() {
    emit('hidden');
}

function loadDocument() {
  urlError.value = '';

  if (!externalUrl.value || !externalUrl.value.trim()) {
    urlError.value = 'Please enter a URL to load a CASE document.';
    return;
  }

  emit('load', externalUrl.value);
  if(bsModal) bsModal.hide();
}
</script>
