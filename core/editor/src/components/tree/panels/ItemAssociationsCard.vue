<template>
  <div
    v-if="mergedAssociations.length > 0 || !isCrossFrameworkItem || isProcessingAssociations"
    class="card mt-3"
  >
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0">
        Associations
        <span
          v-if="isProcessingAssociations"
          class="spinner-border spinner-border-sm ms-2"
          role="status"
        >
          <span class="visually-hidden">Loading associations…</span>
        </span>
      </h6>
      <button
        v-if="showAddButton && !isReadOnly"
        type="button"
        class="btn btn-sm btn-outline-primary"
        @click="$emit('add-association')"
      >
        <i
          class="bi bi-plus"
          aria-hidden="true"
        /> Add
      </button>
    </div>

    <!-- Context note when viewing different framework -->
    <div
      v-if="isViewingDifferentFramework && !isReadOnly"
      class="card-header bg-light border-top-0 pt-0 pb-2"
    >
      <small class="text-muted">
        <i
          class="bi bi-info-circle me-1"
          aria-hidden="true"
        />
        Associations created from this item will be saved in
        <strong>{{ currentDocument?.title || 'the edited framework' }}</strong>
      </small>
    </div>

    <div class="card-body">
      <!-- Loading state -->
      <div
        v-if="isProcessingAssociations && mergedAssociations.length === 0"
        class="text-center py-3"
        role="status"
      >
        <span
          class="spinner-border spinner-border-sm me-2"
          aria-hidden="true"
        />
        <span class="text-muted">Loading associations...</span>
      </div>
      <div
        v-else-if="!isProcessingAssociations && mergedAssociations.length === 0"
        class="text-muted py-2"
        role="status"
      >
        No associations to display.
      </div>

      <AssociationGroupDisplay
        v-for="group in mergedAssociations"
        :key="`${group.type}-${group.direction}`"
        :association-type="group.type"
        :associations="group.associations"
        :association-groups="associationGroups"
        :direction="group.direction"
        :item-identifier="itemIdentifier"
        :is-read-only="associationActionsReadOnly === true"
        @edit-association="$emit('edit-association', $event)"
        @delete-association="$emit('delete-association', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import AssociationGroupDisplay from '../../association/AssociationGroupDisplay.vue';

const _props = defineProps({
  mergedAssociations: { type: Array, default: () => [] },
  isProcessingAssociations: { type: Boolean, default: false },
  isCrossFrameworkItem: { type: Boolean, default: false },
  associationGroups: { type: Array, default: () => [] },
  itemIdentifier: { type: [String, null], default: null },
  isReadOnly: { type: Boolean, default: false },
  canEditItem: { type: Boolean, default: false },
  canManageAssociationActions: { type: Boolean, default: false },
  associationActionsReadOnly: { type: Boolean, default: undefined },
  isViewingDifferentFramework: { type: Boolean, default: false },
  currentDocument: { type: Object, default: null },
  showAddButton: { type: Boolean, default: true },
});

defineEmits(['add-association', 'edit-association', 'delete-association']);
</script>
