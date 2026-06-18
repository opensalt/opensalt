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
    id="addNewIdentifierModal"
    class="modal fade"
    :class="{ 'show d-block': props.show }"
    tabindex="-1"
    aria-hidden="true"
    :style="{ display: props.show ? 'block' : 'none' }"
  >
    <div
      class="modal-dialog"
      role="document"
      @click.stop
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="addNewIdentifierModalLabel"
            class="modal-title"
          >
            {{ isEdit ? 'Edit Identifier' : 'Add New Identifier' }}
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
            name="ls_identifier"
            @submit.prevent="createItem"
          >
            <div class="row mb-3">
              <label
                for="ls_identifier_identifier"
                class="col-sm-3 col-form-label required-label"
              >Identifier</label>
              <div class="col-sm-9">
                <input
                  id="ls_identifier_identifier"
                  v-model="formData.identifier"
                  type="text"
                  class="form-control"
                  name="ls_identifier[identifier]"
                  placeholder="Enter identifier (unique URI)"
                  spellcheck="false"
                  required
                >
                <small class="text-muted">The identifier for the parent object. It should be a unique URI.</small>
              </div>
            </div>
            <div class="row mb-3">
              <label
                for="ls_identifier_description"
                class="col-sm-3 col-form-label required-label"
              >Description</label>
              <div class="col-sm-9">
                <textarea
                  id="ls_identifier_description"
                  v-model="formData.description"
                  class="form-control"
                  name="ls_identifier[description]"
                  rows="3"
                  placeholder="Enter description"
                  required
                />
                <small class="text-muted">Description of the identifier.</small>
              </div>
            </div>
            <div class="row mb-3">
              <label
                for="ls_identifier_type"
                class="col-sm-3 col-form-label"
              >Identifier Type</label>
              <div class="col-sm-9">
                <input
                  id="ls_identifier_type"
                  v-model="formData.type"
                  type="text"
                  class="form-control"
                  name="ls_identifier[type]"
                  placeholder="e.g., URI, COCI, CD-ID, DOI"
                >
                <small class="text-muted">The type of the identifier (e.g., URI, COCI, CD-ID).</small>
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
                <small class="text-muted d-block">View or edit proprietary extension values for this identifier.</small>
              </div>
            </div>

            <ExtensionsEditor
              v-model="extensionsEditor.editable.value"
              v-model:show="extensionsEditor.show.value"
              entity-label="Identifier"
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
  kind: 'identifier',
  getRawExtensions: () => props.item?.extensions
});

const formData = reactive({
  identifier: '',
  description: '',
  type: ''
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
    // abbreviatedStatement maps to identifier
    formData.identifier = props.item.abbreviatedStatement || '';
    // fullStatement maps to description
    formData.description = props.item.fullStatement || '';
    // salt:idType is stored in extensions
    formData.type = props.item.extensions?.['salt:idType'] || '';
  } else {
    // Reset for new
    formData.identifier = '';
    formData.description = '';
    formData.type = '';
  }

  loading.value = false;
}

function saveItem() {
  if (!formData.identifier.trim()) {
    error.value = 'Identifier is required';
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
        identifier: formData.identifier,
        description: formData.description,
        type: formData.type,
        extensions: {
          ...extensionsEditor.buildExtensions(),
          'salt:type': 'identifier'
        },
        updated: new Date().toISOString()
      };
      emit('updated', savedItem);
    } else {
      savedItem = {
        identifier: formData.identifier,
        description: formData.description,
        type: formData.type,
        extensions: { ...extensionsEditor.buildExtensions(), 'salt:type': 'identifier' },
        parentId: props.parentItem?.identifier || null,
        created: new Date().toISOString(),
        children: []
      };
      emit('created', savedItem);
    }
  } catch (e) {
    error.value = 'Failed to save identifier: ' + e.message;
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

.form-control:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.required-label::before {
  content: "*";
  color: red;
}
</style>
