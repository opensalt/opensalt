<template>
  <section
    class="tree-panel d-flex flex-column h-100 overflow-hidden"
    aria-labelledby="tree-heading"
  >
    <h2 id="tree-heading" class="visually-hidden">Document Tree</h2>

    <!-- Document Selector -->
    <DocumentSelector
      :current-doc="currentDoc"
      :available-documents="availableDocuments"
      :viewed-doc="viewedDoc"
      :is-viewing-different-framework="isViewingDifferentFramework"
      @viewed-document-changed="onViewedDocumentChanged"
      @external-document-requested="onExternalDocumentRequested"
    />

    <!-- Tree Filter -->
    <TreeFilter
      v-model="treeSearchQueryModel"
      :match-count="matchCount"
      @clear="onClearTreeFilter"
      class="mx-2"
    />

    <!-- Search and Filter (legacy, hidden) -->
    <SearchFilter
      v-if="false"
      :available-subjects="availableSubjects"
      @search="onSearch"
      @filter="onFilter"
      @clear="onClearSearch"
    />

    <!-- Association Group Selector -->
    <AssociationGroupSelector
      v-model="selectedAssociationGroupModel"
      :association-groups="associationGroups"
    />

    <!-- Tree View -->
    <div class="mt-2 mb-1 px-2 pb-3 flex-grow-1 overflow-hidden">
      <TreeView
        :doc="displayedDoc"
        :selected-id="selectedId"
        :search-query="treeSearchQuery"
        :matching-item-ids="matchingItemIds"
        :is-view-mode="isViewingDifferentFramework"
        :is-viewing-different-framework="isViewingDifferentFramework"
        @select="onSelect"
        @dblclick="onDblClick"
        @tree-change="onTreeChange"
        @focus="onFocus"
      />
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { useEditorContextStore } from '@/stores/editorContextStore';
import { useCurrentDocumentStore } from '@/stores/currentDocumentStore';
import DocumentSelector from '../shared/common/DocumentSelector.vue';
import TreeFilter from './TreeFilter.vue';
import SearchFilter from '../shared/common/SearchFilter.vue';
import AssociationGroupSelector from '../shared/common/AssociationGroupSelector.vue';
import TreeView from './TreeView.vue';

// Props
const props = defineProps({
  /**
   * The current document being displayed
   */
  currentDoc: {
    type: Object,
    default: null
  },

  /**
   * The filtered document with items filtered by search/group
   */
  filteredDoc: {
    type: Object,
    required: true
  },

  /**
   * List of available documents for the selector
   */
  availableDocuments: {
    type: Array,
    default: () => []
  },

  /**
   * Currently selected item ID
   */
  selectedId: {
    type: String,
    default: null
  },

  /**
   * Current tree search query
   */
  treeSearchQuery: {
    type: String,
    default: ''
  },

  /**
   * Number of search matches
   */
  matchCount: {
    type: Number,
    default: null
  },

  /**
   * Set of matching item IDs for highlighting
   */
  matchingItemIds: {
    type: Set,
    default: () => new Set()
  },

  /**
   * Available association groups
   */
  associationGroups: {
    type: Array,
    default: () => []
  },

  /**
   * Selected association group ID
   */
  selectedAssociationGroup: {
    type: String,
    default: 'all'
  },

  /**
   * Available subjects for filtering
   */
  availableSubjects: {
    type: Array,
    default: () => []
  }
});

// Emits
// NEW: Changed 'document-changed' to 'viewed-document-changed' for dual framework edit/view separation
const emit = defineEmits([
  'viewed-document-changed',
  'external-document-requested',
  'select',
  'dblclick',
  'tree-change',
  'focus',
  'update:treeSearchQuery',
  'update:selectedAssociationGroup',
  'search',
  'filter',
  'clear-search'
]);

// Computed models for two-way binding
const treeSearchQueryModel = computed({
  get: () => props.treeSearchQuery,
  set: (value) => emit('update:treeSearchQuery', value)
});

const contextStore = useEditorContextStore();
const currentDocumentStore = useCurrentDocumentStore();

const isViewingDifferentFramework = computed(() => contextStore.isViewingDifferentFramework);

const viewedDoc = computed(() => {
  const registryVersion = contextStore.registryVersion;
  void registryVersion;
  const id = contextStore.viewedDocumentId;
  if (!id) return null;
  const doc = contextStore.documentRegistry.get(id);
  if (!doc) return null;

  const pkg = contextStore.loadedPackages.get(id);
  let items = [];
  if (pkg && pkg.CFItems) {
    const transformed = currentDocumentStore.transformCASEItems(pkg.CFItems, pkg.CFAssociations || [], id);
    items = transformed.items || transformed;
  }

  return { ...doc, id: doc.identifier, items };
});

// NEW: Compute the document to display based on view mode
// When viewing a different framework, use viewed document; otherwise use filtered document
const displayedDoc = computed(() => {
  if (isViewingDifferentFramework.value && viewedDoc.value) {
    return viewedDoc.value;
  }
  return props.filteredDoc;
});

const selectedAssociationGroupModel = computed({
  get: () => props.selectedAssociationGroup,
  set: (value) => emit('update:selectedAssociationGroup', value)
});

// Event handlers
// NEW: Changed to emit 'viewed-document-changed' for dual framework edit/view separation
function onViewedDocumentChanged(event) {
  emit('viewed-document-changed', event);
}

function onExternalDocumentRequested() {
  emit('external-document-requested');
}

function onSelect(id) {
  emit('select', id);
}

function onDblClick(id) {
  emit('dblclick', id);
}

function onTreeChange(event) {
  emit('tree-change', event);
}

function onFocus(itemId) {
  emit('focus', itemId);
}

function onClearTreeFilter() {
  emit('update:treeSearchQuery', '');
}

function onSearch(params) {
  emit('search', params);
}

function onFilter(filters) {
  emit('filter', filters);
}

function onClearSearch() {
  emit('clear-search');
}
</script>

<style scoped>
/* Component-specific styles */
.tree-panel {
  min-height: 0;
}
</style>
