<template>
  <div
    id="updateFrameworkModal"
    ref="modalElement"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="updateFrameworkModalLabel"
    aria-hidden="true"
  >
    <div
      class="modal-dialog modal-lg"
      role="document"
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="updateFrameworkModalLabel"
            class="modal-title"
          >
            Update Framework
          </h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          />
        </div>
        <div class="modal-body">
          <!-- Error Message -->
          <div
            v-if="errorMessage"
            class="alert alert-danger"
            role="alert"
          >
            {{ errorMessage }}
          </div>

          <!-- Success Message -->
          <div
            v-if="successMessage"
            class="alert alert-success"
            role="alert"
          >
            <strong>Success!</strong> {{ successMessage }}
          </div>

          <!-- Tabs -->
          <ul
            class="nav nav-tabs"
            role="tablist"
          >
            <li class="nav-item">
              <button
                class="nav-link active"
                data-bs-toggle="tab"
                data-bs-target="#ufExcel"
                type="button"
                role="tab"
              >
                Import Spreadsheet File
              </button>
            </li>
          </ul>
          <br>

          <!-- Tab Content -->
          <div
            v-show="!isLoading"
            class="tab-content"
          >
            <div
              id="ufExcel"
              class="tab-pane fade show active"
            >
              <div class="mb-3">
                <label
                  for="updateFrameworkFile"
                  class="form-label"
                >
                  Select a spreadsheet file to update this framework
                </label>
                <input
                  id="updateFrameworkFile"
                  ref="fileInput"
                  type="file"
                  class="form-control"
                  accept=".xls,.xlsx,.json,.csv"
                  @change="onFileSelected"
                >
                <input
                  id="excel-url"
                  type="hidden"
                >
              </div>
              <div class="form-text text-muted mb-3">
                Accepted formats: .xls, .xlsx, .json, .csv
              </div>
              <button
                type="button"
                class="btn btn-primary"
                :disabled="!selectedFile || isLoading"
                @click="importFramework"
              >
                <span
                  v-if="isLoading"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                />
                Import Framework
              </button>
            </div>
          </div>

          <!-- Loading Spinner -->
          <div
            v-if="isLoading"
            class="text-center py-4"
          >
            <div
              class="spinner-border text-primary"
              role="status"
            >
              <span class="visually-hidden">Loading file...</span>
            </div>
            <p class="mt-2 text-muted">
              Importing framework, please wait...
            </p>
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import { logger } from '../../../utils/logger.js';

const props = defineProps({
  show: Boolean
});

const emit = defineEmits(['imported', 'hidden']);

const modalElement = ref(null);
const fileInput = ref(null);
const selectedFile = ref(null);
const isLoading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
let bsModal = null;

const ALLOWED_EXTENSIONS = ['xls', 'xlsx', 'json', 'csv'];

onMounted(() => {
  if (modalElement.value) {
    bsModal = new Modal(modalElement.value);
    modalElement.value.addEventListener('hidden.bs.modal', () => {
      resetForm();
      emit('hidden');
    });
  }
});

watch(() => props.show, (newVal) => {
  if (bsModal) {
    if (newVal) {
      resetForm();
      bsModal.show();
    } else {
      bsModal.hide();
    }
  }
});

function resetForm() {
  selectedFile.value = null;
  isLoading.value = false;
  errorMessage.value = '';
  successMessage.value = '';
  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

function onFileSelected(event) {
  const file = event.target.files?.[0];
  if (file) {
    if (isFileTypeValid(file.name)) {
      selectedFile.value = file;
      errorMessage.value = '';
    } else {
      selectedFile.value = null;
      errorMessage.value = 'File type not allowed. Please select a .xls, .xlsx, .json, or .csv file.';
    }
  }
}

function isFileTypeValid(filename) {
  const ext = filename.split('.').pop()?.toLowerCase();
  return ext && ALLOWED_EXTENSIONS.includes(ext);
}

async function importFramework() {
  if (!selectedFile.value) return;

  isLoading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const formData = new FormData();
    formData.append('file', selectedFile.value);

    const response = await fetch('/salt/excel/import', {
      method: 'POST',
      body: formData
    });

    if (!response.ok) {
      throw new Error(`Import failed with status ${response.status}`);
    }

    successMessage.value = 'Framework imported successfully. The page will reload shortly.';
    emit('imported');

    // Reload the page after a brief delay so the user can see the success message
    setTimeout(() => {
      window.location.reload();
    }, 1500);
  } catch (error) {
    logger.error('Error importing framework:', error);
    errorMessage.value =
      "We're sorry, we cannot load this document. Please ensure this document is not already on the server, or see the Spreadsheet loading guide at docs.opensalt.org";
    isLoading.value = false;
  }
}
</script>

<style scoped>
.modal-dialog {
  max-width: 700px;
}
</style>
