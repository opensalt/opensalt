<template>
  <!--
    ModalManager - Centralized management of all modals
    This component renders all modals used by the EnhancedDocumentTreeEditor
    and provides a clean interface for modal state management.
  -->
  <div class="modal-manager">
    <!-- Edit Document Modal -->
    <EditDocModal
      :document="currentDoc"
      :show="showEditDocModal"
      @saved="onDocSaved"
      @hidden="onEditDocModalHidden"
    />

    <!-- Associate Modal -->
    <AssociateModal
      :origin-item="associationOrigin"
      :destination-item="associationDestination"
      :available-groups="associationGroups"
      :show="showAssociateModal"
      @created="onAssociationCreated"
      @hidden="onAssociateModalHidden"
    />

    <!-- Edit Association Modal (dual-mode: add/edit) -->
    <EditAssociationModal
      :association="editingAssociation"
      :available-groups="associationGroups"
      :show="showEditAssociationModal"
      :selected-item-identifier="selectedId"
      :mode="addingAssociation ? 'add' : 'edit'"
      :current-item="addingAssociationOrigin"
      :initial-type="addingAssociationType"
      @updated="onAssociationUpdated"
      @created="onAssociationCreated"
      @hidden="onEditAssociationModalHidden"
    />

    <!-- Delete Items Modal -->
    <DeleteItemsModal
      :items="itemsToDelete"
      :delete-type="deleteType"
      :show="showDeleteModal"
      @confirmed="onItemsDeleted"
      @hidden="onDeleteModalHidden"
    />

    <!-- Exemplar Modal -->
    <ExemplarModal
      :current-item="selectedItem"
      :show="showExemplarModal"
      @added="onExemplarAdded"
      @hidden="onExemplarModalHidden"
    />

    <!-- Association Group Modal -->
    <AssociationGroupModal
      :show="showAssocGroupModal"
      :association-groups="associationGroups"
      @saved="onAssocGroupSaved"
      @deleted="onAssocGroupDeleted"
      @hidden="onAssocGroupModalHidden"
    />

    <!-- Cross-Tree Drop Modal -->
    <CrossTreeDropModal
      :show="showCrossTreeModal"
      :source-item="crossTreeSource"
      :target-item="crossTreeTarget"
      @close="onCrossTreeClose"
      @copy="onCrossTreeCopy"
      @associate="onCrossTreeAssociate"
    />

    <!-- Dynamic type-specific edit modal -->
    <component
      :is="editModalComponent"
      v-if="isEditModalVisible"
      :item="editingItem"
      :show="true"
      @updated="onDynamicEditUpdated"
      @created="onDynamicEditUpdated"
      @hidden="onDynamicEditHidden"
    />

    <!-- Load External Document Modal -->
    <LoadExternalDocumentModal
      :show="showLoadExternalModal"
      @load="onExternalDocumentLoad"
      @hidden="onLoadExternalModalHidden"
    />

    <!-- Update Framework Modal -->
    <UpdateFrameworkModal
      :show="showUpdateFrameworkModal"
      @imported="onUpdateFrameworkImported"
      @hidden="onUpdateFrameworkModalHidden"
    />

    <!-- Import Children Modal -->
    <ImportChildrenModal
      :show="showImportChildrenModal"
      @imported="onImportChildrenImported"
      @hidden="onImportChildrenModalHidden"
    />
  </div>
</template>

<script setup>
import { defineAsyncComponent } from 'vue';

// Lazy-loaded modal components
const EditDocModal = defineAsyncComponent(() => import('../shared/modals/EditDocModal.vue'));
const AssociateModal = defineAsyncComponent(() => import('../association/AssociateModal.vue'));
const EditAssociationModal = defineAsyncComponent(() => import('../association/EditAssociationModal.vue'));
const DeleteItemsModal = defineAsyncComponent(() => import('../shared/modals/DeleteItemsModal.vue'));
const ExemplarModal = defineAsyncComponent(() => import('../shared/modals/ExemplarModal.vue'));
const AssociationGroupModal = defineAsyncComponent(() => import('../association/AssociationGroupModal.vue'));
const CrossTreeDropModal = defineAsyncComponent(() => import('./CrossTreeDropModal.vue'));
const LoadExternalDocumentModal = defineAsyncComponent(() => import('../shared/modals/LoadExternalDocumentModal.vue'));
const UpdateFrameworkModal = defineAsyncComponent(() => import('../shared/modals/UpdateFrameworkModal.vue'));
const ImportChildrenModal = defineAsyncComponent(() => import('../shared/modals/ImportChildrenModal.vue'));

