<template>
  <div class="modal fade" id="editDocModal" tabindex="-1" role="dialog" aria-labelledby="editDocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="width:99%">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editDocModalLabel">Edit Document</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="loading" class="d-flex justify-content-center align-items-center p-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading form...</span>
            </div>
          </div>
          <div v-else-if="error" class="alert alert-danger" role="alert">
            {{ error }}
          </div>
          <form v-else @submit.prevent="saveDocument" name="ls_doc">
            <div class="row mb-3">
              <label for="ls_doc_title" class="col-sm-2 col-form-label">Title *</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="ls_doc_title" name="ls_doc[title]" v-model="formData.title" required>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_doc_version" class="col-sm-2 col-form-label">Version</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="ls_doc_version" name="ls_doc[version]" v-model="formData.version">
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_doc_adoptionStatus" class="col-sm-2 col-form-label">Status</label>
              <div class="col-sm-10">
                <select class="form-select" id="ls_doc_adoptionStatus" name="ls_doc[adoptionStatus]" v-model="formData.adoptionStatus">
                  <option value="Draft">Draft</option>
                  <option value="Adopted">Adopted</option>
                  <option value="Deprecated">Deprecated</option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_doc_subjects" class="col-sm-2 col-form-label">Subjects</label>
              <div class="col-sm-10">
                <select class="form-select" id="ls_doc_subjects" name="ls_doc[subjects][]" multiple v-model="formData.subjects">
                  <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">
                    {{ subject.title }}
                  </option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_doc_licence" class="col-sm-2 col-form-label">License</label>
              <div class="col-sm-10">
                <select class="form-select" id="ls_doc_licence" name="ls_doc[licence]" v-model="formData.licence">
                  <option v-for="license in availableLicenses" :key="license.id" :value="license.id">
                    {{ license.title }}
                  </option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_doc_description" class="col-sm-2 col-form-label">Description</label>
              <div class="col-sm-10">
                <textarea class="form-control" id="ls_doc_description" name="ls_doc[description]" rows="3" v-model="formData.description"></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" @click="saveDocument" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { Modal } from 'bootstrap';

const props = defineProps({
  document: Object,
  show: Boolean
});

const emit = defineEmits(['saved', 'hidden']);

const loading = ref(false);
const error = ref('');
const saving = ref(false);
const modal = ref(null);

const formData = reactive({
  title: '',
  version: '',
  adoptionStatus: 'Draft',
  subjects: [],
  licence: '',
  description: ''
});

const availableSubjects = ref([]);
const availableLicenses = ref([]);

watch(() => props.show, (newVal) => {
  if (newVal && props.document) {
    loadDocumentData();
    if (!modal.value) {
      modal.value = new Modal(document.getElementById('editDocModal'));
    }
    modal.value.show();
  } else if (modal.value) {
    modal.value.hide();
  }
});

watch(() => props.document, (newDoc) => {
  if (newDoc) {
    loadDocumentData();
  }
}, { immediate: true });

function loadDocumentData() {
  if (!props.document) return;

  loading.value = true;
  error.value = '';

  // Simulate loading form data - in real app this would be an API call
  setTimeout(() => {
    formData.title = props.document.title || '';
    formData.version = props.document.version || '';
    formData.adoptionStatus = props.document.adoptionStatus || 'Draft';
    formData.subjects = props.document.subjects || [];
    formData.licence = props.document.licence || '';
    formData.description = props.document.description || '';

    // Load available subjects and licenses
    availableSubjects.value = [
      { id: 'math', title: 'Mathematics' },
      { id: 'science', title: 'Science' },
      { id: 'english', title: 'English Language Arts' }
    ];

    availableLicenses.value = [
      { id: 'cc-by', title: 'Creative Commons Attribution' },
      { id: 'cc-by-sa', title: 'Creative Commons Attribution-ShareAlike' },
      { id: 'public-domain', title: 'Public Domain' }
    ];

    loading.value = false;
  }, 500);
}

function saveDocument() {
  saving.value = true;
  error.value = '';

  // Simulate saving - in real app this would be an API call
  setTimeout(() => {
    try {
      // Emit saved event with form data
      emit('saved', { ...formData });
      if (modal.value) {
        modal.value.hide();
      }
    } catch (e) {
      error.value = 'Failed to save document: ' + e.message;
    } finally {
      saving.value = false;
    }
  }, 1000);
}

// Handle modal hidden event
document.addEventListener('hidden.bs.modal', (event) => {
  if (event.target.id === 'editDocModal') {
    emit('hidden');
  }
});
</script>

<style scoped>
.modal-dialog {
  max-width: 95vw;
}

.form-control:focus,
.form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
