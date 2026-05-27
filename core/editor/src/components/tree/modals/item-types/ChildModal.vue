<template>
  <!-- Backdrop -->
  <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events -->
  <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events -->
  <div
    v-if="props.show"
    class="modal-backdrop fade"
    :class="{ 'show': props.show }"
    @click="closeModal"
  />

  <!-- Modal -->
  <div
    :id="isEdit ? 'editItemModal' : 'addNewChildModal'"
    class="modal fade"
    :class="{ 'show d-block': props.show }"
    tabindex="-1"
    aria-hidden="true"
    :style="{ display: props.show ? 'block' : 'none' }"
  >
    <div
      class="modal-dialog modal-xl"
      role="document"
      style="width:99%"
      @click.stop
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="addNewChildModalLabel"
            class="modal-title"
          >
            {{ isEdit ? 'Edit Child Item' : 'Add New Child Item' }}
          </h5>
          <button
            type="button"
            class="btn-close"
            aria-label="Close"
            @click="closeModal"
          />
        </div>
        <div class="modal-body">
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
            id="ls_item"
            name="ls_item"
            @submit.prevent="saveItem"
          >
            <div class="row mb-3">
              <label
                for="ls_item_fullStatement"
                class="col-sm-2 col-form-label required-label"
              >Full Statement</label>
              <div class="col-sm-10">
                <EasyMDE
                  id="ls_item_fullStatement"
                  v-model="formData.fullStatement"
                  name="ls_item[fullStatement]"
                  :required="true"
                  placeholder="Enter the complete statement for this competency item"
                />
                <small class="text-muted">The full, complete statement of the competency.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="ls_item_humanCodingScheme"
                class="col-sm-2 col-form-label"
              >Human Coding Scheme</label>
              <div class="col-sm-10">
                <input
                  id="ls_item_humanCodingScheme"
                  v-model="formData.humanCodingScheme"
                  type="text"
                  class="form-control"
                  name="ls_item[humanCodingScheme]"
                  placeholder="e.g., CCSS.ELA-Literacy.RL.1.1"
                >
                <small class="text-muted">Human-readable identifier for the item, e.g., CCSS.ELA-Literacy.RL.1.1</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="ls_item_abbreviatedStatement"
                class="col-sm-2 col-form-label"
              >Abbreviated Statement</label>
              <div class="col-sm-10">
                <input
                  id="ls_item_abbreviatedStatement"
                  v-model="formData.abbreviatedStatement"
                  type="text"
                  class="form-control"
                  name="ls_item[abbreviatedStatement]"
                  placeholder="Short version of the statement"
                >
                <small class="text-muted">A short version of the statement for display purposes.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="ls_item_listEnumInSource"
                class="col-sm-2 col-form-label"
              >List Enum In Source</label>
              <div class="col-sm-10">
                <input
                  id="ls_item_listEnumInSource"
                  v-model="formData.listEnumInSource"
                  type="text"
                  class="form-control"
                  name="ls_item[listEnumInSource]"
                  placeholder="e.g., 1"
                >
                <small class="text-muted">The position of this item in the source enumeration.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="ls_item_conceptKeywords"
                class="col-sm-2 col-form-label"
              >Concept Keywords</label>
              <div class="col-sm-10">
                <input
                  id="ls_item_conceptKeywords"
                  v-model="formData.conceptKeywords"
                  type="text"
                  class="form-control"
                  name="ls_item[conceptKeywords]"
                  placeholder="e.g., reading, literature, analysis"
                >
                <small class="text-muted">Keywords or concepts associated with this item, comma-separated.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="ls_item_language"
                class="col-sm-2 col-form-label"
              >Language</label>
              <div class="col-sm-10">
                <select
                  id="ls_item_language"
                  v-model="formData.language"
                  class="form-select"
                  name="ls_item[language]"
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
                <small class="text-muted">The language of this item.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="ls_item_educationalAlignment"
                class="col-sm-2 col-form-label"
              >Education Level</label>
              <div class="col-sm-10">
                <MultiSelect
                  id="ls_item_educationalAlignment"
                  v-model="formData.educationalAlignment"
                  name="ls_item[educationalAlignment][]"
                  :options="availableEducationLevels"
                  option-value="code"
                  option-label="label"
                  placeholder="Select education levels"
                  search-placeholder="Search education levels..."
                />
                <small class="text-muted">Education levels associated with this item.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="ls_item_itemType"
                class="col-sm-2 col-form-label"
              >Item Type</label>
              <div class="col-sm-10">
                <SingleSelect
                  id="ls_item_itemType"
                  v-model="formData.itemType"
                  name="ls_item[itemType]"
                  :options="availableItemTypes"
                  option-value="id"
                  option-label="text"
                  placeholder="Select Item Type"
                  search-placeholder="Search item types..."
                  :allow-clear="true"
                  :creatable="true"
                />
                <small class="text-muted">The type of this item.</small>
              </div>
            </div>

            <SubjectSelector
              id="ls_item_subjects"
              ref="subjectSelectorRef"
              v-model="formData.subjects"
              name="ls_item[subjects][]"
              help-text="Subject areas associated with this item."
            />

            <LicenseSelector
              id="ls_item_licence"
              ref="licenseSelectorRef"
              v-model="formData.licence"
              name="ls_item[licence]"
              help-text="License governing the use of this item."
            />

            <div class="row mb-3">
              <label
                for="ls_item_notes"
                class="col-sm-2 col-form-label"
              >Notes</label>
              <div class="col-sm-10">
                <textarea
                  id="ls_item_notes"
                  v-model="formData.notes"
                  class="form-control"
                  name="ls_item[notes]"
                  rows="3"
                  placeholder="Additional notes or comments"
                />
                <small class="text-muted">Additional notes or comments about this item.</small>
              </div>
            </div>

            <AdditionalFields
              v-model="formData.additionalFields"
              :field-definitions="itemFieldDefinitions"
            />
          </form>
        </div>
        <div class="modal-footer">
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
            @click="saveItem"
          >
            <span
              v-if="saving"
              class="spinner-border spinner-border-sm me-2"
              role="status"
            />
            {{ isEdit ? 'Save Changes' : 'Create' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, nextTick } from 'vue';
import EasyMDE from '../../EasyMDE.vue';
import MultiSelect from '../../MultiSelect.vue';
import SingleSelect from '../../SingleSelect.vue';
import SubjectSelector from '../../common/SubjectSelector.vue';
import LicenseSelector from '../../common/LicenseSelector.vue';
import AdditionalFields from '../../fields/AdditionalFields.vue';
import { logger } from '../../../../utils/logger.js';
import { useItemTypeModal } from '../../../../composables/useItemTypeModal';
import { useAdditionalFields } from '../../../../composables/useAdditionalFields.js';
import educationLevels from '../../../../data/EducationLevel.json';

const props = defineProps({
  parentItem: {
    type: Object,
    default: null
  },
  show: {
    type: Boolean,
    default: false
  },
  itemType: {
    type: String,
    default: ''
  },
  item: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['created', 'updated', 'hidden']);

const { loading, error, saving, isEdit, closeModal } = useItemTypeModal(props, emit, { typeName: 'child' });

const { fieldDefinitions: itemFieldDefinitions, fetchFields: fetchItemFields } = useAdditionalFields();

const subjectSelectorRef = ref(null);
const licenseSelectorRef = ref(null);

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
  notes: '',
  additionalFields: {}
});

