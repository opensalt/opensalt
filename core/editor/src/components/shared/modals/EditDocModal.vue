<template>
  <BaseModal
    id="editDocModal"
    :is-open="show"
    title="Edit Document"
    size="xl"
    aria-labelledby="editDocModalLabel"
    modal-dialog-classes="modal-dialog-wide"
    @update:isOpen="handleVisibilityChange"
    @hidden="handleHidden"
  >
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
        <label for="ls_doc_title" class="col-sm-2 col-form-label required-label">Title</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" id="ls_doc_title" name="ls_doc[title]" v-model="formData.title" placeholder="Enter document title" required>
          <small class="text-muted">The title of the document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_creator" class="col-sm-2 col-form-label">Creator</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" id="ls_doc_creator" name="ls_doc[creator]" v-model="formData.creator" placeholder="e.g., Organization or Person">
          <small class="text-muted">The entity responsible for creating the document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_officialUri" class="col-sm-2 col-form-label">Official URI</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" id="ls_doc_officialUri" name="ls_doc[officialUri]" v-model="formData.officialUri" placeholder="e.g., https://example.org/standards">
          <small class="text-muted">The official URI for this document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_publisher" class="col-sm-2 col-form-label">Publisher</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" id="ls_doc_publisher" name="ls_doc[publisher]" v-model="formData.publisher" placeholder="e.g., Publishing Organization">
          <small class="text-muted">The entity responsible for publishing the document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_urlName" class="col-sm-2 col-form-label">URL Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" id="ls_doc_urlName" name="ls_doc[urlName]" v-model="formData.urlName" placeholder="e.g., my-framework">
          <small class="text-muted">A URL-friendly name for this document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_version" class="col-sm-2 col-form-label">Version</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" id="ls_doc_version" name="ls_doc[version]" v-model="formData.version" placeholder="e.g., 1.0">
          <small class="text-muted">The version of the document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_description" class="col-sm-2 col-form-label">Description</label>
        <div class="col-sm-10">
          <textarea class="form-control" id="ls_doc_description" name="ls_doc[description]" rows="3" v-model="formData.description" placeholder="Enter document description"></textarea>
          <small class="text-muted">A description of the document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_subjects" class="col-sm-2 col-form-label">Subjects</label>
        <div class="col-sm-10">
          <MultiSelect
            id="ls_doc_subjects"
            name="ls_doc[subjects][]"
            v-model="formData.subjects"
            :options="availableSubjects"
            option-value="id"
            option-label="title"
            :show-select-all="false"
            placeholder="Select subjects"
            searchPlaceholder="Search subjects..."
          />
          <small class="text-muted">Subject areas associated with this document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_language" class="col-sm-2 col-form-label">Language</label>
        <div class="col-sm-10">
          <select class="form-select" id="ls_doc_language" name="ls_doc[language]" v-model="formData.language">
            <option value="">Select Language</option>
            <option value="en">English</option>
            <option value="es">Spanish</option>
            <option value="fr">French</option>
          </select>
          <small class="text-muted">The primary language of this document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_adoptionStatus" class="col-sm-2 col-form-label">Status</label>
        <div class="col-sm-10">
          <select class="form-select" id="ls_doc_adoptionStatus" name="ls_doc[adoptionStatus]" v-model="formData.adoptionStatus">
            <option value="Private Draft">Private Draft</option>
            <option value="Draft">Draft</option>
            <option value="Adopted">Adopted</option>
            <option value="Deprecated">Deprecated</option>
          </select>
          <small class="text-muted">The adoption status of this document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_statusStart" class="col-sm-2 col-form-label">Status Start</label>
        <div class="col-sm-10">
          <input type="date" class="form-control" id="ls_doc_statusStart" name="ls_doc[statusStart]" v-model="formData.statusStart">
          <small class="text-muted">The date when this status takes effect.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_statusEnd" class="col-sm-2 col-form-label">Status End</label>
        <div class="col-sm-10">
          <input type="date" class="form-control" id="ls_doc_statusEnd" name="ls_doc[statusEnd]" v-model="formData.statusEnd">
          <small class="text-muted">The date when this status ends.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_note" class="col-sm-2 col-form-label">Note</label>
        <div class="col-sm-10">
          <textarea class="form-control" id="ls_doc_note" name="ls_doc[note]" rows="3" v-model="formData.note" placeholder="Additional notes"></textarea>
          <small class="text-muted">Additional notes about this document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_licence" class="col-sm-2 col-form-label">License</label>
        <div class="col-sm-10">
          <SingleSelect
            id="ls_doc_licence"
            name="ls_doc[licence]"
            v-model="formData.licence"
            :options="availableLicenses"
            option-value="id"
            option-label="title"
            placeholder="Select License"
            searchPlaceholder="Search licenses..."
            :allow-clear="true"
          />
          <small class="text-muted">License governing the use of this document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label for="ls_doc_frameworkType" class="col-sm-2 col-form-label">Framework Type</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" id="ls_doc_frameworkType" name="ls_doc[frameworkType]" v-model="formData.frameworkType" placeholder="e.g., Standard, Rubric" list="frameworkTypeOptions">
          <datalist id="frameworkTypeOptions">
            <option v-for="type in availableFrameworkTypes" :key="type.id" :value="type.frameworkType">
              {{ type.frameworkType }}
            </option>
          </datalist>
          <small class="text-muted">The type of framework this document represents.</small>
        </div>
      </div>

      <div v-if="isAdmin" class="row mb-3">
        <label for="ls_doc_org" class="col-sm-2 col-form-label">Owning Access Group</label>
        <div class="col-sm-10">
          <select class="form-select" id="ls_doc_org" name="ls_doc[org]" v-model="formData.org">
            <option :value="null">None</option>
            <option v-for="group in availableAccessGroups" :key="group.id" :value="group.id">
              {{ group.name }}
            </option>
          </select>
          <small class="text-muted">The organization that owns this document. Only administrators can change this.</small>
        </div>
      </div>
    </form>

    <template #footer>
      <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
      <button type="button" class="btn btn-primary" @click="saveDocument" :disabled="saving">
        <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
        Save Changes
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import BaseModal from '../BaseModal.vue';
import MultiSelect from '../MultiSelect.vue';
import SingleSelect from '../SingleSelect.vue';
import { logger } from '../../../utils/logger.js';

