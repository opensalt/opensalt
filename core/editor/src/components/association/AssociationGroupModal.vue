<template>
  <BaseModal
    :id="modalId"
    :is-open="show"
    :title="modalTitle"
    size="xl"
    :aria-labelledby="modalLabel"
    @update:is-open="handleVisibilityChange"
    @hidden="handleHidden"
  >
    <div
      v-if="loading"
      class="d-flex justify-content-center align-items-center p-4"
    >
      <div
        class="spinner-border text-primary"
        role="status"
      >
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <div
      v-else-if="error"
      class="alert alert-danger"
      role="alert"
    >
      {{ error }}
    </div>
    <div v-else-if="mode === 'list'">
      <div class="mb-4">
        <p>Use association groups to organize a competency framework in different ways. For example, you may want to organize your framework by subject area by default, but use an alternative taxonomy to organize the same set of competencies by a set of reporting categories or by grade level. All frameworks include a "Default" association group.</p>
      </div>

      <table class="table table-striped">
        <thead>
          <tr>
            <th style="width:30%">
              Association Group Name
            </th>
            <th>Description</th>
            <th style="width:20%">
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <tr data-assocgroupid="default">
            <td><strong>– Default Group –</strong></td>
            <td>—</td>
            <td />
          </tr>
          <tr
            v-for="group in editableAssociationGroups"
            :key="group.id"
            :data-assocgroupid="group.id"
          >
            <td>{{ group.title }}</td>
            <td>{{ group.description || '—' }}</td>
            <td>
              <div class="btn-group btn-group-sm">
                <button
                  type="button"
                  class="btn btn-outline-primary"
                  title="Edit group"
                  @click="editGroup(group)"
                >
                  <i
                    class="bi bi-pencil"
                    aria-hidden="true"
                  />
                </button>
                <button
                  type="button"
                  class="btn btn-outline-danger"
                  title="Delete group"
                  @click="deleteGroup(group)"
                >
                  <i
                    class="bi bi-trash"
                    aria-hidden="true"
                  />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="mt-3">
        <button
          type="button"
          class="btn btn-primary"
          @click="showAddModal"
        >
          <i
            class="bi bi-plus-circle"
            aria-hidden="true"
          /> Add a New Association Group
        </button>
      </div>
    </div>

    <form
      v-else
      name="ls_def_association_grouping"
      @submit.prevent="saveGroup"
    >
      <div class="row mb-3">
        <label
          for="ls_def_association_grouping_title"
          class="col-sm-3 col-form-label required"
        >
          Title *
        </label>
        <div class="col-sm-9">
          <input
            id="ls_def_association_grouping_title"
            v-model="formData.title"
            type="text"
            class="form-control"
            name="ls_def_association_grouping[title]"
            required
            placeholder="Enter group title"
          >
        </div>
      </div>

      <div class="row mb-3">
        <label
          for="ls_def_association_grouping_description"
          class="col-sm-3 col-form-label"
        >
          Description
        </label>
        <div class="col-sm-9">
          <textarea
            id="ls_def_association_grouping_description"
            v-model="formData.description"
            class="form-control"
            name="ls_def_association_grouping[description]"
            rows="3"
            placeholder="Optional description for this association group"
          />
        </div>
      </div>
    </form>

    <template #footer>
      <button
        v-if="mode === 'list'"
        type="button"
        class="btn btn-secondary"
        @click="closeModal"
      >
        Done
      </button>
      <template v-else>
        <button
          type="button"
          class="btn btn-secondary"
          @click="cancelEdit"
        >
          Cancel
        </button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="saving"
          @click="saveGroup"
        >
          <span
            v-if="saving"
            class="spinner-border spinner-border-sm me-2"
            role="status"
          />
          {{ isEdit ? 'Save Changes' : 'Create' }}
        </button>
      </template>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import BaseModal from '../shared/BaseModal.vue';

const props = defineProps({
  show: Boolean,
  initialMode: {
    type: String,
    default: 'list'
  },
  associationGroups: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['saved', 'deleted', 'hidden', 'update:show']);

const loading = ref(false);
const error = ref('');
const saving = ref(false);
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

function closeModal() {
  emit('update:show', false);
  emit('hidden');
}

function handleVisibilityChange(val) {
  emit('update:show', val);
  if (!val) {
    emit('hidden');
  }
}

function handleHidden() {
  emit('hidden');
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