const availableEducationLevels = ref(educationLevels);

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
    await nextTick();
    await Promise.all([
      fetchItemTypes(),
      fetchItemFields('item'),
      subjectSelectorRef.value?.ensureLoaded(),
      licenseSelectorRef.value?.ensureLoaded()
    ]);
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
    formData.listEnumInSource = props.item.listEnumeration || props.item.listEnumInSource || '';
    formData.conceptKeywords = props.item.conceptKeywords || '';
    formData.language = props.item.language || '';
    // Handle educationalAlignment as array - the GET endpoint returns educationLevel as
    // a comma-separated string (e.g., "09, 10"), so split it into individual codes
    const eduLevel = props.item.educationLevel || props.item.educationalAlignment;
    if (Array.isArray(eduLevel)) {
      formData.educationalAlignment = eduLevel;
    } else if (typeof eduLevel === 'string' && eduLevel.trim()) {
      formData.educationalAlignment = eduLevel.split(',').map(s => s.trim()).filter(Boolean);
    } else {
      formData.educationalAlignment = [];
    }
    // Find the matching option by text property and use its id
    const matchingType = availableItemTypes.value.find(
      opt => opt.text === props.item.itemType
    );
    formData.itemType = matchingType ? matchingType.id : '';
    // Read from subjectURI and match by title to get IDs
    const availableSubjects = subjectSelectorRef.value?.availableSubjects || [];
    logger.debug('Available subjects:', availableSubjects);
    logger.debug('Item subjects:', props.item.subjectURI);
    const subjectIds = (props.item.subjectURI || []).map(uri => {
      const match = availableSubjects.find(opt => opt.title === uri.title);
      return match ? match.id : null;
    }).filter(Boolean);
    formData.subjects = subjectIds;
    formData.licence = props.item.licence || '';
    formData.notes = props.item.notes || '';
    formData.additionalFields = props.item.additionalFields || {};
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
    formData.additionalFields = {};
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
        listEnumeration: formData.listEnumInSource,
        conceptKeywords: formData.conceptKeywords,
        language: formData.language,
        educationalAlignment: formData.educationalAlignment,
        itemType: formData.itemType,
        subjects: formData.subjects,
        licence: formData.licence,
        notes: formData.notes,
        additionalFields: formData.additionalFields,
        extensions: {
          ...(props.item.extensions || {}),
        },
        updated: new Date().toISOString()
      };
      emit('updated', savedItem);
    } else {
      savedItem = {
        fullStatement: formData.fullStatement,
        humanCodingScheme: formData.humanCodingScheme,
        abbreviatedStatement: formData.abbreviatedStatement,
        listEnumeration: formData.listEnumInSource,
        conceptKeywords: formData.conceptKeywords,
        language: formData.language || 'en',
        educationalAlignment: formData.educationalAlignment,
        itemType: formData.itemType,
        subjects: formData.subjects,
        licence: formData.licence,
        notes: formData.notes,
        additionalFields: formData.additionalFields,
        extensions: {
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
