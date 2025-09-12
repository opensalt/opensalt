<template>
  <!-- Backdrop -->
  <div v-if="props.show" class="modal-backdrop fade" :class="{ 'show': props.show }" @click="closeModal"></div>

  <!-- Modal -->
  <div class="modal fade" :class="{ 'show d-block': props.show }" tabindex="-1" id="addNewPublicKeyModal" aria-hidden="true" :style="{ display: props.show ? 'block' : 'none' }">
    <div class="modal-dialog" role="document" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addNewPublicKeyModalLabel">{{ isEdit ? 'Edit Public Key' : 'Add New Public Key' }}</h5>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="createItem" name="ls_public_key">
            <div class="row mb-3">
              <label for="ls_public_key_publicKey" class="col-sm-2 col-form-label">Public Key *</label>
              <div class="col-sm-10">
                <input
                  type="text"
                  class="form-control"
                  id="ls_public_key_publicKey"
                  name="ls_public_key[publicKey]"
                  v-model="formData.publicKey"
                  placeholder="Enter the public key"
                  :required="true"
                >
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
  publicKey: ''
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
    formData.publicKey = props.item.publicKey || '';
  } else {
    formData.publicKey = '';
  }

  loading.value = false;
}

function saveItem() {
  if (!formData.publicKey.trim()) {
    error.value = 'Public Key is required';
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
          publicKey: formData.publicKey,
          updated: new Date().toISOString()
        };
        emit('updated', savedItem);
      } else {
        savedItem = {
          identifier: 'public_key_' + Date.now(),
          publicKey: formData.publicKey,
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
      error.value = 'Failed to save public key: ' + e.message;
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
