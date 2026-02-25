<template>
  <div class="modal fade" id="editAssociationModal" tabindex="-1" role="dialog" aria-labelledby="editAssociationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editAssociationModalLabel">{{ modalTitle }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="error" class="alert alert-danger mb-3" role="alert">
            {{ error }}
          </div>

          <!-- Association visualization -->
          <div class="container-fluid mb-4">
            <div class="row vcenter">
              <!-- Left Side Item Display -->
              <AssociationItemDisplay
                :itemData="leftSideItemData"
                :isLoading="leftSideIsLoading"
                :frameworkTitle="leftSideFrameworkTitle"
                :targetTypeInfo="leftSideTargetTypeInfo"
                :fetchError="leftSideFetchError"
                :isSelected="isLeftSideSelected"
                :fallbackText="leftSideFallbackText"
                :displayText="leftSideDisplayText"
                side="origin"
              />

              <!-- Direction Switch Button -->
              <DirectionSwitchButton
                :leftSideText="leftSideShortText"
                :rightSideText="rightSideShortText"
                :directionIcon="directionIcon"
                @switch="switchDirection"
              />

              <!-- Right Side Item Display -->
              <AssociationItemDisplay
                :itemData="rightSideItemData"
                :isLoading="rightSideIsLoading"
                :frameworkTitle="rightSideFrameworkTitle"
                :targetTypeInfo="rightSideTargetTypeInfo"
                :fetchError="rightSideFetchError"
                :isSelected="isRightSideSelected"
                :fallbackText="rightSideFallbackText"
                :displayText="rightSideDisplayText"
                side="destination"
              />
            </div>
          </div>

          <!-- Association form -->
          <div class="form-horizontal">
            <!-- Type Selector -->
            <AssociationTypeSelector
              v-model="formData.type"
              v-model:customType="customType"
              :isDisabled="isTypeDropdownDisabled"
              :isValidCustomType="isValidCustomType"
              @change="onTypeChange"
            />

            <!-- Annotation Field -->
            <div class="row mb-3">
               <label for="editAssociationFormAnnotation" class="col-sm-3 col-form-label text-end">
                 Annotation
               </label>
               <div class="col-sm-9">
                 <textarea
                   id="editAssociationFormAnnotation"
                   class="form-control"
                   rows="3"
                   v-model="formData.annotation"
                   placeholder="Optional annotation or description for this association"
                 ></textarea>
               </div>
             </div>

            <!-- Exemplar-specific fields -->
            <ExemplarFields
              v-if="isExemplarType"
              v-model:url="formData.exemplarUrl"
              v-model:description="formData.exemplarDescription"
              :urlError="exemplarUrlError"
            />

             <!-- Group Selector -->
             <div v-if="showGroupSelector" class="row mb-3" id="editAssociationFormGroupHolderOuter">
              <label for="editAssociationFormGroup" class="col-sm-3 col-form-label required text-end">
                Association Group
              </label>
              <div class="col-sm-9" id="editAssociationFormGroupHolder">
                <select
                  id="editAssociationFormGroup"
                  class="form-select"
                  v-model="formData.groupId"
                >
                  <option value="default">Default Group</option>
                  <option v-for="group in availableGroups" :key="group.id" :value="group.id">
                    {{ group.title }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" @click="saveAssociation" :disabled="saving || !isFormValid">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ saveButtonText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick, toRef, onMounted, onUnmounted, computed } from 'vue';
import { Modal } from 'bootstrap';

// Import composables
import { useAssociationDirection } from '../../composables/useAssociationDirection';
import { useAssociationForm } from '../../composables/useAssociationForm';

// Import components
import AssociationItemDisplay from './AssociationItemDisplay.vue';
import DirectionSwitchButton from './DirectionSwitchButton.vue';
import AssociationTypeSelector from './AssociationTypeSelector.vue';
import ExemplarFields from './ExemplarFields.vue';

const props = defineProps({
  association: Object,
  availableGroups: Array,
  show: Boolean,
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
  initialType: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['updated', 'hidden', 'created']);

// Error and saving state
const error = ref('');
const saving = ref(false);
const modal = ref(null);

// Use the form composable
const {
  formData,
  customType,
  exemplarUrlError,
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
  isReversed: ref(false) // Will be updated from direction composable
});

// Use the direction composable
const {
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
  mode: toRef(props, 'mode'),
  initialType: toRef(props, 'initialType'),
  selectedItemIdentifier: toRef(props, 'selectedItemIdentifier'),
  formData: formData
});

// Available groups for the group selector
const availableGroups = computed(() => props.availableGroups || []);

// Watch for show prop changes
watch(() => props.show, async (newVal) => {
  if (newVal) {
    loadAssociationData();
    resetDirection();
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

  // Simulate API call - in real app this would be an API call
  setTimeout(() => {
    try {
      if (props.mode === 'add') {
        // Create new association
        const newAssociation = createAssociationData(finalType);
        emit('created', newAssociation);
      } else {
        // Update existing association
        const updatedAssociation = updateAssociationData(finalType);
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
  }, 1000);
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
}

.form-control:focus,
.form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
