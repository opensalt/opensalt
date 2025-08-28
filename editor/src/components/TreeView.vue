<template>
  <div class="tree-view card">
    <div class="card-body">
      <div v-if="!doc">
        <em>No document loaded.</em>
      </div>
      <div v-else role="tree" aria-label="Document structure tree">
        <TreeNode
          v-for="(item, idx) in doc.items"
          :key="item.identifier"
          :item="item"
          :level="0"
          :selected-id="selectedId"
          :parent-items="doc.items"
          :index="idx"
          @select="onSelect"
          @dblclick="onDblClick"
          @move="onMove"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import TreeNode from './TreeNode.vue';

const props = defineProps({
  doc: Object
});

const emit = defineEmits(['select', 'dblclick']);
const selectedId = ref(null);

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