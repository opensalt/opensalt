<template>
  <!-- Node with children: uses <details> for native expand/collapse -->
  <details
    v-if="hasChildren"
    :open="isExpanded"
    @toggle="onToggle"
    class="tree-node"
    :class="{ 'tree-node--hidden': !isVisible, 'view-mode': isViewMode }"
    role="treeitem"
    :aria-level="level + 1"
    :aria-expanded="isExpanded"
    :aria-selected="selectedId === item.identifier"
    :aria-setsize="siblingCount"
    :aria-posinset="siblingPosition"
    :aria-hidden="!isVisible"
    :tabindex="isFocused ? '0' : '-1'"
    :data-tree-node-id="item.identifier"
    @keydown="handleKeyDown"
    :aria-readonly="isViewMode"
  >
    <summary
      class="expand-control"
      :style="{ marginLeft: (level * 20) + 'px' }"
      @click="onSummaryClick"
      :draggable="!isViewMode && !isCrossFrameworkItem"
      @dragstart="onDragStart"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      tabindex="-1"
      :class="{
        'drop-before': dropPosition === 'before',
        'drop-after': dropPosition === 'after',
        'drop-inside': dropPosition === 'inside',
        'tree-node--ancestor-match': isAncestorOnlyMatch,
        'view-mode': isViewMode
      }"
    >
      <span class="expand-indicator" aria-hidden="true">
        <i :class="isExpanded ? 'bi bi-caret-down-fill' : 'bi bi-caret-right-fill'"></i>
      </span>
      <img :src="iconSrc" class="tree-icon" aria-hidden="true" />
      <div
        class="tree-node-label"
        :class="{ 'selected': selectedId === item.identifier, 'focused': isFocused, 'cross-framework': isCrossFrameworkItem }"
        @click.stop.prevent="select"
        @dblclick.stop="dblClick"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
        style="cursor:pointer"
        role="button"
        :aria-expanded="isExpanded"
      >
        <TreeNodeLabel
          :is-cross-framework-item="isCrossFrameworkItem"
          :is-loading-cross-framework="isLoadingCrossFramework"
          :external-framework-title="item.externalFrameworkTitle"
          :display-human-coding-scheme="displayHumanCodingScheme"
          :search-query="searchQuery"
          :has-match="hasMatch"
          :highlighted-title="highlightedTitle"
          :display-title="displayTitle"
          :show-popover="showPopover"
          :full-statement-html="fullStatementHtml"
        />
      </div>
      <slot name="actions" :item="item" />
    </summary>

    <div v-if="hasChildren" class="children-container" role="group">
      <div v-for="(child, index) in item.children" :key="child.identifier">
        <TreeNode
          :item="child"
          :level="level + 1"
          :selected-id="selectedId"
          :parent-items="item.children"
          :index="index"
          :search-query="searchQuery"
          :matching-item-ids="matchingItemIds"
          :is-view-mode="isViewMode"
          :disable-drop="disableDrop"
          @select="$emit('select', $event)"
          @dblclick="$emit('dblclick', $event)"
          @move="$emit('move', $event)"
          @item-change="$emit('item-change', $event)"
          @focus="$emit('focus', $event)"
        >
          <template #actions="slotProps">
            <slot name="actions" v-bind="slotProps" />
          </template>
        </TreeNode>
      </div>
    </div>
  </details>

  <!-- Leaf node (no children): plain <div> -->
  <div
    v-else
    class="tree-node"
    :class="{ 'tree-node--hidden': !isVisible, 'view-mode': isViewMode }"
    role="treeitem"
    :aria-level="level + 1"
    :aria-selected="selectedId === item.identifier"
    :aria-setsize="siblingCount"
    :aria-posinset="siblingPosition"
    :aria-hidden="!isVisible"
    :tabindex="isFocused ? '0' : '-1'"
    :data-tree-node-id="item.identifier"
    @keydown="handleKeyDown"
    :aria-readonly="isViewMode"
  >
    <div
      class="tree-node-content"
      :style="{ marginLeft: (level * 20) + 'px' }"
      :draggable="!isViewMode && !isCrossFrameworkItem"
      @dragstart="onDragStart"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      :tabindex="isFocused ? '0' : '-1'"
      :class="{
        'drop-before': dropPosition === 'before',
        'drop-after': dropPosition === 'after',
        'drop-inside': dropPosition === 'inside',
        'tree-node--ancestor-match': isAncestorOnlyMatch,
        'view-mode': isViewMode
      }"
    >
      <span class="no-children-spacer" aria-hidden="true"></span>
      <img :src="iconSrc" class="tree-icon" aria-hidden="true" />
      <div
        class="tree-node-label"
        :class="{ 'selected': selectedId === item.identifier, 'focused': isFocused, 'cross-framework': isCrossFrameworkItem }"
        @click="select"
        @dblclick="dblClick"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
        style="cursor:pointer"
        role="button"
      >
        <TreeNodeLabel
          :is-cross-framework-item="isCrossFrameworkItem"
          :is-loading-cross-framework="isLoadingCrossFramework"
          :external-framework-title="item.externalFrameworkTitle"
          :display-human-coding-scheme="displayHumanCodingScheme"
          :search-query="searchQuery"
          :has-match="hasMatch"
          :highlighted-title="highlightedTitle"
          :display-title="displayTitle"
          :show-popover="showPopover"
          :full-statement-html="fullStatementHtml"
        />
      </div>
      <slot name="actions" :item="item" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, inject } from 'vue';
