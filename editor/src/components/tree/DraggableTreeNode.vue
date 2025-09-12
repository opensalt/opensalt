<template>
  <details v-if="hasChildren" :open="isExpanded" @toggle="onToggle" class="tree-node" role="treeitem" :aria-level="level + 1">
    <summary class="expand-control" :style="{ marginLeft: (level * 20) + 'px' }" @click="onSummaryClick">
      <span class="expand-indicator" aria-hidden="true">
        <i :class="isExpanded ? 'bi bi-caret-down-fill' : 'bi bi-caret-right-fill'"></i>
      </span>
      <span
        class="tree-node-label"
        :class="{ 'selected': selectedId === item.identifier, 'drag-over': dragOver }"
        @click.stop.prevent="select"
        @dblclick.stop="dblClick"
        style="cursor:pointer"
        draggable="true"
        @dragstart="onDragStart"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
        @dragend="onDragEnd"
      >
        <span v-if="item.humanCodingScheme" class="coding-scheme" style="font-weight: bold;">{{ item.humanCodingScheme }}: </span>
        {{ item.abbreviatedTitle || item.title || item.identifier }}
      </span>
      <slot name="actions" :item="item" />
    </summary>

    <DraggableTreeNode
      v-for="(child, cidx) in item.children"
      :key="child.identifier"
      :item="child"
      :level="level + 1"
      :selected-id="selectedId"
      :parent-items="item.children"
      :index="cidx"
      :drag-mode="dragMode"
      @select="$emit('select', $event)"
      @dblclick="$emit('dblclick', $event)"
      @move="$emit('move', $event)"
      @dragstart="$emit('dragstart', $event)"
      @drop="$emit('drop', $event)"
    >
      <template #actions="slotProps">
        <slot name="actions" v-bind="slotProps" />
      </template>
    </DraggableTreeNode>
  </details>

  <!-- For items without children -->
  <div v-else class="tree-node" role="treeitem" :aria-level="level + 1">
    <div class="tree-node-content" :style="{ marginLeft: (level * 20) + 'px' }">
      <span class="no-children-spacer" aria-hidden="true"></span>
      <span
        class="tree-node-label"
        :class="{ 'selected': selectedId === item.identifier, 'drag-over': dragOver }"
        @click="select"
        @dblclick="dblClick"
        style="cursor:pointer"
        draggable="true"
        @dragstart="onDragStart"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
        @dragend="onDragEnd"
      >
        <span v-if="item.humanCodingScheme" class="coding-scheme" style="font-weight: bold;">{{ item.humanCodingScheme }}: </span>
        {{ item.abbreviatedTitle || item.title || item.identifier }}
      </span>
      <slot name="actions" :item="item" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  item: Object,
  level: Number,
  selectedId: String,
  parentItems: Array,
  index: Number,
  dragMode: {
    type: String,
    default: 'move' // 'move', 'copy', 'associate'
  }
});

const emit = defineEmits(['select', 'dblclick', 'move', 'dragstart', 'drop']);

const isExpanded = ref(false);
const dragOver = ref(false);
const hasChildren = computed(() => props.item.children && props.item.children.length > 0);

const onToggle = (event) => {
  if (hasChildren.value) {
    isExpanded.value = event.target.open;
  }
};

const onSummaryClick = (event) => {
  const target = event.target;
  if (target.closest('.expand-indicator') || target.classList.contains('expand-indicator')) {
    return;
  }
  event.preventDefault();
  select();
};

const select = () => { emit('select', props.item.identifier); };
const dblClick = () => { emit('dblclick', props.item.identifier); };

// Enhanced drag-and-drop functionality
function onDragStart(e) {
  e.dataTransfer.effectAllowed = props.dragMode === 'copy' ? 'copy' : 'move';
  e.dataTransfer.setData('text/plain', JSON.stringify({
    item: props.item,
    index: props.index,
    dragMode: props.dragMode
  }));

  // Add visual feedback
  e.target.classList.add('dragging');

  emit('dragstart', {
    item: props.item,
    index: props.index,
    dragMode: props.dragMode
  });
}

