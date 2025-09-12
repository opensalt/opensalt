
<template>
  <!-- Backdrop -->
  <div v-if="props.show" class="modal-backdrop fade" :class="{ 'show': props.show }" @click="closeModal"></div>

  <!-- Modal -->
  <div class="modal fade" :class="{ 'show d-block': props.show }" tabindex="-1" id="addNewOrganizationModal" aria-hidden="true" :style="{ display: props.show ? 'block' : 'none' }">
    <div class="modal-dialog modal-xl" role="document" style="width:99%" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addNewOrganizationModalLabel">{{ props.item ? 'Edit Organization' : 'Add New Organization' }}</h5>
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
          <form v-else @submit.prevent="createItem" name="org_form">
            <div class="row mb-3">
              <label for="org_name" class="col-sm-2 col-form-label">Name *</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="org_name"
                  name="org[name]"
                  v-model="formData.name"
                  required
                  placeholder="Enter organization name"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="org_description" class="col-sm-2 col-form-label">Description</label>
              <div class="col-sm-10">
                <textarea
                  class="form-control"
                  id="org_description"
                  name="org[description]"
                  rows="3"
                  v-model="formData.description"
                  placeholder="Enter description"
                ></textarea>
              </div>
            </div>

            <div class="row mb-3">
              <label for="org_type" class="col-sm-2 col-form-label">Type</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="org_type"
                  name="org[type]"
                  v-model="formData.type"
                  placeholder="e.g., Educational Institution"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="org_logo" class="col-sm-2 col-form-label">Logo</label>
              <div class="col-sm-10">
                <input
                  type="file"
                  class="form-control"
                  id="org_logo"
                  name="org[logo]"
                  @change="handleFileUpload($event)"
                  placeholder="Upload logo"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="org_legalName" class="col-sm-2 col-form-label">Legal Name</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="org_legalName"
                  name="org[legalName]"
                  v-model="formData.legalName"
                  placeholder="Enter legal name"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="org_ctid" class="col-sm-2 col-form-label">CTID</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="org_ctid"
                  name="org[ctid]"
                  v-model="formData.ctid"
                  placeholder="Enter CTID"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="org_webpage" class="col-sm-2 col-form-label">Webpage</label>
              <div class="col-sm-10">
                <input
                  type="url"
                  class="form-control"
                  id="org_webpage"
                  name="org[webpage]"
                  v-model="formData.webpage"
                  placeholder="Enter webpage URL"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="org_jurisdiction" class="col-sm-2 col-form-label">Jurisdiction</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="org_jurisdiction"
                  name="org[jurisdiction]"
                  v-model="formData.jurisdiction"
                  placeholder="e.g., Province, Country"
                >
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
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
import { ref, reactive, watch } from 'vue';

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

const formData = reactive({
  name: '',
  description: '',
  type: '',
  logo: '',
  legalName: '',
  ctid: '',
  webpage: '',
  jurisdiction: ''
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    loadFormData();
  }
});

watch(() => props.itemType, (newType) => {
  if (newType) {
    formData.type = newType;
  }
}, { immediate: true });

function loadFormData() {
  loading.value = true;
  error.value = '';

  // Reset form
  formData.name = '';
  formData.description = '';
  formData.type = '';
  formData.logo = '';
  formData.legalName = '';
  formData.ctid = '';
  formData.webpage = '';
  formData.jurisdiction = '';

  if (props.item) {
    formData.name = props.item.name || '';
    formData.description = props.item.description || '';
    formData.type = props.item.type || '';
    formData.logo = props.item.logo || '';
    formData.legalName = props.item.legalName || '';
    formData.ctid = props.item.ctid || '';
    formData.webpage = props.item.webpage || '';
    formData.jurisdiction = props.item.jurisdiction || '';
  }

  loading.value = false;
}

function createItem() {
  if (!formData.name.trim()) {
    error.value = 'Name is required';
    return;
  }

  saving.value = true;
  error.value = '';

  // Simulate API call
  setTimeout(() => {
    try {
      const newItem = {
        identifier: 'org_' + Date.now(),
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
      error.value = 'Failed to create organization: ' + e.message;
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
