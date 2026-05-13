<template>
  <div
    id="exportModal"
    ref="modalElement"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="exportDocumentModalLabel"
    aria-hidden="true"
  >
    <div
      class="modal-dialog modal-lg"
      role="document"
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="exportDocumentModalLabel"
            class="modal-title"
          >
            Export Document
          </h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          />
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
                  class="btn btn-primary w-100 btn-export btn-export-case"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <i
                    class="bi bi-filetype-json d-block mb-1"
                    style="font-size: 1.5em;"
                  />
                  Competency Framework Package (JSON)
                </a>
                <div class="form-text text-muted mt-1 small">
                  Exports a JSON file using the IMS-standard format.
                  Best for archiving Frameworks.
                </div>
              </div>

              <!-- Excel/Spreadsheet Export -->
              <div
                v-if="sessionStore.isAuthenticated"
                class="col-sm-4"
              >
                <a
                  :href="excelExportUrl"
                  role="button"
                  class="btn btn-primary w-100 btn-export btn-export-excel"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <i
                    class="bi bi-file-earmark-spreadsheet d-block mb-1"
                    style="font-size: 1.5em;"
                  />
                  Spreadsheet export
                </a>
                <div class="form-text text-muted mt-1 small">
                  Exports an Excel spreadsheet file that you can open
                  in spreadsheet programs such as Microsoft Excel.
                </div>
              </div>

              <!-- CSV Export (hidden placeholder for future) -->
              <div class="col-sm-4 d-none">
                <button
                  class="btn btn-primary w-100 btn-export disabled"
                  disabled
                >
                  <i
                    class="bi bi-filetype-html d-block mb-1"
                    style="font-size: 1.5em;"
                  />
                  HTML Archive
                </button>
                <div class="form-text text-muted mt-1 small">
                  HTML export functionality coming soon...
                </div>
              </div>
            </div>
          </div>

          <hr>

          <p>
            You can also share the following link, which anyone can use to view the
            Competency Framework in their web browser (no login required):
          </p>

          <a
            :href="viewUrl"
            target="_blank"
            rel="noopener noreferrer"
          >{{ viewUrl }}</a>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal"
          >
            Done
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import { useRoute } from 'vue-router';
import { useSessionStore } from '@/stores/sessionStore.js';

const props = defineProps({
  show: Boolean,
  document: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['hidden']);

const sessionStore = useSessionStore();
const route = useRoute();

const modalElement = ref(null);
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
 * JSON export URL using the CASE v1.1 API endpoint
 */
const jsonExportUrl = computed(() => {
  if (!frameworkIdentifier.value) return '#';
  return `/ims/case/v1p1/CFPackages/${frameworkIdentifier.value}.json?download=1`;
});

/**
 * Excel export URL
 * Uses the framework identifier (UUID) taking advantage of the new backed route.
 */
const excelExportUrl = computed(() => {
  if (!frameworkIdentifier.value) return '#';
  return `/cfdoc/${frameworkIdentifier.value}/excel`;
});

const frameworkSlug = computed(() => {
  return route.params.frameworkId || '';
});

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
      bsModal.show();
    } else {
      bsModal.hide();
    }
  }
});
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
