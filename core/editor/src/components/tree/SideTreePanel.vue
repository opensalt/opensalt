<template>
  <div class="side-tree-panel h-100 d-flex flex-column">
    <!-- Document Selector -->
    <DocumentSelector
      :current-doc="currentDocForSelector"
      :available-documents="availableDocuments"
      :label="'External Document'"
      side="right"
      @viewed-document-changed="onDocumentChanged"
      @external-document-requested="onExternalDocumentRequested"
    />

    <!-- Instructions -->
    <div
      v-if="!selectedDocumentId"
      class="instructions alert alert-info py-2 mb-2"
    >
      <small>
        <i class="bi bi-info-circle me-1" />
        <span>
          Select a document to act on its items.
        </span>
      </small>
    </div>

    <!-- Action Bar -->
    <div
      v-if="sideDocument"
      class="mb-2 p-2 border rounded bg-light d-flex flex-column align-items-center"
    >
      <small class="text-muted mb-2">
        <span v-if="!sideSelectedId">Select an item below to act on it.</span>
        <span v-else>Item selected. Switch to main tree to select target.</span>
      </small>
      <div class="d-flex gap-2 ms-auto">
        <button
          type="button"
          class="btn btn-outline-primary"
          :disabled="!sideSelectedId"
          @click="emit('action', { type: 'associate', itemId: sideSelectedId })"
        >
          <i class="bi bi-link-45deg" /> Associate
        </button>
        <div
          v-click-outside="() => copyMenuOpen = false"
          class="btn-group"
        >
          <button
            id="copyDropdownBtn"
            type="button"
            class="btn btn-outline-primary dropdown-toggle"
            :disabled="copyDisabled"
            :aria-expanded="copyMenuOpen"
            @click="toggleCopyMenu"
          >
            <i class="bi bi-copy" /> Copy...
          </button>
          <ul
            v-if="copyMenuOpen"
            class="dropdown-menu show shadow-sm"
            aria-labelledby="copyDropdownBtn"
          >
            <li>
              <button
                class="dropdown-item py-2"
                @click="onCopyAction('before')"
              >
                <i class="bi bi-arrow-bar-up text-muted me-2" /> Before Target
              </button>
            </li>
            <li>
              <button
                class="dropdown-item py-2"
                @click="onCopyAction('after')"
              >
                <i class="bi bi-arrow-bar-down text-muted me-2" /> After Target
              </button>
            </li>
            <li>
              <button
                class="dropdown-item py-2"
                @click="onCopyAction('inside')"
              >
                <i class="bi bi-arrow-bar-right text-muted me-2" /> As Child
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Side Tree -->
    <div
      v-if="selectedDocumentId"
      class="side-tree flex-grow-1 d-flex flex-column overflow-hidden border rounded p-2"
    >
      <div
        v-if="loadingSideDoc"
        class="d-flex justify-content-center align-items-center h-100"
      >
        <div
          class="spinner-border spinner-border-sm text-primary"
          role="status"
        >
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <div
        v-else-if="sideDocError"
        class="alert alert-danger py-2"
      >
        {{ sideDocError }}
      </div>
      <div
        v-else-if="sideDocument"
        ref="sideTreeContainerRef"
        class="side-tree-content"
      >
        <TreeView
          :doc="sideDocument"
          :selected-id="sideSelectedId"
          :disable-drop="true"
          :disable-drag="true"
          @select="onSideSelect"
        />
      </div>
      <div
        v-else
        class="d-flex justify-content-center align-items-center h-100 text-muted"
      >
        <span>Loading document...</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, provide } from 'vue';
import TreeView from './TreeView.vue';
import DocumentSelector from '../shared/common/DocumentSelector.vue';
import { useEditorContextStore } from '../../stores/editorContextStore';
import { useSideTreePanel } from '../../composables/useSideTreePanel';
import { useTreeNavigation } from '../../composables/useTreeNavigation';
import { findItem } from '../../utils/tree';

// Custom directive to detect clicks outside an element
const vClickOutside = {
  mounted(el, binding) {
    el.__clickOutsideHandler = (event) => {
      if (!el.contains(event.target)) {
        binding.value(event);
      }
    };
    document.addEventListener('click', el.__clickOutsideHandler);
  },
  unmounted(el) {
    document.removeEventListener('click', el.__clickOutsideHandler);
    delete el.__clickOutsideHandler;
  }
};

const editorContextStore = useEditorContextStore();

const props = defineProps({
  mode: {
    type: String,
    default: 'externalDocument'
  },
  currentDocument: {
    type: Object,
    default: null
  },
  currentDocumentId: {
    type: String,
    default: ''
  },
  availableDocuments: {
    type: Array,
    default: () => []
  },
  sideDocument: {
    type: Object,
    default: null
  },
  loadingSideDoc: {
    type: Boolean,
    default: false
  },
  sideDocError: {
    type: String,
    default: ''
  }
});

