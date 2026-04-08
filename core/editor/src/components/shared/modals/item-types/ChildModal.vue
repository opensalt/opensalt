<template>
  <!-- Backdrop -->
  <div v-if="props.show" class="modal-backdrop fade" :class="{ 'show': props.show }" @click="closeModal"></div>

  <!-- Modal -->
  <div class="modal fade" :class="{ 'show d-block': props.show }" tabindex="-1" id="addNewChildModal" aria-hidden="true" :style="{ display: props.show ? 'block' : 'none' }">
    <div class="modal-dialog modal-xl" role="document" style="width:99%" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addNewChildModalLabel">{{ isEdit ? 'Edit Child Item' : 'Add New Child Item' }}</h5>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
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
          <form v-else @submit.prevent="saveItem" name="ls_item">
            <div class="row mb-3">
              <label for="ls_item_fullStatement" class="col-sm-2 col-form-label required-label">Full Statement</label>
              <div class="col-sm-10">
                <EasyMDE
                  id="ls_item_fullStatement"
                  name="ls_item[fullStatement]"
                  v-model="formData.fullStatement"
                  :required="true"
                  placeholder="Enter the complete statement for this competency item"
                />
                <small class="text-muted">The full, complete statement of the competency.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_humanCodingScheme" class="col-sm-2 col-form-label">Human Coding Scheme</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="ls_item_humanCodingScheme" name="ls_item[humanCodingScheme]" v-model="formData.humanCodingScheme" placeholder="e.g., CCSS.ELA-Literacy.RL.1.1">
                <small class="text-muted">Human-readable identifier for the item, e.g., CCSS.ELA-Literacy.RL.1.1</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_abbreviatedStatement" class="col-sm-2 col-form-label">Abbreviated Statement</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="ls_item_abbreviatedStatement" name="ls_item[abbreviatedStatement]" v-model="formData.abbreviatedStatement" placeholder="Short version of the statement">
                <small class="text-muted">A short version of the statement for display purposes.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_listEnumInSource" class="col-sm-2 col-form-label">List Enum In Source</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="ls_item_listEnumInSource" name="ls_item[listEnumInSource]" v-model="formData.listEnumInSource" placeholder="e.g., 1">
                <small class="text-muted">The position of this item in the source enumeration.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_conceptKeywords" class="col-sm-2 col-form-label">Concept Keywords</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="ls_item_conceptKeywords" name="ls_item[conceptKeywords]" v-model="formData.conceptKeywords" placeholder="e.g., reading, literature, analysis">
                <small class="text-muted">Keywords or concepts associated with this item, comma-separated.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_language" class="col-sm-2 col-form-label">Language</label>
              <div class="col-sm-10">
                <select class="form-select" id="ls_item_language" name="ls_item[language]" v-model="formData.language">
                  <option value="">Select Language</option>
                  <option value="en">English</option>
                  <option value="es">Spanish</option>
                  <option value="fr">French</option>
                </select>
                <small class="text-muted">The language of this item.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_educationalAlignment" class="col-sm-2 col-form-label">Education Level</label>
              <div class="col-sm-10">
                <MultiSelect
                  id="ls_item_educationalAlignment"
                  name="ls_item[educationalAlignment][]"
                  v-model="formData.educationalAlignment"
                  :options="availableEducationLevels"
                  option-value="code"
                  option-label="code"
                  placeholder="Select education levels"
                  searchPlaceholder="Search education levels..."
                />
                <small class="text-muted">Education levels associated with this item.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_itemType" class="col-sm-2 col-form-label">Item Type</label>
              <div class="col-sm-10">
                <SingleSelect
                  id="ls_item_itemType"
                  name="ls_item[itemType]"
                  v-model="formData.itemType"
                  :options="availableItemTypes"
                  option-value="id"
                  option-label="text"
                  placeholder="Select Item Type"
                  searchPlaceholder="Search item types..."
                  :allow-clear="true"
                />
                <small class="text-muted">The type of this item.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_subjects" class="col-sm-2 col-form-label">Subjects</label>
              <div class="col-sm-10">
                <MultiSelect
                  id="ls_item_subjects"
                  name="ls_item[subjects][]"
                  v-model="formData.subjects"
                  :options="availableSubjects"
                  option-value="id"
                  option-label="title"
                  :show-select-all="false"
                  placeholder="Select subjects"
                  searchPlaceholder="Search subjects..."
                />
                <small class="text-muted">Subject areas associated with this item.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_licence" class="col-sm-2 col-form-label">License</label>
              <div class="col-sm-10">
                <select class="form-select" id="ls_item_licence" name="ls_item[licence]" v-model="formData.licence">
                  <option value="">Select License</option>
                  <option v-for="licence in availableLicences" :key="licence.id" :value="licence.id">
                    {{ licence.title }}
                  </option>
                </select>
                <small class="text-muted">License governing the use of this item.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_notes" class="col-sm-2 col-form-label">Notes</label>
              <div class="col-sm-10">
                <textarea class="form-control" id="ls_item_notes" name="ls_item[notes]" rows="3" v-model="formData.notes" placeholder="Additional notes or comments"></textarea>
                <small class="text-muted">Additional notes or comments about this item.</small>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
          <button type="button" class="btn btn-primary" @click="saveItem" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ isEdit ? 'Update' : 'Add' }} Item
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import EasyMDE from '../../EasyMDE.vue';
import MultiSelect from '../../MultiSelect.vue';
import SingleSelect from '../../SingleSelect.vue';
import { logger } from '../../../../utils/logger.js';
import { useItemTypeModal } from '../../../../composables/useItemTypeModal';

