<template>
  <div class="modal fade" id="addNewChildModal" tabindex="-1" role="dialog" aria-labelledby="addNewChildModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="width:99%">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addNewChildModalLabel">{{ props.item ? 'Edit Item' : 'Add New Child Item' }}</h5>
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
          <form v-else @submit.prevent="createItem" name="ls_item">
            <div class="row mb-3">
              <label for="ls_item_fullStatement" class="col-sm-2 col-form-label">Full Statement *</label>
              <div class="col-sm-10">
                <EasyMDE
                  id="ls_item_fullStatement"
                  name="ls_item[fullStatement]"
                  v-model="formData.fullStatement"
                  :required="true"
                  placeholder="Enter the complete statement for this competency item"
                />
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_abbreviatedStatement" class="col-sm-2 col-form-label">Abbreviated Statement</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="ls_item_abbreviatedStatement"
                  name="ls_item[abbreviatedStatement]"
                  v-model="formData.abbreviatedStatement"
                  placeholder="Short version of the statement"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_humanCodingScheme" class="col-sm-2 col-form-label">Human Coding Scheme</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="ls_item_humanCodingScheme"
                  name="ls_item[humanCodingScheme]"
                  v-model="formData.humanCodingScheme"
                  placeholder="e.g., CCSS.ELA-Literacy.RL.1.1"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_listEnumInSource" class="col-sm-2 col-form-label">List Enum In Source</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="ls_item_listEnumInSource"
                  name="ls_item[listEnumInSource]"
                  v-model="formData.listEnumInSource"
                  placeholder="List enum in source"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_itemType" class="col-sm-2 col-form-label">Item Type</label>
              <div class="col-sm-10">
                <select class="form-select" id="ls_item_itemType" name="ls_item[itemType]" v-model="formData.itemType">
                  <option value="">General Item</option>
                  <option value="assessment">Assessment</option>
                  <option value="course">Course</option>
                  <option value="credential">Credential</option>
                  <option value="job">Job</option>
                  <option value="organization">Organization</option>
                  <option value="identifier">Identifier</option>
                  <option value="public_key">Public Key</option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_subjects" class="col-sm-2 col-form-label">Subjects</label>
              <div class="col-sm-10">
                <select class="form-select" id="ls_item_subjects" name="ls_item[subjects][]" multiple v-model="formData.subjects">
                  <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">
                    {{ subject.title }}
                  </option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_educationalAlignment" class="col-sm-2 col-form-label">Educational Alignment</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="ls_item_educationalAlignment"
                  name="ls_item[educationalAlignment]"
                  v-model="formData.educationalAlignment"
                  placeholder="Enter educational alignment"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_notes" class="col-sm-2 col-form-label">Notes</label>
              <div class="col-sm-10">
                <textarea
                  class="form-control"
                  id="ls_item_notes"
                  name="ls_item[notes]"
                  rows="3"
                  v-model="formData.notes"
                  placeholder="Additional notes or comments"
                ></textarea>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_conceptKeywords" class="col-sm-2 col-form-label">Concept Keywords</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="ls_item_conceptKeywords"
                  name="ls_item[conceptKeywords]"
                  v-model="formData.conceptKeywords"
                  placeholder="Comma-separated keywords"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_language" class="col-sm-2 col-form-label">Language</label>
              <div class="col-sm-10">
                <select class="form-select" id="ls_item_language" name="ls_item[language]" v-model="formData.language">
                  <option value="en">English</option>
                  <option value="es">Spanish</option>
                  <option value="fr">French</option>
                  <option value="de">German</option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_licence" class="col-sm-2 col-form-label">Licence</label>
              <div class="col-sm-10">
                <select class="form-select" id="ls_item_licence" name="ls_item[licence]" v-model="formData.licence">
                  <option value="">Select Licence</option>
                  <option value="cc-by">Creative Commons BY</option>
                  <option value="cc-by-sa">Creative Commons BY-SA</option>
                  <option value="cc-by-nc">Creative Commons BY-NC</option>
                  <option value="public-domain">Public Domain</option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_educationLevel" class="col-sm-2 col-form-label">Education Level</label>
              <div class="col-sm-10">
                <MultiSelect
                  v-model="formData.educationLevel"
                  :options="educationLevelOptions"
                  placeholder="Select education levels"
                  search-placeholder="Search education levels..."
                  option-value="value"
                  option-label="label"
                />
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" @click="createItem" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ props.item ? 'Update' : 'Create' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { Modal } from 'bootstrap';
import educationLevelsData from '../../../data/EducationLevel.json';
import MultiSelect from '../MultiSelect.vue';
import EasyMDE from '../EasyMDE.vue';

