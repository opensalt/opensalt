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
      @document-changed="onDocumentChanged"
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
    <div class="mt-3 flex-grow-1 overflow-auto mb-3">
      <TreeView
        :doc="filteredDoc"
        :selected-id="selectedId"
        :search-query="treeSearchQuery"
        :matching-item-ids="matchingItemIds"
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
const emit = defineEmits([
  'document-changed',
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

const selectedAssociationGroupModel = computed({
  get: () => props.selectedAssociationGroup,
  set: (value) => emit('update:selectedAssociationGroup', value)
});

// Event handlers
function onDocumentChanged(event) {
  emit('document-changed', event);
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
