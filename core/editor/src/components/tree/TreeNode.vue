<template>
  <details v-if="hasChildren" :open="isExpanded" @toggle="onToggle" class="tree-node" :class="{ 'tree-node--hidden': !isVisible }" role="treeitem" :aria-level="level + 1" :aria-expanded="isExpanded" :aria-selected="selectedId === item.identifier" :aria-setsize="siblingCount" :aria-posinset="siblingPosition" :aria-hidden="!isVisible" :tabindex="isFocused ? '0' : '-1'" :data-tree-node-id="item.identifier" @keydown="handleKeyDown">
    <summary
      class="expand-control"
      :style="{ marginLeft: (level * 20) + 'px' }"
      @click="onSummaryClick"
      draggable="true"
      @dragstart="onDragStart"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      tabindex="-1"
      :class="{
        'drop-before': dropPosition === 'before',
        'drop-after': dropPosition === 'after',
        'drop-inside': dropPosition === 'inside',
        'tree-node--ancestor-match': isAncestorOnlyMatch
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
        <span class="label-text">
          <!-- Loading spinner for cross-framework items -->
          <span v-if="isCrossFrameworkItem && isLoadingCrossFramework" class="loading-spinner" aria-hidden="true">
            <i class="bi bi-arrow-repeat spin"></i>
          </span>
          <!-- External link icon for cross-framework items -->
          <span v-else-if="isCrossFrameworkItem" class="cross-framework-badge" aria-hidden="true" title="External framework item">
            <i class="bi bi-box-arrow-up-right"></i>
          </span>
          <span v-if="isCrossFrameworkItem && item.externalFrameworkTitle" class="badge bg-primary text-white me-2 ms-1">
            <i class="bi bi-box-arrow-up-right me-1"></i>{{ item.externalFrameworkTitle }}
          </span>
          <span v-if="displayHumanCodingScheme" class="coding-scheme" style="color: #6c757d;">{{ displayHumanCodingScheme }}: </span>
          <span v-if="searchQuery && hasMatch" v-html="highlightedTitle"></span>
          <span v-else>{{ displayTitle }}</span>
        </span>
        <div v-if="showPopover && fullStatementHtml" class="popover" v-html="fullStatementHtml" role="tooltip"></div>
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

  <!-- For items without children -->
  <div v-else class="tree-node" :class="{ 'tree-node--hidden': !isVisible }" role="treeitem" :aria-level="level + 1" :aria-selected="selectedId === item.identifier" :aria-setsize="siblingCount" :aria-posinset="siblingPosition" :aria-hidden="!isVisible" :tabindex="isFocused ? '0' : '-1'" :data-tree-node-id="item.identifier" @keydown="handleKeyDown">
    <div
      class="tree-node-content"
      :style="{ marginLeft: (level * 20) + 'px' }"
      draggable="true"
      @dragstart="onDragStart"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      :tabindex="isFocused ? '0' : '-1'"
      :class="{
        'drop-before': dropPosition === 'before',
        'drop-after': dropPosition === 'after',
        'drop-inside': dropPosition === 'inside',
        'tree-node--ancestor-match': isAncestorOnlyMatch
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
        <span class="label-text">
          <!-- Loading spinner for cross-framework items -->
          <span v-if="isCrossFrameworkItem && isLoadingCrossFramework" class="loading-spinner" aria-hidden="true">
            <i class="bi bi-arrow-repeat spin"></i>
          </span>
          <!-- External link icon for cross-framework items -->
          <span v-else-if="isCrossFrameworkItem" class="cross-framework-badge" aria-hidden="true" title="External framework item">
            <i class="bi bi-box-arrow-up-right"></i>
          </span>
          <span v-if="isCrossFrameworkItem && item.externalFrameworkTitle" class="badge bg-primary text-white me-2 ms-1">
            <i class="bi bi-box-arrow-up-right me-1"></i>{{ item.externalFrameworkTitle }}
          </span>
          <span v-if="displayHumanCodingScheme" class="coding-scheme" style="color: #6c757d;">{{ displayHumanCodingScheme }}: </span>
          <span v-if="searchQuery && hasMatch" v-html="highlightedTitle"></span>
          <span v-else>{{ displayTitle }}</span>
        </span>
        <div v-if="showPopover && fullStatementHtml" class="popover" v-html="fullStatementHtml" role="tooltip"></div>
      </div>
      <slot name="actions" :item="item" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, inject, toValue, nextTick } from 'vue';