const props = defineProps({
  parentItem: Object,
  itemType: String,
  show: Boolean,
  item: Object
});

const emit = defineEmits(['created', 'hidden']);

const loading = ref(false);
const error = ref('');
const saving = ref(false);
const modal = ref(null);

const formData = reactive({
  fullStatement: '',
  abbreviatedStatement: '',
  humanCodingScheme: '',
  listEnumInSource: '',
  itemType: '',
  subjects: [],
  educationalAlignment: '',
  notes: '',
  conceptKeywords: '',
  language: 'en',
  licence: '',
  educationLevel: []
});

const availableSubjects = ref([]);
const availableAlignments = ref([]);
const educationLevels = ref([]);

const educationLevelOptions = computed(() => {
  return educationLevelsData.map(level => {
    const key = Object.keys(level)[0];
    const label = Object.values(level)[0];
    return {
      value: key,
      label: label
    };
  });
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    loadFormData();
    if (!modal.value) {
      modal.value = new Modal(document.getElementById('addNewChildModal'));
    }
    modal.value.show();
  } else if (modal.value) {
    modal.value.hide();
  }
});

watch(() => props.itemType, (newType) => {
  if (newType) {
    formData.itemType = newType;
  }
}, { immediate: true });

function loadFormData() {
  loading.value = true;
  error.value = '';

  // Reset form
  Object.keys(formData).forEach(key => {
    if (Array.isArray(formData[key])) {
      formData[key] = [];
    } else {
      formData[key] = '';
    }
  });
  formData.language = 'en';
  formData.itemType = props.itemType || '';
  formData.listEnumInSource = '';
  formData.licence = '';
  formData.educationLevel = [];

  // If editing, populate form with item data
  if (props.item) {
    formData.fullStatement = props.item.fullStatement || '';
    formData.abbreviatedStatement = props.item.abbreviatedStatement || '';
    formData.humanCodingScheme = props.item.humanCodingScheme || '';
    formData.listEnumInSource = props.item.listEnumInSource || '';
    formData.itemType = props.item.itemType || '';
    formData.subjects = props.item.subjects || [];
    formData.educationalAlignment = Array.isArray(props.item.educationalAlignment) ? props.item.educationalAlignment.join(', ') : props.item.educationalAlignment || '';
    formData.notes = props.item.notes || '';
    formData.conceptKeywords = props.item.conceptKeywords || '';
    formData.language = props.item.language || 'en';
    formData.licence = props.item.licence || '';
    formData.educationLevel = props.item.educationLevel || [];
  }

  // Load education levels from JSON
  educationLevels.value = educationLevelsData;

  // Simulate loading available options - in real app this would be API calls
  setTimeout(() => {
    availableSubjects.value = [
      { id: 'math', title: 'Mathematics' },
      { id: 'science', title: 'Science' },
      { id: 'english', title: 'English Language Arts' },
      { id: 'history', title: 'History' },
      { id: 'art', title: 'Visual Arts' }
    ];

    availableAlignments.value = [
      { id: 'ccss-math', title: 'CCSS Mathematics', description: 'Common Core State Standards for Mathematics' },
      { id: 'ccss-ela', title: 'CCSS ELA', description: 'Common Core State Standards for English Language Arts' },
      { id: 'ngss', title: 'NGSS', description: 'Next Generation Science Standards' },
      { id: 'c3', title: 'C3 Framework', description: 'College, Career, and Civic Life Framework' }
    ];

    loading.value = false;
  }, 300);
}

function createItem() {
  if (!formData.fullStatement.trim()) {
    error.value = 'Full Statement is required';
    return;
  }

  saving.value = true;
  error.value = '';

  // Simulate creating item - in real app this would be an API call
  setTimeout(() => {
    try {
      const newItem = {
        identifier: 'item_' + Date.now(),
        ...formData,
        parentId: props.parentItem?.identifier || null,
        created: new Date().toISOString(),
        children: []
      };

      emit('created', newItem);
      if (modal.value) {
        modal.value.hide();
      }
    } catch (e) {
      error.value = 'Failed to create item: ' + e.message;
    } finally {
      saving.value = false;
    }
  }, 1000);
}

// Handle modal hidden event
document.addEventListener('hidden.bs.modal', (event) => {
  if (event.target.id === 'addNewChildModal') {
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

textarea.form-control {
  resize: vertical;
  min-height: 80px;
}
</style>
