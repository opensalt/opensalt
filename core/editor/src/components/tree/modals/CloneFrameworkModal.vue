<template>
  <div
    id="cloneFrameworkModal"
    ref="modalElement"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="cloneFrameworkModalLabel"
    aria-hidden="true"
  >
    <div
      class="modal-dialog"
      role="document"
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="cloneFrameworkModalLabel"
            class="modal-title"
          >
            Clone Framework
          </h5>
          <button
            type="button"
            class="btn-close"
            :disabled="isCloning"
            data-bs-dismiss="modal"
            aria-label="Close"
          />
        </div>
        <div class="modal-body">
          <div
            v-if="isCloning"
            class="d-flex align-items-center gap-2 mb-3"
            role="status"
            aria-live="polite"
          >
            <span
              class="spinner-border spinner-border-sm"
              aria-hidden="true"
            />
            <span>Cloning framework… This may take a minute for large frameworks.</span>
          </div>
          <div
            v-if="errorMessage"
            class="alert alert-danger"
            role="alert"
          >
            {{ errorMessage }}
          </div>
          <p>Are you sure you want to clone this framework?</p>
          <p><strong>{{ frameworkTitle }}</strong></p>
          <div class="form-check">
            <input
              id="cloneAssociateWithOriginal"
              v-model="associateWithOriginal"
              class="form-check-input"
              type="checkbox"
              :disabled="isCloning"
            >
            <label
              class="form-check-label"
              for="cloneAssociateWithOriginal"
            >
              Associate with original framework
              <small class="text-muted d-block">
                Add exactMatchOf associations from each cloned item to the matching item in the source framework.
              </small>
            </label>
          </div>
          <div class="form-check mt-2">
            <input
              id="cloneCopyAssociations"
              v-model="copyAssociations"
              class="form-check-input"
              type="checkbox"
              :disabled="isCloning"
            >
            <label
              class="form-check-label"
              for="cloneCopyAssociations"
            >
              Copy associations
              <small class="text-muted d-block">
                Copy non-hierarchy associations into the clone, remapping endpoints that pointed at items in the source framework.
              </small>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            :disabled="isCloning"
            data-bs-dismiss="modal"
          >
            Cancel
          </button>
          <button
            type="button"
            class="btn btn-primary"
            :disabled="isCloning"
            @click="confirmClone"
          >
            <span
              v-if="isCloning"
              class="spinner-border spinner-border-sm me-1"
              aria-hidden="true"
            />
            Clone Framework
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import Modal from 'bootstrap/js/dist/modal';

const props = defineProps({
  show: Boolean,
  frameworkTitle: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['confirmed', 'hidden']);

const modalElement = ref(null);
const associateWithOriginal = ref(false);
const copyAssociations = ref(false);
const isCloning = ref(false);
const errorMessage = ref('');
let bsModal = null;

onMounted(() => {
  if (modalElement.value) {
    bsModal = new Modal(modalElement.value);
    modalElement.value.addEventListener('hide.bs.modal', (event) => {
      if (isCloning.value) {
        event.preventDefault();
        event.stopPropagation();
      }
    });
    modalElement.value.addEventListener('hidden.bs.modal', () => {
      isCloning.value = false;
      errorMessage.value = '';
      emit('hidden');
    });
  }
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    associateWithOriginal.value = false;
    copyAssociations.value = false;
    isCloning.value = false;
    errorMessage.value = '';
  }
  if (bsModal) {
    if (newVal) {
      bsModal.show();
    } else if (!isCloning.value) {
      bsModal.hide();
    }
  }
});

function confirmClone() {
  if (isCloning.value) {
    return;
  }

  errorMessage.value = '';
  isCloning.value = true;
  emit('confirmed', {
    associateWithOriginal: associateWithOriginal.value,
    copyAssociations: copyAssociations.value,
    onError: (message) => {
      isCloning.value = false;
      errorMessage.value = message || 'Failed to clone framework. Please try again.';
    }
  });
}
</script>