import { useCurrentDocumentStore } from '@/stores/currentDocumentStore';
import { useCrossFrameworkItem } from '@/composables/useCrossFrameworkItem';
import { logger } from '@/utils/logger.js';
import sanitizeHtml from 'sanitize-html';

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
  },
  matchingItemIds: {
    type: Set,
    default: () => new Set()
  }
});
const emit = defineEmits(['select', 'dblclick', 'move', 'item-change', 'focus']);

// Check if this is a cross-framework item
const isCrossFrameworkItem = computed(() => props.item.isCrossFramework === true);

// Setup cross-framework item loading for placeholder items
// Structure matches what useCrossFrameworkItem expects (like AssociationItem.vue)
// The composable expects destinationNodeURI when direction is 'normal'
const crossFrameworkData = computed(() => {
  if (!isCrossFrameworkItem.value || !props.item.crossFrameworkUri) {
    return null;
  }
  return {
    destinationNodeURI: {
      uri: props.item.crossFrameworkUri,
      identifier: props.item.identifier,
      title: props.item.title || props.item.abbreviatedStatement || props.item.fullStatement
    },
    associationType: 'isChildOf'
  };
});

// Use the composable only for cross-framework items (like AssociationItem.vue)
const {
  itemData,
  itemTitle,
  isLoading: isLoadingCrossFramework,
  frameworkTitle,
  fetchError,
  loadExternalItem
} = useCrossFrameworkItem({
  association: crossFrameworkData,
  direction: 'normal'
});

// Watch for fetched data and merge into the local item object
// This ensures all computed properties (iconSrc, humanCodingScheme, etc.) work correctly
watch(itemData, (newData) => {
  logger.debug(`[TreeNode] itemData watcher triggered for ${props.item.identifier}, has data: ${!!newData}, isCrossFramework: ${isCrossFrameworkItem.value}`);
  if (newData && isCrossFrameworkItem.value) {
    // Merge fetched data into the item to enable proper display
    const mergedData = {
      fullStatement: newData.fullStatement || newData.CFItemFullStatement,
      abbreviatedStatement: newData.abbreviatedStatement || newData.CFItemAbbreviatedStatement,
      humanCodingScheme: newData.humanCodingScheme || newData.CFItemHumanCodingScheme,
      itemType: newData.itemType || newData.CFItemType,
      notes: newData.notes || newData.CFItemNotes,
      language: newData.language || newData.CFItemLanguage,
      educationLevel: newData.educationLevel || newData.CFItemEducationLevel,
      conceptKeywords: newData.conceptKeywords || newData.CFItemConceptKeywords,
      licenseURI: newData.licenseURI || newData.CFItemLicenseURI,
      lastChanged: newData.lastChanged || newData.CFItemLastChangeDateTime,
      extensions: newData.extensions || newData.CFItemExtensions
    };

    // Only assign defined values to avoid overwriting with undefined
    Object.keys(mergedData).forEach(key => {
      if (mergedData[key] !== undefined) {
        props.item[key] = mergedData[key];
      }
    });

    // Also update the title display property if available
    const newTitle = newData.title || newData.fullStatement || newData.CFItemFullStatement;
    if (newTitle && (props.item.title === 'Loading...' || !props.item.title)) {
      props.item.title = newTitle;
    }

    // Store the framework title on the item for reference
    if (frameworkTitle.value &&
        (!props.item.externalFrameworkTitle || props.item.externalFrameworkTitle === 'Loading...')) {
      props.item.externalFrameworkTitle = frameworkTitle.value;
    }

    logger.debug(`[TreeNode] Merged cross-framework data for ${props.item.identifier}`);
  }
}, { immediate: true });

