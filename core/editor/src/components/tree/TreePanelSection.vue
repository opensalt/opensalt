<template>
  <section
    id="tree1Section"
    class="tree-panel d-flex flex-column h-100 overflow-hidden"
    aria-labelledby="tree-heading"
  >
    <h2
      v-if="isViewingDifferentFramework"
      id="tree-heading"
      class="visually-hidden"
    >
      Viewed framework tree. Read-only.
    </h2>
    <h2
      v-else
      id="tree-heading"
      class="visually-hidden"
    >
      Edited framework tree.
    </h2>

    <DocumentSelector
      v-if="canSwitchViewedFramework"
      :current-doc="currentDoc"
      :available-documents="availableDocuments"
      :viewed-doc="viewedDoc"
      :is-viewing-different-framework="isViewingDifferentFramework"
      :side="'treeView'"
      :hide-external="true"
      :compact="true"
      :related-framework-ids="relatedFrameworkIds"
      @viewed-document-changed="onViewedDocumentChanged"
    />

    <!-- Tree Filter -->
    <TreeFilter
      v-model="treeSearchQueryModel"
      :match-count="matchCount"
      class="mx-2"
      @clear="onClearTreeFilter"
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
        :disable-drop="isViewingDifferentFramework"
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
import { useViewedDoc } from '../../composables/useViewedDoc';
import DocumentSelector from '../shared/common/DocumentSelector.vue';
import TreeFilter from './TreeFilter.vue';
import SearchFilter from './common/SearchFilter.vue';
import AssociationGroupSelector from './common/AssociationGroupSelector.vue';
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

  filteredViewedDoc: {
    type: Object,
    default: null
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
  },

  canSwitchViewedFramework: {
    type: Boolean,
    default: false
  },

  /**
   * Set of framework identifiers that are referenced by the current crosswalk
   * framework's associations. When shown in the document selector, these
   * frameworks are grouped at the top under "Mapped Frameworks".
   */
  relatedFrameworkIds: {
    type: Set,
    default: () => new Set()
  }
});

// Emits
// NEW: Changed 'document-changed' to 'viewed-document-changed' for dual framework edit/view separation
const emit = defineEmits([
  'viewed-document-changed',
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

const { viewedDoc, isViewingDifferentFramework } = useViewedDoc({ transformItems: true });

// NEW: Compute the document to display based on view mode
// When viewing a different framework, use viewed document; otherwise use filtered document
const displayedDoc = computed(() => {
  if (isViewingDifferentFramework.value && viewedDoc.value) {
    return props.filteredViewedDoc || viewedDoc.value;
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
  padding-top: 0.75rem;
}
</style>
