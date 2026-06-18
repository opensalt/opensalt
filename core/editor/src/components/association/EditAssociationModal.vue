<template>
  <div
    id="editAssociationModal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="editAssociationModalLabel"
    aria-hidden="true"
  >
    <div
      class="modal-dialog modal-xl"
      role="document"
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="editAssociationModalLabel"
            class="modal-title"
          >
            {{ modalTitle }}
          </h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          />
        </div>
        <div class="modal-body">
          <div
            v-if="error"
            class="alert alert-danger mb-3"
            role="alert"
          >
            {{ error }}
          </div>

          <!-- Association visualization -->
          <div class="container-fluid mb-4">
            <div class="row vcenter">
              <!-- Left Side Item Display -->
              <AssociationItemDisplay
                :item-data="leftSideItemData"
                :is-loading="leftSideIsLoading"
                :framework-title="leftSideFrameworkTitle"
                :target-type-info="leftSideTargetTypeInfo"
                :fetch-error="leftSideFetchError"
                :is-selected="isLeftSideSelected"
                :fallback-text="leftSideFallbackText"
                :display-text="leftSideDisplayText"
                side="origin"
              />

              <!-- Direction Switch Button -->
              <DirectionSwitchButton
                :left-side-text="leftSideShortText"
                :right-side-text="rightSideShortText"
                :direction-icon="directionIcon"
                @switch="switchDirection"
              />

              <!-- Right Side Item Display -->
              <AssociationItemDisplay
                :item-data="rightSideItemData"
                :is-loading="rightSideIsLoading"
                :framework-title="rightSideFrameworkTitle"
                :target-type-info="rightSideTargetTypeInfo"
                :fetch-error="rightSideFetchError"
                :is-selected="isRightSideSelected"
                :fallback-text="rightSideFallbackText"
                :display-text="rightSideDisplayText"
                side="destination"
              />
            </div>
          </div>

          <!-- Association form -->
          <div class="form-horizontal">
            <!-- Type Selector -->
            <AssociationTypeSelector
              v-model="formData.type"
              v-model:custom-type="customType"
              :is-disabled="isTypeDropdownDisabled"
              :is-valid-custom-type="isValidCustomType"
              :types="prioritizedTypes"
              @change="onTypeChange"
            />

            <!-- Annotation Field -->
            <div class="row mb-3">
              <label
                for="editAssociationFormAnnotation"
                class="col-sm-3 col-form-label text-end"
              >
                Annotation
              </label>
              <div class="col-sm-9">
                <textarea
                  id="editAssociationFormAnnotation"
                  v-model="formData.annotation"
                  class="form-control"
                  rows="3"
                  placeholder="Optional annotation or description for this association"
                />
              </div>
            </div>

            <!-- Exemplar-specific fields -->
            <ExemplarFields
              v-if="isExemplarType"
              v-model:url="formData.exemplarUrl"
              :url-error="exemplarUrlError"
            />

            <!-- Manual Destination Fields -->
            <DestinationFields
              v-if="isAddMode && !destinationItem && !isExemplarType"
              v-model:uri="formData.destinationUri"
              v-model:identifier="formData.destinationIdentifier"
              v-model:title="formData.destinationTitle"
              v-model:target-type="formData.destinationTargetType"
              :is-origin="isReversed"
            />
            <div
              v-if="destinationUriError"
              class="text-danger small mt-1 ms-3 mb-3"
            >
              {{ destinationUriError }}
            </div>

            <!-- Group Selector -->
            <div
              v-if="showGroupSelector && availableGroups.length > 0"
              id="editAssociationFormGroupHolderOuter"
              class="row mb-3"
            >
              <label
                for="editAssociationFormGroup"
                class="col-sm-3 col-form-label required text-end"
              >
                Association Group
              </label>
              <div
                id="editAssociationFormGroupHolder"
                class="col-sm-9"
              >
                <select
                  id="editAssociationFormGroup"
                  v-model="formData.groupId"
                  class="form-select"
                >
                  <option value="default">
                    None
                  </option>
                  <option
                    v-for="group in availableGroups"
                    :key="group.id"
                    :value="group.id"
                  >
                    {{ group.title }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Additional Fields -->
            <AdditionalFields
              v-model="additionalFields"
              :field-definitions="assocFieldDefinitions"
            />

            <div class="row mb-3">
              <div class="col-sm-3 col-form-label text-end">
                Extensions
              </div>
              <div class="col-sm-9">
                <button
                  type="button"
                  class="btn btn-outline-secondary btn-sm"
                  data-testid="open-extensions"
                  @click="extensionsEditor.open()"
                >
                  Edit extensions
                </button>
                <small class="text-muted d-block">View or edit proprietary extension values for this association.</small>
              </div>
            </div>

            <ExtensionsEditor
              v-model="extensionsEditor.editable.value"
              v-model:show="extensionsEditor.show.value"
              entity-label="Association"
              :reserved-keys="extensionsEditor.reservedKeys"
            />
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal"
          >
            Cancel
          </button>
          <button
            type="button"
            class="btn btn-primary"
            data-testid="save-association"
            :disabled="saving || !isFormValid"
            @click="saveAssociation"
          >
            <span
              v-if="saving"
              class="spinner-border spinner-border-sm me-2"
              role="status"
            />
            {{ saveButtonText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, toRef, onMounted, onUnmounted, computed } from 'vue';
import Modal from 'bootstrap/js/dist/modal';

// Import composables
import { useAssociationDirection } from '../../composables/useAssociationDirection';
import { useAssociationForm } from '../../composables/useAssociationForm';
import { useAdditionalFields } from '../../composables/useAdditionalFields.js';
import ExtensionsEditor from '../shared/ExtensionsEditor.vue';
import { useExtensionsEditor } from '../../composables/useExtensionsEditor.js';

// Import components
import AssociationItemDisplay from './AssociationItemDisplay.vue';
import DirectionSwitchButton from './DirectionSwitchButton.vue';
import AssociationTypeSelector from './AssociationTypeSelector.vue';
import ExemplarFields from './ExemplarFields.vue';
import DestinationFields from './DestinationFields.vue';
import AdditionalFields from '../tree/fields/AdditionalFields.vue';
import { getOrderedAssociationTypes } from '../../composables/useAssociationTypePriority';
import { useEditorContextStore } from '../../stores/editorContextStore';

const props = defineProps({
  association: {
    type: Object,
    default: null
  },
  availableGroups: {
    type: Array,
    default: () => []
  },
  show: {
    type: Boolean,
    default: false
  },
  selectedItemIdentifier: {
    type: String,
    default: null
  },
  // New props for add mode
  mode: {
    type: String,
    default: 'edit',
    validator: (value) => ['add', 'edit'].includes(value)
  },
  currentItem: {
    type: Object,
    default: null
  },
  destinationItem: {
    type: Object,
    default: null
  },
  initialType: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['updated', 'hidden', 'created']);
const contextStore = useEditorContextStore();

const { fieldDefinitions: assocFieldDefinitions, fetchFields: fetchAssocFields } = useAdditionalFields();

// Error and saving state
const error = ref('');
const saving = ref(false);
const modal = ref(null);

// Additional fields managed separately from the composable's formData
const additionalFields = ref({});

const extensionsEditor = useExtensionsEditor({
  scope: 'association',
  getRawExtensions: () => props.association?.extensions
});

// Use the form composable
const {
  formData,
  customType,
  exemplarUrlError,
  destinationUriError,
  isTypeDropdownDisabled,
  isValidCustomType,
  isExemplarType,
  showGroupSelector,
  modalTitle,
  saveButtonText,
  isFormValid,
  loadAssociationData,
  onTypeChange,
  validateExemplarUrl,
  createAssociationData,
  updateAssociationData,
  getFinalType
} = useAssociationForm({
  mode: toRef(props, 'mode'),
  initialType: toRef(props, 'initialType'),
  association: toRef(props, 'association'),
  currentItem: toRef(props, 'currentItem'),
  destinationItem: toRef(props, 'destinationItem'),
  isReversed: ref(false) // Will be updated from direction composable
});

// Use the direction composable
const {
  isAddMode,
  isReversed,
  leftSideItemData,
  rightSideItemData,
  leftSideIsLoading,
  rightSideIsLoading,
  leftSideFrameworkTitle,
  rightSideFrameworkTitle,
  leftSideTargetTypeInfo,
  rightSideTargetTypeInfo,
  leftSideFetchError,
  rightSideFetchError,
  leftSideDisplayText,
  rightSideDisplayText,
  leftSideShortText,
  rightSideShortText,
  leftSideFallbackText,
  rightSideFallbackText,
  isLeftSideSelected,
  isRightSideSelected,
  directionIcon,
  switchDirection,
  resetDirection
} = useAssociationDirection({
  association: toRef(props, 'association'),
  currentItem: toRef(props, 'currentItem'),
  destinationItem: toRef(props, 'destinationItem'),
  mode: toRef(props, 'mode'),
  initialType: toRef(props, 'initialType'),
  selectedItemIdentifier: toRef(props, 'selectedItemIdentifier'),
  formData: formData
});

const allowIsChildOf = computed(() => {
  const source = leftSideItemData.value;
  if (!source) return false;

  const sourceFrameworkId =
    source.documentIdentifier ||
    source.CFDocumentURI?.identifier ||
    (typeof source.CFDocumentURI === 'string' ? source.CFDocumentURI : null) ||
    source.documentId ||
    null;

  if (!sourceFrameworkId || !contextStore.activeWriteDocumentId) return false;
  return sourceFrameworkId !== contextStore.activeWriteDocumentId;
});

// Computed prioritized association types
const prioritizedTypes = computed(() => {
  const source = leftSideItemData.value;
  const target = rightSideItemData.value;
  const isExemplar = rightSideTargetTypeInfo.value?.isUnknown && !rightSideTargetTypeInfo.value?.isCase;

  return getOrderedAssociationTypes(source, target, {
    isEditing: props.mode === 'edit',
    currentType: formData.type === 'other' ? customType.value : formData.type,
    isExemplarTarget: isExemplar || formData.type === 'exemplar',
    allowIsChildOf: allowIsChildOf.value
  });
});

// Available groups for the group selector (filtering out virtual groups)
const availableGroups = computed(() => {
  if (!props.availableGroups) return [];
  return props.availableGroups.filter(g =>
    g.id !== 'default' &&
    g.id !== 'all' &&
    g.title !== 'Default' &&
    g.title !== 'All'
  );
});

// Watch for show prop changes
watch(() => props.show, async (newVal) => {
  if (newVal) {
    error.value = '';
    loadAssociationData();
    resetDirection();
    // Fetch additional field definitions
    await fetchAssocFields('association');
    // Load existing additionalFields when editing
    if (props.mode === 'edit' && props.association) {
      additionalFields.value = props.association.additionalFields || {};
    } else {
      additionalFields.value = {};
    }
    // Wait for DOM to be ready before accessing the modal element
    await nextTick();
    const modalEl = document.getElementById('editAssociationModal');
    if (!modal.value && modalEl) {
      modal.value = new Modal(modalEl);
    }
    if (modal.value) {
      modal.value.show();
    }
  } else if (modal.value) {
    modal.value.hide();
  }
}, { immediate: true });

// Watch for association changes
watch(() => props.association, (newAssoc) => {
  if (newAssoc && props.mode === 'edit') {
    loadAssociationData();
  }
}, { immediate: true });

// Watch for initial type changes
watch(() => props.initialType, (newType) => {
  if (props.mode === 'add' && newType) {
    formData.type = newType;
  }
}, { immediate: true });

// Save association handler
function saveAssociation() {
  // Validate form
  if (!formData.type) {
    error.value = 'Please select an association type';
    return;
  }

  // Validate exemplar URL
  if (!validateExemplarUrl()) {
    return;
  }

  const finalType = getFinalType();

  saving.value = true;
  error.value = '';

  try {
    if (props.mode === 'add') {
      // Create new association
      const newAssociation = createAssociationData(finalType);
      newAssociation.additionalFields = additionalFields.value;
      newAssociation.extensions = {
        ...(newAssociation.extensions || {}),
        ...extensionsEditor.buildExtensions()
      };
      emit('created', newAssociation);
    } else {
      // Update existing association
      const updatedAssociation = updateAssociationData(finalType);
      updatedAssociation.additionalFields = additionalFields.value;
      updatedAssociation.extensions = extensionsEditor.buildExtensions();
      emit('updated', updatedAssociation);
    }
    if (modal.value) {
      modal.value.hide();
    }
  } catch (e) {
    error.value = `Failed to ${props.mode === 'add' ? 'create' : 'update'} association: ${e.message}`;
  } finally {
    saving.value = false;
  }
}

// Handle modal hidden event with proper lifecycle management
let modalHiddenHandler = null;

onMounted(() => {
  modalHiddenHandler = (event) => {
    if (event.target.id === 'editAssociationModal') {
      emit('hidden');
    }
  };
  document.addEventListener('hidden.bs.modal', modalHiddenHandler);
});

onUnmounted(() => {
  if (modalHiddenHandler) {
    document.removeEventListener('hidden.bs.modal', modalHiddenHandler);
  }
});
</script>

<style scoped>
.modal-dialog {
  max-width: 90vw;
}

.vcenter {
  display: flex;
  align-items: center;
  justify-content: center;
}

.form-control:focus,
.form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
