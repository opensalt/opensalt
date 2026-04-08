<template>
  <div class="modal fade" id="importChildrenModal" tabindex="-1" role="dialog"
       aria-labelledby="importChildrenModalLabel" aria-hidden="true" ref="modalElement">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="importChildrenModalLabel">Import Items</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">
            Note: The CSV importer does not import all CASE fields but is intended as a simple
            statement importer. Questions about fields?
            <a href="http://docs.opensalt.org/en/latest/#h5777746416576973633711c4a42414c"
               rel="noopener noreferrer" target="_blank">See this guide</a>.
          </p>

          <!-- Error Messages -->
          <div v-if="errorMessage" class="alert alert-danger" role="alert">
            {{ errorMessage }}
          </div>

          <!-- Success Message -->
          <div v-if="successMessage" class="alert alert-success" role="alert">
            <strong>Success!</strong> {{ successMessage }}
          </div>

          <!-- Missing Fields Warnings -->
          <div v-for="(warning, index) in missingFieldWarnings" :key="index"
               class="alert alert-warning alert-dismissible" role="alert">
            <button type="button" class="btn-close" @click="missingFieldWarnings.splice(index, 1)"
                    aria-label="close"></button>
            <strong>Missing field "{{ warning }}"</strong>, if you did not list a column
            {{ warning.toLowerCase() }} in your CSV ignore this message! If you meant to,
            please take a look at the import template and try again!
          </div>

          <!-- Import Form -->
          <div v-show="!isLoading" id="importChildrenForm">
            <ul class="nav nav-tabs" role="tablist">
              <li class="nav-item">
                <a href="#icLocalFile" class="nav-link active" data-bs-toggle="tab">
                  Import local file
                </a>
              </li>
            </ul>
            <br />

            <div class="tab-content">
              <div class="tab-pane fade show active" id="icLocalFile">
                <div class="row align-items-end">
                  <div class="col-5">
                    <label for="importChildrenFile" class="form-label">
                      Select a CSV or JSON file
                    </label>
                    <input
                      id="importChildrenFile"
                      type="file"
                      class="form-control"
                      accept=".csv,.json"
                      ref="fileInput"
                      @change="onFileSelected"
                    />
                  </div>
                  <div class="col-3 text-end" style="line-height: 34px;">
                    <label for="importChildrenAssocFramework">Framework to be associated</label>
                  </div>
                  <div class="col-4">
                    <select id="importChildrenAssocFramework"
                            class="form-select" v-model="selectedAssociationFramework">
                      <option value="all">All</option>
                      <optgroup v-for="group in groupedDocuments" :key="group.creator"
                                :label="group.creator || 'No Creator'">
                        <option v-for="doc in group.docs" :key="doc.id" :value="doc.id">
                          {{ doc.title }}
                        </option>
                      </optgroup>
                    </select>
                  </div>
                </div>
                <button
                  type="button"
                  class="btn btn-primary mt-3"
                  :disabled="!selectedFile || isLoading"
                  @click="importChildren"
                >
                  <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                  Import Children
                </button>
              </div>
            </div>
          </div>

          <!-- Loading Spinner -->
          <div v-if="isLoading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading file...</span>
            </div>
            <p class="mt-2 text-muted">Importing items, please wait...</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import { useDocumentStore } from '../../../stores/documentStore';
import { useRoute } from 'vue-router';
import { logger } from '../../../utils/logger.js';

const props = defineProps({
  show: Boolean
});

const emit = defineEmits(['imported', 'hidden']);

const route = useRoute();
const documentStore = useDocumentStore();

const modalElement = ref(null);
const fileInput = ref(null);
const selectedFile = ref(null);
const selectedAssociationFramework = ref('all');
const isLoading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const missingFieldWarnings = ref([]);
let bsModal = null;

const ALLOWED_EXTENSIONS = ['csv', 'json'];

/**
 * Known CASE/CF item fields for CSV column matching
 */
const CF_ITEM_FIELDS = [
  'identifier',
  'fullStatement',
  'humanCodingScheme',
  'abbreviatedStatement',
  'conceptKeywords',
  'notes',
  'language',
  'educationLevel',
  'cfItemType',
  'license',
  'isChildOf',
  'isPartOf',
  'replacedBy',
  'exemplar',
  'precedes',
  'isPeerOf',
  'hasSkillLevel',
  'isRelatedTo',
  'sequenceNumber'
];

/**
 * Group available documents by creator for the dropdown
 */
const groupedDocuments = computed(() => {
  const docs = documentStore.documents || [];
  const groups = {};

  docs.forEach(doc => {
    const creator = doc.creator || 'No Creator';
    if (!groups[creator]) {
      groups[creator] = { creator, docs: [] };
    }
    groups[creator].docs.push(doc);
  });

  return Object.values(groups);
});

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
  selectedAssociationFramework.value = 'all';
  isLoading.value = false;
  errorMessage.value = '';
  successMessage.value = '';
  missingFieldWarnings.value = [];
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
      errorMessage.value = 'File type not allowed. Please select a .csv or .json file.';
    }
  }
}

