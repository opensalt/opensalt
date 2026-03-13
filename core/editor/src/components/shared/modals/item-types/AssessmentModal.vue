
<template>
  <!-- Backdrop -->
  <div v-if="props.show" class="modal-backdrop fade" :class="{ 'show': props.show }" @click="closeModal"></div>

  <!-- Modal -->
  <div class="modal fade" :class="{ 'show d-block': props.show }" tabindex="-1" id="addNewAssessmentModal" aria-hidden="true" :style="{ display: props.show ? 'block' : 'none' }">
    <div class="modal-dialog modal-xl" role="document" style="width:99%" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addNewAssessmentModalLabel"><span>{{ isEdit ? 'Edit Assessment' : 'Add New Assessment' }}</span></h5>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="loading" class="d-flex justify-content-center align-items-center p-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading form...</span>
            </div>
          </div>
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <span>{{ error }}</span>
          </div>
          <form v-else @submit.prevent="createItem" name="assessment_form">
            <div class="row mb-3">
              <label for="assessment_name" class="col-sm-2 col-form-label required-label">Name</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="assessment_name"
                  name="assessment[name]"
                  v-model="formData.name"
                  placeholder="Enter the name of the assessment"
                  required
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="assessment_description" class="col-sm-2 col-form-label required-label">Description</label>
              <div class="col-sm-10">
                <textarea
                  class="form-control"
                  id="assessment_description"
                  name="assessment[description]"
                  rows="4"
                  v-model="formData.description"
                  placeholder="Enter a description"
                  required
                ></textarea>
              </div>
            </div>

            <div class="row mb-3">
              <label for="assessment_deliveryType" class="col-sm-2 col-form-label">Delivery Type</label>
              <div class="col-sm-10">
                <select
                  class="form-select"
                  id="assessment_deliveryType"
                  name="assessment[deliveryType]"
                  v-model="formData.deliveryType"
                >
                  <option value="">Select Delivery Type</option>
                  <option value="in-person">In Person</option>
                  <option value="online">Online</option>
                  <option value="hybrid">Hybrid</option>
                </select>
                <small class="text-muted">The method of delivering this course</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="assessment_inLanguage" class="col-sm-2 col-form-label">Language</label>
              <div class="col-sm-10">
                <select
                  class="form-select"
                  id="assessment_inLanguage"
                  name="assessment[inLanguage]"
                  v-model="formData.inLanguage"
                >
                  <option value="">Select Language</option>
                  <option value="en">English</option>
                  <option value="es">Spanish</option>
                  <option value="fr">French</option>
                </select>
                <small class="text-muted">Language used to teach this course</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="assessment_keywords" class="col-sm-2 col-form-label">Keywords</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="assessment_keywords"
                  name="assessment[keywords]"
                  v-model="formData.keywords"
                  placeholder="Enter keywords separated by commas"
                >
                <small class="text-muted">Separate keywords with a comma (,)</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="assessment_webpage" class="col-sm-2 col-form-label">Webpage</label>
              <div class="col-sm-10">
                <input
                  type="url"
                  class="form-control"
                  id="assessment_webpage"
                  name="assessment[webpage]"
                  v-model="formData.webpage"
                  placeholder="Enter webpage URL"
                >
                <small class="text-muted">Webpage that describes this job</small>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
          <button type="button" class="btn btn-primary" @click="saveItem" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
            <span>{{ isEdit ? 'Update' : 'Create' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';

const props = defineProps({
  parentItem: Object,
  itemType: String,
  show: Boolean,
  item: Object
});

const emit = defineEmits(['created', 'updated', 'hidden']);

const loading = ref(false);
const error = ref('');
const saving = ref(false);

const isEdit = computed(() => !!props.item);

const formData = reactive({
  name: '',
  description: '',
  deliveryType: '',
  inLanguage: '',
  keywords: '',
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
  loading.value = true;
  error.value = '';

  if (isEdit.value) {
    // Map CASE properties to form fields
    formData.name = props.item.abbreviatedStatement || '';
    formData.description = props.item.fullStatement || '';
    formData.deliveryType = props.item.extensions?.['ceterms:deliveryType'] || '';
    formData.inLanguage = props.item.language || '';
    // Handle keywords array
    formData.keywords = Array.isArray(props.item.conceptKeywords)
      ? props.item.conceptKeywords.join(', ')
      : (props.item.conceptKeywords || '');
    // Handle extensions
    formData.webpage = props.item.extensions?.['ceterms:subjectWebpage'] || '';
  } else {
    // Reset for new
    formData.name = '';
    formData.description = '';
    formData.deliveryType = '';
    formData.inLanguage = '';
    formData.keywords = '';
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

  saving.value = true;
  error.value = '';

  try {
    let savedItem;
    if (isEdit.value) {
      // Map form fields back to CASE structure
      savedItem = {
        ...props.item,
        name: formData.name,
        description: formData.description,
        inLanguage: formData.inLanguage,
        keywords: formData.keywords,
        deliveryType: formData.deliveryType,
        webpage: formData.webpage,
        extensions: {
          ...props.item?.extensions,
          'salt:type': 'assessment'
        },
        updated: new Date().toISOString()
      };
      emit('updated', savedItem);
    } else {
      savedItem = {
        identifier: 'assessment_' + Date.now(),
        name: formData.name,
        description: formData.description,
        inLanguage: formData.inLanguage,
        keywords: formData.keywords,
        deliveryType: formData.deliveryType,
        webpage: formData.webpage,
        extensions: {
          'salt:type': 'assessment'
        },
        parentId: props.parentItem?.identifier || null,
        created: new Date().toISOString(),
        children: []
      };
      emit('created', savedItem);
    }
  } catch (e) {
    error.value = 'Failed to save assessment: ' + e.message;
  } finally {
    saving.value = false;
  }
}

function closeModal() {
 emit('hidden');
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