const props = defineProps({
  parentItem: Object,
  show: Boolean,
  itemType: String,
  item: Object
});

const emit = defineEmits(['created', 'updated', 'hidden']);

const { loading, error, saving, isEdit, closeModal } = useItemTypeModal(props, emit, { typeName: 'child' });

const formData = reactive({
  fullStatement: '',
  humanCodingScheme: '',
  abbreviatedStatement: '',
  listEnumInSource: '',
  conceptKeywords: '',
  language: '',
  educationalAlignment: [],
  itemType: '',
  subjects: [],
  licence: '',
  notes: ''
});

// Subjects fetched from API
const availableSubjects = ref([]);
const loadingSubjects = ref(false);

/**
 * Fetch subjects from the API
 */
async function fetchSubjects() {
  if (availableSubjects.value.length > 0) return; // Already loaded

  loadingSubjects.value = true;
  try {
    logger.debug('Fetching subjects from API...');
    // Use direct fetch to handle the response properly
    // The API endpoint returns JSON but may have text/html content-type due to Twig template
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

    // Try to parse as JSON regardless of content-type
    const text = await response.text();
    logger.debug('Raw subjects response:', text);

    // Parse the JSON
    const data = JSON.parse(text);
    logger.debug('Parsed subjects response:', data);

    if (data) {
      availableSubjects.value = data;
      logger.debug('Loaded subjects:', availableSubjects.value);
    } else {
      logger.warn('Unexpected response format for subjects:', data);
      throw new Error('Invalid response format');
    }
  } catch (err) {
    logger.error('Failed to fetch subjects:', err);
    // Fallback to default subjects if API fails
    availableSubjects.value = [
      { id: 'math', title: 'Mathematics' },
      { id: 'science', title: 'Science' },
      { id: 'english', title: 'English Language Arts' },
      { id: 'history', title: 'History' }
    ];
    logger.debug('Using fallback subjects:', availableSubjects.value);
  } finally {
    loadingSubjects.value = false;
  }
}