// Props
const props = defineProps({
  /**
   * Current document being edited
   */
  currentDoc: {
    type: Object,
    default: null
  },

  /**
   * Currently selected item
   */
  selectedItem: {
    type: Object,
    default: null
  },

  /**
   * Currently selected item ID
   */
  selectedId: {
    type: String,
    default: null
  },

  /**
   * Available association groups
   */
  associationGroups: {
    type: Array,
    default: () => []
  },

  // Modal visibility states
  showEditDocModal: {
    type: Boolean,
    default: false
  },
  showAssociateModal: {
    type: Boolean,
    default: false
  },
  showEditAssociationModal: {
    type: Boolean,
    default: false
  },
  showDeleteModal: {
    type: Boolean,
    default: false
  },
  showExemplarModal: {
    type: Boolean,
    default: false
  },
  showAssocGroupModal: {
    type: Boolean,
    default: false
  },
  showCrossTreeModal: {
    type: Boolean,
    default: false
  },
  showLoadExternalModal: {
    type: Boolean,
    default: false
  },
  showUpdateFrameworkModal: {
    type: Boolean,
    default: false
  },
  showImportChildrenModal: {
    type: Boolean,
    default: false
  },

  // Modal data
  associationOrigin: {
    type: Object,
    default: null
  },
  associationDestination: {
    type: Object,
    default: null
  },
  editingAssociation: {
    type: Object,
    default: null
  },
  itemsToDelete: {
    type: Array,
    default: () => []
  },
  deleteType: {
    type: String,
    default: 'single'
  },
  addingAssociation: {
    type: Boolean,
    default: false
  },
  addingAssociationType: {
    type: String,
    default: ''
  },
  addingAssociationOrigin: {
    type: Object,
    default: null
  },
  crossTreeSource: {
    type: Object,
    default: null
  },
  crossTreeTarget: {
    type: Object,
    default: null
  },

  // Dynamic edit modal
  isEditModalVisible: {
    type: Boolean,
    default: false
  },
  editingItem: {
    type: Object,
    default: null
  },
  editModalComponent: {
    type: Object,
    default: null
  }
});

// Emits
const emit = defineEmits([
  // Document events
  'doc-saved',

  // Association events
  'association-created',
  'association-updated',

  // Item events
  'items-deleted',
  'exemplar-added',
  'edit-item',

  // Association group events
  'assoc-group-saved',
  'assoc-group-deleted',

  // Cross-tree events
  'cross-tree-close',
  'cross-tree-copy',
  'cross-tree-associate',

  // External document events
  'external-document-load',

  // Modal hidden events
  'edit-doc-modal-hidden',
  'associate-modal-hidden',
  'edit-association-modal-hidden',
  'delete-modal-hidden',
  'exemplar-modal-hidden',
  'assoc-group-modal-hidden',
  'load-external-modal-hidden',

  // Import modal events
  'update-framework-imported',
  'update-framework-modal-hidden',
  'import-children-imported',
  'import-children-modal-hidden',

  // Dynamic edit modal events
  'dynamic-edit-updated',
  'dynamic-edit-hidden'
]);

// Event handlers
function onDocSaved(data) {
  emit('doc-saved', data);
}

function onEditDocModalHidden() {
  emit('edit-doc-modal-hidden');
}

function onAssociationCreated(association) {
  emit('association-created', association);
}

function onAssociationUpdated(association) {
  emit('association-updated', association);
}

function onAssociateModalHidden() {
  emit('associate-modal-hidden');
}

function onEditAssociationModalHidden() {
  emit('edit-association-modal-hidden');
}

function onItemsDeleted(data) {
  emit('items-deleted', data);
}

function onDeleteModalHidden() {
  emit('delete-modal-hidden');
}

function onExemplarAdded(exemplar) {
  emit('exemplar-added', exemplar);
}

function onExemplarModalHidden() {
  emit('exemplar-modal-hidden');
}

function onAssocGroupSaved(group) {
  emit('assoc-group-saved', group);
}

function onAssocGroupDeleted(group) {
  emit('assoc-group-deleted', group);
}

function onAssocGroupModalHidden() {
  emit('assoc-group-modal-hidden');
}

function onCrossTreeClose() {
  emit('cross-tree-close');
}

function onCrossTreeCopy() {
  emit('cross-tree-copy');
}

function onCrossTreeAssociate() {
  emit('cross-tree-associate');
}

function onExternalDocumentLoad(url) {
  emit('external-document-load', url);
}

function onLoadExternalModalHidden() {
  emit('load-external-modal-hidden');
}

function onDynamicEditUpdated(item) {
  emit('dynamic-edit-updated', item);
}

function onDynamicEditHidden() {
  emit('dynamic-edit-hidden');
}

function onUpdateFrameworkImported() {
  emit('update-framework-imported');
}

function onUpdateFrameworkModalHidden() {
  emit('update-framework-modal-hidden');
}

function onImportChildrenImported() {
  emit('import-children-imported');
}

function onImportChildrenModalHidden() {
  emit('import-children-modal-hidden');
}
</script>

<style scoped>
/* ModalManager has no visible styles - it's just a container for modals */
.modal-manager {
  display: contents;
}
</style>
