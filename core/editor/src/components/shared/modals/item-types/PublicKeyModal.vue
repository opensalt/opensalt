<template>
  <!-- Backdrop -->
  <div v-if="props.show" class="modal-backdrop fade" :class="{ 'show': props.show }" @click="closeModal"></div>

  <!-- Modal -->
  <div class="modal fade" :class="{ 'show d-block': props.show }" tabindex="-1" id="addNewPublicKeyModal" aria-hidden="true" :style="{ display: props.show ? 'block' : 'none' }">
    <div class="modal-dialog modal-xl" role="document" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addNewPublicKeyModalLabel">{{ isEdit ? 'Edit Public Key' : 'Add New Public Key' }}</h5>
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
          <form v-else @submit.prevent="createItem" name="ls_public_key">
            <div class="row mb-3">
              <label for="ls_public_key_publicKey" class="col-sm-3 col-form-label required-label">Public Key</label>
              <div class="col-sm-9">
                <textarea
                  class="form-control"
                  id="ls_public_key_publicKey"
                  name="ls_public_key[publicKey]"
                  v-model="formData.publicKey"
                  spellcheck="false"
                  rows="6"
                  placeholder="Paste the public key here as a JWK or certificate"
                  required
                ></textarea>
                <small class="text-muted">Paste the public key here as a JWK or certificate.</small>
              </div>
            </div>
            <div class="row mb-3" v-if="false">
              <label for="ls_public_key_type" class="col-sm-3 col-form-label">Type</label>
              <div class="col-sm-9">
                <select
                  class="form-select"
                  id="ls_public_key_type"
                  name="ls_public_key[type]"
                  v-model="formData.type"
                >
                  <option value="">Select Type</option>
                  <option value="jwk">JWK</option>
                  <option value="certificate">Certificate</option>
                </select>
                <small class="text-muted">The type of the public key.</small>
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
  publicKey: '',
  kid: '',
  type: 'jwk'
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

function loadFormData() {
  loading.value = true;
  error.value = '';

  if (isEdit.value) {
    // Map CASE properties to form fields
    // fullStatement contains the public key as JSON
    formData.publicKey = props.item.fullStatement || '';
    // abbreviatedStatement contains the kid
    formData.kid = props.item.abbreviatedStatement || '';
    // Note: In edit mode, kid is read-only (derived from key)
    // salt:kid is stored in extensions
    formData.type = props.item.extensions?.['salt:kid'] || 'jwk';
  } else {
    // Reset for new
    formData.publicKey = '';
    formData.kid = '';
    formData.type = 'jwk';
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

  try {
    let savedItem;
    if (isEdit.value) {
      // Map form fields back to CASE structure
      savedItem = {
        ...props.item,
        publicKey: formData.publicKey,
        kid: formData.kid,
        type: formData.type,
        extensions: {
          ...(props.item?.extensions || {}),
          'salt:type': 'public_key'
        },
        updated: new Date().toISOString()
      };
      emit('updated', savedItem);
    } else {
      savedItem = {
        identifier: 'public_key_' + Date.now(),
        publicKey: formData.publicKey,
        kid: formData.kid,
        type: formData.type,
        extensions: {
          'salt:type': 'public_key'
        },
        parentId: props.parentItem?.identifier || null,
        created: new Date().toISOString(),
        children: []
      };
      emit('created', savedItem);
    }
  } catch (e) {
    error.value = 'Failed to save public key: ' + e.message;
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
  min-height: 120px;
}

.required-label::before {
  content: "*";
  color: red;
}
</style>
