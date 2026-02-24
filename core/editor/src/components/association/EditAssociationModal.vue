<template>
  <div class="modal fade" id="editAssociationModal" tabindex="-1" role="dialog" aria-labelledby="editAssociationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editAssociationModalLabel">Edit Association</h5>
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
                  v-model="formData.type"
                  @change="onTypeChange"
                >
                  <option v-for="type in forwardTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
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
          <button type="button" class="btn btn-primary" @click="updateAssociation" :disabled="saving || (formData.type === 'other' && !isValidCustomType)">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Save Changes
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
  }
});

const emit = defineEmits(['updated', 'hidden']);

const error = ref('');
const saving = ref(false);
const modal = ref(null);

const formData = reactive({
  type: '',
  annotation: '',
  groupId: 'default'
});

const customType = ref('');

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
  association: toRef(props, 'association'),
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
  association: toRef(props, 'association'),
  direction: 'normal' // Get destination item
});

// Get the origin node identifier
const originIdentifier = computed(() => {
  return props.association?.originNodeURI?.identifier ||
         props.association?.origin?.identifier;
});

// Get the destination node identifier
const destinationIdentifier = computed(() => {
  return props.association?.destinationNodeURI?.identifier ||
         props.association?.destination?.identifier;
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
  return props.association?.originNodeURI?.title ||
         props.association?.origin?.title ||
         'Origin item';
});

const destinationFallbackText = computed(() => {
  return props.association?.destinationNodeURI?.title ||
         props.association?.destination?.title ||
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
  if (newVal && props.association) {
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
  if (newAssoc) {
    loadAssociationData();
  }
}, { immediate: true });

function loadAssociationData() {
  if (!props.association) return;

  // Use associationType instead of type
  const assocType = props.association.associationType || props.association.type || '';
  if (assocType.startsWith('ext:')) {
    formData.type = 'other';
    customType.value = assocType;
  } else {
    formData.type = assocType;
  }

  // Use notes instead of annotation
  formData.annotation = props.association.notes || props.association.annotation || '';

  // Handle group ID from CFAssociationGroupingURI
  formData.groupId = props.association.CFAssociationGroupingURI?.identifier ||
                     props.association.groupId ||
                     'default';

  isReversed.value = false;
  error.value = '';
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

function updateAssociation() {
  if (!formData.type) {
    error.value = 'Please select an association type';
    return;
  }

  const finalType = formData.type === 'other' ? customType.value.trim() : formData.type;

  saving.value = true;
  error.value = '';

  // Simulate updating association - in real app this would be an API call
  setTimeout(() => {
    try {
      // Determine effective origin/destination based on direction switch
      const effectiveOriginNodeURI = isReversed.value
        ? props.association.destinationNodeURI || props.association.destination
        : props.association.originNodeURI || props.association.origin;
      const effectiveDestinationNodeURI = isReversed.value
        ? props.association.originNodeURI || props.association.origin
        : props.association.destinationNodeURI || props.association.destination;

      const updatedAssociation = {
        ...props.association,
        originNodeURI: effectiveOriginNodeURI,
        destinationNodeURI: effectiveDestinationNodeURI,
        associationType: finalType,
        notes: formData.annotation,
        groupId: formData.groupId,
        updated: new Date().toISOString()
      };

      emit('updated', updatedAssociation);
      if (modal.value) {
        modal.value.hide();
      }
    } catch (e) {
      error.value = 'Failed to update association: ' + e.message;
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

.gap-2 {
  gap: 0.5rem;
}
</style>
