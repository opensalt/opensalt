<template>
  <div class="modal fade" id="associateModal" tabindex="-1" role="dialog" aria-labelledby="associateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="associateModalLabel">Create Association</h5>
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
                <div class="ls-association-item-display border p-3 rounded" id="lsAssociationOriginDisplay">
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
                <div class="ls-association-item-display border p-3 rounded" id="lsAssociationDestinationDisplay">
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
              <label for="associationFormType" class="col-sm-3 col-form-label required text-end">
                Association Type *
              </label>
              <div class="col-sm-9">
                <select
                  id="associationFormType"
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
              <label for="associationFormAnnotation" class="col-sm-3 col-form-label text-end">
                Annotation
              </label>
              <div class="col-sm-9">
                <textarea
                  id="associationFormAnnotation"
                  class="form-control"
                  rows="3"
                  v-model="formData.annotation"
                  placeholder="Optional annotation or description for this association"
                ></textarea>
              </div>
            </div>

            <div v-if="showGroupSelector" class="row mb-3" id="associationFormGroupHolderOuter">
              <label for="associationFormGroup" class="col-sm-3 col-form-label required text-end">
                Association Group
              </label>
              <div class="col-sm-9" id="associationFormGroupHolder">
                <select
                  id="associationFormGroup"
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
          <button type="button" class="btn btn-primary" @click="createAssociation" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Associate
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
  originItem: Object,
  destinationItem: Object,
  show: Boolean,
  availableGroups: Array
});

const emit = defineEmits(['created', 'hidden']);

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
  return isReversed.value ? props.destinationItem : props.originItem;
});

const destinationItem = computed(() => {
  return isReversed.value ? props.originItem : props.destinationItem;
});

const directionIcon = computed(() => {
  return isReversed.value ? 'fa fa-arrow-left' : 'fa fa-arrow-right';
});

const showGroupSelector = computed(() => {
  return formData.type && !['exemplar', 'isChildOf'].includes(formData.type);
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm();
    if (!modal.value) {
      modal.value = new Modal(document.getElementById('associateModal'));
    }
    modal.value.show();
  } else if (modal.value) {
    modal.value.hide();
  }
});

function resetForm() {
  formData.type = '';
  formData.annotation = '';
  formData.groupId = 'default';
  isReversed.value = false;
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

function createAssociation() {
  if (!formData.type) {
    error.value = 'Please select an association type';
    return;
  }

  saving.value = true;
  error.value = '';

  // Simulate creating association - in real app this would be an API call
  setTimeout(() => {
    try {
      const association = {
        id: 'assoc_' + Date.now(),
        identifier: 'assoc_' + Date.now(),
        origin: {
          identifier: originItem.value?.identifier,
          title: originItem.value?.title
        },
        destination: {
          identifier: destinationItem.value?.identifier,
          title: destinationItem.value?.title
        },
        type: formData.type,
        annotation: formData.annotation,
        groupId: formData.groupId,
        created: new Date().toISOString()
      };

      emit('created', association);
      if (modal.value) {
        modal.value.hide();
      }
    } catch (e) {
      error.value = 'Failed to create association: ' + e.message;
    } finally {
      saving.value = false;
    }
  }, 1000);
}

// Handle modal hidden event
document.addEventListener('hidden.bs.modal', (event) => {
  if (event.target.id === 'associateModal') {
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
