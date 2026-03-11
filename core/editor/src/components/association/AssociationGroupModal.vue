<template>
  <div class="modal fade" :id="modalId" tabindex="-1" role="dialog" :aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" :id="modalLabel">{{ modalTitle }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="loading" class="d-flex justify-content-center align-items-center p-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
          <div v-else-if="error" class="alert alert-danger" role="alert">
            {{ error }}
          </div>
          <div v-else-if="mode === 'list'">
            <div class="mb-4">
              <p>Use association groups to organize a competency framework in different ways. For example, you may want to organize your framework by subject area by default, but use an alternative taxonomy to organize the same set of competencies by a set of reporting categories or by grade level. All frameworks include a "Default" association group.</p>
            </div>

            <table class="table table-striped">
              <thead>
                <tr>
                  <th style="width:30%">Association Group Name</th>
                  <th>Description</th>
                  <th style="width:20%">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr data-assocgroupid="default">
                  <td><strong>– Default Group –</strong></td>
                  <td>—</td>
                  <td></td>
                </tr>
                <tr v-for="group in editableAssociationGroups" :key="group.id" :data-assocgroupid="group.id">
                  <td>{{ group.title }}</td>
                  <td>{{ group.description || '—' }}</td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <button
                        type="button"
                        class="btn btn-outline-primary"
                        @click="editGroup(group)"
                        title="Edit group"
                      >
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button
                        type="button"
                        class="btn btn-outline-danger"
                        @click="deleteGroup(group)"
                        title="Delete group"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="mt-3">
              <button type="button" class="btn btn-primary" @click="showAddModal">
                <i class="bi bi-plus-circle"></i> Add a New Association Group
              </button>
            </div>
          </div>

          <form v-else @submit.prevent="saveGroup" name="ls_def_association_grouping">
            <div class="row mb-3">
              <label for="ls_def_association_grouping_title" class="col-sm-3 col-form-label required">
                Title *
              </label>
              <div class="col-sm-9">
                <input
                  type="text"
                  class="form-control"
                  id="ls_def_association_grouping_title"
                  name="ls_def_association_grouping[title]"
                  v-model="formData.title"
                  required
                  placeholder="Enter group title"
                >
              </div>
            </div>

            <div class="row mb-3">
              <label for="ls_def_association_grouping_description" class="col-sm-3 col-form-label">
                Description
              </label>
              <div class="col-sm-9">
                <textarea
                  class="form-control"
                  id="ls_def_association_grouping_description"
                  name="ls_def_association_grouping[description]"
                  rows="3"
                  v-model="formData.description"
                  placeholder="Optional description for this association group"
                ></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button v-if="mode === 'list'" type="button" class="btn btn-secondary" data-bs-dismiss="modal">Done</button>
          <template v-else>
            <button type="button" class="btn btn-secondary" @click="cancelEdit">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveGroup" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
              {{ isEdit ? 'Save Changes' : 'Create' }}
            </button>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { Modal } from 'bootstrap';

const props = defineProps({
  show: Boolean,
  initialMode: {
    type: String,
    default: 'list' // 'list', 'add', 'edit'
  },
  associationGroups: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['saved', 'deleted', 'hidden']);

const loading = ref(false);
const error = ref('');
const saving = ref(false);
const modal = ref(null);
const mode = ref(props.initialMode);
const isEdit = ref(false);
const editingGroup = ref(null);

const formData = reactive({
  title: '',
  description: ''
});

const modalId = computed(() => 'manageAssocGroupsModal');
const modalLabel = computed(() => 'manageAssocGroupsModalLabel');
const modalTitle = computed(() => {
  switch (mode.value) {
    case 'add': return 'Add New Association Group';
    case 'edit': return 'Edit Association Group';
    default: return 'Manage Association Groups';
  }
});

const editableAssociationGroups = computed(() => {
  return props.associationGroups.filter(group =>
    group.id !== 'all' && group.id !== 'default'
  );
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    mode.value = props.initialMode;
    resetForm();
    if (!modal.value) {
      modal.value = new Modal(document.getElementById(modalId.value));
    }
    modal.value.show();
  } else if (modal.value) {
    modal.value.hide();
  }
});

function resetForm() {
  formData.title = '';
  formData.description = '';
  isEdit.value = false;
  editingGroup.value = null;
  error.value = '';
}

function showAddModal() {
  mode.value = 'add';
  resetForm();
}

function editGroup(group) {
  mode.value = 'edit';
  isEdit.value = true;
  editingGroup.value = group;
  formData.title = group.title || '';
  formData.description = group.description || '';
}

function cancelEdit() {
  mode.value = 'list';
  resetForm();
}

function saveGroup() {
  try {
    const groupData = {
      title: formData.title,
      description: formData.description
    };

    if (isEdit.value && editingGroup.value) {
      groupData.id = editingGroup.value.id;
    }

    emit('saved', groupData);
    mode.value = 'list';
    resetForm();
  } catch (e) {
    error.value = 'Failed to save group: ' + e.message;
  } finally {
    saving.value = false;
  }
}

function deleteGroup(group) {
  if (confirm(`Are you sure you want to delete the association group "${group.title}"?`)) {
    emit('deleted', group);
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
.modal-dialog {
  max-width: 90vw;
}

.table th {
  border-top: none;
  font-weight: 600;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.form-control:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
