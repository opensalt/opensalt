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
      :is-admin="contextStore.isAdmin"
      @saved="onDocSaved"
      @hidden="onEditDocModalHidden"
    />

    <!-- Edit Association Modal (dual-mode: add/edit) -->
    <EditAssociationModal
      :association="editingAssociation"
      :available-groups="associationGroups"
      :show="showEditAssociationModal"
      :selected-item-identifier="selectedId"
      :mode="addingAssociation ? 'add' : 'edit'"
      :current-item="addingAssociationOrigin"
      :destination-item="addingAssociationDestination"
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
      :association-groups="associationGroups"
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
      :parent-item="modalParentItem"
      :show="true"
      @updated="onDynamicEditUpdated"
      @created="onDynamicEditCreated"
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

    <!-- Export Document Modal -->
    <ExportModal
      :show="showExportModal"
      :document="currentDoc"
      @hidden="onExportModalHidden"
    />

    <!-- Delete Association Modal -->
    <DeleteAssociationModal
      :association="associationToDelete"
      :show="showDeleteAssociationModal"
      @confirmed="onDeleteAssociationConfirmed"
      @hidden="onDeleteAssociationModalHidden"
    />

    <!-- Clone Framework Modal -->
    <CloneFrameworkModal
      :show="showCloneFrameworkModal"
      :framework-title="cloneFrameworkTitle"
      @confirmed="onCloneFrameworkConfirmed"
      @hidden="onCloneFrameworkModalHidden"
    />
  </div>
</template>

<script setup>
import { defineAsyncComponent } from 'vue';
import { useEditorContextStore } from '../../stores/editorContextStore';

const contextStore = useEditorContextStore();

// Lazy-loaded modal components
const EditDocModal = defineAsyncComponent(() => import('../shared/modals/EditDocModal.vue'));
const EditAssociationModal = defineAsyncComponent(() => import('../association/EditAssociationModal.vue'));
const DeleteItemsModal = defineAsyncComponent(() => import('../shared/modals/DeleteItemsModal.vue'));
const ExemplarModal = defineAsyncComponent(() => import('../shared/modals/ExemplarModal.vue'));
const AssociationGroupModal = defineAsyncComponent(() => import('../association/AssociationGroupModal.vue'));
const CrossTreeDropModal = defineAsyncComponent(() => import('./CrossTreeDropModal.vue'));
const LoadExternalDocumentModal = defineAsyncComponent(() => import('../shared/modals/LoadExternalDocumentModal.vue'));
const UpdateFrameworkModal = defineAsyncComponent(() => import('../shared/modals/UpdateFrameworkModal.vue'));
const ExportModal = defineAsyncComponent(() => import('../shared/modals/ExportModal.vue'));
const CloneFrameworkModal = defineAsyncComponent(() => import('../shared/modals/CloneFrameworkModal.vue'));
const DeleteAssociationModal = defineAsyncComponent(() => import('../association/DeleteAssociationModal.vue'));

// Props
const _props = defineProps({
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
   * Show flag for association deletion confirmation
   */
  showDeleteAssociationModal: {
    type: Boolean,
    default: false
  },

  /**
   * Association object currently targeted for deletion
   */
  associationToDelete: {
    type: Object,
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
  showExportModal: {
    type: Boolean,
    default: false
  },
  showCloneFrameworkModal: {
    type: Boolean,
    default: false
  },

  // Modal data
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
  addingAssociationDestination: {
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
  },
  modalParentItem: {
    type: Object,
    default: null
  },
  cloneFrameworkTitle: {
    type: String,
    default: ''
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
  'delete-association-confirmed',
  'delete-association-modal-hidden',
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
  'edit-association-modal-hidden',
  'delete-modal-hidden',
  'exemplar-modal-hidden',
  'assoc-group-modal-hidden',
  'load-external-modal-hidden',

  // Import modal events
  'update-framework-imported',
  'update-framework-modal-hidden',
  'export-modal-hidden',

  // Clone Framework modal events
  'clone-framework-confirmed',
  'clone-framework-modal-hidden',

  // Dynamic edit modal events
  'dynamic-edit-updated',
  'dynamic-edit-created',
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

function onEditAssociationModalHidden() {
  emit('edit-association-modal-hidden');
}

function onItemsDeleted(data) {
  emit('items-deleted', data);
}

function onDeleteModalHidden() {
  emit('delete-modal-hidden');
}

function onDeleteAssociationConfirmed(association) {
  emit('delete-association-confirmed', association);
}

function onDeleteAssociationModalHidden() {
  emit('delete-association-modal-hidden');
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

function onDynamicEditCreated(item) {
  emit('dynamic-edit-created', item);
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

function onExportModalHidden() {
  emit('export-modal-hidden');
}

function onCloneFrameworkConfirmed() {
  emit('clone-framework-confirmed');
}

function onCloneFrameworkModalHidden() {
  emit('clone-framework-modal-hidden');
}
</script>

<style scoped>
/* ModalManager has no visible styles - it's just a container for modals */
.modal-manager {
  display: contents;
}
</style>
