<template>
  <!-- Backdrop -->
  <div v-if="props.show" class="modal-backdrop fade" :class="{ 'show': props.show }" @click="closeModal"></div>

  <!-- Modal -->
  <div class="modal fade" :class="{ 'show d-block': props.show }" tabindex="-1" id="addNewChildModal" aria-hidden="true" :style="{ display: props.show ? 'block' : 'none' }">
    <div class="modal-dialog modal-xl" role="document" style="width:99%" @click.stop>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addNewChildModalLabel">{{ isEdit ? 'Edit Child Item' : 'Add New Child Item' }}</h5>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="error" class="alert alert-danger" role="alert">
            {{ error }}
          </div>
          <form @submit.prevent="saveItem" name="ls_item">
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
                  <option value="general">General Item</option>
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
          <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
          <button type="button" class="btn btn-primary" @click="saveItem" :disabled="saving">
            <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ isEdit ? 'Update' : 'Add' }} Item
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import EasyMDE from '../EasyMDE.vue';

const props = defineProps({
  parentItem: Object,
  show: Boolean,
  itemType: String,
  item: Object
});

const emit = defineEmits(['created', 'updated', 'hidden']);

const error = ref('');
const saving = ref(false);

const isEdit = computed(() => !!props.item);

const formData = reactive({
  humanCodingScheme: '',
  abbreviatedStatement: '',
  fullStatement: '',
  itemType: 'general',
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
  if (newVal) {
    loadFormData();
  }
});

watch(() => props.item, (newItem) => {
  if (newItem) {
    loadFormData();
  }
}, { immediate: true });

function loadFormData() {
  if (isEdit.value) {
    formData.humanCodingScheme = props.item.humanCodingScheme || '';
    formData.abbreviatedStatement = props.item.abbreviatedStatement || '';
    formData.fullStatement = props.item.fullStatement || '';
    formData.itemType = props.item.itemType || 'general';
    formData.subjects = props.item.subjects || [];
    formData.notes = props.item.notes || '';
  } else {
    // Reset for new
    formData.humanCodingScheme = '';
    formData.abbreviatedStatement = '';
    formData.fullStatement = '';
    formData.itemType = props.itemType || 'general';
    formData.subjects = [];
    formData.notes = '';
  }
}

function saveItem() {
  saving.value = true;
  error.value = '';

  // Simulate saving - in real app, API call to create/update item
  setTimeout(() => {
    try {
      let savedItem;
      if (isEdit.value) {
        savedItem = {
          ...props.item,
          humanCodingScheme: formData.humanCodingScheme,
          abbreviatedStatement: formData.abbreviatedStatement,
          fullStatement: formData.fullStatement,
          itemType: formData.itemType,
          subjects: formData.subjects,
          notes: formData.notes,
          updated: new Date().toISOString()
        };
        emit('updated', savedItem);
      } else {
        savedItem = {
          humanCodingScheme: formData.humanCodingScheme,
          abbreviatedStatement: formData.abbreviatedStatement,
          fullStatement: formData.fullStatement,
          itemType: formData.itemType,
          subjects: formData.subjects,
          notes: formData.notes,
          language: 'en',
          children: [],
          associations: []
        };

        // Set isChildOf association if parent provided
        if (props.parentItem && formData.humanCodingScheme) {
          savedItem.associations.push({
            type: 'isChildOf',
            originNode: props.parentItem.humanCodingScheme,
            targetNode: formData.humanCodingScheme,
            originIdentifier: props.parentItem.humanCodingScheme,
            targetIdentifier: formData.humanCodingScheme
          });
        }

        savedItem.created = new Date().toISOString();
        emit('created', savedItem);
      }
    } catch (e) {
      error.value = 'Failed to save item: ' + e.message;
    } finally {
      saving.value = false;
    }
  }, 1000);
}

function closeModal() {
  emit('hidden');
}
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
