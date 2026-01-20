<template>
  <details v-if="hasChildren" :open="isExpanded" @toggle="onToggle" class="tree-node" role="treeitem" :aria-level="level + 1">
    <summary
      class="expand-control"
      :style="{ marginLeft: (level * 20) + 'px' }"
      @click="onSummaryClick"
      draggable="true"
      @dragstart="onDragStart"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      :class="{
        'drop-before': dropPosition === 'before',
        'drop-after': dropPosition === 'after',
        'drop-inside': dropPosition === 'inside'
      }"
    >
      <span class="expand-indicator" aria-hidden="true">
        <i :class="isExpanded ? 'bi bi-caret-down-fill' : 'bi bi-caret-right-fill'"></i>
      </span>
      <img :src="iconSrc" class="tree-icon" aria-hidden="true" />
      <div
        class="tree-node-label"
        :class="{ 'selected': selectedId === item.identifier }"
        @click.stop.prevent="select"
        @dblclick.stop="dblClick"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
        style="cursor:pointer"
      >
        <span class="label-text">
          <span v-if="item.humanCodingScheme" class="coding-scheme" style="font-weight: bold;">{{ item.humanCodingScheme }}: </span>
          {{ item.abbreviatedStatement || item.fullStatement || item.title || item.identifier }}
        </span>
        <div v-if="showPopover && fullStatementHtml" class="popover" v-html="fullStatementHtml"></div>
      </div>
      <slot name="actions" :item="item" />
    </summary>

    <div v-if="hasChildren" class="children-container">
      <!-- Drag and Drop temporarily disabled to fix rendering issue -->
      <div v-for="(child, index) in item.children" :key="child.identifier">
          <TreeNode
            :item="child"
            :level="level + 1"
            :selected-id="selectedId"
            :parent-items="item.children"
            :index="index"
            @select="$emit('select', $event)"
            @dblclick="$emit('dblclick', $event)"
            @move="$emit('move', $event)"
            @item-change="$emit('item-change', $event)"
          >
            <template #actions="slotProps">
              <slot name="actions" v-bind="slotProps" />
            </template>
          </TreeNode>
      </div>
      <!--
      <draggable
        class="drag-area"
        tag="div"
        v-model="item.children"
        group="salt-tree"
        @change="onChange"
        item-key="identifier"
      >
        <template #item="{ element, index }">
          <TreeNode
            :item="element"
            :level="level + 1"
            :selected-id="selectedId"
            :parent-items="item.children"
            :index="index"
            @select="$emit('select', $event)"
            @dblclick="$emit('dblclick', $event)"
            @move="$emit('move', $event)"
            @item-change="$emit('item-change', $event)"
          >
            <template #actions="slotProps">
              <slot name="actions" v-bind="slotProps" />
            </template>
          </TreeNode>
        </template>
      </draggable>
      -->
    </div>
  </details>

  <!-- For items without children -->
  <div v-else class="tree-node" role="treeitem" :aria-level="level + 1">
    <div
      class="tree-node-content"
      :style="{ marginLeft: (level * 20) + 'px' }"
      draggable="true"
      @dragstart="onDragStart"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      :class="{
        'drop-before': dropPosition === 'before',
        'drop-after': dropPosition === 'after',
        'drop-inside': dropPosition === 'inside'
      }"
    >
      <span class="no-children-spacer" aria-hidden="true"></span>
      <img :src="iconSrc" class="tree-icon" aria-hidden="true" />
      <div
        class="tree-node-label"
        :class="{ 'selected': selectedId === item.identifier }"
        @click="select"
        @dblclick="dblClick"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
        style="cursor:pointer"
      >
        <span class="label-text">
          <span v-if="item.humanCodingScheme" class="coding-scheme" style="font-weight: bold;">{{ item.humanCodingScheme }}: </span>
          {{ item.abbreviatedTitle || item.title || item.identifier }}
        </span>
        <div v-if="showPopover && fullStatementHtml" class="popover" v-html="fullStatementHtml"></div>
      </div>
      <slot name="actions" :item="item" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useCurrentDocumentStore } from '@/stores/currentDocumentStore';
import { renderMarkdown } from '@/utils/markdownRenderer.js';

import docIcon from '@/assets/icons/ph/graph-fill.svg';
import itemIcon from '@/assets/icons/lucide/target.svg';
import assessmentIcon from '@/assets/icons/iconoir/learning.svg';
import courseIcon from '@/assets/icons/fluent-mdl2/learning-tools.svg';
import credentialIcon from '@/assets/icons/ph/certificate.svg';
import jobIcon from '@/assets/icons/eos-icons/role-binding.svg';
import organizationIcon from '@/assets/icons/f7/building-columns-fill.svg';
import identifierIcon from '@/assets/icons/lucide/id-card.svg';
import publicKeyIcon from '@/assets/icons/lucide/key-round.svg';
import folderIcon from '@/assets/icons/material-symbols/folder.svg';

const props = defineProps({
  item: Object,
  level: Number,
  selectedId: String,
  parentItems: Array,
  index: Number,
    startExpanded: {
        type: Boolean,
        default: false
    }
});
const emit = defineEmits(['select', 'dblclick', 'move', 'item-change']);

const isExpanded = ref(props.startExpanded); // Start closed by default
const isFocused = ref(false);
const hasChildren = computed(() => props.item.children && props.item.children.length > 0);