// Watch for framework title changes separately to handle the case where
// frameworkTitle is updated after itemData (due to async document fetch)
watch(frameworkTitle, (newTitle) => {
  if (newTitle && newTitle !== 'Loading...' && isCrossFrameworkItem.value) {
    logger.debug(`[TreeNode] frameworkTitle watcher updating externalFrameworkTitle for ${props.item.identifier}: ${newTitle}`);
    props.item.externalFrameworkTitle = newTitle;
  }
}, { immediate: true });

// Eagerly load the external item data if it's a cross-framework item
watch(isCrossFrameworkItem, (isCross) => {
  logger.debug(`[TreeNode] isCrossFrameworkItem watcher: ${isCross} for ${props.item.identifier}`);
  if (isCross) {
    logger.debug(`[TreeNode] Calling loadExternalItem for ${props.item.identifier}, crossFrameworkUri: ${props.item.crossFrameworkUri}`);
    loadExternalItem();
  }
}, { immediate: true });

onMounted(() => {
  if (isCrossFrameworkItem.value) {
    logger.debug(`[TreeNode] Mounted cross-framework item: ${props.item.identifier}, loading: ${isLoadingCrossFramework.value}`);
  }
});

onUnmounted(() => {
  if (isCrossFrameworkItem.value) {
    logger.debug(`[TreeNode] Unmounted cross-framework item: ${props.item.identifier}`);
  }
});


const hasChildren = computed(() => props.item.children && props.item.children.length > 0);

// Computed properties for ARIA attributes
const siblingCount = computed(() => props.parentItems?.length || 1);
const siblingPosition = computed(() => (props.index ?? 0) + 1);

// Inject navigation context with expanded state management
const navigation = inject('treeNavigation', {
  focusedItemId: ref(null),
  isItemExpanded: () => false,
  expandItem: () => {},
  collapseItem: () => {},
  toggleExpanded: () => {}
});

// Use centralized expanded state from navigation context
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
// This replaces the prop-based approach to ensure focus state is synchronized
// across all tree nodes via the navigation composable
const isFocused = computed(() => {
  // Use the injected focusedItemId from navigation context
  return navigation.focusedItemId?.value === props.item.identifier;
});

const labelRef1 = ref(null);
const labelRef2 = ref(null);

const displayHumanCodingScheme = computed(() => {
  if (isCrossFrameworkItem.value && itemData.value) {
    const scheme = itemData.value.humanCodingScheme || itemData.value.CFItemHumanCodingScheme;
    if (scheme) return scheme;
  }
  return props.item.humanCodingScheme;
});

