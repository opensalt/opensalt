<template>
  <details
    v-if="hasChildren"
    :open="isExpanded"
    class="tree-node"
    role="treeitem"
    :aria-level="level + 1"
    :aria-expanded="isExpanded"
    :aria-selected="selectedId === item.identifier"
    :aria-grabbed="isKeyboardDragging"
    :tabindex="isFocused ? '0' : '-1'"
    :data-tree-node-id="item.identifier"
    @toggle="onToggle"
    @keydown="handleKeyDown"
  >
    <summary
      class="expand-control"
      :style="{ marginLeft: (level * 20) + 'px' }"
      :tabindex="isFocused ? '0' : '-1'"
      @click="onSummaryClick"
    >
      <span
        class="expand-indicator"
        aria-hidden="true"
      >
        <i :class="isExpanded ? 'bi bi-caret-down-fill' : 'bi bi-caret-right-fill'" />
      </span>
      <span
        class="tree-node-label"
        :class="{ 'selected': selectedId === item.identifier, 'drag-over': dragOver, 'keyboard-dragging': isKeyboardDragging }"
        style="cursor:pointer"
        draggable="true"
        role="button"
        :aria-pressed="isKeyboardDragging"
        @click.stop.prevent="select"
        @dblclick.stop="dblClick"
        @focus="onFocus"
        @dragstart="onDragStart"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
        @dragend="onDragEnd"
      >
        <span
          v-if="item.humanCodingScheme"
          class="coding-scheme"
          style="font-weight: bold;"
        >{{ item.humanCodingScheme }}: </span>
        {{ item.abbreviatedTitle || item.title || item.identifier }}
      </span>
      <slot
        name="actions"
        :item="item"
      />
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
        <slot
          name="actions"
          v-bind="slotProps"
        />
      </template>
    </DraggableTreeNode>
  </details>

  <!-- For items without children -->
  <div
    v-else
    class="tree-node"
    role="treeitem"
    :aria-level="level + 1"
    :aria-selected="selectedId === item.identifier"
    :aria-grabbed="isKeyboardDragging"
    :tabindex="isFocused ? '0' : '-1'"
    :data-tree-node-id="item.identifier"
    @keydown="handleKeyDown"
  >
    <div
      class="tree-node-content"
      :style="{ marginLeft: (level * 20) + 'px' }"
    >
      <span
        class="no-children-spacer"
        aria-hidden="true"
      />
      <span
        class="tree-node-label"
        :class="{ 'selected': selectedId === item.identifier, 'drag-over': dragOver, 'keyboard-dragging': isKeyboardDragging }"
        style="cursor:pointer"
        draggable="true"
        role="button"
        :aria-pressed="isKeyboardDragging"
        @click="select"
        @dblclick="dblClick"
        @focus="onFocus"
        @dragstart="onDragStart"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
        @dragend="onDragEnd"
      >
        <span
          v-if="item.humanCodingScheme"
          class="coding-scheme"
          style="font-weight: bold;"
        >{{ item.humanCodingScheme }}: </span>
        {{ item.abbreviatedTitle || item.title || item.identifier }}
      </span>
      <slot
        name="actions"
        :item="item"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, inject } from 'vue';
import { useAnnouncer } from '../../composables/useAnnouncer.js';
import { logger } from '../../utils/logger.js';

const props = defineProps({
  item: {
    type: Object,
    default: null
  },
  level: {
    type: Number,
    default: 0
  },
  selectedId: {
    type: String,
    default: null
  },
  parentItems: {
    type: Array,
    default: () => []
  },
  index: {
    type: Number,
    default: 0
  },
  dragMode: {
    type: String,
    default: 'move' // 'move', 'copy', 'associate'
  }
});

const emit = defineEmits(['select', 'dblclick', 'move', 'dragstart', 'drop', 'focus']);

const dragOver = ref(false);
const hasChildren = computed(() => props.item.children && props.item.children.length > 0);

// Initialize announcer directly (creates its own announcer element if needed)
const announcer = useAnnouncer();

// Inject navigation context with focus state management (same pattern as TreeNode.vue)
const navigation = inject('treeNavigation', {
  focusedItemId: ref(null),
  isItemExpanded: () => false,
  expandItem: () => {},
  collapseItem: () => {},
  toggleExpanded: () => {}
});

