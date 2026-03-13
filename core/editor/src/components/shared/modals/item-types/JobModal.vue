
<template>
  <!-- Backdrop -->
  <div v-if="props.show" class="modal-backdrop fade" :class="{ 'show': props.show }" @click="closeModal"></div>

  <!-- Modal -->
  <div class="modal fade" :class="{ 'show d-block': props.show }" tabindex="-1" id="addNewJobModal" aria-hidden="true" :style="{ display: props.show ? 'block' : 'none' }">
    <div class="modal-dialog modal-xl" role="document" style="width:99%" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addNewJobModalLabel">{{ isEdit ? 'Edit Job' : 'Add New Job' }}</h5>
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
          <form v-else @submit.prevent="createItem" name="job_item">
            <div class="row mb-3">
              <label for="job_item_title" class="col-sm-2 col-form-label required-label">Title</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="job_item_title"
                  name="job_item[title]"
                  v-model="formData.title"
                  placeholder="Enter the title of the job"
                  required
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="job_item_description" class="col-sm-2 col-form-label required-label">Description</label>
              <div class="col-sm-10">
                <textarea
                  class="form-control"
                  id="job_item_description"
                  name="job_item[description]"
                  rows="4"
                  v-model="formData.description"
                  placeholder="Enter a description of the job"
                  required
                ></textarea>
              </div>
            </div>

            <div class="row mb-3">
              <label for="job_item_codedNotation" class="col-sm-2 col-form-label">Coded Notation</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="job_item_codedNotation"
                  name="job_item[codedNotation]"
                  v-model="formData.codedNotation"
                  placeholder="Enter coded notation"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="job_item_keywords" class="col-sm-2 col-form-label">Keywords</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="job_item_keywords"
                  name="job_item[keywords]"
                  v-model="formData.keywords"
                  placeholder="Enter keywords separated by commas"
                >
                <small class="text-muted">Separate keywords with a comma (,)</small>
              </div>
            </div>

            <div class="row mb-3">
              <label for="job_item_webpage" class="col-sm-2 col-form-label">Webpage</label>
              <div class="col-sm-10">
                <input
                  type="url"
                  class="form-control"
                  id="job_item_webpage"
                  name="job_item[webpage]"
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
            {{ isEdit ? 'Update' : 'Create' }}
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
  title: '',
  description: '',
  codedNotation: '',
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
    // Map CASE item properties to form fields
    // fullStatement is the main text field in CASE, maps to title in the form
    formData.title = props.item.abbreviatedStatement || '';
    // fullStatement maps to description
    formData.description = props.item.fullStatement || '';
    // humanCodingScheme maps to codedNotation
    formData.codedNotation = props.item.humanCodingScheme || '';
    // conceptKeywords is an array in CASE, join as comma-separated string
    formData.keywords = Array.isArray(props.item.conceptKeywords)
      ? props.item.conceptKeywords.join(', ')
      : (props.item.conceptKeywords || '');
    // webpage is stored in extensions object under 'ceterms:subjectWebpage'
    formData.webpage = props.item.extensions?.['ceterms:subjectWebpage'] || '';
  } else {
    // Reset for new
    formData.title = '';
    formData.description = '';
    formData.codedNotation = '';
    formData.keywords = '';
    formData.webpage = '';
  }

  loading.value = false;
}

function saveItem() {
  if (!formData.title.trim()) {
    error.value = 'Title is required';
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
      savedItem = {
        ...props.item,
        title: formData.title,
        description: formData.description,
        codedNotation: formData.codedNotation,
        keywords: formData.keywords,
        webpage: formData.webpage,
        extensions: {
          ...(props.item.extensions || {}),
          'salt:type': 'job'
        },
        updated: new Date().toISOString()
      };
      emit('updated', savedItem);
    } else {
      savedItem = {
        identifier: 'item_' + Date.now(),
        title: formData.title,
        description: formData.description,
        codedNotation: formData.codedNotation,
        keywords: formData.keywords,
        webpage: formData.webpage,
        extensions: {
          'salt:type': 'job'
        },
        parentId: props.parentItem?.identifier || null,
        created: new Date().toISOString(),
        children: []
      };
      emit('created', savedItem);
    }
  } catch (e) {
    error.value = 'Failed to save item: ' + e.message;
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
