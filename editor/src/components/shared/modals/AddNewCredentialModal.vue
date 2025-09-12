
<template>
  <!-- Backdrop -->
  <div v-if="props.show" class="modal-backdrop fade" :class="{ 'show': props.show }" @click="closeModal"></div>

  <!-- Modal -->
  <div class="modal fade" :class="{ 'show d-block': props.show }" tabindex="-1" id="addNewCredentialModal" aria-hidden="true" :style="{ display: props.show ? 'block' : 'none' }">
    <div class="modal-dialog modal-xl" role="document" style="width:99%" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addNewCredentialModalLabel">{{ props.item ? 'Edit Credential' : 'Add New Credential' }}</h5>
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
          <form v-else @submit.prevent="createItem" name="ls_item">
            <div class="row mb-3">
              <label for="ls_item_credential" class="col-sm-2 col-form-label">Credential</label>
              <div class="col-sm-10">
                <input type="hidden" name="ls_item[credential]" v-model="formData.credential">
              </div>
            </div>
            <div class="row mb-3">
              <label for="ls_item__isCredentialForm" class="col-sm-2 col-form-label">Is Credential Form</label>
              <div class="col-sm-10">
                <input type="hidden" name="ls_item[_isCredentialForm]" v-model="formData._isCredentialForm">
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
  credential: '',
  _isCredentialForm: ''
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    loadFormData();
  }
});

function loadFormData() {
  loading.value = true;
  error.value = '';

  // Reset form
  formData.credential = '';
  formData._isCredentialForm = '';

  // If editing, populate form with item data
  if (props.item) {
    formData.credential = props.item.credential || '';
    formData._isCredentialForm = props.item._isCredentialForm || '';
  }

  loading.value = false;
}

function createItem() {
  if (!formData.credential.trim()) {
    error.value = 'Credential is required';
    return;
  }

  saving.value = true;
  error.value = '';

  // Simulate creating item - in a real application, this would be an API call
  setTimeout(() => {
    try {
      const newItem = {
        identifier: 'credential_' + Date.now(),
        credential: formData.credential,
        _isCredentialForm: formData._isCredentialForm,
        parentId: props.parentItem?.identifier || null,
        created: new Date().toISOString(),
        children: []
      };

      emit('created', newItem);
      if (modal.value) {
        modal.value.hide();
      }
    } catch (e) {
      error.value = 'Failed to create credential: ' + e.message;
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
