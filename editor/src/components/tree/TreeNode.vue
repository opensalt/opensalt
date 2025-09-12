<template>
  <details v-if="hasChildren" :open="isExpanded" @toggle="onToggle" class="tree-node" role="treeitem" :aria-level="level + 1">
    <summary class="expand-control" :style="{ marginLeft: (level * 20) + 'px' }" @click="onSummaryClick">
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

    <TreeNode
      v-for="(child, cidx) in item.children"
      :key="child.identifier"
      :item="child"
      :level="level + 1"
      :selected-id="selectedId"
      :parent-items="item.children"
      :index="cidx"
      @select="$emit('select', $event)"
      @dblclick="$emit('dblclick', $event)"
      @move="$emit('move', $event)"
    >
      <template #actions="slotProps">
        <slot name="actions" v-bind="slotProps" />
      </template>
    </TreeNode>
  </details>

  <!-- For items without children -->
  <div v-else class="tree-node" role="treeitem" :aria-level="level + 1">
    <div class="tree-node-content" :style="{ marginLeft: (level * 20) + 'px' }">
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
const emit = defineEmits(['select', 'dblclick', 'move']);

const isExpanded = ref(props.startExpanded); // Start closed by default
const isFocused = ref(false);
const hasChildren = computed(() => props.item.children && props.item.children.length > 0);

const showPopover = ref(false);
const popoverTimeout = ref(null);

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
const dragOver = ref(false);
function onDragStart(e) {
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', props.index);
}
function onDragOver(e) {
  e.preventDefault();
  dragOver.value = true;
}
function onDragLeave() {
  dragOver.value = false;
}
function onDrop(e) {
  e.preventDefault();
  dragOver.value = false;
  const fromIdx = parseInt(e.dataTransfer.getData('text/plain'));
  emit('move', { fromIdx, toIdx: props.index, parentItems: props.parentItems });
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
</style>
