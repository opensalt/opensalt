<template>
  <div class="tree-view card h-100">
    <div class="card-body d-flex flex-column h-100">
      <div v-if="!doc">
        <em>No document loaded.</em>
      </div>
      <div v-else role="tree" aria-label="Document structure tree" class="tree-container">
        <TreeNode
          :key="documentRoot.identifier"
          :item="documentRoot"
          :level="0"
          :selected-id="props.selectedId"
          :parent-items="[documentRoot]"
          :index="0"
          :startExpanded="true"
          :search-query="props.searchQuery || props.search"
          :matching-item-ids="props.matchingItemIds"
          @select="onSelect"
          @dblclick="onDblClick"
          @move="onMove"
          @item-change="onItemChange"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import TreeNode from './TreeNode.vue';

const props = defineProps({
  doc: Object,
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
  }
});

const emit = defineEmits(['select', 'dblclick', 'tree-change']);

// Create a document root node with items as children
const documentRoot = computed(() => {
  if (!props.doc) return null;

  return {
    identifier: props.doc.id || 'document-root',
    title: props.doc.title || 'Document Root',
    abbreviatedTitle: props.doc.title || 'Document Root',
    humanCodingScheme: '', // No human coding scheme for document root
    children: props.doc.items || [],
    itemType: 'document',
    lastChanged: props.doc.lastModified || '',
    // Add other document properties as needed
    ...props.doc
  };
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
</script>

<style scoped>
.tree-view {
  /* Height controlled by flexbox parent */
}

.tree-container {
  /* Height controlled by flex parent */
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  position: relative;

  /* Reset any parent margins/paddings that could cause positioning issues */
  margin: 0 !important;
  padding: 0 !important;
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

.tree-node-label.selected {
  background: #e3f2fd;
  border-radius: 4px;
  padding: 2px 6px;
}
</style>