const emit = defineEmits([
  'document-select',
  'external-document-requested',
  'side-select',
  'action'
]);

const { selectedDocumentId, currentDocForSelector, onDocumentSelected } = useSideTreePanel(props);
const sideSelectedId = ref(null);
const copyMenuOpen = ref(false);

// Ref for the side tree container element (used for scoping DOM queries)
const sideTreeContainerRef = ref(null);

// Create independent navigation for the side tree so it doesn't share
// the main tree's expansion/focus state from EnhancedDocumentTreeEditor.
const {
  focusedItemId: sideFocusedItemId,
  setFocus: sideSetFocus,
  handleKeyDown: sideHandleKeyDown,
  isItemExpanded: sideIsItemExpanded,
  expandItem: sideExpandItem,
  collapseItem: sideCollapseItem,
  toggleExpanded: sideToggleExpanded,
  initializeFocus: sideInitializeFocus
} = useTreeNavigation({
  items: computed(() => props.sideDocument?.items || []),
  selectedId: computed(() => sideSelectedId.value),
  onSelect: (id) => onSideSelect(id),
  containerRef: sideTreeContainerRef
});

// Provide the side tree's independent navigation so that TreeView (and its
// TreeNodes) use this instead of the main tree's navigation from above.
provide('treeNavigation', {
  focusedItemId: sideFocusedItemId,
  setFocus: sideSetFocus,
  handleKeyDown: sideHandleKeyDown,
  isItemExpanded: sideIsItemExpanded,
  expandItem: sideExpandItem,
  collapseItem: sideCollapseItem,
  toggleExpanded: sideToggleExpanded,
  initializeFocus: sideInitializeFocus
});

const isSideSelectedDocument = computed(() => {
  if (!sideSelectedId.value || !props.sideDocument) return false;

  if (sideSelectedId.value === props.sideDocument.id ||
      sideSelectedId.value === props.sideDocument.identifier ||
      sideSelectedId.value === 'document-root') {
    return true;
  }

  const itemsToSearch = props.sideDocument.items || props.sideDocument.children || [];
  const found = findItem(itemsToSearch, sideSelectedId.value);

  // If the selected item is not in the tree, it must be the document root itself
  return !found;
});

const copyDisabled = computed(() => !sideSelectedId.value || isSideSelectedDocument.value);

function toggleCopyMenu() {
  if (copyDisabled.value) return;
  copyMenuOpen.value = !copyMenuOpen.value;
}

function onCopyAction(position) {
  copyMenuOpen.value = false;
  emit('action', { type: 'copy', position, itemId: sideSelectedId.value });
}

function onDocumentChanged(event) {
  const { documentId } = event;
  if (documentId) {
    onDocumentSelected(documentId);
    emit('document-select', documentId);

    // Save framework selection to centralized state
    if (props.mode === 'externalDocument') {
      editorContextStore.setFrameworkSelection(props.mode, documentId);
    }
  }
}

function onExternalDocumentRequested() {
  emit('external-document-requested');
}

function onSideSelect(id) {
  sideSelectedId.value = id;
  copyMenuOpen.value = false;
  emit('side-select', id);
}

// Reset selection when document changes
watch(() => props.sideDocument, () => {
  sideSelectedId.value = null;
});

// Sync selectedDocumentId with sideDocument
watch(() => props.sideDocument, (newDoc) => {
  if (newDoc) {
    selectedDocumentId.value = newDoc.id;
  }
}, { immediate: true });

// Watch for sideDocument changes to initialize the side tree for Tab accessibility.
// Sets focusedItemId to the document root (always rendered) so it gets tabindex="0"
// (WAI-ARIA roving tabindex pattern), and expands the root so children are visible.
watch(() => props.sideDocument?.id, (newDocId, oldDocId) => {
  if (newDocId && newDocId !== oldDocId) {
    // Set focus to the document root node — this is the node TreeView.vue creates
    // with identifier = doc.id, which is always rendered regardless of expansion.
    // Without this, focusedItemId points to a child item that isn't rendered yet
    // (root not expanded), so no treeitem has tabindex="0" and Tab can't enter the tree.
    sideFocusedItemId.value = newDocId;
    // Expand the document root by default (one level expansion)
    sideExpandItem(newDocId);
  }
}, { immediate: false });
</script>

<style scoped>
.side-tree-panel {
  min-height: 0;
}

.side-tree {
  background-color: #f8f9fa;
  min-height: 0;
}

.side-tree-content {
  min-height: 0;
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
}

.side-tree-content :deep(.tree-view) {
  min-height: 0;
}

.instructions {
  font-size: 0.85rem;
}

.drag-instructions {
  font-size: 0.8rem;
}
</style>
