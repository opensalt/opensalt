<template>
  <!-- Backdrop -->
  <div v-if="props.show" class="modal-backdrop fade" :class="{ 'show': props.show }" @click="closeModal"></div>

  <!-- Modal -->
  <div class="modal fade" :class="{ 'show d-block': props.show }" tabindex="-1" id="addNewIdentifierModal" aria-hidden="true" :style="{ display: props.show ? 'block' : 'none' }">
    <div class="modal-dialog" role="document" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addNewIdentifierModalLabel">{{ isEdit ? 'Edit Identifier' : 'Add New Identifier' }}</h5>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="createItem" name="ls_identifier">
            <div class="row mb-3">
              <label for="ls_identifier_identifier" class="col-sm-3 col-form-label">Identifier *</label>
              <div class="col-sm-9">
                <input
                  type="text"
                  class="form-control"
                  id="ls_identifier_identifier"
                  name="ls_identifier[identifier]"
                  v-model="formData.identifier"
                  placeholder="Enter the identifier (unique URI)"
                  :required="true"
                >
              </div>
            </div>
            <div class="row mb-3">
              <label for="ls_identifier_description" class="col-sm-3 col-form-label">Description</label>
              <div class="col-sm-9">
                <textarea
                  class="form-control"
                  id="ls_identifier_description"
                  name="ls_identifier[description]"
                  v-model="formData.description"
                  rows="3"
                  placeholder="Enter the description"
                ></textarea>
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
  identifier: '',
  description: ''
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    loadFormData();
  }
});

function loadFormData() {
  loading.value = true;
  error.value = '';

  if (isEdit.value) {
    formData.identifier = props.item.identifier || '';
    formData.description = props.item.description || '';
  } else {
    formData.identifier = '';
    formData.description = '';
  }

  loading.value = false;
}

function saveItem() {
  if (!formData.identifier.trim()) {
    error.value = 'Identifier is required';
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
          identifier: formData.identifier,
          description: formData.description,
          updated: new Date().toISOString()
        };
        emit('updated', savedItem);
      } else {
        savedItem = {
          identifier: 'identifier_' + Date.now(),
          itemType: 'Identifier',
          identifier: formData.identifier,
          description: formData.description,
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
      error.value = 'Failed to save identifier: ' + e.message;
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
  max-width: 500px;
}

.form-control:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
