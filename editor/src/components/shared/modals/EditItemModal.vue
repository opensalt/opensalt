<template>
  <div class="modal fade" id="editItemModal" tabindex="-1" role="dialog" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="width:99%">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editItemModalLabel">Edit Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="loading" class="d-flex justify-content-center align-items-center p-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading form...</span>
            </div>
          </div>
          <div v-else-if="error" class="alert alert-danger" role="alert">
            {{ error }}
          </div>
          <form v-else @submit.prevent="saveItem" name="ls_item">
            <div class="row mb-3">
              <label for="ls_item_humanCodingScheme" class="col-sm-2 col-form-label">Human Coding Scheme *</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="ls_item_humanCodingScheme" name="ls_item[humanCodingScheme]" v-model="formData.humanCodingScheme" required placeholder="e.g., CCSS.ELA-Literacy.RL.1.1">
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_abbreviatedStatement" class="col-sm-2 col-form-label">Abbreviated Statement</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="ls_item_abbreviatedStatement" name="ls_item[abbreviatedStatement]" v-model="formData.abbreviatedStatement" placeholder="Short version of the statement">
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_fullStatement" class="col-sm-2 col-form-label">Full Statement *</label>
              <div class="col-sm-10">
                <EasyMDE
                  id="ls_item_fullStatement"
                  name="ls_item[fullStatement]"
                  v-model="formData.fullStatement"
                  :required="true"
                  placeholder="Enter the complete statement for this competency item"
                />
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_itemType" class="col-sm-2 col-form-label">Item Type</label>
              <div class="col-sm-10">
                <select class="form-select" id="ls_item_itemType" name="ls_item[itemType]" v-model="formData.itemType">
                  <option value="">General Item</option>
                  <option value="assessment">Assessment</option>
                  <option value="course">Course</option>
                  <option value="credential">Credential</option>
                  <option value="job">Job</option>
                  <option value="organization">Organization</option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_subjects" class="col-sm-2 col-form-label">Subjects</label>
              <div class="col-sm-10">
                <select class="form-select" id="ls_item_subjects" name="ls_item[subjects][]" multiple v-model="formData.subjects">
                  <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">
                    {{ subject.title }}
                  </option>
                </select>
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_item_notes" class="col-sm-2 col-form-label">Notes</label>
              <div class="col-sm-10">
                <textarea class="form-control" id="ls_item_notes" name="ls_item[notes]" rows="3" v-model="formData.notes" placeholder="Additional notes or comments"></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" @click="saveItem" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { Modal } from 'bootstrap';
import EasyMDE from '../EasyMDE.vue';

const props = defineProps({
  item: Object,
  show: Boolean
});

const emit = defineEmits(['saved', 'hidden']);

const loading = ref(false);
const error = ref('');
const saving = ref(false);
const modal = ref(null);

const formData = reactive({
  humanCodingScheme: '',
  abbreviatedStatement: '',
  fullStatement: '',
  itemType: '',
  subjects: [],
  notes: ''
});

const availableSubjects = ref([
  { id: 'math', title: 'Mathematics' },
  { id: 'science', title: 'Science' },
  { id: 'english', title: 'English Language Arts' },
  { id: 'history', title: 'History' }
]);

watch(() => props.show, (newVal) => {
  if (newVal && props.item) {
    loadItemData();
    if (!modal.value) {
      modal.value = new Modal(document.getElementById('editItemModal'));
    }
    modal.value.show();
  } else if (modal.value) {
    modal.value.hide();
  }
});

watch(() => props.item, (newItem) => {
  if (newItem) {
    loadItemData();
  }
}, { immediate: true });

function loadItemData() {
  if (!props.item) return;

  loading.value = true;
  error.value = '';

  // Simulate loading - in real app, API call
  setTimeout(() => {
    formData.humanCodingScheme = props.item.humanCodingScheme || '';
    formData.abbreviatedStatement = props.item.abbreviatedStatement || '';
    formData.fullStatement = props.item.fullStatement || '';
    formData.itemType = props.item.itemType || '';
    formData.subjects = props.item.subjects || [];
    formData.notes = props.item.notes || '';

    loading.value = false;
  }, 500);
}

function saveItem() {
  saving.value = true;
  error.value = '';

  // Simulate saving - in real app, API call to update item
  setTimeout(() => {
    try {
      // Merge updated fields back into original item structure
      const updatedItem = {
        ...props.item,
        humanCodingScheme: formData.humanCodingScheme,
        abbreviatedStatement: formData.abbreviatedStatement,
        fullStatement: formData.fullStatement,
        itemType: formData.itemType,
        subjects: formData.subjects,
        notes: formData.notes,
        updated: new Date().toISOString()
      };
      emit('saved', updatedItem);
      if (modal.value) {
        modal.value.hide();
      }
    } catch (e) {
      error.value = 'Failed to save item: ' + e.message;
    } finally {
      saving.value = false;
    }
  }, 1000);
}

// Handle modal hidden event
document.addEventListener('hidden.bs.modal', (event) => {
  if (event.target.id === 'editItemModal') {
    emit('hidden');
  }
});
</script>

<style scoped>
.modal-dialog {
  max-width: 95vw;
}

.form-control:focus,
.form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.easymde-wrapper {
  min-height: 200px;
}
</style>
