<template>
  <BaseModal
    id="deleteAssociationModal"
    :is-open="show"
    title="Delete Association"
    aria-labelledby="deleteAssociationModalLabel"
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

    <div
      class="alert alert-warning"
      role="alert"
    >
      <strong>Warning:</strong> This action cannot be undone.
    </div>

    <p>Are you sure you want to delete this association?</p>

    <div
      v-if="association"
      class="mt-3"
    >
      <div class="card">
        <div class="card-body">
          <h6 class="card-title text-muted mb-3">
            Association Details
          </h6>

          <div class="mb-3">
            <strong class="d-block small text-muted">Type</strong>
            <span class="badge bg-primary">{{ associationTypeLabel }}</span>
          </div>

          <div class="mb-3">
            <strong class="d-block small text-muted">From (Origin)</strong>
            <div
              v-if="originItemData"
              class="mt-1"
            >
              <span
                v-if="originItemData.humanCodingScheme"
                class="badge bg-secondary me-2"
              >
                {{ originItemData.humanCodingScheme }}
              </span>
              <span>{{ originDisplayText }}</span>
            </div>
            <div
              v-else
              class="text-muted mt-1"
            >
              {{ originFallbackText }}
            </div>
          </div>

          <div class="mb-0">
            <strong class="d-block small text-muted">To (Destination)</strong>
            <div
              v-if="destinationItemData"
              class="mt-1"
            >
              <span
                v-if="destinationItemData.humanCodingScheme"
                class="badge bg-secondary me-2"
              >
                {{ destinationItemData.humanCodingScheme }}
              </span>
              <span>{{ destinationDisplayText }}</span>
            </div>
            <div
              v-else
              class="text-muted mt-1"
            >
              {{ destinationFallbackText }}
            </div>
          </div>
        </div>
      </div>
    </div>

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
        class="btn btn-danger"
        :disabled="deleting"
        @click="confirmDelete"
      >
        <span
          v-if="deleting"
          class="spinner-border spinner-border-sm me-2"
          role="status"
          aria-hidden="true"
        />
        Delete
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import BaseModal from '../shared/BaseModal.vue';
import { findItem } from '../../utils/tree.js';
import { getAssociationTypeLabel } from '../../utils/associationHelpers.js';
import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';

const props = defineProps({
  show: Boolean,
  association: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['confirmed', 'hidden', 'update:show']);

const currentDocumentStore = useCurrentDocumentStore();

const error = ref('');
const deleting = ref(false);

const originIdentifier = computed(() => {
  return props.association?.originNodeURI?.identifier ||
         props.association?.origin?.identifier;
});

const destinationIdentifier = computed(() => {
  return props.association?.destinationNodeURI?.identifier ||
         props.association?.destination?.identifier;
});

const associationTypeLabel = computed(() => {
  return getAssociationTypeLabel(props.association?.associationType || props.association?.type || '');
});

const originItemData = computed(() => {
  const identifier = originIdentifier.value;
  if (!identifier) return null;

  const items = currentDocumentStore.currentDocument?.items || [];
  return findItem(items, identifier);
});

const destinationItemData = computed(() => {
  const identifier = destinationIdentifier.value;
  if (!identifier) return null;

  const items = currentDocumentStore.currentDocument?.items || [];
  return findItem(items, identifier);
});

const originDisplayText = computed(() => {
  const item = originItemData.value;
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

const destinationDisplayText = computed(() => {
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

watch(() => props.show, (newVal) => {
  if (newVal) {
    resetModal();
  }
});

function resetModal() {
  error.value = '';
  deleting.value = false;
}

function closeModal() {
  emit('update:show', false);
  emit('hidden');
}

function confirmDelete() {
  if (!props.association) {
    error.value = 'No association selected';
    return;
  }

  deleting.value = true;
  error.value = '';

  emit('confirmed', props.association);
  closeModal();

  deleting.value = false;
}

function handleHidden() {
  emit('update:show', false);
  emit('hidden');
}
</script>

<style scoped>
.card-title {
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.card-body {
  padding: 1rem;
}
</style>
