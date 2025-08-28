<template>
  <div class="modal fade" id="addExemplarModal" tabindex="-1" role="dialog" aria-labelledby="addExemplarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addExemplarModalLabel">Add Exemplar</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="error" class="alert alert-danger mb-3" role="alert">
            {{ error }}
          </div>

          <div class="mb-4">
            <strong>Item:</strong>
            <div class="card mt-2">
              <div class="card-body">
                <h6 class="card-title" id="addExemplarOriginTitle">
                  <span v-if="currentItem?.humanCodingScheme" class="badge bg-secondary me-2">
                    {{ currentItem.humanCodingScheme }}
                  </span>
                  {{ currentItem?.title || currentItem?.abbreviatedTitle || currentItem?.identifier || 'No item selected' }}
                </h6>
              </div>
            </div>
          </div>

          <form @submit.prevent="addExemplar">
            <div class="row mb-3">
              <label for="addExemplarFormUrl" class="col-sm-3 col-form-label required">
                URL *
              </label>
              <div class="col-sm-9">
                <input
                  type="url"
                  id="addExemplarFormUrl"
                  class="form-control"
                  v-model="formData.exemplarUrl"
                  placeholder="https://example.com/resource"
                  required
                >
                <div class="form-text">
                  Enter the URL of the exemplar resource
                </div>
              </div>
            </div>

            <div class="row mb-3">
              <label for="addExemplarFormDescription" class="col-sm-3 col-form-label">
                Description
              </label>
              <div class="col-sm-9">
                <textarea
                  id="addExemplarFormDescription"
                  class="form-control"
                  rows="3"
                  v-model="formData.exemplarDescription"
                  placeholder="Optional description of the exemplar"
                ></textarea>
              </div>
            </div>

            <div class="row mb-3">
              <label for="addExemplarFormAnnotation" class="col-sm-3 col-form-label">
                Annotation
              </label>
              <div class="col-sm-9">
                <textarea
                  id="addExemplarFormAnnotation"
                  class="form-control"
                  rows="2"
                  v-model="formData.annotation"
                  placeholder="Optional annotation for this exemplar association"
                ></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" @click="addExemplar" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Add Exemplar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { Modal } from 'bootstrap';

const props = defineProps({
  currentItem: Object,
  show: Boolean
});

const emit = defineEmits(['added', 'hidden']);

const error = ref('');
const saving = ref(false);
const modal = ref(null);

const formData = reactive({
  exemplarUrl: '',
  exemplarDescription: '',
  annotation: ''
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm();
    if (!modal.value) {
      modal.value = new Modal(document.getElementById('addExemplarModal'));
    }
    modal.value.show();
  } else if (modal.value) {
    modal.value.hide();
  }
});

function resetForm() {
  formData.exemplarUrl = '';
  formData.exemplarDescription = '';
  formData.annotation = '';
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

  saving.value = true;
  error.value = '';

  // Simulate adding exemplar - in real app this would be an API call
  setTimeout(() => {
    try {
      const exemplar = {
        id: 'exemplar_' + Date.now(),
        identifier: 'exemplar_' + Date.now(),
        originItem: props.currentItem,
        type: 'exemplar',
        exemplarUrl: formData.exemplarUrl,
        exemplarDescription: formData.exemplarDescription,
        annotation: formData.annotation,
        created: new Date().toISOString()
      };

      emit('added', exemplar);
      if (modal.value) {
        modal.value.hide();
      }
    } catch (e) {
      error.value = 'Failed to add exemplar: ' + e.message;
    } finally {
      saving.value = false;
    }
  }, 1000);
}

// Handle modal hidden event
document.addEventListener('hidden.bs.modal', (event) => {
  if (event.target.id === 'addExemplarModal') {
    emit('hidden');
  }
});
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
