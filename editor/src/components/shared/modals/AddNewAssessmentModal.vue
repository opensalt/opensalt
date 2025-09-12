
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
              <label for="assessment_name" class="col-sm-2 col-form-label">Name *</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="assessment_name" name="assessment[name]" v-model="formData.name" :required="true" placeholder="Enter the name of the assessment">
              </div>
            </div>

            <div class="row mb-3">
              <label for="assessment_description" class="col-sm-2 col-form-label">Description</label>
              <div class="col-sm-10">
                <textarea class="form-control" id="assessment_description" name="assessment[description]" rows="3" v-model="formData.description" placeholder="Enter a description"></textarea>
              </div>
            </div>

            <div class="row mb-3">
              <label for="assessment_deliveryType" class="col-sm-2 col-form-label">Delivery Type</label>
              <div class="col-sm-10">
                <select class="form-select" id="assessment_deliveryType" name="assessment[deliveryType]" v-model="formData.deliveryType">
                  <option value="">Select Delivery Type</option>
                  <option value="online">Online</option>
                  <option value="offline">Offline</option>
                  <option value="blended">Blended</option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label for="assessment_inLanguage" class="col-sm-2 col-form-label">Language</label>
              <div class="col-sm-10">
                <select class="form-select" id="assessment_inLanguage" name="assessment[inLanguage]" v-model="formData.inLanguage">
                  <option value="en">English</option>
                  <option value="es">Spanish</option>
                  <option value="fr">French</option>
                  <option value="de">German</option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label for="assessment_keywords" class="col-sm-2 col-form-label">Keywords</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="assessment_keywords" name="assessment[keywords]" v-model="formData.keywords" placeholder="Comma-separated keywords">
              </div>
            </div>

            <div class="row mb-3">
              <label for="assessment_webpage" class="col-sm-2 col-form-label">Webpage</label>
              <div class="col-sm-10">
                <input type="url" class="form-control" id="assessment_webpage" name="assessment[webpage]" v-model="formData.webpage" placeholder="https://example.com">
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
  inLanguage: 'en',
  keywords: '',
  webpage: ''
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    loadFormData();
  }
});

watch(() => props.itemType, (newType) => {
  if (newType && !isEdit.value) {
    formData.itemType = newType;
  }
}, { immediate: true });

function loadFormData() {
  loading.value = true;
  error.value = '';

  // Reset form
  formData.name = '';
  formData.description = '';
  formData.deliveryType = '';
  formData.inLanguage = 'en';
  formData.keywords = '';
  formData.webpage = '';

  if (props.item) {
    formData.name = props.item.name || '';
    formData.description = props.item.description || '';
    formData.deliveryType = props.item.deliveryType || '';
    formData.inLanguage = props.item.inLanguage || 'en';
    formData.keywords = props.item.keywords || '';
    formData.webpage = props.item.webpage || '';
  }

  loading.value = false;
}

function saveItem() {
  if (!formData.name.trim()) {
    error.value = 'Name is required';
    return;
  }

  saving.value = true;
  error.value = '';

  // Simulate saving item - in real app this would be an API call
  setTimeout(() => {
    try {
      let savedItem;
      if (isEdit.value) {
        savedItem = {
          ...props.item,
          ...formData,
          updated: new Date().toISOString()
        };
        emit('updated', savedItem);
      } else {
        savedItem = {
          identifier: 'assessment_' + Date.now(),
          ...formData,
          parentId: props.parentItem?.identifier || null,
          created: new Date().toISOString(),
          children: []
        };
        emit('created', savedItem);
      }
      if (modal.value) {
        modal.value.hide();
      }
    } catch (e) {
      error.value = 'Failed to save assessment: ' + e.message;
    } finally {
      saving.value = false;
    }
  }, 1000);
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
</style>
