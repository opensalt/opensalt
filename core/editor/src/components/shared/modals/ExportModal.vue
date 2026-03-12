<template>
  <div class="modal fade" id="exportDocumentModal" tabindex="-1" role="dialog"
       aria-labelledby="exportDocumentModalLabel" aria-hidden="true" ref="modalElement">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exportDocumentModalLabel">Export Document</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>
            Export Competency Framework "<b>{{ docTitle }}</b>" as:
          </p>

          <div class="container-fluid my-3">
            <div class="row g-3">
              <!-- JSON Export -->
              <div class="col-sm-4">
                <a
                  :href="jsonExportUrl"
                  role="button"
                  class="btn btn-primary w-100 btn-export"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <i class="bi bi-filetype-json d-block mb-1" style="font-size: 1.5em;"></i>
                  Competency Framework Package (JSON)
                </a>
                <div class="form-text text-muted mt-1 small">
                  Exports a JSON file using the IMS-standard format.
                  Best for archiving Frameworks.
                </div>
              </div>

              <!-- Excel/Spreadsheet Export -->
              <div class="col-sm-4">
                <a
                  :href="excelExportUrl"
                  role="button"
                  class="btn btn-primary w-100 btn-export"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <i class="bi bi-file-earmark-spreadsheet d-block mb-1" style="font-size: 1.5em;"></i>
                  Spreadsheet export
                </a>
                <div class="form-text text-muted mt-1 small">
                  Exports an Excel spreadsheet file that you can open
                  in spreadsheet programs such as Microsoft Excel.
                </div>
              </div>

              <!-- CSV Export (hidden placeholder for future) -->
              <div class="col-sm-4 d-none">
                <button class="btn btn-primary w-100 btn-export disabled" disabled>
                  <i class="bi bi-filetype-html d-block mb-1" style="font-size: 1.5em;"></i>
                  HTML Archive
                </button>
                <div class="form-text text-muted mt-1 small">
                  HTML export functionality coming soon...
                </div>
              </div>
            </div>
          </div>

          <hr />

          <p>
            You can also share the following link, which anyone can use to view the
            Competency Framework in their web browser (no login required):
          </p>

          <div class="row align-items-center g-2">
            <div class="col-sm-6">
              <a :href="viewUrl" target="_blank" rel="noopener noreferrer">{{ viewUrl }}</a>
            </div>
            <div class="col-sm-5">
              <div class="input-group">
                <input
                  type="text"
                  class="form-control form-control-sm"
                  :value="viewUrl"
                  readonly
                  ref="viewUrlInput"
                />
                <button class="btn btn-outline-secondary btn-sm" type="button"
                        @click="copyToClipboard" :title="copyTooltip">
                  <i class="bi" :class="copyIcon"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Done</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import { useRoute } from 'vue-router';

const props = defineProps({
  show: Boolean,
  document: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['hidden']);

const route = useRoute();

const modalElement = ref(null);
const viewUrlInput = ref(null);
const copyIcon = ref('bi-clipboard');
const copyTooltip = ref('Copy to clipboard');
let bsModal = null;

/**
 * The document title for display
 */
const docTitle = computed(() => {
  return props.document?.title || 'Unknown Document';
});

/**
 * The framework identifier (UUID) from the current document
 */
const frameworkIdentifier = computed(() => {
  return props.document?.identifier || '';
});

/**
 * The framework slug from the route params (used for URL construction)
 * This is the same value used to access the editor: /editor/{frameworkId}
 */
const frameworkSlug = computed(() => {
  return route.params.frameworkId || '';
});

/**
 * JSON export URL using the CASE v1.1 API endpoint
 * This endpoint accepts the UUID identifier
 */
const jsonExportUrl = computed(() => {
  if (!frameworkIdentifier.value) return '#';
  return `/ims/case/v1p1/CFPackages/${frameworkIdentifier.value}.json`;
});

/**
 * Excel export URL
 * Uses the framework identifier (UUID) taking advantage of the new backed route.
 */
const excelExportUrl = computed(() => {
  if (!frameworkIdentifier.value) return '#';
  return `/cfdoc/${frameworkIdentifier.value}/excel`;
});

/**
 * Public view URL for the framework (no login required)
 */
const viewUrl = computed(() => {
  if (!frameworkSlug.value) return '';
  return `${window.location.origin}/editor/${frameworkSlug.value}`;
});

onMounted(() => {
  if (modalElement.value) {
    bsModal = new Modal(modalElement.value);
    modalElement.value.addEventListener('hidden.bs.modal', () => {
      emit('hidden');
    });
  }
});

watch(() => props.show, (newVal) => {
  if (bsModal) {
    if (newVal) {
      // Reset copy button
      copyIcon.value = 'bi-clipboard';
      copyTooltip.value = 'Copy to clipboard';
      bsModal.show();
    } else {
      bsModal.hide();
    }
  }
});

async function copyToClipboard() {
  try {
    await navigator.clipboard.writeText(viewUrl.value);
    copyIcon.value = 'bi-check-lg';
    copyTooltip.value = 'Copied!';
    setTimeout(() => {
      copyIcon.value = 'bi-clipboard';
      copyTooltip.value = 'Copy to clipboard';
    }, 2000);
  } catch (err) {
    // Fallback: select the input text
    if (viewUrlInput.value) {
      viewUrlInput.value.select();
      document.execCommand('copy');
    }
  }
}
</script>

<style scoped>
.modal-dialog {
  max-width: 750px;
}

.btn-export {
  white-space: normal;
  min-height: 4em;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
}
</style>
