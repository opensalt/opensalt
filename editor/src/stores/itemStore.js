import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useItemStore = defineStore('items', () => {
  // O(1) item lookup cache
  const itemLookupMap = ref(new Map());

  // Build lookup map from items array (O(n) once, then O(1) lookups)
  function buildItemLookupMap(items) {
    itemLookupMap.value.clear();
    if (!Array.isArray(items)) return;
    
    function traverse(itemList) {
      for (const item of itemList) {
        itemLookupMap.value.set(item.identifier, item);
        if (item.children) {
          traverse(item.children);
        }
      }
    }
    traverse(items);
  }

  // O(1) lookup using the map
  function getItemByIdentifierFast(identifier) {
    return itemLookupMap.value.get(identifier) || null;
  }

  // Invalidate cache when items change
  function invalidateCache() {
    itemLookupMap.value.clear();
  }

  // Actions
  function updateItem(currentDocument, updatedItem) {
    if (!currentDocument || !updatedItem || !updatedItem.identifier) {
      console.warn('Cannot update item: missing document or item identifier');
      return false;
    }

    const updated = updateItemRecursively(currentDocument.items, updatedItem);
    if (updated) {
      console.log('Item updated successfully:', updatedItem.identifier);
      // Invalidate cache since item was modified
      invalidateCache();
    } else {
      console.warn('Item not found for update:', updatedItem.identifier);
    }
    return updated;
  }

  function updateItemRecursively(items, updatedItem) {
    if (!Array.isArray(items)) return false;

    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      if (item.identifier === updatedItem.identifier) {
        // Update the item properties, preserving structure
        Object.assign(item, updatedItem);
        // Ensure children and associations are preserved if not overwritten
        if (!updatedItem.children) item.children = item.children || [];
        if (!updatedItem.associations) item.associations = item.associations || [];
        return true;
      }
      if (item.children && updateItemRecursively(item.children, updatedItem)) {
        return true;
      }
    }
    return false;
  }

  function findItemByIdentifier(items, identifier) {
    if (!Array.isArray(items)) return null;
    for (const item of items) {
      if (item.identifier === identifier) return item;
      if (item.children) {
        const found = findItemByIdentifier(item.children, identifier);
        if (found) return found;
      }
    }
    return null;
  }

  function getMaxSequence(items) {
    if (!Array.isArray(items)) return 0;
    return Math.max(...items.map(item => item.sequenceNumber || 0), 0);
  }

  function addItem(currentDocument, newItem, parentIdentifier) {
    if (!currentDocument) {
      console.error('No current document to add item to');
      return false;
    }

    let targetArray = currentDocument.items;
    let sequenceNum = getMaxSequence(currentDocument.items) + 1;

    if (parentIdentifier) {
      const parent = findItemByIdentifier(currentDocument.items, parentIdentifier);
      if (!parent) {
        console.error('Parent item not found:', parentIdentifier);
        return false;
      }
      targetArray = parent.children;
      sequenceNum = getMaxSequence(parent.children) + 1;
    }

    newItem.sequenceNumber = sequenceNum;
    targetArray.push(newItem);
    
    // Sort the target array by sequenceNumber
    targetArray.sort((a, b) => (a.sequenceNumber || 0) - (b.sequenceNumber || 0));

    console.log('Item added successfully:', newItem.identifier);
    // Invalidate cache since items were modified
    invalidateCache();
    return true;
  }

  function removeItemRecursively(items, identifier) {
    if (!Array.isArray(items)) return null;
    for (let i = 0; i < items.length; i++) {
      if (items[i].identifier === identifier) {
        return items.splice(i, 1)[0];
      }
      if (items[i].children) {
        const removed = removeItemRecursively(items[i].children, identifier);
        if (removed) return removed;
      }
    }
    return null;
  }

  async function moveItem(currentDocument, { draggedItem, targetItem, position }) {
    if (!currentDocument || !draggedItem || !targetItem) return false;

    // Remove from current position
    const itemToMove = removeItemRecursively(currentDocument.items, draggedItem.identifier);
    if (!itemToMove) {
      console.warn('Could not find dragged item to move:', draggedItem.identifier);
      return false;
    }

    // Find parent of target
    let targetParentArray = currentDocument.items;
    let targetParent = null;

    if (targetItem.identifier !== currentDocument.id) {
      // Find the parent of targetItem
      const findParent = (items, targetId) => {
        for (const item of items) {
          if (item.children && item.children.some(c => c.identifier === targetId)) {
            return item;
          }
          if (item.children) {
            const found = findParent(item.children, targetId);
            if (found) return found;
          }
        }
        return null;
      };
      targetParent = findParent(currentDocument.items, targetItem.identifier);
      if (targetParent) {
        targetParentArray = targetParent.children;
      }
    }

    const targetIndex = targetParentArray.findIndex(item => item.identifier === targetItem.identifier);

    if (position === 'before') {
      targetParentArray.splice(targetIndex, 0, itemToMove);
    } else if (position === 'after') {
      targetParentArray.splice(targetIndex + 1, 0, itemToMove);
    } else if (position === 'inside') {
      if (!targetItem.children) targetItem.children = [];
      targetItem.children.push(itemToMove);
    }

    // Update sequence numbers
    const updateSequence = (items) => {
      items.forEach((item, index) => {
        item.sequenceNumber = (index + 1) * 10; // Use spacing to allow future reorders
      });
    };

    if (position === 'inside') {
      updateSequence(targetItem.children);
    } else {
      updateSequence(targetParentArray);
    }

    console.log('Item moved successfully from', draggedItem.identifier, 'to', targetItem.identifier, position);

    // In a real app, you would also trigger an API call here to persist the change.
    // For now, it's just local state.
    
    // Invalidate cache since items were moved
    invalidateCache();

    return true;
  }

  return {
    updateItem,
    addItem,
    findItemByIdentifier,
    getItemByIdentifierFast,
    buildItemLookupMap,
    invalidateCache,
    getMaxSequence,
    moveItem
  };
});
