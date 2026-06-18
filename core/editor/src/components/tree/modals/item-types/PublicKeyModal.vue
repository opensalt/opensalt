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
    id="addNewPublicKeyModal"
    class="modal fade"
    :class="{ 'show d-block': props.show }"
    tabindex="-1"
    aria-hidden="true"
    :style="{ display: props.show ? 'block' : 'none' }"
  >
    <div
      class="modal-dialog modal-xl"
      role="document"
      @click.stop
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="addNewPublicKeyModalLabel"
            class="modal-title"
          >
            {{ isEdit ? 'Edit Public Key' : 'Add New Public Key' }}
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
            name="ls_public_key"
            @submit.prevent="createItem"
          >
            <div class="row mb-3">
              <label
                for="ls_public_key_publicKey"
                class="col-sm-3 col-form-label required-label"
              >Public Key</label>
              <div class="col-sm-9">
                <textarea
                  id="ls_public_key_publicKey"
                  v-model="formData.publicKey"
                  class="form-control"
                  name="ls_public_key[publicKey]"
                  spellcheck="false"
                  rows="6"
                  placeholder="Paste the public key here as a JWK or certificate"
                  required
                />
                <small class="text-muted">Paste the public key here as a JWK or certificate.</small>
              </div>
            </div>
            <div
              v-if="false"
              class="row mb-3"
            >
              <label
                for="ls_public_key_type"
                class="col-sm-3 col-form-label"
              >Type</label>
              <div class="col-sm-9">
                <select
                  id="ls_public_key_type"
                  v-model="formData.type"
                  class="form-select"
                  name="ls_public_key[type]"
                >
                  <option value="">
                    Select Type
                  </option>
                  <option value="jwk">
                    JWK
                  </option>
                  <option value="certificate">
                    Certificate
                  </option>
                </select>
                <small class="text-muted">The type of the public key.</small>
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
                <small class="text-muted d-block">View or edit proprietary extension values for this public key.</small>
              </div>
            </div>

            <ExtensionsEditor
              v-model="extensionsEditor.editable.value"
              v-model:show="extensionsEditor.show.value"
              entity-label="Public Key"
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
            data-testid="save-item"
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
import { ref, reactive, watch, computed } from 'vue';
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

const loading = ref(false);
const error = ref('');
const saving = ref(false);

const isEdit = computed(() => !!props.item);

const extensionsEditor = useExtensionsEditor({
  scope: 'item',
  kind: 'public_key',
  getRawExtensions: () => props.item?.extensions
});

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
  // Seed extensions working copy so saves preserve existing extensions even
  // when the overlay is never opened.
  extensionsEditor.seed();
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
          ...extensionsEditor.buildExtensions(),
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
          ...extensionsEditor.buildExtensions(),
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
