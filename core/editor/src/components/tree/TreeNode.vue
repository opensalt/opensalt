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
          <span v-if="item.humanCodingScheme" class="coding-scheme" style="color: #6c757d;">{{ item.humanCodingScheme }}: </span>
          <span v-if="searchQuery && hasMatch" v-html="highlightedTitle"></span>
          <span v-else>{{ displayTitle }}</span>
        </span>
        <div v-if="showPopover && fullStatementHtml" class="popover" v-html="fullStatementHtml"></div>
      </div>
      <slot name="actions" :item="item" />
    </summary>

    <div v-if="hasChildren" class="children-container">
      <div v-for="(child, index) in item.children" :key="child.identifier">
          <TreeNode
            :item="child"
            :level="level + 1"
            :selected-id="selectedId"
            :parent-items="item.children"
            :index="index"
            :search-query="searchQuery"
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
          <span v-if="item.humanCodingScheme" class="coding-scheme" style="color: #6c757d;">{{ item.humanCodingScheme }}: </span>
          <span v-if="searchQuery && hasMatch" v-html="highlightedTitle"></span>
          <span v-else>{{ displayTitle }}</span>
        </span>
        <div v-if="showPopover && fullStatementHtml" class="popover" v-html="fullStatementHtml"></div>
      </div>
      <slot name="actions" :item="item" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useCurrentDocumentStore } from '@/stores/currentDocumentStore';

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

// Lazy-loaded markdown renderer with caching
let markdownRendererPromise = null;
let cachedRenderMarkdown = null;

async function getMarkdownRenderer() {
  if (cachedRenderMarkdown) {
    return cachedRenderMarkdown;
  }
  if (!markdownRendererPromise) {
    markdownRendererPromise = import('@/utils/markdownRenderer.js').then(module => {
      cachedRenderMarkdown = module.renderMarkdown;
      return cachedRenderMarkdown;
    });
  }
  return markdownRendererPromise;
}

const props = defineProps({
  item: Object,
  level: Number,
  selectedId: String,
  parentItems: Array,
  index: Number,
  startExpanded: {
    type: Boolean,
    default: false
  },
  searchQuery: {
    type: String,
    default: ''
  }
});
const emit = defineEmits(['select', 'dblclick', 'move', 'item-change']);

const isExpanded = ref(props.startExpanded); // Start closed by default
const isFocused = ref(false);
const hasChildren = computed(() => props.item.children && props.item.children.length > 0);

const labelRef1 = ref(null);
const labelRef2 = ref(null);

// Search/filter computed properties
const displayTitle = computed(() => {
  return props.item.abbreviatedStatement || props.item.fullStatement || props.item.title || props.item.identifier;
});

const searchableText = computed(() => {
  const parts = [
    props.item.humanCodingScheme,
    props.item.abbreviatedStatement,
    props.item.fullStatement,
    props.item.title,
    props.item.identifier
  ].filter(Boolean);
  return parts.join(' ').toLowerCase();
});

const hasMatch = computed(() => {
  if (!props.searchQuery) return true;
  return searchableText.value.includes(props.searchQuery.toLowerCase());
});

const hasMatchingDescendant = computed(() => {
  if (!props.searchQuery) return false;
  return checkDescendantsForMatch(props.item.children || [], props.searchQuery.toLowerCase());
});

function checkDescendantsForMatch(children, query) {
  for (const child of children) {
    const childText = [
      child.humanCodingScheme,
      child.abbreviatedStatement,
      child.fullStatement,
      child.title,
      child.identifier
    ].filter(Boolean).join(' ').toLowerCase();

    if (childText.includes(query)) return true;
    if (child.children && checkDescendantsForMatch(child.children, query)) return true;
  }
  return false;
}

const highlightedTitle = computed(() => {
  if (!props.searchQuery || !hasMatch.value) return displayTitle.value;

  const query = props.searchQuery;
  const title = displayTitle.value;
  const regex = new RegExp(`(${escapeRegExp(query)})`, 'gi');
  return title.replace(regex, '<mark class="search-highlight">$1</mark>');
});

function escapeRegExp(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Auto-expand when there are matching descendants
watch(() => props.searchQuery, (newQuery) => {
  if (newQuery && hasMatchingDescendant.value) {
    isExpanded.value = true;
  }
});

const showPopover = ref(false);
const popoverTimeout = ref(null);
const fullStatementHtml = ref('');

const onChange = (evt) => {
    emit('item-change', { event: evt, parent: props.item });
};

const onMouseEnter = () => {
  if (popoverTimeout.value) {
    clearTimeout(popoverTimeout.value);
  }
  popoverTimeout.value = setTimeout(async () => {
    showPopover.value = true;
    // Lazy load markdown renderer when popover is shown
    const text = props.item.fullStatement || props.item.title || '';
    if (text) {
      const renderMarkdown = await getMarkdownRenderer();
      fullStatementHtml.value = renderMarkdown(text);
    }
  }, 500);
};

const onMouseLeave = () => {
  if (popoverTimeout.value) {
    clearTimeout(popoverTimeout.value);
    popoverTimeout.value = null;
  }
  showPopover.value = false;
};

const tooltipTitle = computed(() => {
  const statement = props.item.fullStatement || props.item.abbreviatedStatement || '';
  const identifier = props.item.identifier || '';
  const notes = props.item.notes || '';
  return `<strong>Statement:</strong> ${statement}<br><strong>Identifier:</strong> ${identifier}<br><strong>Notes:</strong> ${notes}`;
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
  // Only expand/collapse if clicking caret area, not text
  const target = event.target;
  if (target.closest('.expand-indicator') || target.classList.contains('expand-indicator')) {
    // Let native details toggle happen
    return;
  }
  // If clicking text or other areas, prevent toggle and select instead
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
      // Always select item, regardless of whether it has children
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

onMounted(() => {
  if (labelRef1.value) {
    new bootstrap.Tooltip(labelRef1.value);
  }
  if (labelRef2.value) {
    new bootstrap.Tooltip(labelRef2.value);
  }
});
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

.search-highlight,
:deep(.search-highlight) {
  background-color: #fff3cd;
  padding: 0 2px;
  border-radius: 2px;
  font-weight: 600;
}
</style>