// Search/filter computed properties
const displayTitle = computed(() => {
  // For cross-framework items, use the loaded title if available
  if (isCrossFrameworkItem.value && itemTitle.value && itemTitle.value !== 'Unknown') {
    return itemTitle.value;
  }
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

// Visibility for search filtering
const isVisible = computed(() => {
  // No search = all visible
  if (!props.searchQuery) return true;

  // Item matches = visible
  if (props.matchingItemIds.has(props.item.identifier)) return true;

  // Has matching descendant = visible (ancestor path)
  if (hasMatchingDescendant.value) return true;

  return false;
});

// Determine if this item is an ancestor-only match (visible but doesn't match itself)
const isAncestorOnlyMatch = computed(() => {
  // If no search, not an ancestor match
  if (!props.searchQuery) return false;
  // If this item matches directly, it's not ancestor-only
  if (hasMatch.value) return false;
  // If this item is visible and has matching descendants, it's an ancestor-only match
  return isVisible.value && hasMatchingDescendant.value;
});

const highlightedTitle = computed(() => {
  if (!props.searchQuery || !hasMatch.value) return displayTitle.value;

  // Sanitize both the title and query to prevent XSS attacks
  const sanitizedTitle = sanitizeHtml(displayTitle.value, {
    allowedTags: [],
    allowedAttributes: {}
  });
  // Also sanitize the query to prevent any potential injection through search input
  const sanitizedQuery = sanitizeHtml(props.searchQuery, {
    allowedTags: [],
    allowedAttributes: {}
  });
  const regex = new RegExp(`(${escapeRegExp(sanitizedQuery)})`, 'gi');
  return sanitizedTitle.replace(regex, '<mark class="search-highlight">$1</mark>');
});

function escapeRegExp(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Auto-expand when there are matching descendants
watch(() => props.searchQuery, (newQuery) => {
  if (newQuery && hasMatchingDescendant.value) {
    navigation.expandItem(props.item.identifier);
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
      const rawHtml = renderMarkdown(text);
      // Sanitize the rendered markdown to prevent XSS attacks
      fullStatementHtml.value = sanitizeHtml(rawHtml, {
        allowedTags: ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'code', 'pre', 'a', 'span', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td'],
        allowedAttributes: {
          'a': ['href', 'title', 'target', 'rel'],
          'span': ['class'],
          'div': ['class'],
          'code': ['class']
        },
        transformTags: {
          'a': sanitizeHtml.simpleTransform('a', { target: '_blank', rel: 'noopener noreferrer' })
        }
      });
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
  // Only handle expansion/collapse - use centralized state
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

// Handle keyboard events - use navigation context if available
const handleKeyDown = (event) => {
  // Stop propagation to prevent ancestor tree nodes from handling this event twice
  event.stopPropagation();

  // First, let the navigation context handle navigation keys if available
  if (navigation?.handleKeyDown) {
    // Check if it's a navigation key
    const navigationKeys = ['ArrowDown', 'ArrowUp', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'PageDown', 'PageUp', '*', 'Enter', ' '];
    if (navigationKeys.includes(event.key)) {
      navigation.handleKeyDown(event, props.item);
      return;
    }
  }
};

// Drag-and-drop
const currentDocumentStore = useCurrentDocumentStore();
const dropPosition = ref(null);

function onDragStart(e) {
  // Disable drag for cross-framework items
  if (isCrossFrameworkItem.value) {
    e.preventDefault();
    return;
  }
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
  // Only initialize Bootstrap tooltips if Bootstrap is available
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    if (labelRef1.value) {
      new bootstrap.Tooltip(labelRef1.value);
    }
    if (labelRef2.value) {
      new bootstrap.Tooltip(labelRef2.value);
    }
  }
});

// Clean up any pending timeouts on unmount
onUnmounted(() => {
  if (popoverTimeout.value) {
    clearTimeout(popoverTimeout.value);
    popoverTimeout.value = null;
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

/* Hidden items during search */
.tree-node--hidden {
  display: none !important;
}

/* Ensure hidden items are not accessible */
.tree-node--hidden[aria-hidden="true"] {
  visibility: hidden;
  position: absolute;
  left: -9999px;
}

/* Ancestor-only match styling - items visible only due to matching descendants */
.tree-node--ancestor-match {
  opacity: 0.5;
}

/* Cross-framework item styling */
.tree-node-label.cross-framework {
  font-style: italic;
}

.tree-node-label.cross-framework:hover {
  background-color: rgba(59, 130, 246, 0.15); /* Slightly darker on hover */
}

.tree-node-label.cross-framework.selected {
  background-color: rgba(59, 130, 246, 0.2); /* Darker blue when selected */
  border-left-color: #2563eb; /* Darker blue border when selected */
}

/* Loading spinner for cross-framework items */
.loading-spinner {
  display: inline-flex;
  align-items: center;
  margin-right: 4px;
  color: #6c757d;
}

.loading-spinner .spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Cross-framework badge */
.cross-framework-badge {
  display: inline-flex;
  align-items: center;
  margin-right: 4px;
  color: #0d6efd;
  font-size: 0.875em;
}
</style>