function onDragOver(e) {
  e.preventDefault();

  // Only allow drops if this is a valid target
  if (canAcceptDrop(e)) {
    e.dataTransfer.dropEffect = props.dragMode === 'copy' ? 'copy' : 'move';
    dragOver.value = true;
  } else {
    e.dataTransfer.dropEffect = 'none';
  }
}

function onDragLeave() {
  dragOver.value = false;
}

function onDrop(e) {
  e.preventDefault();
  dragOver.value = false;

  if (!canAcceptDrop(e)) return;

  try {
    const dragData = JSON.parse(e.dataTransfer.getData('text/plain'));

    if (dragData.dragMode === 'move') {
      // Handle move operation
      emit('move', {
        fromIdx: dragData.index,
        toIdx: props.index,
        parentItems: props.parentItems,
        draggedItem: dragData.item
      });
    } else if (dragData.dragMode === 'copy') {
      // Handle copy operation
      emit('drop', {
        type: 'copy',
        draggedItem: dragData.item,
        targetItem: props.item,
        dropPosition: getDropPosition(e)
      });
    } else if (dragData.dragMode === 'associate') {
      // Handle association operation
      emit('drop', {
        type: 'associate',
        draggedItem: dragData.item,
        targetItem: props.item
      });
    }
  } catch (error) {
    console.error('Error handling drop:', error);
  }
}

function onDragEnd(e) {
  e.target.classList.remove('dragging');
  dragOver.value = false;
}

function canAcceptDrop(e) {
  try {
    const dragData = JSON.parse(e.dataTransfer.getData('text/plain'));

    // Don't allow dropping on itself
    if (dragData.item.identifier === props.item.identifier) {
      return false;
    }

    // Don't allow dropping on descendants
    if (isDescendant(dragData.item, props.item)) {
      return false;
    }

    return true;
  } catch {
    return false;
  }
}

function isDescendant(potentialChild, potentialParent) {
  if (!potentialParent.children) return false;

  for (const child of potentialParent.children) {
    if (child.identifier === potentialChild.identifier) {
      return true;
    }
    if (isDescendant(potentialChild, child)) {
      return true;
    }
  }
  return false;
}

function getDropPosition(e) {
  const rect = e.target.getBoundingClientRect();
  const y = e.clientY - rect.top;
  const height = rect.height;

  if (y < height / 3) return 'before';
  if (y > (height * 2) / 3) return 'after';
  return 'over';
}
</script>

<style scoped>
.tree-node {
  width: 100%;
}

.tree-node-content {
  display: flex;
  align-items: center;
  padding: 2px 0;
  min-height: 24px;
}

.expand-control {
  list-style: none;
  cursor: pointer;
  padding: 2px 0;
  margin: 0;
  display: flex;
  align-items: center;
  min-height: 24px;
}

.expand-control::-webkit-details-marker {
  display: none;
}

.expand-indicator {
  width: 16px;
  margin-right: 4px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  transition: color 0.2s;
}

.expand-indicator:hover {
  color: #495057;
}

.no-children-spacer {
  width: 16px;
  margin-right: 4px;
  display: inline-block;
}

.tree-node-label {
  flex: 1;
  padding: 2px 6px;
  border-radius: 4px;
  transition: background-color 0.2s;
  user-select: text;
}

.tree-node-label:hover {
  background-color: #f8f9fa;
}

.tree-node-label.selected {
  background: #e3f2fd;
  border-radius: 4px;
  padding: 2px 6px;
  font-weight: 600;
}

.tree-node-label.drag-over {
  background-color: #d1ecf1;
  border: 2px dashed #17a2b8;
}

.tree-node-label.dragging {
  opacity: 0.5;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .tree-node-label.selected {
    background: #000;
    color: #fff;
  }
  .tree-node-label.drag-over {
    background: #000;
    border-color: #fff;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .tree-node-label,
  .expand-indicator {
    transition: none;
  }
}
</style>
