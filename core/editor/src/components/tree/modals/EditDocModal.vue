<template>
  <BaseModal
    id="editDocModal"
    :is-open="show"
    title="Edit Document"
    size="xl"
    aria-labelledby="editDocModalLabel"
    modal-dialog-classes="modal-dialog-wide"
    @update:is-open="handleVisibilityChange"
    @hidden="handleHidden"
  >
    <div
      v-if="loading"
      class="d-flex justify-content-center align-items-center p-4"
    >
      <div
        class="spinner-border text-primary"
        role="status"
      >
        <span class="visually-hidden">Loading form...</span>
      </div>
    </div>
    <div
      v-else-if="error"
      class="alert alert-danger"
      role="alert"
    >
      {{ error }}
    </div>
    <form
      v-else
      name="ls_doc"
      @submit.prevent="saveDocument"
    >
      <div class="row mb-3">
        <label
          for="ls_doc_title"
          class="col-sm-2 col-form-label required-label"
        >Title</label>
        <div class="col-sm-10">
          <input
            id="ls_doc_title"
            v-model="formData.title"
            type="text"
            class="form-control"
            name="ls_doc[title]"
            placeholder="Enter document title"
            required
          >
          <small class="text-muted">The title of the document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="ls_doc_creator"
          class="col-sm-2 col-form-label"
        >Creator</label>
        <div class="col-sm-10">
          <input
            id="ls_doc_creator"
            v-model="formData.creator"
            type="text"
            class="form-control"
            name="ls_doc[creator]"
            placeholder="e.g., Organization or Person"
          >
          <small class="text-muted">The entity responsible for creating the document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="ls_doc_officialUri"
          class="col-sm-2 col-form-label"
        >Official URI</label>
        <div class="col-sm-10">
          <input
            id="ls_doc_officialUri"
            v-model="formData.officialUri"
            type="text"
            class="form-control"
            name="ls_doc[officialUri]"
            placeholder="e.g., https://example.org/standards"
          >
          <small class="text-muted">The official URI for this document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="ls_doc_publisher"
          class="col-sm-2 col-form-label"
        >Publisher</label>
        <div class="col-sm-10">
          <input
            id="ls_doc_publisher"
            v-model="formData.publisher"
            type="text"
            class="form-control"
            name="ls_doc[publisher]"
            placeholder="e.g., Publishing Organization"
          >
          <small class="text-muted">The entity responsible for publishing the document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="ls_doc_urlName"
          class="col-sm-2 col-form-label"
        >URL Name</label>
        <div class="col-sm-10">
          <input
            id="ls_doc_urlName"
            v-model="formData.urlName"
            type="text"
            class="form-control"
            name="ls_doc[urlName]"
            placeholder="e.g., my-framework"
          >
          <small class="text-muted">A URL-friendly name for this document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="ls_doc_version"
          class="col-sm-2 col-form-label"
        >Version</label>
        <div class="col-sm-10">
          <input
            id="ls_doc_version"
            v-model="formData.version"
            type="text"
            class="form-control"
            name="ls_doc[version]"
            placeholder="e.g., 1.0"
          >
          <small class="text-muted">The version of the document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="ls_doc_description"
          class="col-sm-2 col-form-label"
        >Description</label>
        <div class="col-sm-10">
          <textarea
            id="ls_doc_description"
            v-model="formData.description"
            class="form-control"
            name="ls_doc[description]"
            rows="3"
            placeholder="Enter document description"
          />
          <small class="text-muted">A description of the document.</small>
        </div>
      </div>

      <SubjectSelector
        id="ls_doc_subjects"
        ref="subjectSelectorRef"
        v-model="formData.subjects"
        name="ls_doc[subjects][]"
        help-text="Subject areas associated with this document."
      />

      <div class="row mb-3">
        <label
          for="ls_doc_language"
          class="col-sm-2 col-form-label"
        >Language</label>
        <div class="col-sm-10">
          <select
            id="ls_doc_language"
            v-model="formData.language"
            class="form-select"
            name="ls_doc[language]"
          >
            <option value="">
              Select Language
            </option>
            <option value="en">
              English
            </option>
            <option value="es">
              Spanish
            </option>
            <option value="fr">
              French
            </option>
          </select>
          <small class="text-muted">The primary language of this document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="ls_doc_adoptionStatus"
          class="col-sm-2 col-form-label"
        >Status</label>
        <div class="col-sm-10">
          <select
            id="ls_doc_adoptionStatus"
            v-model="formData.adoptionStatus"
            class="form-select"
            name="ls_doc[adoptionStatus]"
          >
            <option value="Private Draft">
              Private Draft
            </option>
            <option value="Draft">
              Draft
            </option>
            <option value="Adopted">
              Adopted
            </option>
            <option value="Deprecated">
              Deprecated
            </option>
          </select>
          <small class="text-muted">The adoption status of this document.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="ls_doc_statusStart"
          class="col-sm-2 col-form-label"
        >Status Start</label>
        <div class="col-sm-10">
          <input
            id="ls_doc_statusStart"
            v-model="formData.statusStart"
            type="date"
            class="form-control"
            name="ls_doc[statusStart]"
          >
          <small class="text-muted">The date when this status takes effect.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="ls_doc_statusEnd"
          class="col-sm-2 col-form-label"
        >Status End</label>
        <div class="col-sm-10">
          <input
            id="ls_doc_statusEnd"
            v-model="formData.statusEnd"
            type="date"
            class="form-control"
            name="ls_doc[statusEnd]"
          >
          <small class="text-muted">The date when this status ends.</small>
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="ls_doc_note"
          class="col-sm-2 col-form-label"
        >Note</label>
        <div class="col-sm-10">
          <textarea
            id="ls_doc_note"
            v-model="formData.note"
            class="form-control"
            name="ls_doc[note]"
            rows="3"
            placeholder="Additional notes"
          />
          <small class="text-muted">Additional notes about this document.</small>
        </div>
      </div>

      <LicenseSelector
        id="ls_doc_licence"
        ref="licenseSelectorRef"
        v-model="formData.licence"
        name="ls_doc[licence]"
        help-text="License governing the use of this document."
      />

      <div class="row mb-3">
        <label
          for="ls_doc_frameworkType"
          class="col-sm-2 col-form-label"
        >Framework Type</label>
        <div class="col-sm-10">
          <input
            id="ls_doc_frameworkType"
            v-model="formData.frameworkType"
            type="text"
            class="form-control"
            name="ls_doc[frameworkType]"
            placeholder="e.g., Standard, Rubric"
            list="frameworkTypeOptions"
          >
          <datalist id="frameworkTypeOptions">
            <option
              v-for="type in availableFrameworkTypes"
              :key="type.id"
              :value="type.frameworkType"
            >
              {{ type.frameworkType }}
            </option>
          </datalist>
          <small class="text-muted">The type of framework this document represents.</small>
        </div>
      </div>

      <div
        v-if="isAdmin"
        class="row mb-3"
      >
        <label
          for="ls_doc_org"
          class="col-sm-2 col-form-label"
        >Owning Access Group</label>
        <div class="col-sm-10">
          <select
            id="ls_doc_org"
            v-model="formData.org"
            class="form-select"
            name="ls_doc[org]"
          >
            <option :value="null">
              None
            </option>
            <option
              v-for="group in availableAccessGroups"
              :key="group.id"
              :value="group.id"
            >
              {{ group.name }}
            </option>
          </select>
          <small class="text-muted">The organization that owns this document. Only administrators can change this.</small>
        </div>
      </div>

      <AdditionalFields
        v-model="formData.additionalFields"
        :field-definitions="docFieldDefinitions"
      />
    </form>

    <template #footer>
      <button
        type="button"
        class="btn btn-secondary"
        @click="closeModal"
      >
        Cancel
      </button>
      <button
        type="button"
        class="btn btn-primary"
        :disabled="saving"
        @click="saveDocument"
      >
        <span
          v-if="saving"
          class="spinner-border spinner-border-sm me-2"
          role="status"
        />
        Save Changes
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, reactive, watch, nextTick } from 'vue';
import BaseModal from '../../shared/BaseModal.vue';
import SubjectSelector from '../common/SubjectSelector.vue';
import LicenseSelector from '../common/LicenseSelector.vue';
import AdditionalFields from '../fields/AdditionalFields.vue';
import { logger } from '../../../utils/logger.js';
import { useAdditionalFields } from '../../../composables/useAdditionalFields.js';

