<template>
  <div
    class="tree-view card h-100"
    :class="{ 'viewing-different-framework': isViewingDifferentFramework }"
  >
    <div class="card-body d-flex flex-column h-100">
      <div v-if="!doc">
        <em>No document loaded.</em>
      </div>
      <div
        v-else
        ref="treeContainer"
        role="tree"
        :aria-label="treeLabel"
        :aria-multiselectable="false"
        :aria-setsize="totalItems"
        tabindex="-1"
        class="tree-container"
        :class="{ 'view-mode': isViewMode }"
        @keydown="handleTreeKeyDown"
        @focus="handleTreeFocus"
      >
        <TreeNode
          :key="documentRoot.identifier"
          :item="documentRoot"
          :level="0"
          :selected-id="props.selectedId"
          :parent-items="[documentRoot]"
          :index="0"
          :start-expanded="true"
          :search-query="props.searchQuery || props.search"
          :matching-item-ids="props.matchingItemIds"
          :is-view-mode="isViewMode"
          :disable-drop="disableDrop"
          :disable-drag="disableDrag"
          @select="onSelect"
          @dblclick="onDblClick"
          @move="onMove"
          @item-change="onItemChange"
          @focus="handleFocus"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, provide, inject, nextTick } from 'vue';
import TreeNode from './TreeNode.vue';
import { useTreeNavigation } from '../../composables/useTreeNavigation';
import { sortTreeNodes } from '../../utils/tree.js';

const props = defineProps({
  doc: {
    type: Object,
    default: null
  },
  selectedId: {
    type: String,
    default: null
  },
  search: {
    type: String,
    default: ''
  },
  searchQuery: {
    type: String,
    default: ''
  },
  matchingItemIds: {
    type: Set,
    default: () => new Set()
  },
  isViewMode: {
    type: Boolean,
    default: false
  },
  isViewingDifferentFramework: {
    type: Boolean,
    default: false
  },
  disableDrop: {
    type: Boolean,
    default: false
  },
  disableDrag: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['select', 'dblclick', 'tree-change', 'focus']);

// Check if a parent already provides tree navigation (e.g. EnhancedDocumentTreeEditor
// or SideBySideTreePanel). If so, TreeNodes should use the parent's navigation and
// we do NOT override it. This prevents a local empty expandedIds from shadowing the
// parent's expansion state (which caused the tree not to expand on initial load).
const existingNavigation = inject('treeNavigation', null);

// Setup tree navigation for this tree instance
const treeContainer = ref(null);

const {
  focusedItemId,
  setFocus,
  handleKeyDown: navigationHandleKeyDown,
  isItemExpanded,
  expandItem,
  collapseItem,
  toggleExpanded,
  initializeFocus
} = useTreeNavigation({
  items: computed(() => props.doc?.items || []),
  selectedId: computed(() => props.selectedId),
  onSelect: (id) => onSelect(id),
  containerRef: treeContainer
});

// Only provide navigation if no parent already provides it.
// When a parent (EnhancedDocumentTreeEditor, SideBySideTreePanel, SideTreePanel)
// provides treeNavigation, TreeNodes should use that — ensuring correct expansion
// state and independent navigation per tree.
if (!existingNavigation) {
  provide('treeNavigation', {
    focusedItemId,
    setFocus,
    handleKeyDown: navigationHandleKeyDown,
    isItemExpanded,
    expandItem,
    collapseItem,
    toggleExpanded,
    initializeFocus
  });
}

// Create a document root node with items as children
const documentRoot = computed(() => {
  if (!props.doc) return null;

  const items = props.doc.items || [];
  sortTreeNodes(items);

  return {
    identifier: props.doc.id || 'document-root',
    title: props.doc.title || 'Document Root',
    abbreviatedTitle: props.doc.title || 'Document Root',
    humanCodingScheme: '', // No human coding scheme for document root
    children: items,
    itemType: 'document',
    lastChanged: props.doc.lastModified || '',
    ...props.doc
  };
});

// Compute ARIA attributes
const treeLabel = computed(() =>
  props.doc ? `Document structure tree: ${props.doc.title || 'Document'}` : 'Document structure tree'
);

// Count root-level items only (for aria-setsize on the tree container)
const totalItems = computed(() => {
  if (!props.doc) return 0;
  return (props.doc.items || []).length;
});

function onSelect(id) {
  emit('select', id);
}
function onDblClick(id) {
  emit('dblclick', id);
}
function onItemChange(event) {
  emit('tree-change', event);
}
function onMove(event) {
  emit('tree-change', {
    type: 'move',
    ...event
  });
}
function handleFocus(itemId) {
  emit('focus', itemId);
}

// Handle keyboard events at tree level
function handleTreeKeyDown(event) {
  navigationHandleKeyDown(event);
}

// Handle focus on tree container — redirect to the focused item (roving tabindex)
function handleTreeFocus(event) {
  if (event.target === treeContainer.value) {
    nextTick(() => {
      const container = treeContainer.value;
      if (!container) return;
      if (focusedItemId.value) {
        const element = container.querySelector(`[data-tree-node-id="${focusedItemId.value}"]`);
        if (element) {
          element.focus();
          return;
        }
      }
      // Fallback: focus the first tree item
      const firstItem = container.querySelector('[data-tree-node-id]');
      if (firstItem) {
        firstItem.focus();
      }
    });
  }
}
</script>

<style scoped>
.tree-view {
  /* Height controlled by flexbox parent */
}

.tree-container {
  /* Fill available card body space without forcing overflow */
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  position: relative;

  /* Reset any parent margins/paddings that could cause positioning issues */
  margin: 0 !important;
  padding: 4px 8px 10px !important;
  box-sizing: border-box;
}

.tree-view .card-body {
  min-height: 0;
}

/* Ensure tree content starts at the top of the container */
.tree-container > * {
  margin-top: 0 !important;
  padding-top: 0 !important;
  position: relative;
  top: 0;
}

.tree-view[role="tree"] {
  outline: none;
}

/* Ensure proper focus management for tree navigation */
.tree-view[role="tree"]:focus {
  outline: 2px solid #007bff;
  outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .tree-view[role="tree"]:focus {
    outline: 3px solid #000;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .tree-view[role="tree"]:focus {
    transition: none;
  }
}

.tree-node-label.selected {
  background: #e3f2fd;
  border-radius: 4px;
  padding: 2px 6px;
}

/* View mode styling for the tree container */
.tree-container.view-mode {
  background-color: #f8f9fa;
}

/* Visual distinction when viewing a different framework */
.tree-view.viewing-different-framework {
  border: 2px solid #adb5bd;
  background-color: #f8f9fa;
}

.tree-view.viewing-different-framework .card-body {
  background-color: #f8f9fa;
}

/* High contrast mode support for view mode */
@media (prefers-contrast: high) {
  .tree-view.viewing-different-framework {
    border: 3px solid #000;
  }

  .tree-container.view-mode {
    background-color: #fff;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .tree-view.viewing-different-framework,
  .tree-container.view-mode {
    transition: none;
  }
}
</style>