const showPopover = ref(false);
const popoverTimeout = ref(null);

const onChange = (evt) => {
    emit('item-change', { event: evt, parent: props.item });
};

const onMouseEnter = () => {
  if (popoverTimeout.value) {
    clearTimeout(popoverTimeout.value);
  }
  popoverTimeout.value = setTimeout(() => {
    showPopover.value = true;
  }, 500);
};

const onMouseLeave = () => {
  if (popoverTimeout.value) {
    clearTimeout(popoverTimeout.value);
    popoverTimeout.value = null;
  }
  showPopover.value = false;
};

const fullStatementHtml = computed(() => {
  const text = props.item.fullStatement || props.item.title || '';
  if (!text) return '';
  return renderMarkdown(text);
});

const iconSrc = computed(() => {
  const type = props.item.extensions?.['salt:type'] || 'item';
  if (props.item.creator) {
      return docIcon;
  }
  if (type === 'item' && hasChildren.value) {
    return folderIcon;
  }
  const iconMap = {
    document: docIcon,
    assessment: assessmentIcon,
    course: courseIcon,
    credential: credentialIcon,
    job: jobIcon,
    organization: organizationIcon,
    identifier: identifierIcon,
    public_key: publicKeyIcon,
    item: itemIcon
  };
  return iconMap[type] || itemIcon;
});

const onToggle = (event) => {
  // Only handle expansion/collapse
  if (hasChildren.value) {
    isExpanded.value = event.target.open;
  }
};

const onSummaryClick = (event) => {
  // Only expand/collapse if clicking the caret area, not the text
  const target = event.target;
  if (target.closest('.expand-indicator') || target.classList.contains('expand-indicator')) {
    // Let the native details toggle happen
    return;
  }
  // If clicking text or other areas, prevent the toggle and select instead
  event.preventDefault();
  select();
};

const select = () => { emit('select', props.item.identifier); };
const dblClick = () => { emit('dblclick', props.item.identifier); };

const onFocus = () => {
  isFocused.value = true;
};

const onBlur = () => {
  isFocused.value = false;
};

const onKeyDown = (event) => {
  switch (event.key) {
    case 'Enter':
    case ' ':
      event.preventDefault();
      // Always select the item, regardless of whether it has children
      select();
      break;
  }
};

// Drag-and-drop
const currentDocumentStore = useCurrentDocumentStore();
const dropPosition = ref(null);

function onDragStart(e) {
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('application/json', JSON.stringify({
    identifier: props.item.identifier,
    documentId: props.item.CFDocumentURI?.identifier || props.item.documentId
  }));
  currentDocumentStore.setDraggedItem(props.item);
}

function onDragOver(e) {
  e.preventDefault();
  const rect = e.currentTarget.getBoundingClientRect();
  const y = e.clientY - rect.top;
  const height = rect.height;

  // Sensitivity areas: top 25% = before, bottom 25% = after, middle 50% = inside
  if (y < height * 0.25) {
    dropPosition.value = 'before';
  } else if (y > height * 0.75) {
    dropPosition.value = 'after';
  } else {
    dropPosition.value = 'inside';
  }
}

function onDragLeave() {
  dropPosition.value = null;
}

function onDrop(e) {
  e.preventDefault();
  const position = dropPosition.value;
  dropPosition.value = null;

  const draggedItem = currentDocumentStore.draggedItem;
  if (!draggedItem) return;

  // Don't drop on self
  if (draggedItem.identifier === props.item.identifier) return;

  emit('move', {
    draggedItem,
    targetItem: props.item,
    position // 'before', 'inside', 'after'
  });
}
</script>

<style scoped>
.tree-node {
  width: 100%;
}

.tree-node-content {
  position: relative;
  display: flex;
  align-items: center;
  padding: 2px 0;
  min-height: 24px;
}

.expand-control {
  position: relative;
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
  position: relative;
  flex: 1 1 0;
  min-width: 0;
  display: flex;
  align-items: center;
  padding: 2px 6px;
  border-radius: 4px;
  transition: background-color 0.2s;
  user-select: text;
}

.label-text {
  flex: 1 1 0;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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

/* High contrast mode support */
@media (prefers-contrast: high) {
  .tree-node-label.selected {
    background: #000;
    color: #fff;
  }

}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .tree-node-label,
  .expand-indicator {
    transition: none;
  }
}

.tree-icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  margin-right: 4px;
}

.popover {
  position: absolute;
  z-index: 9999;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 8px;
  max-width: 400px;
  max-height: 300px;
  overflow-y: auto;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  white-space: pre-wrap;
  word-wrap: break-word;
  top: 100%;
  left: 0;
  margin-top: 5px;
  pointer-events: none;
}

.popover::before {
  content: '';
  position: absolute;
  top: -6px;
  left: 12px;
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-bottom: 6px solid #ddd;
  pointer-events: none;
}

.popover::after {
  content: '';
  position: absolute;
  top: -5px;
  left: 13px;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-bottom: 5px solid white;
  pointer-events: none;
}

.drop-before {
  border-top: 2px solid #007bff !important;
}

.drop-after {
  border-bottom: 2px solid #007bff !important;
}

.drop-inside {
  background-color: rgba(0, 123, 255, 0.1) !important;
  border: 1px dashed #007bff !important;
}
</style>