const props = defineProps({
  document: {
    type: Object,
    default: null
  },
  show: {
    type: Boolean,
    default: false
  },
  isAdmin: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['saved', 'hidden', 'update:show']);

const { fieldDefinitions: docFieldDefinitions, fetchFields: fetchDocFields } = useAdditionalFields();

const loading = ref(false);
const error = ref('');
const saving = ref(false);

const subjectSelectorRef = ref(null);
const licenseSelectorRef = ref(null);

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
  org: null,
  additionalFields: {}
});

const availableFrameworkTypes = ref([]);
const availableAccessGroups = ref([]);

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
    await nextTick();
    const fetches = [
      subjectSelectorRef.value?.ensureLoaded(),
      licenseSelectorRef.value?.ensureLoaded(),
      fetchFrameworkTypes(),
      fetchDocFields('doc')
    ];
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

  const availableSubjects = subjectSelectorRef.value?.availableSubjects || [];
  const docSubjects = props.document.subjects || [];
  const subjectIds = docSubjects.map(s => {
    if (typeof s === 'object' && s !== null) {
      const match = availableSubjects.find(opt => opt.title === s.title || opt.text === s.title);
      return match ? match.id : null;
    }
    return s;
  }).filter(v => v !== null && v !== undefined);
  formData.subjects = subjectIds;

  formData.language = props.document.language || '';
  formData.adoptionStatus = props.document.adoptionStatus || 'Draft';
  formData.statusStart = props.document.statusStart || '';
  formData.statusEnd = props.document.statusEnd || '';
  formData.note = props.document.notes || props.document.note || '';

  const availableLicenses = licenseSelectorRef.value?.availableLicenses || [];
  const docLicence = props.document.licence || props.document.licenseURI?.identifier || '';
  if (docLicence && !isNumeric(docLicence)) {
    const match = availableLicenses.find(opt => opt.id == docLicence || opt.text === props.document.licenseURI?.title);
    formData.licence = match ? match.id : '';
  } else {
    formData.licence = docLicence;
  }

  formData.frameworkType = props.document.frameworkType || '';
  formData.org = props.document.org || null;
  formData.additionalFields = props.document.additionalFields || {};

  loading.value = false;
}

function isNumeric(val) {
  return typeof val === 'number' || (typeof val === 'string' && !isNaN(val) && val.trim() !== '');
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
