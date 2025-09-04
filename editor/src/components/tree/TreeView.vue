<template>
  <div class="tree-view card">
    <div class="card-body">
      <div v-if="!doc">
        <em>No document loaded.</em>
      </div>
      <div v-else role="tree" aria-label="Document structure tree">
        <TreeNode
          :key="documentRoot.identifier"
          :item="documentRoot"
          :level="0"
          :selected-id="selectedId"
          :parent-items="[documentRoot]"
          :index="0"
          :startExpanded="true"
          @select="onSelect"
          @dblclick="onDblClick"
          @move="onMove"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import TreeNode from './TreeNode.vue';

const props = defineProps({
  doc: Object
});

const emit = defineEmits(['select', 'dblclick']);
const selectedId = ref(null);

// Create a document root node with items as children
const documentRoot = computed(() => {
  if (!props.doc) return null;

  return {
    identifier: props.doc.id || 'document-root',
    title: props.doc.title || 'Document Root',
    abbreviatedTitle: props.doc.title || 'Document Root',
    hcs: '', // No human coding scheme for document root
    children: props.doc.items || [],
    itemType: 'document',
    lastChanged: props.doc.lastModified || '',
    // Add other document properties as needed
    ...props.doc
  };
});

function onSelect(id) {
  selectedId.value = id;
  emit('select', id);
}
function onDblClick(id) {
  emit('dblclick', id);
}
function onMove({ fromIdx, toIdx, parentItems }) {
  if (fromIdx === toIdx) return;
  const moved = parentItems.splice(fromIdx, 1)[0];
  parentItems.splice(toIdx, 0, moved);
}
</script>

<style scoped>
.tree-view {
  min-height: 300px;
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
