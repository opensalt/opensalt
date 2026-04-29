<template>
  <BaseModal
    id="addExemplarModal"
    :is-open="show"
    title="Add Exemplar"
    size="lg"
    aria-labelledby="addExemplarModalLabel"
    @update:is-open="(val) => $emit('update:show', val)"
    @hidden="handleHidden"
  >
    <div
      v-if="error"
      class="alert alert-danger mb-3"
      role="alert"
    >
      {{ error }}
    </div>

    <div class="mb-4">
      <strong>Item:</strong>
      <div class="card mt-2">
        <div class="card-body">
          <h6
            id="addExemplarOriginTitle"
            class="card-title"
          >
            <span
              v-if="currentItem?.humanCodingScheme"
              class="badge bg-secondary me-2"
            >
              {{ currentItem.humanCodingScheme }}
            </span>
            {{ currentItem?.title || currentItem?.abbreviatedTitle || currentItem?.identifier || 'No item selected' }}
          </h6>
        </div>
      </div>
    </div>

    <form @submit.prevent="addExemplar">
      <div class="row mb-3">
        <label
          for="addExemplarFormUrl"
          class="col-sm-3 col-form-label required"
        >
          URL *
        </label>
        <div class="col-sm-9">
          <input
            id="addExemplarFormUrl"
            v-model="formData.exemplarUrl"
            type="url"
            class="form-control"
            placeholder="https://example.com/resource"
            required
          >
          <div class="form-text">
            Enter the URL of the exemplar resource
          </div>
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="addExemplarFormDescription"
          class="col-sm-3 col-form-label"
        >
          Description
        </label>
        <div class="col-sm-9">
          <textarea
            id="addExemplarFormDescription"
            v-model="formData.exemplarDescription"
            class="form-control"
            rows="3"
            placeholder="Optional description of the exemplar"
          />
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="addExemplarFormAnnotation"
          class="col-sm-3 col-form-label"
        >
          Annotation
        </label>
        <div class="col-sm-9">
          <textarea
            id="addExemplarFormAnnotation"
            v-model="formData.annotation"
            class="form-control"
            rows="2"
            placeholder="Optional annotation for this exemplar association"
          />
        </div>
      </div>

      <div
        v-if="filteredGroups.length > 0"
        class="row mb-3"
      >
        <label
          for="addExemplarFormGroup"
          class="col-sm-3 col-form-label"
        >
          Association Group
        </label>
        <div class="col-sm-9">
          <select
            id="addExemplarFormGroup"
            v-model="formData.groupId"
            class="form-select"
          >
            <option value="default">
              None
            </option>
            <option
              v-for="group in filteredGroups"
              :key="group.id"
              :value="group.id"
            >
              {{ group.title }}
            </option>
          </select>
        </div>
      </div>
    </form>

    <template #footer>
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
        @click="addExemplar"
      >
        <span
          v-if="saving"
          class="spinner-border spinner-border-sm me-2"
          role="status"
        />
        Add Exemplar
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import BaseModal from '../BaseModal.vue';
import { useFilterStore } from '../../../stores/filterStore';

const props = defineProps({
  currentItem: {
    type: Object,
    default: null
  },
  show: {
    type: Boolean,
    default: false
  },
  associationGroups: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['added', 'hidden', 'update:show']);

const filterStore = useFilterStore();
const error = ref('');
const saving = ref(false);

const formData = reactive({
  exemplarUrl: '',
  exemplarDescription: '',
  annotation: '',
  groupId: 'default'
});

const filteredGroups = computed(() => {
  return props.associationGroups.filter(g => 
    g.id !== 'default' && 
    g.id !== 'all' && 
    g.title !== 'Default' && 
    g.title !== 'All'
  );
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm();
  }
});

function resetForm() {
  formData.exemplarUrl = '';
  formData.exemplarDescription = '';
  formData.annotation = '';
  formData.groupId = filterStore.selectedAssociationGroup === 'all' ? 'default' : filterStore.selectedAssociationGroup;
  error.value = '';
}

function validateUrl(url) {
  try {
    new URL(url);
    return true;
  } catch {
    return false;
  }
}

function closeModal() {
  emit('update:show', false);
  emit('hidden');
}

function addExemplar() {
  if (!formData.exemplarUrl.trim()) {
    error.value = 'URL is required';
    return;
  }

  if (!validateUrl(formData.exemplarUrl)) {
    error.value = 'Please enter a valid URL';
    return;
  }

  if (formData.exemplarUrl.length > 300) {
    error.value = 'URL must be 300 characters or less';
    return;
  }

  const exemplarData = {
      originNodeIdentifier: props.currentItem?.identifier,
      destinationNodeUri: formData.exemplarUrl,
      associationType: 'exemplar',
      annotation: formData.annotation,
      notes: formData.exemplarDescription,
      assocGroup: formData.groupId !== 'default' ? formData.groupId : null
  };

  emit('added', exemplarData);
  closeModal();
}

function handleHidden() {
  emit('hidden');
}
</script>

<style scoped>
.modal-dialog {
  max-width: 80vw;
}

.form-control:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.card-title {
  margin-bottom: 0.5rem;
}

.badge {
  font-size: 0.75em;
}
</style>
