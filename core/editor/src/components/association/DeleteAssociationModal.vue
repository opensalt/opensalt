<template>
  <div
    class="modal fade"
    id="deleteAssociationModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="deleteAssociationModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteAssociationModalLabel">Delete Association</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <div v-if="error" class="alert alert-danger mb-3" role="alert">
            {{ error }}
          </div>

          <div class="alert alert-warning" role="alert">
            <strong>Warning:</strong> This action cannot be undone.
          </div>

          <p>Are you sure you want to delete this association?</p>

          <!-- Association details -->
          <div v-if="association" class="mt-3">
            <div class="card">
              <div class="card-body">
                <h6 class="card-title text-muted mb-3">Association Details</h6>

                <!-- Association Type -->
                <div class="mb-3">
                  <strong class="d-block small text-muted">Type</strong>
                  <span class="badge bg-primary">{{ associationTypeLabel }}</span>
                </div>

                <!-- Origin Item -->
                <div class="mb-3">
                  <strong class="d-block small text-muted">From (Origin)</strong>
                  <div v-if="originItemData" class="mt-1">
                    <span v-if="originItemData.humanCodingScheme" class="badge bg-secondary me-2">
                      {{ originItemData.humanCodingScheme }}
                    </span>
                    <span>{{ originDisplayText }}</span>
                  </div>
                  <div v-else class="text-muted mt-1">
                    {{ originFallbackText }}
                  </div>
                </div>

                <!-- Destination Item -->
                <div class="mb-0">
                  <strong class="d-block small text-muted">To (Destination)</strong>
                  <div v-if="destinationItemData" class="mt-1">
                    <span v-if="destinationItemData.humanCodingScheme" class="badge bg-secondary me-2">
                      {{ destinationItemData.humanCodingScheme }}
                    </span>
                    <span>{{ destinationDisplayText }}</span>
                  </div>
                  <div v-else class="text-muted mt-1">
                    {{ destinationFallbackText }}
                  </div>
                </div>
              </div>
            </div>
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
            class="btn btn-danger"
            @click="confirmDelete"
            :disabled="deleting"
          >
            <span
              v-if="deleting"
              class="spinner-border spinner-border-sm me-2"
              role="status"
              aria-hidden="true"
            ></span>
            Delete
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/* global document */
import { ref, computed, watch, nextTick } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
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
const modal = ref(null);

// Association type labels mapping
const associationTypeLabels = {
  isRelatedTo: 'Is Related To',
  exactMatchOf: 'Exact Match Of',
  isPartOf: 'Is Part Of',
  hasSkillLevel: 'Has Skill Level',
  isPeerOf: 'Is Peer Of',
  exemplar: 'Exemplar',
  isTranslationOf: 'Is Translation Of',
  isChildOf: 'Is Child Of',
  replacedBy: 'Replaced By',
  precedes: 'Precedes'
};

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

// Get the association type label
const associationTypeLabel = computed(() => {
  const type = props.association?.associationType || props.association?.type || '';
  if (type.startsWith('ext:')) {
    return type; // Return custom type as-is
  }
  return associationTypeLabels[type] || type || 'Unknown';
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

// Display text for origin item
const originDisplayText = computed(() => {
  const item = originItemData.value;
  if (!item) return 'Unknown item';

  // Prefer abbreviatedStatement, fall back to shortened fullStatement
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

// Display text for destination item
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

watch(() => props.show, async (newVal) => {
  if (newVal) {
    resetModal();
    // Wait for DOM to be ready before accessing the modal element
    await nextTick();
    const modalEl = document.getElementById('deleteAssociationModal');
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

function resetModal() {
  error.value = '';
  deleting.value = false;
}

function confirmDelete() {
  if (!props.association) {
    error.value = 'No association selected';
    return;
  }

  deleting.value = true;
  error.value = '';

  emit('confirmed', props.association);

  // Parent component is responsible for closing the modal
  // after successful deletion, but we can close it here
  // if the parent doesn't handle it
  if (modal.value) {
    modal.value.hide();
  }

  deleting.value = false;
}

// Handle modal hidden event
document.addEventListener('hidden.bs.modal', (event) => {
  if (event.target.id === 'deleteAssociationModal') {
    emit('update:show', false);
    emit('hidden');
  }
});
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