import { useViewStore } from '@/stores/viewStore';
import { useEditorContextStore } from '@/stores/editorContextStore';
import { useCrossFrameworkItem } from '@/composables/useCrossFrameworkItem';
import { useTreeNodeSearch } from '@/composables/useTreeNodeSearch.js';
import { useTreeNodeDragDrop } from '@/composables/useTreeNodeDragDrop.js';
import { logger } from '@/utils/logger.js';

import TreeNodeLabel from './TreeNodeLabel.vue';

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

// ---------------------------------------------------------------------------
// Lazy markdown renderer
// ---------------------------------------------------------------------------
let markdownRendererPromise = null;
let cachedRenderMarkdown = null;

async function getMarkdownRenderer() {
  if (cachedRenderMarkdown) return cachedRenderMarkdown;
  if (!markdownRendererPromise) {
    markdownRendererPromise = import('@/utils/markdownRenderer').then((m) => {
      cachedRenderMarkdown = m.renderMarkdown;
      return cachedRenderMarkdown;
    });
  }
  return markdownRendererPromise;
}

// ---------------------------------------------------------------------------
// Props / emits
// ---------------------------------------------------------------------------
const props = defineProps({
  item: Object,
  level: Number,
  selectedId: String,
  parentItems: Array,
  index: Number,
  startExpanded: { type: Boolean, default: false },
  searchQuery: { type: String, default: '' },
  matchingItemIds: { type: Set, default: () => new Set() },
  isViewMode: { type: Boolean, default: false },
  disableDrop: { type: Boolean, default: false },
});
const emit = defineEmits(['select', 'dblclick', 'move', 'item-change', 'focus']);

// ---------------------------------------------------------------------------
// Cross-framework item resolution
// ---------------------------------------------------------------------------
const isCrossFrameworkItem = computed(() => props.item.isCrossFramework === true);

const crossFrameworkData = computed(() => {
  if (!isCrossFrameworkItem.value || !props.item.crossFrameworkUri) return null;
  return {
    destinationNodeURI: {
      uri: props.item.crossFrameworkUri,
      identifier: props.item.identifier,
      title: props.item.title || props.item.abbreviatedStatement || props.item.fullStatement,
    },
    associationType: 'isChildOf',
  };
});

const {
  itemData,
  itemTitle,
  isLoading: isLoadingCrossFramework,
  frameworkTitle,
  loadExternalItem,
} = useCrossFrameworkItem({ association: crossFrameworkData, direction: 'normal' });

const contextStore = useEditorContextStore();

const resolvedItem = computed(() => {
  if (!isCrossFrameworkItem.value) return props.item;
  const registered = contextStore.itemRegistry.get(props.item.identifier);
  if (registered?.item) {
    return {
      ...props.item,
      ...registered.item,
      externalFrameworkTitle: props.item.externalFrameworkTitle || frameworkTitle.value,
    };
  }
  return {
    ...props.item,
    ...(itemData.value || {}),
    externalFrameworkTitle: props.item.externalFrameworkTitle || frameworkTitle.value,
  };
});

watch(isCrossFrameworkItem, (isCross) => { if (isCross) loadExternalItem(); }, { immediate: true });

onMounted(() => {
  if (isCrossFrameworkItem.value) {
    logger.debug(`[TreeNode] Mounted cross-framework item: ${props.item.identifier}`);
  }
});
onUnmounted(() => {
  if (isCrossFrameworkItem.value) {
    logger.debug(`[TreeNode] Unmounted cross-framework item: ${props.item.identifier}`);
  }
});

// ---------------------------------------------------------------------------
// Basic computed
// ---------------------------------------------------------------------------
const hasChildren = computed(() => props.item.children && props.item.children.length > 0);
const siblingCount = computed(() => props.parentItems?.length || 1);
const siblingPosition = computed(() => (props.index ?? 0) + 1);

// ---------------------------------------------------------------------------
// Navigation context (injected from EnhancedDocumentTreeEditor)
// ---------------------------------------------------------------------------
const navigation = inject('treeNavigation', {
  focusedItemId: ref(null),
  isItemExpanded: () => false,
  expandItem: () => {},
  collapseItem: () => {},
  toggleExpanded: () => {},
});