// Use centralized expanded state from navigation context (consistent with TreeNode.vue)
const isExpanded = computed({
  get: () => navigation.isItemExpanded(props.item.identifier),
  set: (value) => {
    if (value) {
      navigation.expandItem(props.item.identifier);
    } else {
      navigation.collapseItem(props.item.identifier);
    }
  }
});

// Compute isFocused based on injected navigation context
const isFocused = computed(() => {
  return navigation.focusedItemId?.value === props.item.identifier;
});

// Keyboard drag state
const isKeyboardDragging = ref(false);
const keyboardDragPosition = ref(0); // 0 = original position, >0 = offset

const onToggle = (event) => {
  if (hasChildren.value) {
    const newState = event.target.open;
    if (newState) {
      navigation.expandItem(props.item.identifier);
    } else {
      navigation.collapseItem(props.item.identifier);
    }
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

const select = () => { emit('select', props.item.identifier); emit('focus', props.item.identifier); };
const dblClick = () => { emit('dblclick', props.item.identifier); };

const onFocus = () => {
  emit('focus', props.item.identifier);
};

// Keyboard handlers for drag operations
const handleKeyDown = (event) => {
  if (!isFocused.value) return;

  switch (event.key) {
    case ' ':
    case 'Space':
      event.preventDefault();
      if (isKeyboardDragging.value) {
        // Drop the item when already dragging
        isKeyboardDragging.value = false;
        emit('move', {
          fromIdx: keyboardDragPosition.value,
          toIdx: props.index,
          parentItems: props.parentItems,
          draggedItem: props.item
        });
        if (announcer) {
          announcer.announceDrag(props.item, 'drop');
        }
      } else {
        // Start keyboard drag mode
        isKeyboardDragging.value = true;
        keyboardDragPosition.value = props.index;
        if (announcer) {
          announcer.announceDrag(props.item, 'start');
        }
      }
      break;
    case 'ArrowUp':
    case 'ArrowDown':
      // Move dragged item up/down
      if (isKeyboardDragging.value) {
        event.preventDefault();
        const direction = event.key === 'ArrowUp' ? -1 : 1;
        emit('move', {
          fromIdx: keyboardDragPosition.value,
          toIdx: props.index + direction,
          parentItems: props.parentItems,
          draggedItem: props.item
        });
        keyboardDragPosition.value = props.index + direction;
      }
      break;
    case 'ArrowLeft':
    case 'ArrowRight':
      // Left/Right arrows could be used for nesting changes in the future
      // For now, just prevent default during drag to avoid confusion
      if (isKeyboardDragging.value) {
        event.preventDefault();
        // Announce that horizontal movement is not supported
        if (announcer) {
          announcer.announce('Use up and down arrows to move the item', 'polite');
        }
      }
      break;
    case 'Enter':
      // Select or drop the item
      if (isKeyboardDragging.value) {
        event.preventDefault();
        isKeyboardDragging.value = false;
        emit('move', {
          fromIdx: keyboardDragPosition.value,
          toIdx: props.index,
          parentItems: props.parentItems,
          draggedItem: props.item
        });
        if (announcer) {
          announcer.announceDrag(props.item, 'drop');
        }
      } else {
        // Normal select when not dragging
        event.preventDefault();
        select();
      }
      break;
    case 'Escape':
      // Cancel drag
      if (isKeyboardDragging.value) {
        event.preventDefault();
        isKeyboardDragging.value = false;
        keyboardDragPosition.value = 0;
        if (announcer) {
          announcer.announceDrag(props.item, 'cancel');
        }
      }
      break;
  }
};

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
    logger.error('Error handling drop:', error);
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
  box-sizing: border-box;
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
  margin: 0 2px;
  font-weight: 600;
}

.tree-node-label:focus,
.expand-control:focus,
.tree-node:focus {
  outline: none;
}

.tree-node-label.drag-over {
  background-color: #d1ecf1;
  border: 2px dashed #17a2b8;
}

.tree-node-label.dragging {
  opacity: 0.5;
}

.tree-node-label.keyboard-dragging {
  outline: 2px solid #007bff;
  outline-offset: 2px;
  background-color: rgba(0, 123, 255, 0.1);
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
  .tree-node-label.keyboard-dragging {
    outline: 3px solid #000;
    background-color: rgba(0, 0, 0, 0.3);
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
