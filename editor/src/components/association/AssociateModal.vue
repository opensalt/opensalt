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
              <div class="col-auto d-flex flex-column align-items-center">
                <i :class="directionIcon" class="bi fs-1 text-muted mb-2"></i>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="switchDirection">
                  ↔ Switch Direction
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
          <button type="button" class="btn btn-primary" @click="createAssociation" :disabled="saving || (formData.type === 'other' && !isValidCustomType)">
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

const originItem = computed(() => props.originItem);
const destinationItem = computed(() => props.destinationItem);

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

function onTypeChange() {
  // Auto-select default group for certain association types
  if (['isChildOf', 'exemplar'].includes(formData.type)) {
    formData.groupId = 'default';
  }
}

function switchDirection() {
  isReversed.value = !isReversed.value;
  const bidirectionalTypes = ['isRelatedTo', 'isPeerOf', 'exactMatchOf', 'isTranslationOf'];
  if (!bidirectionalTypes.includes(formData.type) && formData.type !== 'other') {
    formData.type = '';
  }
}

function createAssociation() {
  if (!formData.type) {
    error.value = 'Please select an association type';
    return;
  }

  const finalType = formData.type === 'other' ? customType.value.trim() : formData.type;

  saving.value = true;
  error.value = '';

  // Simulate creating association - in real app this would be an API call
  setTimeout(() => {
    try {
      const effectiveOrigin = isReversed.value ? props.destinationItem : props.originItem;
      const effectiveDestination = isReversed.value ? props.originItem : props.destinationItem;
      const association = {
        id: 'assoc_' + Date.now(),
        identifier: 'assoc_' + Date.now(),
        origin: {
          identifier: effectiveOrigin?.identifier,
          title: effectiveOrigin?.title,
          humanCodingScheme: effectiveOrigin?.humanCodingScheme
        },
        destination: {
          identifier: effectiveDestination?.identifier,
          title: effectiveDestination?.title,
          humanCodingScheme: effectiveDestination?.humanCodingScheme
        },
        type: finalType,
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
