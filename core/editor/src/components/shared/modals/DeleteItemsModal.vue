<template>
  <div class="modal fade" :id="modalId" tabindex="-1" role="dialog" :aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" :id="modalLabel">{{ modalTitle }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="error" class="alert alert-danger mb-3" role="alert">
            {{ error }}
          </div>

          <div v-if="isDeleteFramework">
            <div class="alert alert-danger" role="alert">
              <strong>DANGER:</strong> Are you sure you want to delete this framework?
            </div>
            <form>
              <div class="form-group">
                <label for="deleteFrameworkAcknowledgement">If yes, please type "DELETE" into the text box:</label>
                <input
                  id="deleteFrameworkAcknowledgement"
                  type="text"
                  class="form-control"
                  v-model="deleteConfirmation"
                  @input="checkDeleteConfirmation"
                >
              </div>
            </form>
          </div>

          <div v-else-if="isDeleteMultiple">
            <p>Are you sure you want to delete the <strong>{{ itemsToDelete.length }}</strong> selected items?</p>
            <div class="mt-3">
              <strong>Items to be deleted:</strong>
              <ul class="list-group mt-2">
                <li v-for="item in itemsToDelete" :key="item.identifier" class="list-group-item">
                  <span v-if="item.humanCodingScheme" class="badge bg-secondary me-2">{{ item.humanCodingScheme }}</span>
                  {{ item.title || item.abbreviatedTitle || item.identifier }}
                </li>
              </ul>
            </div>
          </div>

          <div v-else-if="isDeleteWithChildren">
            <div class="alert alert-warning" role="alert">
              <strong>Warning:</strong> This item has children.
            </div>
            <p>Are you sure you want to delete this item <strong>and</strong> all its children?</p>
            <div class="mt-3">
              <strong>Item to be deleted:</strong>
              <div class="card mt-2">
                <div class="card-body">
                  <h6 class="card-title">
                    <span v-if="itemsToDelete[0]?.humanCodingScheme" class="badge bg-secondary me-2">
                      {{ itemsToDelete[0].humanCodingScheme }}
                    </span>
                    {{ itemsToDelete[0]?.title || itemsToDelete[0]?.abbreviatedTitle || itemsToDelete[0]?.identifier }}
                  </h6>
                  <div v-if="itemsToDelete[0]?.children && itemsToDelete[0].children.length > 0" class="mt-2">
                    <small class="text-muted">This will also delete {{ itemsToDelete[0].children.length }} child items.</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else>
            <p>Are you sure you want to delete this item?</p>
            <div class="mt-3">
              <strong>Item to be deleted:</strong>
              <div class="card mt-2">
                <div class="card-body">
                  <h6 class="card-title">
                    <span v-if="itemsToDelete[0]?.humanCodingScheme" class="badge bg-secondary me-2">
                      {{ itemsToDelete[0].humanCodingScheme }}
                    </span>
                    {{ itemsToDelete[0]?.title || itemsToDelete[0]?.abbreviatedTitle || itemsToDelete[0]?.identifier }}
                  </h6>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button
            type="button"
            class="btn"
            :class="buttonClass"
            @click="confirmDelete"
            :disabled="!canDelete"
          >
            <span v-if="deleting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ buttonText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Modal } from 'bootstrap';

const props = defineProps({
  items: Array,
  deleteType: {
    type: String,
    default: 'single' // 'single', 'multiple', 'with-children', 'framework'
  },
  show: Boolean
});

const emit = defineEmits(['confirmed', 'hidden']);

const error = ref('');
const deleting = ref(false);
const deleteConfirmation = ref('');
const modal = ref(null);

const modalId = computed(() => {
  switch (props.deleteType) {
    case 'framework': return 'deleteFrameworkModal';
    case 'multiple': return 'deleteMultipleItemsModal';
    case 'with-children': return 'deleteItemAndChildrenModal';
    default: return 'deleteOneItemModal';
  }
});

const modalLabel = computed(() => {
  switch (props.deleteType) {
    case 'framework': return 'deleteFrameworkModalLabel';
    case 'multiple': return 'deleteMultipleItemsModalLabel';
    case 'with-children': return 'deleteItemAndChildrenModalLabel';
    default: return 'deleteOneItemModalLabel';
  }
});

const modalTitle = computed(() => {
  switch (props.deleteType) {
    case 'framework': return 'Delete Framework';
    case 'multiple': return 'Delete Items';
    case 'with-children': return 'Delete Item and Children';
    default: return 'Delete Item';
  }
});

const itemsToDelete = computed(() => {
  return props.items || [];
});

const isDeleteFramework = computed(() => props.deleteType === 'framework');
const isDeleteMultiple = computed(() => props.deleteType === 'multiple');
const isDeleteWithChildren = computed(() => props.deleteType === 'with-children');

const canDelete = computed(() => {
  if (isDeleteFramework.value) {
    return deleteConfirmation.value === 'DELETE';
  }
  return true;
});

const buttonClass = computed(() => {
  return isDeleteFramework.value && !canDelete.value ? 'btn-danger btn-disabled' : 'btn-danger';
});

const buttonText = computed(() => {
  switch (props.deleteType) {
    case 'framework': return 'Delete';
    case 'multiple': return `Delete ${itemsToDelete.value.length} Items`;
    case 'with-children': return 'Delete Item and Children';
    default: return 'Delete';
  }
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    resetModal();
    if (!modal.value) {
      modal.value = new Modal(document.getElementById(modalId.value));
    }
    modal.value.show();
  } else if (modal.value) {
    modal.value.hide();
  }
});

function resetModal() {
  error.value = '';
  deleting.value = false;
  deleteConfirmation.value = '';
}

function checkDeleteConfirmation() {
  // This is handled by the computed property
}

function confirmDelete() {
  if (!canDelete.value) return;

  deleting.value = true;
  error.value = '';

  try {
    emit('confirmed', {
      items: itemsToDelete.value,
      deleteType: props.deleteType
    });
    if (modal.value) {
      modal.value.hide();
    }
  } catch (e) {
    error.value = 'Failed to delete: ' + e.message;
  } finally {
    deleting.value = false;
  }
}

// Handle modal hidden event
document.addEventListener('hidden.bs.modal', (event) => {
  if (event.target.id === modalId.value) {
    emit('hidden');
  }
});
</script>

<style scoped>
.btn-disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.list-group-item {
  padding: 0.5rem 1rem;
}

.card-title {
  margin-bottom: 0.5rem;
}
</style>
