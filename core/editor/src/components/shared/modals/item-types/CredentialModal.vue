<template>
  <!-- Backdrop -->
  <div
    v-if="props.show"
    class="modal-backdrop fade"
    :class="{ 'show': props.show }"
    @click="closeModal"
  />

  <!-- Modal -->
  <div
    id="addNewCredentialModal"
    class="modal fade"
    :class="{ 'show d-block': props.show }"
    tabindex="-1"
    aria-hidden="true"
    :style="{ display: props.show ? 'block' : 'none' }"
  >
    <div
      class="modal-dialog modal-xl"
      role="document"
      style="width:99%"
      @click.stop
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="addNewCredentialModalLabel"
            class="modal-title"
          >
            {{ isEdit ? 'Edit Credential' : 'Add New Credential' }}
          </h5>
          <button
            type="button"
            class="btn-close"
            aria-label="Close"
            @click="closeModal"
          />
        </div>
        <div class="modal-body overflow-y-auto">
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
          <div v-else>
            <!-- OB3 Definer Widget Container -->
            <div
              id="ob3-definer"
              data-submit-text="Save"
            />
          </div>
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
import { ref, reactive, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';

import '@opensalt/ob3-definer/dist/ob3-definer.js';
import '@opensalt/ob3-definer/dist/ob3-definer.css';
import { logger } from '../../../../utils/logger.js';
import { useItemTypeModal } from '../../../../composables/useItemTypeModal';

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

const { loading, error, saving, isEdit, resetState } = useItemTypeModal(props, emit, { typeName: 'credential' });

// Store the achievement definition JSON from the widget
const achievementData = ref(null);

// Ref to track the resolve function for widget submission
const saveDefinitionResolve = ref(null);

// Form data to be saved
const formData = reactive({
  credential: ''
});

// Load existing achievement data for edit mode
function loadAchievementData() {
  loading.value = true;
  error.value = '';

  if (props.item && props.item.extensions && props.item.extensions['ob3']) {
    try {
      // Parse the existing OB3 achievement JSON from extensions
      achievementData.value = typeof props.item.extensions['ob3'] === 'string'
        ? JSON.parse(props.item.extensions['ob3'])
        : props.item.extensions['ob3'];
    } catch (e) {
      error.value = 'Failed to load credential data: ' + e.message;
      achievementData.value = {};
    }
  } else if (props.item) {
    // Try to reconstruct from item properties if no extensions.ob3
    achievementData.value = {
      name: props.item.abbreviatedStatement || '',
      description: props.item.fullStatement || '',
      humanCode: props.item.humanCodingScheme || '',
      inLanguage: props.item.language || '',
      tag: props.item.conceptKeywords || []
    };
  } else {
    // New credential - start with empty object
    achievementData.value = {};
  }

  loading.value = false;
}

// Open the widget when modal is shown
async function openWidget() {
  await nextTick();

  const element = document.querySelector('#ob3-definer');
  if (!element) {
    logger.error('[AddNewCredentialModal] #ob3-definer element NOT FOUND in DOM!');
    return;
  }

  window.dispatchEvent(new CustomEvent('ob3-open', {
    detail: {
      selector: '#ob3-definer',
      achievement: achievementData.value  // Pass data directly in event
    }
  }));
}

// Handle saveDefinition event from the widget
function handleSaveDefinition(event) {
  achievementData.value = event.detail;
  formData.credential = JSON.stringify(event.detail);

  // Resolve the promise if we're waiting for it
  if (saveDefinitionResolve.value) {
    saveDefinitionResolve.value(true);
    saveDefinitionResolve.value = null;
  }
}

// New function to trigger widget submission and wait for result
async function submitWidgetForm() {
  return new Promise((resolve) => {
    // Set up the resolve callback
    saveDefinitionResolve.value = resolve;

    // Find and click the submit button inside the widget
    const widgetContainer = document.querySelector('#ob3-definer');
    if (widgetContainer) {
      const submitButton = widgetContainer.querySelector('button[type="submit"], input[type="submit"]');
      if (submitButton) {
        submitButton.click();
      } else {
        // Fallback: try to submit any form inside the widget
        const form = widgetContainer.querySelector('form');
        if (form) {
          form.requestSubmit();
        } else {
          // If no form found, dispatch a custom event that the widget might listen to
          window.dispatchEvent(new CustomEvent('ob3-submit'));
        }
      }
    }

    // Set a timeout to detect validation failure
    // If no saveDefinition event within 500ms, assume validation failed
    setTimeout(() => {
      if (saveDefinitionResolve.value) {
        saveDefinitionResolve.value(false);
        saveDefinitionResolve.value = null;
      }
    }, 500);
  });
}

// Save the item
async function saveItem() {
  saving.value = true;
  error.value = '';

  try {
    // Step 1: Submit the widget form and wait for result
    const widgetValid = await submitWidgetForm();

    if (!widgetValid) {
      // Validation failed - widget shows its own errors
      // Keep the modal open, just reset saving state
      saving.value = false;
      return;
    }

    // Step 2: Widget validation passed, formData.credential is now populated
    if (!formData.credential) {
      error.value = 'Please fill in the credential details';
      saving.value = false;
      return;
    }

    // Step 3: Proceed with save logic (existing code)
    const credentialInfo = JSON.parse(formData.credential);
    let savedItem;

    if (isEdit.value) {
      // Update existing item
      savedItem = {
        ...props.item,
        abbreviatedStatement: credentialInfo.name || props.item.abbreviatedStatement,
        fullStatement: credentialInfo.description || props.item.fullStatement,
        humanCodingScheme: credentialInfo.humanCode || props.item.humanCodingScheme,
        language: credentialInfo.inLanguage || props.item.language,
        conceptKeywords: credentialInfo.tag || props.item.conceptKeywords,
        credential: formData.credential,
        extensions: {
          ...(props.item.extensions || {}),
          'ob3': formData.credential
        },
        updated: new Date().toISOString()
      };
      emit('updated', savedItem);
    } else {
      // Create new item
      savedItem = {
        identifier: 'credential_' + Date.now(),
        abbreviatedStatement: credentialInfo.name || '',
        fullStatement: credentialInfo.description || '',
        humanCodingScheme: credentialInfo.humanCode || '',
        language: credentialInfo.inLanguage || '',
        conceptKeywords: credentialInfo.tag || [],
        credential: formData.credential,
        extensions: {
          'ob3': formData.credential
        },
        parentId: props.parentItem?.identifier || null,
        created: new Date().toISOString(),
        children: []
      };
      emit('created', savedItem);
    }

    // Step 4: Close modal on success
    closeModal();

  } catch (e) {
    error.value = 'Failed to save credential: ' + e.message;
  } finally {
    saving.value = false;
  }
}

function closeModal() {
  resetState();
  window.dispatchEvent(new CustomEvent('ob3-close'));
  emit('hidden');
}

// Watch for modal show to open the widget
watch(() => props.show, (newVal) => {
  if (newVal) {
    loadAchievementData();
    openWidget();
  } else {
    // Close the widget when modal is hidden
    window.dispatchEvent(new CustomEvent('ob3-close'));
  }
}, { immediate: true });

// Watch for item changes (edit mode)
watch(() => props.item, (newItem) => {
  if (newItem) {
    loadAchievementData();
  }
}, { immediate: true });

// Set up event listener for saveDefinition
onMounted(() => {
  window.addEventListener('saveDefinition', handleSaveDefinition);
});

// Clean up event listener
onBeforeUnmount(() => {
  window.removeEventListener('saveDefinition', handleSaveDefinition);
});
</script>

<style>
#ob3-definer button[type="submit"] {
    display: none;
}
</style>

<style scoped>
.modal-dialog {
  max-width: 95vw;
}

.modal-body {
  max-height: 80vh;
}

/* Ensure the widget container has enough space */
#ob3-definer {
  min-height: 400px;
}
</style>
