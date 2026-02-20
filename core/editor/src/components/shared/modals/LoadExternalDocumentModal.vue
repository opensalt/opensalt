<template>
  <div class="modal fade" id="loadExternalDocumentModal" tabindex="-1" role="dialog" aria-labelledby="loadExternalDocumentModalLabel" aria-hidden="true" ref="modalElement">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="loadExternalDocumentModalLabel">Load External Document</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="hide"></button>
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
              @keyup.enter="loadDocument"
            >
            <div class="form-text">
              Enter the URL of a CASE document to load
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="hide">Cancel</button>
          <button type="button" class="btn btn-primary" @click="loadDocument" :disabled="!externalUrl">
            Load Document
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Modal } from 'bootstrap';

const props = defineProps({
  show: Boolean
});

const emit = defineEmits(['load', 'hidden']);

const modalElement = ref(null);
const externalUrl = ref('');
let bsModal = null;

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
import { watch } from 'vue';
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
  if (externalUrl.value) {
    emit('load', externalUrl.value);
    // Modal will be closed by parent changing 'show' prop or we can hide it here
    // Usually better to let parent control state, but for UX 'instant' feedback:
    if(bsModal) bsModal.hide();
  }
}
</script>
