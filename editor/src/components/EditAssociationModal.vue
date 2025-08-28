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
                  <div v-if="originItem">
                    <strong>{{ originItem.title }}</strong>
                    <div v-if="originItem.humanCodingScheme" class="text-muted small">
                      {{ originItem.humanCodingScheme }}
                    </div>
                  </div>
                  <div v-else class="text-muted">Origin item</div>
                </div>
              </div>
              <div class="col-sm-2 text-center">
                <i :class="directionIcon" class="fa fa-arrow-right fa-2x" aria-hidden="true"></i><br>
                <button type="button" class="btn btn-default btn-sm mt-2" @click="switchDirection">
                  Switch
                </button>
              </div>
              <div class="col-sm-5">
                <div class="ls-association-item-display border p-3 rounded" id="editLsAssociationDestinationDisplay">
                  <div v-if="destinationItem">
                    <strong>{{ destinationItem.title }}</strong>
                    <div v-if="destinationItem.humanCodingScheme" class="text-muted small">
                      {{ destinationItem.humanCodingScheme }}
                    </div>
                  </div>
                  <div v-else class="text-muted">Destination item</div>
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
                  <optgroup label="Forward Associations">
                    <option v-for="type in forwardTypes" :key="type.value" :value="type.value">
                      {{ type.label }}
                    </option>
                  </optgroup>
                  <optgroup label="Reverse Associations">
                    <option v-for="type in reverseTypes" :key="type.value" :value="type.value">
                      {{ type.label }}
                    </option>
                  </optgroup>
                </select>
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
          <button type="button" class="btn btn-primary" @click="updateAssociation" :disabled="saving">
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

const props = defineProps({
  association: Object,
  availableGroups: Array,
  show: Boolean
});

const emit = defineEmits(['updated', 'hidden']);

const error = ref('');
const saving = ref(false);
const modal = ref(null);
const isReversed = ref(false);

const formData = reactive({
  type: '',
  annotation: '',
  groupId: 'default'
});

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
  { value: 'precedes', label: 'Precedes' }
];

const reverseTypes = [
  { value: 'relatedFrom', label: 'Related From' },
  { value: 'matchedFrom', label: 'Matched From' },
  { value: 'hasPart', label: 'Has Part' },
  { value: 'skillLevelFor', label: 'Skill Level For' },
  { value: 'peerOf', label: 'Peer Of' },
  { value: 'exemplarFor', label: 'Exemplar For' },
  { value: 'translationOf', label: 'Translation Of' },
  { value: 'isParentOf', label: 'Is Parent Of' },
  { value: 'replaces', label: 'Replaces' },
  { value: 'hasPredecessor', label: 'Has Predecessor' }
];

const originItem = computed(() => {
  if (!props.association) return null;
  return isReversed.value ? props.association.destination : props.association.origin;
});

const destinationItem = computed(() => {
  if (!props.association) return null;
  return isReversed.value ? props.association.origin : props.association.destination;
});

const directionIcon = computed(() => {
  return isReversed.value ? 'fa fa-arrow-left' : 'fa fa-arrow-right';
});

const showGroupSelector = computed(() => {
  return formData.type && !['exemplar', 'isChildOf'].includes(formData.type);
});

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

  formData.type = props.association.type || '';
  formData.annotation = props.association.annotation || '';
  formData.groupId = props.association.groupId || 'default';
  isReversed.value = props.association.inverse || false;
  error.value = '';
}

function switchDirection() {
  isReversed.value = !isReversed.value;
  // Reset type when switching direction to avoid confusion
  formData.type = '';
}

function onTypeChange() {
  // Auto-select default group for certain association types
  if (['isChildOf', 'exemplar'].includes(formData.type)) {
    formData.groupId = 'default';
  }
}

function updateAssociation() {
  if (!formData.type) {
    error.value = 'Please select an association type';
    return;
  }

  saving.value = true;
  error.value = '';

  // Simulate updating association - in real app this would be an API call
  setTimeout(() => {
    try {
      const updatedAssociation = {
        ...props.association,
        type: formData.type,
        annotation: formData.annotation,
        groupId: formData.groupId,
        inverse: isReversed.value,
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