const isExpanded = computed({
  get: () => navigation.isItemExpanded(props.item.identifier),
  set: (value) => {
    if (value) navigation.expandItem(props.item.identifier);
    else navigation.collapseItem(props.item.identifier);
  },
});

const isFocused = computed(() => navigation.focusedItemId?.value === props.item.identifier);

// ---------------------------------------------------------------------------
// Display values
// ---------------------------------------------------------------------------
const displayHumanCodingScheme = computed(
  () => resolvedItem.value.CFItemHumanCodingScheme || resolvedItem.value.humanCodingScheme
);

const displayTitle = computed(() => {
  if (isCrossFrameworkItem.value && itemTitle.value && itemTitle.value !== 'Unknown') {
    return itemTitle.value;
  }
  return (
    resolvedItem.value.abbreviatedStatement ||
    resolvedItem.value.fullStatement ||
    resolvedItem.value.title ||
    resolvedItem.value.identifier
  );
});

// ---------------------------------------------------------------------------
// Search / filter (via composable)
// ---------------------------------------------------------------------------
const { hasMatch, isVisible, isAncestorOnlyMatch, highlightedTitle } = useTreeNodeSearch({
  resolvedItem,
  searchQuery: computed(() => props.searchQuery),
  matchingItemIds: computed(() => props.matchingItemIds),
  navigation,
  itemIdentifier: computed(() => props.item.identifier),
});

// ---------------------------------------------------------------------------
// Popover (hover tooltip with full statement)
// ---------------------------------------------------------------------------
const viewStore = useViewStore();
const showPopover = ref(false);
const popoverTimeout = ref(null);
const fullStatementHtml = ref('');

const onMouseEnter = () => {
  if (viewStore.draggedItem) return;
  if (popoverTimeout.value) clearTimeout(popoverTimeout.value);
  popoverTimeout.value = setTimeout(async () => {
    showPopover.value = true;
    const text = resolvedItem.value.fullStatement || resolvedItem.value.title || '';
    if (text) {
      const renderMarkdownText = await getMarkdownRenderer();
      fullStatementHtml.value = renderMarkdownText(text);
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

onUnmounted(() => {
  if (popoverTimeout.value) {
    clearTimeout(popoverTimeout.value);
    popoverTimeout.value = null;
  }
});

// ---------------------------------------------------------------------------
// Icon resolution
// ---------------------------------------------------------------------------
const iconSrc = computed(() => {
  const type = resolvedItem.value.extensions?.['salt:type'] || 'item';
  if (resolvedItem.value.creator) return docIcon;
  if (type === 'item' && hasChildren.value) return folderIcon;
  const iconMap = {
    document: docIcon,
    assessment: assessmentIcon,
    course: courseIcon,
    credential: credentialIcon,
    job: jobIcon,
    organization: organizationIcon,
    identifier: identifierIcon,
    public_key: publicKeyIcon,
    item: itemIcon,
  };
  return iconMap[type] || itemIcon;
});

// ---------------------------------------------------------------------------
// Expand/collapse toggle events
// ---------------------------------------------------------------------------
const onToggle = (event) => {
  if (hasChildren.value) {
    const newState = event.target.open;
    if (newState) navigation.expandItem(props.item.identifier);
    else navigation.collapseItem(props.item.identifier);
  }
};

const onSummaryClick = (event) => {
  const target = event.target;
  if (target.closest('.expand-indicator') || target.classList.contains('expand-indicator')) return;
  event.preventDefault();
  select();
};

// ---------------------------------------------------------------------------
// Selection / interaction
// ---------------------------------------------------------------------------
const select = () => emit('select', props.item.identifier);
const dblClick = () => emit('dblclick', props.item.identifier);

const handleKeyDown = (event) => {
  event.stopPropagation();
  if (navigation?.handleKeyDown) {
    const navigationKeys = ['ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'PageDown', 'PageUp', '*', 'Enter', ' '];
    if (navigationKeys.includes(event.key)) {
      navigation.handleKeyDown(event, props.item);
      return;
    }
  }
};

// ---------------------------------------------------------------------------
// Drag-and-drop (via composable)
// ---------------------------------------------------------------------------
const { dropPosition, onDragStart, onDragOver, onDragLeave, onDrop } = useTreeNodeDragDrop({
  item: computed(() => props.item),
  isViewMode: computed(() => props.isViewMode),
  isCrossFrameworkItem,
  disableDrop: computed(() => props.disableDrop),
  viewStore,
  emit,
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

/* Hidden items during search */
.tree-node--hidden {
  display: none !important;
}

.tree-node--hidden[aria-hidden="true"] {
  visibility: hidden;
  position: absolute;
  left: -9999px;
}

/* Ancestor-only match styling */
.tree-node--ancestor-match {
  opacity: 0.5;
}

/* View mode styling */
.view-mode {
  cursor: default;
}

.tree-node.view-mode .tree-node-label {
  cursor: default;
}
</style>