function isFileTypeValid(filename) {
  const ext = filename.split('.').pop()?.toLowerCase();
  return ext && ALLOWED_EXTENSIONS.includes(ext);
}

/**
 * Simplify a string for column name matching (lowercase, remove non-alphanumeric)
 */
function simplify(str) {
  return str.toLowerCase().replace(/[^a-z0-9]/g, '');
}

/**
 * Parse CSV content and match columns to known CF item fields.
 * Returns an object with matched keys and any remaining (missing) fields.
 */
function matchCsvColumns(csvContent) {
  // Simple CSV header row extraction
  const firstNewline = csvContent.indexOf('\n');
  const headerLine = firstNewline > -1 ? csvContent.substring(0, firstNewline) : csvContent;

  // Parse the header row (handle quoted values)
  const columns = parseCSVRow(headerLine);
  const cfItemKeys = {};
  const remainingFields = [...CF_ITEM_FIELDS];

  for (let i = remainingFields.length - 1; i >= 0; i--) {
    const field = remainingFields[i];
    for (let j = 0; j < columns.length; j++) {
      const column = columns[j].trim();
      if (column.length > 0 && simplify(field) === simplify(column)) {
        cfItemKeys[field] = column.replace(/"/g, '');
        remainingFields.splice(i, 1);
        break;
      }
    }
  }

  return { cfItemKeys, missingFields: remainingFields };
}

/**
 * Parse a single CSV row, handling basic quoting
 */
function parseCSVRow(row) {
  const result = [];
  let current = '';
  let inQuotes = false;

  for (let i = 0; i < row.length; i++) {
    const ch = row[i];
    if (ch === '"') {
      inQuotes = !inQuotes;
    } else if (ch === ',' && !inQuotes) {
      result.push(current);
      current = '';
    } else {
      current += ch;
    }
  }
  result.push(current);
  return result;
}

/**
 * Encode string content to base64, handling Unicode properly
 */
function encodeBase64(content) {
  return btoa(
    encodeURIComponent(content).replace(/%([0-9A-F]{2})/g,
      function toSolidBytes(match, p1) {
        return String.fromCharCode('0x' + p1);
      }
    )
  );
}

/**
 * Get the current document's lsDocId from route params or a hidden input
 */
function getLsDocId() {
  // The Vue app uses route params to identify the framework
  return route.params.frameworkId || null;
}

async function importChildren() {
  if (!selectedFile.value) return;

  isLoading.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  missingFieldWarnings.value = [];

  try {
    const fileContent = await readFileAsText(selectedFile.value);
    const filename = selectedFile.value.name.toLowerCase();

    if (filename.endsWith('.csv')) {
      await importCsvFile(fileContent);
    } else if (filename.endsWith('.json')) {
      // For JSON files, just send as-is
      await importCsvFile(fileContent);
    }
  } catch (error) {
    logger.error('Error importing children:', error);
    errorMessage.value = 'An error occurred while importing. Please check your file and try again.';
    isLoading.value = false;
  }
}

function readFileAsText(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => resolve(e.target.result);
    reader.onerror = (e) => reject(e);
    reader.readAsText(file);
  });
}

async function importCsvFile(fileContent) {
  const { cfItemKeys, missingFields } = matchCsvColumns(fileContent);

  // Show missing field warnings (non-blocking)
  missingFields.forEach(field => {
    // Titleize: convert camelCase to Title Case
    const titleized = field.replace(/([A-Z])/g, ' $1').replace(/^./, s => s.toUpperCase());
    missingFieldWarnings.value.push(titleized);
  });

  // Check if humanCodingScheme was found (required for tree structure)
  const hasHumanCodingScheme = !missingFields.includes('humanCodingScheme');

  if (!hasHumanCodingScheme) {
    // Still try to import even without humanCodingScheme, the backend may handle it
  }

  const requestData = {
    content: encodeBase64(fileContent),
    cfItemKeys: cfItemKeys,
    lsDocId: getLsDocId(),
    frameworkToAssociate: selectedAssociationFramework.value,
    missingFieldsLog: missingFieldWarnings.value
  };

  try {
    const response = await fetch('/cf/github/import', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(requestData)
    });

    if (!response.ok) {
      throw new Error(`Import failed with status ${response.status}`);
    }

    successMessage.value = 'Items imported successfully. The page will reload shortly.';
    emit('imported');

    // Reload the page after a brief delay
    setTimeout(() => {
      window.location.reload();
    }, 1500);
  } catch (error) {
    logger.error('Error sending import request:', error);
    errorMessage.value = 'An error occurred while importing. Please check your file format and try again.';
    isLoading.value = false;
  }
}
</script>

<style scoped>
.modal-dialog {
  max-width: 800px;
}
</style>
