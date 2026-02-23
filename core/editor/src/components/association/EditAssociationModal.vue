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
                <div class="ls-association-item-display border p-3 rounded" id="editLsAssociationOriginDisplay">
                  <div v-if="originItemData">
                    <strong>{{ originItemDisplayText }}</strong>
                    <div v-if="originItemData.humanCodingScheme" class="text-muted small">
                      {{ originItemData.humanCodingScheme }}
                    </div>
                  </div>
                  <div v-else class="text-muted">
                    {{ originFallbackText }}
                  </div>
                </div>
              </div>
              <div class="col-auto d-flex flex-column align-items-center">
                <i :class="directionIcon" class="bi fs-1 text-muted mb-2"></i>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="switchDirection">
                  ↔ Switch Direction
                </button>
              </div>
              <div class="col-sm-5">
                <div class="ls-association-item-display border p-3 rounded" id="editLsAssociationDestinationDisplay">
                  <div v-if="destinationItemData">
                    <strong>{{ destinationItemDisplayText }}</strong>
                    <div v-if="destinationItemData.humanCodingScheme" class="text-muted small">
                      {{ destinationItemData.humanCodingScheme }}
                    </div>
                  </div>
                  <div v-else class="text-muted">
                    {{ destinationFallbackText }}
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
import { ref, reactive, computed, watch } from 'vue';
import { Modal } from 'bootstrap';
import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';

const props = defineProps({
  association: Object,
  availableGroups: Array,
  show: Boolean
});

const emit = defineEmits(['updated', 'hidden']);

const currentDocumentStore = useCurrentDocumentStore();

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

// Helper function to find item recursively
function findItemById(items, identifier) {
  for (const item of items) {
    if (item.identifier === identifier) return item;
    if (item.children) {
      const found = findItemById(item.children, identifier);
      if (found) return found;
    }
  }
  return null;
}

// Look up the full origin item from document items
const originItemData = computed(() => {
  const identifier = originIdentifier.value;
  if (!identifier) return null;

  const items = currentDocumentStore.currentDocument?.items || [];
  return findItemById(items, identifier);
});

// Look up the full destination item from document items
const destinationItemData = computed(() => {
  const identifier = destinationIdentifier.value;
  if (!identifier) return null;

  const items = currentDocumentStore.currentDocument?.items || [];
  return findItemById(items, identifier);
});

// Display text for origin item - prefer abbreviatedStatement
const originItemDisplayText = computed(() => {
  const item = originItemData.value;
  if (!item) return 'Unknown item';

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
  if (!item) return 'Unknown item';

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

const directionIcon = computed(() => `bi-arrow-${isReversed.value ? 'left' : 'right'}`);

watch(() => props.show, (newVal) => {
  if (newVal && props.association) {
    loadAssociationData();
    if (!modal.value) {
      modal.value = new Modal(document.getElementById('editAssociationModal'));
    }
    modal.value.show();
  } else if (modal.value) {
    modal.value.hide();
  }
});

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

// Handle modal hidden event
document.addEventListener('hidden.bs.modal', (event) => {
  if (event.target.id === 'editAssociationModal') {
    emit('hidden');
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
</style>
