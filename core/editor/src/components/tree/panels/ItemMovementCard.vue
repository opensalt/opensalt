<template>
  <div
    v-if="canEditItem && !isItemFromViewedFramework && !isReadOnly"
    class="btn-group btn-group-sm me-2"
  >
    <button
      v-if="canMoveUp"
      type="button"
      class="btn btn-outline-secondary"
      title="Move Up"
      aria-label="Move item up"
      @click="move('up')"
    >
      <i
        class="bi bi-arrow-up"
        aria-hidden="true"
      />
    </button>
    <button
      v-if="canMoveDown"
      type="button"
      class="btn btn-outline-secondary"
      title="Move Down"
      aria-label="Move item down"
      @click="move('down')"
    >
      <i
        class="bi bi-arrow-down"
        aria-hidden="true"
      />
    </button>
    <button
      v-if="canIndent"
      type="button"
      class="btn btn-outline-secondary"
      title="Indent (Make child of previous sibling)"
      aria-label="Indent item"
      @click="move('indent')"
    >
      <i
        class="bi bi-arrow-right"
        aria-hidden="true"
      />
    </button>
    <button
      v-if="canOutdent"
      type="button"
      class="btn btn-outline-secondary"
      title="Outdent (Move to parent level)"
      aria-label="Outdent item"
      @click="move('outdent')"
    >
      <i
        class="bi bi-arrow-left"
        aria-hidden="true"
      />
    </button>
  </div>
</template>

<script setup>
import { computed, inject } from 'vue';
import { useItemStore } from '../../../stores/itemStore';
import { findItemPath } from '../../../utils/tree';
import { logger } from '../../../utils/logger';

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  currentDocument: {
    type: Object,
    required: true
  },
  canEditItem: {
    type: Boolean,
    default: true
  },
  isItemFromViewedFramework: {
    type: Boolean,
    default: false
  },
  isReadOnly: {
    type: Boolean,
    default: false
  }
});

const isInternalItem = computed(() => {
  return props.currentDocument?.id &&
    (props.item?.documentId === props.currentDocument.id ||
     props.item?.CFDocumentURI?.identifier === props.currentDocument.id);
});

// We disable this if item is cross framework or from different framework
const isMoveable = computed(() => {
  return props.canEditItem && !props.isItemFromViewedFramework && !props.isReadOnly && isInternalItem.value;
});

const path = computed(() => {
  if (!isMoveable.value || !props.currentDocument?.items || !props.item?.identifier) return null;
  return findItemPath(props.currentDocument.items, props.item.identifier);
});

// Identify siblings and indices
const parentLevelContext = computed(() => {
  if (!path.value) return null;
  const p = path.value;

  if (p.length === 1) {
    // Root level
    return {
      siblings: props.currentDocument.items,
      index: props.currentDocument.items.findIndex(i => i.identifier === props.item.identifier),
      parent: null
    };
  } else if (p.length >= 2) {
    // Has parent
    const _parentId = p[p.length - 2];
    // Find parent object by doing a path traversal again
    let currentLevel = props.currentDocument.items;
    let parentObj = null;
    for (let i = 0; i < p.length - 1; i++) {
        parentObj = currentLevel.find(item => item.identifier === p[i]);
        if (parentObj) {
            currentLevel = parentObj.children || [];
        } else {
            break;
        }
    }

    if (parentObj) {
        return {
            siblings: parentObj.children || [],
            index: (parentObj.children || []).findIndex(i => i.identifier === props.item.identifier),
            parent: parentObj
        };
    }
  }
  return null;
});

const canMoveUp = computed(() => {
  const ctx = parentLevelContext.value;
  return ctx && ctx.index > 0;
});

const canMoveDown = computed(() => {
  const ctx = parentLevelContext.value;
  return ctx && ctx.index >= 0 && ctx.index < ctx.siblings.length - 1;
});

const canIndent = computed(() => {
  const ctx = parentLevelContext.value;
  return ctx && ctx.index > 0;
});

const canOutdent = computed(() => {
  const ctx = parentLevelContext.value;
  return ctx && ctx.parent !== null;
});

const _emit = defineEmits(['tree-change']);
const itemStore = useItemStore();

const navigation = inject('treeNavigation', {
  expandItem: () => {},
});

async function move(direction) {
  if (!isMoveable.value) return;
  const ctx = parentLevelContext.value;
  if (!ctx) return;

  let targetItem = null;
  let position = '';

  if (direction === 'up') {
    if (ctx.index > 0) {
      targetItem = ctx.siblings[ctx.index - 1];
      position = 'before';
    }
  } else if (direction === 'down') {
    if (ctx.index < ctx.siblings.length - 1) {
      targetItem = ctx.siblings[ctx.index + 1];
      position = 'after';
    }
  } else if (direction === 'indent') {
    if (ctx.index > 0) {
      targetItem = ctx.siblings[ctx.index - 1];
      position = 'inside';
    }
  } else if (direction === 'outdent') {
    if (ctx.parent) {
      targetItem = ctx.parent;
      position = 'after';
    }
  }

  if (targetItem && position) {
    try {
      await itemStore.moveItem(props.currentDocument, {
        draggedItem: props.item,
        targetItem: targetItem,
        position: position
      });
      // The store modifies the tree reactively
      if (direction === 'indent') {
        navigation.expandItem(targetItem.identifier);
      }
    } catch (e) {
      logger.error('Error during internal move:', e);
    }
  }
}

</script>

<style scoped>
.btn-sm {
  font-size: 0.8rem;
}
</style>