const availableEducationLevels = ref([
  { code: 'IT', title: 'Infant/toddler', rank: 1, label: 'IT - Infant/toddler' },
  { code: 'PR', title: 'Preschool', rank: 2, label: 'PR - Preschool' },
  { code: 'PK', title: 'Prekindergarten', rank: 3, label: 'PK - Prekindergarten' },
  { code: 'TK', title: 'Transitional Kindergarten', rank: 4, label: 'TK - Transitional Kindergarten' },
  { code: 'KG', title: 'Kindergarten', rank: 5, label: 'KG - Kindergarten' },
  { code: '01', title: 'First grade', rank: 6, label: '01 - First grade' },
  { code: '02', title: 'Second grade', rank: 7, label: '02 - Second grade' },
  { code: '03', title: 'Third grade', rank: 8, label: '03 - Third grade' },
  { code: '04', title: 'Fourth grade', rank: 9, label: '04 - Fourth grade' },
  { code: '05', title: 'Fifth grade', rank: 10, label: '05 - Fifth grade' },
  { code: '06', title: 'Sixth grade', rank: 11, label: '06 - Sixth grade' },
  { code: '07', title: 'Seventh grade', rank: 12, label: '07 - Seventh grade' },
  { code: '08', title: 'Eighth grade', rank: 13, label: '08 - Eighth grade' },
  { code: '09', title: 'Ninth grade', rank: 14, label: '09 - Ninth grade' },
  { code: '10', title: 'Tenth grade', rank: 15, label: '10 - Tenth grade' },
  { code: '11', title: 'Eleventh grade', rank: 16, label: '11 - Eleventh grade' },
  { code: '12', title: 'Twelfth grade', rank: 17, label: '12 - Twelfth grade' },
  { code: '13', title: 'Grade 13', rank: 18, label: '13 - Grade 13' },
  { code: 'AS', title: "Associate's degree", rank: 19, label: "AS - Associate's degree" },
  { code: 'BA', title: "Bachelor's degree", rank: 20, label: "BA - Bachelor's degree" },
  { code: 'PB', title: 'Post-baccalaureate certificate', rank: 21, label: 'PB - Post-baccalaureate certificate' },
  { code: 'MD', title: "Master's degree", rank: 22, label: "MD - Master's degree" },
  { code: 'PM', title: 'Post-master\'s certificate', rank: 23, label: 'PM - Post-master\'s certificate' },
  { code: 'DO', title: 'Doctoral degree', rank: 24, label: 'DO - Doctoral degree' },
  { code: 'PD', title: 'Post-doctoral certificate', rank: 25, label: 'PD - Post-doctoral certificate' },
  { code: 'AE', title: 'Adult Education', rank: 26, label: 'AE - Adult Education' },
  { code: 'PT', title: 'Professional or technical credential', rank: 27, label: 'PT - Professional or technical credential' },
  { code: 'OT', title: 'Other', rank: 28, label: 'OT - Other' }
]);

const availableLicences = ref([
  { id: 'cc0', title: 'CC0 (Public Domain)' },
  { id: 'cc-by', title: 'CC BY (Attribution)' },
  { id: 'cc-by-sa', title: 'CC BY-SA (Attribution-ShareAlike)' },
  { id: 'cc-by-nd', title: 'CC BY-ND (Attribution-NoDerivs)' },
  { id: 'cc-by-nc', title: 'CC BY-NC (Attribution-NonCommercial)' },
  { id: 'cc-by-nc-sa', title: 'CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)' },
  { id: 'cc-by-nc-nd', title: 'CC BY-NC-ND (Attribution-NonCommercial-NoDerivs)' }
]);

// Item types fetched from API
const availableItemTypes = ref([]);
const loadingItemTypes = ref(false);

/**
 * Fetch item types from the API
 */
