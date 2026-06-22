
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
    id="addNewCourseModal"
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
            id="addNewCourseModalLabel"
            class="modal-title"
          >
            {{ isEdit ? 'Edit Course' : 'Add New Course' }}
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
            name="course_item"
            @submit.prevent="createItem"
          >
            <div class="row mb-3">
              <label
                for="course_item_name"
                class="col-sm-2 col-form-label required-label"
              >Name</label>
              <div class="col-sm-10">
                <input
                  id="course_item_name"
                  v-model="formData.name"
                  type="text"
                  class="form-control"
                  name="course_item[name]"
                  required
                  placeholder="Enter the name of the course"
                >
                <small class="text-muted">Name or title of the course.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="course_item_description"
                class="col-sm-2 col-form-label required-label"
              >Description</label>
              <div class="col-sm-10">
                <textarea
                  id="course_item_description"
                  v-model="formData.description"
                  class="form-control"
                  name="course_item[description]"
                  rows="3"
                  required
                  placeholder="Enter a description of the course"
                />
                <small class="text-muted">Description of this course.</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="course_item_codedNotation"
                class="col-sm-2 col-form-label"
              >Coded Notation</label>
              <div class="col-sm-10">
                <input
                  id="course_item_codedNotation"
                  v-model="formData.codedNotation"
                  type="text"
                  class="form-control"
                  name="course_item[codedNotation]"
                  placeholder="Identifier for the course, e.g., ENG101"
                >
                <small class="text-muted">Identifier for this course, eg. ENG101</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="course_item_inLanguage"
                class="col-sm-2 col-form-label"
              >Language</label>
              <div class="col-sm-10">
                <select
                  id="course_item_inLanguage"
                  v-model="formData.inLanguage"
                  class="form-select"
                  name="course_item[inLanguage]"
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
                <small class="text-muted">Language used to teach this course</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="course_item_deliveryType"
                class="col-sm-2 col-form-label"
              >Delivery Type</label>
              <div class="col-sm-10">
                <select
                  id="course_item_deliveryType"
                  v-model="formData.deliveryType"
                  class="form-select"
                  name="course_item[deliveryType]"
                >
                  <option value="">
                    Select Delivery Type
                  </option>
                  <option value="in-person">
                    In Person
                  </option>
                  <option value="online">
                    Online
                  </option>
                  <option value="hybrid">
                    Hybrid
                  </option>
                </select>
                <small class="text-muted">The method of delivering this course</small>
              </div>
            </div>

            <div class="row mb-3">
              <label
                for="course_item_webpage"
                class="col-sm-2 col-form-label"
              >Webpage</label>
              <div class="col-sm-10">
                <input
                  id="course_item_webpage"
                  v-model="formData.webpage"
                  type="url"
                  class="form-control"
                  name="course_item[webpage]"
                  placeholder="https://example.com"
                >
                <small class="text-muted">Webpage that describes this course.</small>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-2 col-form-label">
                Extensions
              </div>
              <div class="col-sm-10">
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  data-testid="open-extensions"
                  @click="extensionsEditor.open()"
                >
                  Edit extensions
                </button>
                <small class="text-muted d-block">View or edit proprietary extension values for this course.</small>
              </div>
            </div>

            <ExtensionsEditor
              v-model="extensionsEditor.editable.value"
              v-model:show="extensionsEditor.show.value"
              entity-label="Course"
              :reserved-keys="extensionsEditor.reservedKeys"
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
            {{ isEdit ? 'Update' : 'Create' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { useItemTypeModal } from '../../../../composables/useItemTypeModal';
import ExtensionsEditor from '../../../shared/ExtensionsEditor.vue';
import { useExtensionsEditor } from '../../../../composables/useExtensionsEditor.js';

const props = defineProps({
  parentItem: {
    type: Object,
    default: null
  },
  itemType: {
    type: String,
    default: ''
  },
  show: {
    type: Boolean,
    default: false
  },
  item: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['created', 'updated', 'hidden']);

const extensionsEditor = useExtensionsEditor({
  scope: 'item',
  kind: 'course',
  getRawExtensions: () => props.item?.extensions
});
const { loading, error, saving, isEdit, saveItem: doSaveItem, closeModal } = useItemTypeModal(props, emit, {
  typeName: 'course',
  getEditableExtensions: () => extensionsEditor.editable.value
});

const formData = reactive({
  name: '',
  description: '',
  codedNotation: '',
  inLanguage: '',
  deliveryType: '',
  webpage: ''
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    loadFormData();
  }
});

watch(() => props.item, (newItem) => {
  if (newItem) {
    loadFormData();
  }
}, { immediate: true });

watch(() => props.itemType, (newType) => {
  if (newType && !isEdit.value) {
    formData.itemType = newType;
  }
}, { immediate: true });

function loadFormData() {
  // Seed extensions working copy so saves preserve existing extensions even
  // when the overlay is never opened.
  extensionsEditor.seed();
  loading.value = true;
  error.value = '';

  if (isEdit.value) {
    // Map CASE properties to form fields
    // abbreviatedStatement maps to name
    formData.name = props.item.abbreviatedStatement || '';
    // fullStatement maps to description
    formData.description = props.item.fullStatement || '';
    // humanCodingScheme maps to codedNotation
    formData.codedNotation = props.item.humanCodingScheme || '';
    // language maps to inLanguage
    formData.inLanguage = props.item.language || '';
    // ceterms:deliveryType is stored in extensions
    formData.deliveryType = props.item.extensions?.['ceterms:deliveryType'] || '';
    // ceterms:subjectWebpage is stored in extensions
    formData.webpage = props.item.extensions?.['ceterms:subjectWebpage'] || '';
  } else {
    // Reset for new
    formData.name = '';
    formData.description = '';
    formData.codedNotation = '';
    formData.inLanguage = '';
    formData.deliveryType = '';
    formData.webpage = '';
  }

  loading.value = false;
}

function saveItem() {
  if (!formData.name.trim()) {
    error.value = 'Name is required';
    return;
  }

  if (!formData.description.trim()) {
    error.value = 'Description is required';
    return;
  }

  const fields = {
    name: formData.name,
    description: formData.description,
    codedNotation: formData.codedNotation,
    inLanguage: formData.inLanguage,
    deliveryType: formData.deliveryType,
    webpage: formData.webpage
  };

  if (!isEdit.value) {
    fields.identifier = 'item_' + Date.now();
  }

  doSaveItem(fields);
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

textarea.form-control {
  resize: vertical;
  min-height: 80px;
}

.required-label::before {
  content: "*";
  color: red;
}
</style>
