<template>
  <div
    id="jsonViewerModal"
    ref="modalElement"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="jsonViewerModalLabel"
    aria-hidden="true"
  >
    <div
      class="modal-dialog modal-lg"
      role="document"
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="jsonViewerModalLabel"
            class="modal-title"
          >
            <i
              class="bi bi-code-slash me-1"
              aria-hidden="true"
            />
            {{ title }}
          </h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          />
        </div>
        <div class="modal-body">
          <div
            v-if="loading"
            class="text-center py-4"
          >
            <div
              class="spinner-border text-primary"
              role="status"
            >
              <span class="visually-hidden">Loading JSON...</span>
            </div>
          </div>
          <div
            v-else-if="error"
            class="alert alert-danger mb-0"
          >
            <i
              class="bi bi-exclamation-triangle me-1"
              aria-hidden="true"
            />
            {{ error }}
          </div>
          <pre
            v-else
            class="mb-0 p-3"
            style="max-height: 70vh; overflow-y: auto; background-color: #f8f9fa; border-radius: 0.375rem;"
          >{{ formattedJson }}</pre>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import { api } from '../../../services/api.js';
import { logger } from '../../../utils/logger.js';

const props = defineProps({
  show: Boolean,
  jsonType: {
    type: String,
    default: null,
    validator: (v) => v === null || v === 'document' || v === 'item' || v === 'association'
  },
  jsonIdentifier: {
    type: String,
    default: null
  }
});

const emit = defineEmits(['hidden']);

const modalElement = ref(null);
let bsModal = null;

const loading = ref(false);
const error = ref('');
const jsonData = ref(null);

const title = computed(() => {
  if (props.jsonType === 'document') return 'Document JSON';
  if (props.jsonType === 'item') return 'Item JSON';
  if (props.jsonType === 'association') return 'Association JSON';
  return 'JSON View';
});

const formattedJson = computed(() => {
  try {
    return JSON.stringify(jsonData.value, null, 2);
  } catch (e) {
    return 'Error stringifying object: ' + e.message;
  }
});

function buildApiUrl() {
  if (!props.jsonIdentifier) return null;
  if (props.jsonType === 'document') {
    return `/ims/case/v1p1/CFDocuments/${props.jsonIdentifier}`;
  }
  if (props.jsonType === 'item') {
    return `/ims/case/v1p1/CFItems/${props.jsonIdentifier}`;
  }
  if (props.jsonType === 'association') {
    return `/ims/case/v1p1/CFAssociations/${props.jsonIdentifier}`;
  }
  return null;
}

const STRIPPED_KEYS = ['CFPackageURI', 'CFDocumentURI'];

function stripSensitiveKeys(obj) {
  if (!obj || typeof obj !== 'object') return;
  if (Array.isArray(obj)) {
    obj.forEach(item => stripSensitiveKeys(item));
    return;
  }
  for (const key of STRIPPED_KEYS) {
    delete obj[key];
  }
  for (const value of Object.values(obj)) {
    if (value && typeof value === 'object') {
      stripSensitiveKeys(value);
    }
  }
}

async function fetchJson() {
  const url = buildApiUrl();
  if (!url) {
    error.value = 'No identifier provided.';
    return;
  }

  loading.value = true;
  error.value = '';
  jsonData.value = null;

  try {
    const raw = await api.get(url);
    if (raw && typeof raw === 'object') {
      stripSensitiveKeys(raw);
    }
    jsonData.value = raw;
  } catch (e) {
    logger.error('JsonViewerModal: failed to fetch JSON:', e);
    error.value = e.message || 'Failed to load JSON from the API.';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  if (modalElement.value) {
    bsModal = new Modal(modalElement.value);
    modalElement.value.addEventListener('hidden.bs.modal', () => {
      jsonData.value = null;
      error.value = '';
      loading.value = false;
      emit('hidden');
    });
  }
});

watch(() => props.show, (newVal) => {
  if (bsModal) {
    if (newVal) {
      fetchJson();
      bsModal.show();
    } else {
      bsModal.hide();
    }
  }
});
</script>

<style scoped>
</style>