async function fetchItemTypes() {
  if (availableItemTypes.value.length > 0) return; // Already loaded

  loadingItemTypes.value = true;
  try {
    logger.debug('Fetching item types from API...');
    // Use direct fetch to handle the response properly
    // The API endpoint returns JSON but may have text/html content-type due to Twig template
    const response = await fetch('/cfdef/item_type/list?field_name=itemType&page=1&page_limit=1000', {
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

    // Try to parse as JSON regardless of content-type
    const text = await response.text();
    logger.debug('Raw response:', text);

    // Parse the JSON
    const data = JSON.parse(text);
    logger.debug('Parsed response:', data);

    if (data && data.results) {
      availableItemTypes.value = data.results;
      logger.debug('Loaded item types:', availableItemTypes.value);
    } else {
      logger.warn('Unexpected response format:', data);
      throw new Error('Invalid response format');
    }
  } catch (err) {
    logger.error('Failed to fetch item types:', err);
    // Fallback to default item types if API fails
    availableItemTypes.value = [
      { id: 'general', text: 'General Item' },
      { id: 'assessment', text: 'Assessment' },
      { id: 'course', text: 'Course' },
      { id: 'credential', text: 'Credential' },
      { id: 'job', text: 'Job' },
      { id: 'organization', text: 'Organization' }
    ];
    logger.debug('Using fallback item types:', availableItemTypes.value);
  } finally {
    loadingItemTypes.value = false;
  }
}

watch(() => props.show, async (newVal) => {
  if (newVal) {
    await Promise.all([fetchItemTypes(), fetchSubjects()]);
    loadFormData();
  }
}, { immediate: true });

watch(() => props.item, (newItem) => {
  if (newItem) {
    loadFormData();
  }
}, { immediate: true });

function loadFormData() {
  loading.value = true;
  error.value = '';

  if (isEdit.value) {
    formData.fullStatement = props.item.fullStatement || '';
    formData.humanCodingScheme = props.item.humanCodingScheme || '';
    formData.abbreviatedStatement = props.item.abbreviatedStatement || '';
    formData.listEnumInSource = props.item.listEnumInSource || '';
    formData.conceptKeywords = props.item.conceptKeywords || '';
    formData.language = props.item.language || '';
    // Handle educationalAlignment as array - convert single value to array if needed
    formData.educationalAlignment = Array.isArray(props.item.educationLevel)
      ? props.item.educationLevel
      : (props.item.educationLevel ? [props.item.educationLevel] : []);
    // Find the matching option by text property and use its id
    const matchingType = availableItemTypes.value.find(
      opt => opt.text === props.item.itemType
    );
    formData.itemType = matchingType ? matchingType.id : '';
    // Read from subjectURI and match by title to get IDs
    logger.debug('Available subjects:', availableSubjects.value);
    logger.debug('Item subjects:', props.item.subjectURI);
    const subjectIds = (props.item.subjectURI || []).map(uri => {
      const match = availableSubjects.value.find(opt => opt.title === uri.title);
      return match ? match.id : null;
    }).filter(Boolean);
    formData.subjects = subjectIds;
    formData.licence = props.item.licence || '';
    formData.notes = props.item.notes || '';
  } else {
    // Reset for new
    formData.fullStatement = '';
    formData.humanCodingScheme = '';
    formData.abbreviatedStatement = '';
    formData.listEnumInSource = '';
    formData.conceptKeywords = '';
    formData.language = '';
    formData.educationalAlignment = [];
    formData.itemType = props.itemType || '';
    formData.subjects = [];
    formData.licence = '';
    formData.notes = '';
  }

  loading.value = false;
}

function saveItem() {
  if (!formData.fullStatement.trim()) {
    error.value = 'Full Statement is required';
    return;
  }

  saving.value = true;
  error.value = '';

  try {
    let savedItem;
    if (isEdit.value) {
      savedItem = {
        ...props.item,
        fullStatement: formData.fullStatement,
        humanCodingScheme: formData.humanCodingScheme,
        abbreviatedStatement: formData.abbreviatedStatement,
        listEnumInSource: formData.listEnumInSource,
        conceptKeywords: formData.conceptKeywords,
        language: formData.language,
        educationalAlignment: formData.educationalAlignment,
        itemType: formData.itemType,
        subjects: formData.subjects,
        licence: formData.licence,
        notes: formData.notes,
        extensions: {
          ...(props.item.extensions || {}),
          'salt:type': formData.itemType || 'default'
        },
        updated: new Date().toISOString()
      };
      emit('updated', savedItem);
    } else {
      savedItem = {
        fullStatement: formData.fullStatement,
        humanCodingScheme: formData.humanCodingScheme,
        abbreviatedStatement: formData.abbreviatedStatement,
        listEnumInSource: formData.listEnumInSource,
        conceptKeywords: formData.conceptKeywords,
        language: formData.language || 'en',
        educationalAlignment: formData.educationalAlignment,
        itemType: formData.itemType,
        subjects: formData.subjects,
        licence: formData.licence,
        notes: formData.notes,
        extensions: {
          'salt:type': formData.itemType || 'default'
        },
        children: [],
        associations: []
      };

      // Set isChildOf association if parent provided
      if (props.parentItem && formData.humanCodingScheme) {
        savedItem.associations.push({
          type: 'isChildOf',
          originNode: props.parentItem.humanCodingScheme,
          targetNode: formData.humanCodingScheme,
          originIdentifier: props.parentItem.humanCodingScheme,
          targetIdentifier: formData.humanCodingScheme
        });
      }

      savedItem.created = new Date().toISOString();
      emit('created', savedItem);
    }
  } catch (e) {
    error.value = 'Failed to save item: ' + e.message;
  } finally {
    saving.value = false;
  }
}
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

.easymde-wrapper {
  min-height: 200px;
}

.required-label::before {
  content: "*";
  color: red;
}
</style>
