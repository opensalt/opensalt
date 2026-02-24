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
              <div class="col-sm-5">
                <div
                  class="ls-association-item-display border p-3 rounded"
                  id="editLsAssociationOriginDisplay"
                  :class="{ 'selected-item-highlight': isLeftSideSelected }"
                >
                  <div v-if="leftSideItemData" class="d-flex align-items-start flex-wrap gap-2">
                    <div class="flex-grow-1">
                      <strong>{{ leftSideDisplayText }}</strong>
                      <div v-if="leftSideItemData.humanCodingScheme" class="text-muted small">
                        {{ leftSideItemData.humanCodingScheme }}
                      </div>
                    </div>
                    <!-- Loading spinner for cross-framework CASE items -->
                    <span v-if="leftSideIsLoading && leftSideTargetTypeInfo.isCase" class="spinner-border spinner-border-sm text-secondary" role="status" aria-label="Loading"></span>
                    <!-- Framework badge for cross-framework CASE items -->
                    <span v-if="leftSideFrameworkTitle && !leftSideIsLoading && leftSideTargetTypeInfo.isCase" class="badge bg-info text-dark framework-badge">
                      <i class="bi bi-box-arrow-up-right me-1"></i>{{ leftSideFrameworkTitle }}
                    </span>
                    <!-- Non-CASE item indicator -->
                    <span v-if="!leftSideTargetTypeInfo.isCase && leftSideItemData" class="badge bg-secondary external-uri-badge">
                      <i class="bi bi-link-45deg me-1"></i>External URI
                    </span>
                    <!-- Error indicator for failed fetches -->
                    <span v-if="leftSideFetchError && leftSideTargetTypeInfo.isCase" class="badge bg-warning text-dark error-badge" :title="leftSideFetchError.message">
                      <i class="bi bi-exclamation-triangle me-1"></i>{{ leftSideFetchError.type === 'permission' ? 'No access' : leftSideFetchError.type === 'not_found' ? 'Not found' : 'Load error' }}
                    </span>
                  </div>
                  <div v-else class="text-muted">
                    {{ leftSideFallbackText }}
                  </div>
                </div>
              </div>
              <div class="col-auto d-flex flex-column align-items-center">
                <!-- Direction button with item names on each side -->
                <button
                  type="button"
                  class="btn btn-outline-secondary direction-switch-btn"
                  @click="switchDirection"
                  :title="isReversed ? 'Click to reverse direction' : 'Click to reverse direction'"
                  aria-label="Switch association direction"
                >
                  <span class="direction-side direction-left">
                    <span class="direction-label">From:</span>
                    <span class="direction-item-name">{{ leftSideShortText }}</span>
                  </span>
                  <span class="direction-arrow">
                    <i :class="directionIcon" class="bi fs-5"></i>
                  </span>
                  <span class="direction-side direction-right">
                    <span class="direction-label">To:</span>
                    <span class="direction-item-name">{{ rightSideShortText }}</span>
                  </span>
                </button>
                <small class="text-muted mt-2">Click to switch direction</small>
              </div>
              <div class="col-sm-5">
                <div
                  class="ls-association-item-display border p-3 rounded"
                  id="editLsAssociationDestinationDisplay"
                  :class="{ 'selected-item-highlight': isRightSideSelected }"
                >
                  <div v-if="rightSideItemData" class="d-flex align-items-start flex-wrap gap-2">
                    <div class="flex-grow-1">
                      <strong>{{ rightSideDisplayText }}</strong>
                      <div v-if="rightSideItemData.humanCodingScheme" class="text-muted small">
                        {{ rightSideItemData.humanCodingScheme }}
                      </div>
                    </div>
                    <!-- Loading spinner for cross-framework CASE items -->
                    <span v-if="rightSideIsLoading && rightSideTargetTypeInfo.isCase" class="spinner-border spinner-border-sm text-secondary" role="status" aria-label="Loading"></span>
                    <!-- Framework badge for cross-framework CASE items -->
                    <span v-if="rightSideFrameworkTitle && !rightSideIsLoading && rightSideTargetTypeInfo.isCase" class="badge bg-info text-dark framework-badge">
                      <i class="bi bi-box-arrow-up-right me-1"></i>{{ rightSideFrameworkTitle }}
                    </span>
                    <!-- Non-CASE item indicator -->
                    <span v-if="!rightSideTargetTypeInfo.isCase && rightSideItemData" class="badge bg-secondary external-uri-badge">
                      <i class="bi bi-link-45deg me-1"></i>External URI
                    </span>
                    <!-- Error indicator for failed fetches -->
                    <span v-if="rightSideFetchError && rightSideTargetTypeInfo.isCase" class="badge bg-warning text-dark error-badge" :title="rightSideFetchError.message">
                      <i class="bi bi-exclamation-triangle me-1"></i>{{ rightSideFetchError.type === 'permission' ? 'No access' : rightSideFetchError.type === 'not_found' ? 'Not found' : 'Load error' }}
                    </span>
                  </div>
                  <div v-else class="text-muted">
                    {{ rightSideFallbackText }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Association form -->
          <div class="form-horizontal">
            <div class="row mb-3">
              <label for="editAssociationFormType" class="col-sm-3 col-form-label required text-end">
                Association Type *
              </label>
              <div class="col-sm-9">
                <select
                  id="editAssociationFormType"
                  class="form-select"
                  :class="{ 'locked-field': isTypeDropdownDisabled }"
                  v-model="formData.type"
                  @change="onTypeChange"
                  :disabled="isTypeDropdownDisabled"
                >
                  <option v-for="type in forwardTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
                <div v-if="isTypeDropdownDisabled" class="form-text text-muted">
                  <i class="bi bi-lock me-1"></i>Type is locked when adding an exemplar
                </div>
                <div v-if="formData.type === 'other'" class="form-group mt-2">
                  <label for="customType" class="form-label">Custom Association Type</label>
                  <input
                    id="customType"
                    v-model="customType"
                    type="text"
                    class="form-control"
                    placeholder="ext:custom-type"
                  />
                  <div v-if="customType && !isValidCustomType" class="text-danger small mt-1">
                    Invalid format. Must start with "ext:" followed by alphanumeric characters, dots, hyphens, or underscores.
                  </div>
                </div>
              </div>
            </div>

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
            <div v-if="isExemplarType" class="row mb-3">
              <label for="editAssociationFormExemplarUrl" class="col-sm-3 col-form-label required text-end">
                Exemplar URL *
              </label>
              <div class="col-sm-9">
                <input
                  type="url"
                  id="editAssociationFormExemplarUrl"
                  class="form-control"
                  v-model="formData.exemplarUrl"
                  placeholder="https://example.com/resource"
                  :required="isExemplarType"
                >
                <div v-if="exemplarUrlError" class="text-danger small mt-1">
                  {{ exemplarUrlError }}
                </div>
                <div class="form-text">
                  Enter the URL of the exemplar resource
                </div>
              </div>
            </div>

            <div v-if="isExemplarType" class="row mb-3">
              <label for="editAssociationFormExemplarDescription" class="col-sm-3 col-form-label text-end">
                Description
              </label>
              <div class="col-sm-9">
                <textarea
                  id="editAssociationFormExemplarDescription"
                  class="form-control"
                  rows="3"
                  v-model="formData.exemplarDescription"
                  placeholder="Optional description of the exemplar"
                ></textarea>
              </div>
            </div>

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
import { ref, reactive, computed, watch, nextTick, toRef, onMounted, onUnmounted } from 'vue';
import { Modal } from 'bootstrap';
import { useCrossFrameworkItem } from '../../composables/useCrossFrameworkItem';

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

const error = ref('');
const saving = ref(false);
const modal = ref(null);

const formData = reactive({
  type: '',
  annotation: '',
  groupId: 'default',
  // Exemplar-specific fields
  exemplarUrl: '',
  exemplarDescription: ''
});

const customType = ref('');
const exemplarUrlError = ref('');

const isReversed = ref(false);

const forwardTypes = [
  { value: 'isRelatedTo', label: 'Is Related To' },
  { value: 'exactMatchOf', label: 'Exact Match Of' },
  { value: 'isPartOf', label: 'Is Part Of' },
  { value: 'hasSkillLevel', label: 'Has Skill Level' },
  { value: 'isPeerOf', label: 'Is Peer Of' },
  { value: 'exemplar', label: 'Exemplar' },
  { value: 'isTranslationOf', label: 'Is Translation Of' },
  { value: 'isChildOf', label: 'Is Child Of' },
  { value: 'replacedBy', label: 'Replaced By' },
  { value: 'precedes', label: 'Precedes' },
  { value: 'other', label: 'Other' }
];

// Mode detection computed properties - MUST be defined before effectiveAssociation
// because effectiveAssociation references isAddMode
const isAddMode = computed(() => props.mode === 'add');
const isEditMode = computed(() => props.mode === 'edit');

// Disable type dropdown when adding an exemplar (no way to set destination for other types)
const isTypeDropdownDisabled = computed(() => {
  return isAddMode.value && props.initialType === 'exemplar';
});

// Create a synthetic association for add mode so the composable can extract origin data
const effectiveAssociation = computed(() => {
  if (isAddMode.value && props.currentItem) {
    // In add mode, create a synthetic association with the currentItem as origin
    return {
      originNodeURI: {
        identifier: props.currentItem.identifier,
        uri: props.currentItem.uri || props.currentItem.identifier,
        title: props.currentItem.title || props.currentItem.fullStatement || props.currentItem.abbreviatedStatement
      },
      associationType: props.initialType || formData.type
    };
  }
  return props.association;
});

// Use cross-framework item composables for origin and destination
// Origin item (direction = 'reversed' shows origin as the target)
const {
  itemData: originItemData,
  itemTitle: originItemTitle,
  frameworkTitle: originFrameworkTitle,
  isLoading: originIsLoading,
  targetTypeInfo: originTargetTypeInfo,
  fetchError: originFetchError
} = useCrossFrameworkItem({
  association: effectiveAssociation,
  direction: 'reversed' // Get origin item
});

// Destination item (direction = 'normal' shows destination as the target)
const {
  itemData: destinationItemData,
  itemTitle: destinationItemTitle,
  frameworkTitle: destinationFrameworkTitle,
  isLoading: destinationIsLoading,
  targetTypeInfo: destinationTargetTypeInfo,
  fetchError: destinationFetchError
} = useCrossFrameworkItem({
  association: effectiveAssociation,
  direction: 'normal' // Get destination item
});

// Get the origin node identifier
const originIdentifier = computed(() => {
  return effectiveAssociation.value?.originNodeURI?.identifier ||
         effectiveAssociation.value?.origin?.identifier;
});

// Get the destination node identifier
const destinationIdentifier = computed(() => {
  return effectiveAssociation.value?.destinationNodeURI?.identifier ||
         effectiveAssociation.value?.destination?.identifier;
});

// Display text for origin item - prefer abbreviatedStatement
const originItemDisplayText = computed(() => {
  const item = originItemData.value;
  if (!item) return originItemTitle.value || 'Unknown item';

  // Prefer abbreviatedStatement, fall back to shortened fullStatement
  if (item.abbreviatedStatement) {
    return item.abbreviatedStatement;
  }

  if (item.fullStatement) {
    // Truncate to ~100 characters if no abbreviatedStatement
    return item.fullStatement.length > 100
      ? item.fullStatement.substring(0, 100) + '...'
      : item.fullStatement;
  }

  return item.title || item.identifier || 'Unknown item';
});

// Display text for destination item - prefer abbreviatedStatement
const destinationItemDisplayText = computed(() => {
  const item = destinationItemData.value;
  if (!item) return destinationItemTitle.value || 'Unknown item';

  if (item.abbreviatedStatement) {
    return item.abbreviatedStatement;
  }

  if (item.fullStatement) {
    return item.fullStatement.length > 100
      ? item.fullStatement.substring(0, 100) + '...'
      : item.fullStatement;
  }

  return item.title || item.identifier || 'Unknown item';
});

// Fallback text when item not found in document
const originFallbackText = computed(() => {
  return effectiveAssociation.value?.originNodeURI?.title ||
         effectiveAssociation.value?.origin?.title ||
         'Origin item';
});

const destinationFallbackText = computed(() => {
  // For exemplar associations, show the URL as the destination
  if (effectiveAssociation.value?.associationType === 'exemplar') {
    const url = effectiveAssociation.value?.destinationNodeURI?.uri ||
                effectiveAssociation.value?.destination?.uri;
    if (url) {
      return url;
    }
  }
  return effectiveAssociation.value?.destinationNodeURI?.title ||
         effectiveAssociation.value?.destination?.title ||
         'Destination item';
});

// Legacy computed properties for backward compatibility
const originItem = computed(() => originItemData.value || props.association?.origin || null);
const destinationItem = computed(() => destinationItemData.value || props.association?.destination || null);

const isValidCustomType = computed(() => {
  if (formData.type !== 'other') return true;
  const value = customType.value.trim();
  return value.startsWith('ext:') && /^ext:[a-zA-Z0-9._-]+$/.test(value);
});

const showGroupSelector = computed(() => {
  return formData.type && !['exemplar', 'isChildOf'].includes(formData.type);
});

// Exemplar type detection (mode detection moved before effectiveAssociation)
const isExemplarType = computed(() => formData.type === 'exemplar');

// Modal title based on mode
const modalTitle = computed(() => {
  if (isAddMode.value) {
    return isExemplarType.value ? 'Add Exemplar' : 'Add Association';
  }
  return 'Edit Association';
});

// Save button text based on mode
const saveButtonText = computed(() => {
  if (isAddMode.value) {
    return isExemplarType.value ? 'Add Exemplar' : 'Create Association';
  }
  return 'Save Changes';
});

// Form validation
const isFormValid = computed(() => {
  // Check custom type validity
  if (formData.type === 'other' && !isValidCustomType.value) {
    return false;
  }

  // Check exemplar URL validity
  if (isExemplarType.value) {
    if (!formData.exemplarUrl.trim()) {
      return false;
    }
    if (!validateUrl(formData.exemplarUrl)) {
      return false;
    }
    if (formData.exemplarUrl.length > 300) {
      return false;
    }
  }

  return true;
});

const directionIcon = 'bi-arrow-right';

// Computed properties for left/right side items based on direction
const leftSideItemData = computed(() => {
  return isReversed.value ? destinationItemData.value : originItemData.value;
});

const rightSideItemData = computed(() => {
  return isReversed.value ? originItemData.value : destinationItemData.value;
});

// Loading states for left/right sides
const leftSideIsLoading = computed(() => {
  return isReversed.value ? destinationIsLoading.value : originIsLoading.value;
});

const rightSideIsLoading = computed(() => {
  return isReversed.value ? originIsLoading.value : destinationIsLoading.value;
});

// Framework titles for left/right sides
const leftSideFrameworkTitle = computed(() => {
  return isReversed.value ? destinationFrameworkTitle.value : originFrameworkTitle.value;
});

const rightSideFrameworkTitle = computed(() => {
  return isReversed.value ? originFrameworkTitle.value : destinationFrameworkTitle.value;
});

// Target type info for left/right sides
const leftSideTargetTypeInfo = computed(() => {
  return isReversed.value ? destinationTargetTypeInfo.value : originTargetTypeInfo.value;
});

const rightSideTargetTypeInfo = computed(() => {
  return isReversed.value ? originTargetTypeInfo.value : destinationTargetTypeInfo.value;
});

// Fetch errors for left/right sides
const leftSideFetchError = computed(() => {
  return isReversed.value ? destinationFetchError.value : originFetchError.value;
});

const rightSideFetchError = computed(() => {
  return isReversed.value ? originFetchError.value : destinationFetchError.value;
});

// Display text for left side item
const leftSideDisplayText = computed(() => {
  const item = leftSideItemData.value;
  if (!item) return leftSideFallbackText.value;

  if (item.abbreviatedStatement) {
    return item.abbreviatedStatement;
  }

  if (item.fullStatement) {
    return item.fullStatement.length > 100
      ? item.fullStatement.substring(0, 100) + '...'
      : item.fullStatement;
  }

  return item.title || item.identifier || 'Unknown item';
});

// Display text for right side item
const rightSideDisplayText = computed(() => {
  const item = rightSideItemData.value;
  if (!item) return rightSideFallbackText.value;

  if (item.abbreviatedStatement) {
    return item.abbreviatedStatement;
  }

  if (item.fullStatement) {
    return item.fullStatement.length > 100
      ? item.fullStatement.substring(0, 100) + '...'
      : item.fullStatement;
  }

  return item.title || item.identifier || 'Unknown item';
});

// Short text for direction button (truncated for display)
const leftSideShortText = computed(() => {
  const item = leftSideItemData.value;
  if (!item) return leftSideFallbackText.value;

  // Prefer humanCodingScheme for short display, then abbreviatedStatement
  if (item.humanCodingScheme) {
    return item.humanCodingScheme;
  }

  if (item.abbreviatedStatement) {
    return item.abbreviatedStatement.length > 30
      ? item.abbreviatedStatement.substring(0, 30) + '...'
      : item.abbreviatedStatement;
  }

  if (item.fullStatement) {
    return item.fullStatement.length > 30
      ? item.fullStatement.substring(0, 30) + '...'
      : item.fullStatement;
  }

  return item.title || item.identifier || 'Unknown';
});

const rightSideShortText = computed(() => {
  const item = rightSideItemData.value;
  if (!item) return rightSideFallbackText.value;

  if (item.humanCodingScheme) {
    return item.humanCodingScheme;
  }

  if (item.abbreviatedStatement) {
    return item.abbreviatedStatement.length > 30
      ? item.abbreviatedStatement.substring(0, 30) + '...'
      : item.abbreviatedStatement;
  }

  if (item.fullStatement) {
    return item.fullStatement.length > 30
      ? item.fullStatement.substring(0, 30) + '...'
      : item.fullStatement;
  }

  return item.title || item.identifier || 'Unknown';
});

// Fallback text for left/right sides
const leftSideFallbackText = computed(() => {
  if (isReversed.value) {
    return destinationFallbackText.value;
  }
  return originFallbackText.value;
});

const rightSideFallbackText = computed(() => {
  if (isReversed.value) {
    return originFallbackText.value;
  }
  // For add mode with exemplar type, show a placeholder
  if (isAddMode.value && isExemplarType.value) {
    return 'Enter exemplar URL below';
  }
  return destinationFallbackText.value;
});

// Check if origin or destination is the selected item (for reference)
const isOriginSelected = computed(() => {
  if (!props.selectedItemIdentifier || !originIdentifier.value) return false;
  return props.selectedItemIdentifier === originIdentifier.value;
});

const isDestinationSelected = computed(() => {
  if (!props.selectedItemIdentifier || !destinationIdentifier.value) return false;
  return props.selectedItemIdentifier === destinationIdentifier.value;
});

// Check if the item currently displayed on each side is selected
// These follow the item, not the position
const isLeftSideSelected = computed(() => {
  if (!props.selectedItemIdentifier) return false;
  const leftIdentifier = leftSideItemData.value?.identifier;
  return leftIdentifier && props.selectedItemIdentifier === leftIdentifier;
});

const isRightSideSelected = computed(() => {
  if (!props.selectedItemIdentifier) return false;
  const rightIdentifier = rightSideItemData.value?.identifier;
  return rightIdentifier && props.selectedItemIdentifier === rightIdentifier;
});

watch(() => props.show, async (newVal) => {
  if (newVal) {
    loadAssociationData();
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

watch(() => props.association, (newAssoc) => {
  if (newAssoc && isEditMode.value) {
    loadAssociationData();
  }
}, { immediate: true });

watch(() => props.initialType, (newType) => {
  if (isAddMode.value && newType) {
    formData.type = newType;
  }
}, { immediate: true });

function loadAssociationData() {
  // Reset form data
  formData.type = '';
  formData.annotation = '';
  formData.groupId = 'default';
  formData.exemplarUrl = '';
  formData.exemplarDescription = '';
  customType.value = '';
  exemplarUrlError.value = '';
  isReversed.value = false;
  error.value = '';

  if (isAddMode.value) {
    // Add mode: set initial type if provided
    if (props.initialType) {
      formData.type = props.initialType;
    }
    return;
  }

  // Edit mode: load from existing association
  const assoc = effectiveAssociation.value;
  if (!assoc) return;

  // Use associationType instead of type
  const assocType = assoc.associationType || assoc.type || '';
  if (assocType.startsWith('ext:')) {
    formData.type = 'other';
    customType.value = assocType;
  } else {
    formData.type = assocType;
  }

  // Use notes instead of annotation
  formData.annotation = assoc.notes || assoc.annotation || '';

  // Handle group ID from CFAssociationGroupingURI
  formData.groupId = assoc.CFAssociationGroupingURI?.identifier ||
                     assoc.groupId ||
                     'default';

  // Load exemplar-specific fields
  if (formData.type === 'exemplar') {
    // For exemplar, the destination is the URL
    formData.exemplarUrl = assoc.destinationNodeURI?.uri ||
                           assoc.destination?.uri ||
                           '';
    // Description is stored in notes for exemplars
    formData.exemplarDescription = assoc.notes || '';
  }
}

// URL validation function
function validateUrl(url) {
  try {
    new URL(url);
    return true;
  } catch {
    return false;
  }
}

function onTypeChange() {
  // Auto-select default group for certain association types
  if (['isChildOf', 'exemplar'].includes(formData.type)) {
    formData.groupId = 'default';
  }
}

function switchDirection() {
  isReversed.value = !isReversed.value;
  // Association type is preserved when switching direction
}

function saveAssociation() {
  // Validate form
  if (!formData.type) {
    error.value = 'Please select an association type';
    return;
  }

  // Validate exemplar URL
  if (isExemplarType.value) {
    if (!formData.exemplarUrl.trim()) {
      exemplarUrlError.value = 'URL is required';
      return;
    }
    if (!validateUrl(formData.exemplarUrl)) {
      exemplarUrlError.value = 'Please enter a valid URL';
      return;
    }
    if (formData.exemplarUrl.length > 300) {
      exemplarUrlError.value = 'URL must be 300 characters or less';
      return;
    }
  }

  const finalType = formData.type === 'other' ? customType.value.trim() : formData.type;

  saving.value = true;
  error.value = '';
  exemplarUrlError.value = '';

  // Simulate API call - in real app this would be an API call
  setTimeout(() => {
    try {
      if (isAddMode.value) {
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
      error.value = `Failed to ${isAddMode.value ? 'create' : 'update'} association: ${e.message}`;
    } finally {
      saving.value = false;
    }
  }, 1000);
}

function createAssociationData(finalType) {
  if (isExemplarType.value) {
    // Exemplar association
    return {
      originNodeIdentifier: props.currentItem?.identifier,
      destinationNodeUri: formData.exemplarUrl,
      associationType: 'exemplar',
      annotation: formData.annotation,
      notes: formData.exemplarDescription
    };
  }

  // Standard association
  return {
    originNodeIdentifier: props.currentItem?.identifier,
    associationType: finalType,
    annotation: formData.annotation,
    groupId: formData.groupId
  };
}

function updateAssociationData(finalType) {
  const assoc = effectiveAssociation.value;

  // Determine effective origin/destination based on direction switch
  const effectiveOriginNodeURI = isReversed.value
    ? assoc.destinationNodeURI || assoc.destination
    : assoc.originNodeURI || assoc.origin;
  const effectiveDestinationNodeURI = isReversed.value
    ? assoc.originNodeURI || assoc.origin
    : assoc.destinationNodeURI || assoc.destination;

  const baseAssociation = {
    ...assoc,
    originNodeURI: effectiveOriginNodeURI,
    destinationNodeURI: effectiveDestinationNodeURI,
    associationType: finalType,
    notes: formData.annotation,
    groupId: formData.groupId,
    updated: new Date().toISOString()
  };

  // Add exemplar-specific fields
  if (isExemplarType.value) {
    baseAssociation.destinationNodeURI = {
      uri: formData.exemplarUrl,
      title: formData.exemplarDescription || formData.exemplarUrl
    };
    baseAssociation.notes = formData.exemplarDescription;
  }

  return baseAssociation;
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

.ls-association-item-display {
  min-height: 80px;
  background-color: #f8f9fa;
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

/* Direction switch button styling */
.direction-switch-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border: 2px solid #dee2e6;
  transition: all 0.2s ease;
  min-width: 280px;
  justify-content: space-between;
}

.direction-switch-btn:hover {
  background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
  border-color: #0d6efd;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.direction-switch-btn:focus {
  outline: none;
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.direction-side {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  min-width: 0;
  flex: 1;
}

.direction-right {
  align-items: flex-end;
  text-align: right;
}

.direction-label {
  font-size: 0.7rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.125rem;
}

.direction-item-name {
  font-size: 0.85rem;
  font-weight: 500;
  color: #212529;
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.direction-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0d6efd;
  flex-shrink: 0;
}

/* Selected item highlight styling */
.selected-item-highlight {
  border: 2px solid #0d6efd !important;
  background-color: #e7f1ff !important;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.framework-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
}

.framework-badge i {
  font-size: 0.85em;
}

.external-uri-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
}

.external-uri-badge i {
  font-size: 0.85em;
}

.error-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
}

.error-badge i {
  font-size: 0.85em;
}

/* Locked field styling for disabled dropdown */
.locked-field {
  background-color: #e9ecef;
  opacity: 0.65;
  cursor: not-allowed;
}

.gap-2 {
  gap: 0.5rem;
}
</style>