const props = defineProps({
  document: Object,
  show: Boolean,
  isAdmin: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['saved', 'hidden', 'update:show']);

const loading = ref(false);
const error = ref('');
const saving = ref(false);

const formData = reactive({
  title: '',
  creator: '',
  officialUri: '',
  publisher: '',
  urlName: '',
  version: '',
  description: '',
  subjects: [],
  language: '',
  adoptionStatus: 'Draft',
  statusStart: '',
  statusEnd: '',
  note: '',
  licence: '',
  frameworkType: '',
  org: null
});

const availableSubjects = ref([]);
const availableLicenses = ref([]);
const availableFrameworkTypes = ref([]);
const availableAccessGroups = ref([]);

async function fetchSubjects() {
  if (availableSubjects.value.length > 0) return;

  try {
    const response = await fetch('/cfdef/subject/list?field_name=subjects&page=1&page_limit=50', {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const text = await response.text();
    const data = JSON.parse(text);

    if (data) {
      availableSubjects.value = data;
    } else {
      availableSubjects.value = [
        { id: 'math', title: 'Mathematics' },
        { id: 'science', title: 'Science' },
        { id: 'english', title: 'English Language Arts' },
        { id: 'history', title: 'History' }
      ];
    }
  } catch (err) {
    logger.error('Failed to fetch subjects:', err);
    availableSubjects.value = [
      { id: 'math', title: 'Mathematics' },
      { id: 'science', title: 'Science' },
      { id: 'english', title: 'English Language Arts' },
      { id: 'history', title: 'History' }
    ];
  }
}

async function fetchLicenses() {
  if (availableLicenses.value.length > 0) return;

  try {
    const response = await fetch('/cfdef/licence/list?field_name=licence&page=1&page_limit=50', {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const text = await response.text();
    const data = JSON.parse(text);

    if (data) {
      availableLicenses.value = data;
    } else {
      availableLicenses.value = [
        { id: 'cc0', title: 'CC0 (Public Domain)' },
        { id: 'cc-by', title: 'CC BY (Attribution)' },
        { id: 'cc-by-sa', title: 'CC BY-SA (Attribution-ShareAlike)' },
        { id: 'cc-by-nd', title: 'CC BY-ND (Attribution-NoDerivs)' },
        { id: 'cc-by-nc', title: 'CC BY-NC (Attribution-NonCommercial)' },
        { id: 'cc-by-nc-sa', title: 'CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)' },
        { id: 'cc-by-nc-nd', title: 'CC BY-NC-ND (Attribution-NonCommercial-NoDerivs)' }
      ];
    }
  } catch (err) {
    logger.error('Failed to fetch licenses:', err);
    availableLicenses.value = [
      { id: 'cc0', title: 'CC0 (Public Domain)' },
      { id: 'cc-by', title: 'CC BY (Attribution)' },
      { id: 'cc-by-sa', title: 'CC BY-SA (Attribution-ShareAlike)' },
      { id: 'cc-by-nd', title: 'CC BY-ND (Attribution-NoDerivs)' },
      { id: 'cc-by-nc', title: 'CC BY-NC (Attribution-NonCommercial)' },
      { id: 'cc-by-nc-sa', title: 'CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)' },
      { id: 'cc-by-nc-nd', title: 'CC BY-NC-ND (Attribution-NonCommercial-NoDerivs)' }
    ];
  }
}

async function fetchFrameworkTypes() {
  if (availableFrameworkTypes.value.length > 0) return;

  try {
    const response = await fetch('/cfdef/framework_type/list', {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const text = await response.text();
    const data = JSON.parse(text);

    if (data) {
      availableFrameworkTypes.value = data;
    } else {
      availableFrameworkTypes.value = [
        { id: 1, frameworkType: 'Standard' },
        { id: 2, frameworkType: 'Rubric' },
        { id: 3, frameworkType: 'Assessment' }
      ];
    }
  } catch (err) {
    logger.error('Failed to fetch framework types:', err);
    availableFrameworkTypes.value = [
      { id: 1, frameworkType: 'Standard' },
      { id: 2, frameworkType: 'Rubric' },
      { id: 3, frameworkType: 'Assessment' }
    ];
  }
}

async function fetchAccessGroups() {
  if (availableAccessGroups.value.length > 0) return;

  try {
    const response = await fetch('/framework/editor/access-groups', {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    availableAccessGroups.value = await response.json();
  } catch (err) {
    logger.error('Failed to fetch access groups:', err);
    availableAccessGroups.value = [];
  }
}

watch(() => props.show, async (newVal) => {
  if (newVal && props.document) {
    const fetches = [fetchSubjects(), fetchLicenses(), fetchFrameworkTypes()];
    if (props.isAdmin) {
      fetches.push(fetchAccessGroups());
    }
    await Promise.all(fetches);
    loadDocumentData();
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

  formData.title = props.document.title || '';
  formData.creator = props.document.creator || '';
  formData.officialUri = props.document.officialSourceURL || '';
  formData.publisher = props.document.publisher || '';
  formData.urlName = props.document.urlName || '';
  formData.version = props.document.version || '';
  formData.description = props.document.description || '';
  formData.subjects = props.document.subjects || [];
  formData.language = props.document.language || '';
  formData.adoptionStatus = props.document.adoptionStatus || 'Draft';
  formData.statusStart = props.document.statusStart || '';
  formData.statusEnd = props.document.statusEnd || '';
  formData.note = props.document.notes || props.document.note || '';
  formData.licence = props.document.licence || '';
  formData.frameworkType = props.document.frameworkType || '';
  formData.org = props.document.org || null;

  loading.value = false;
}

function closeModal() {
  emit('update:show', false);
  emit('hidden');
}

function handleVisibilityChange(val) {
  emit('update:show', val);
  if (!val) {
    emit('hidden');
  }
}

function handleHidden() {
  emit('hidden');
}

function saveDocument() {
  if (!formData.title.trim()) {
    error.value = 'Title is required';
    return;
  }

  saving.value = true;
  error.value = '';

  try {
    emit('saved', { ...formData });
    closeModal();
  } catch (e) {
    error.value = 'Failed to save document: ' + e.message;
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.modal-dialog-wide {
  max-width: 95vw;
  width: 99%;
}

.form-control:focus,
.form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.required-label::before {
  content: "*";
  color: red;
}
</style>
