<template>
  <div class="association-table-view">
    <!-- Visually hidden caption for accessibility -->
    <caption class="visually-hidden">
      Associations table showing {{ associations.length }} associations
    </caption>

    <div class="table-responsive border rounded bg-white shadow-sm">
      <table class="table align-middle mb-0 border-0 custom-hover-table">
        <thead class="table-light sticky-top">
          <tr>
            <th scope="col" class="py-3 border-0 text-muted small text-uppercase">
              Origin
            </th>
            <th scope="col" class="py-3 border-0 text-muted small text-uppercase">
              Type
            </th>
            <th scope="col" class="py-3 border-0 text-muted small text-uppercase">
              Destination
            </th>
            <th scope="col" class="py-3 border-0 text-muted small text-uppercase text-end">
              Actions
            </th>
          </tr>
        </thead>
          <!-- Empty state -->
          <tbody v-if="associations.length === 0">
            <tr>
              <td colspan="4" class="text-center py-5 text-muted border-0">
                <div class="py-4">
                  <i class="bi bi-link-45deg fs-1 d-block mb-3 opacity-25"></i>
                  <p class="mb-0">No associations found.</p>
                  <small>Associations will appear here when added.</small>
                </div>
              </td>
            </tr>
          </tbody>

          <!-- Association rows as tbody elements -->
          <template v-else>
            <AssociationTableRow
              v-for="assoc in associations"
              :key="assoc.identifier || assoc.id"
              :association="assoc"
              :association-groups="associationGroups"
              :item-identifier="itemIdentifier"
              :is-read-only="isReadOnly"
              @edit="$emit('edit-association', $event)"
              @delete="$emit('delete-association', $event)"
            />
          </template>
      </table>
    </div>
  </div>
</template>

<script setup>
import AssociationTableRow from './AssociationTableRow.vue';

const props = defineProps({
  associations: {
    type: Array,
    required: true
  },
  associationGroups: {
    type: Array,
    default: () => []
  },
  itemIdentifier: {
    type: String,
    default: null
  },
  isReadOnly: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['edit-association', 'delete-association']);
</script>

<style scoped>
.association-table-view {
  width: 100%;
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  min-height: 0;
  overflow: hidden;
}

.table-responsive {
  flex: 1 1 auto;
  overflow-y: auto;
  min-height: 0;
}

.table-responsive::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.table-responsive::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.table-responsive::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
  background: #bbb;
}

.sticky-top {
  top: 0;
  z-index: 1;
}

/* Ensure consistent row styling */
:deep(.association-row) {
  transition: background-color 0.2s ease;
}

:deep(.association-row:hover) {
  background-color: #f8f9fa;
}

/* Annotation row styling */
:deep(.annotation-row) {
  background-color: #f8f9fa;
}
</style>
