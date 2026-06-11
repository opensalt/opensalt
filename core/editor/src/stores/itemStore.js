import { defineStore } from 'pinia';
import { ref } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';

export const useItemStore = defineStore('items', () => {
  // Internal item lookup map for fast access
  const itemLookupMap = ref(new Map());

  // O(1) lookup using the internal map first, then global registry
  function getItemByIdentifierFast(identifier) {
    if (itemLookupMap.value.has(identifier)) {
      return itemLookupMap.value.get(identifier);
    }
    return null;
  }

  // Build item lookup map from items array
  function buildItemLookupMap(items = []) {
    itemLookupMap.value.clear();

    function traverse(itemsArray) {
      if (!Array.isArray(itemsArray)) return;

      for (const item of itemsArray) {
        itemLookupMap.value.set(item.identifier, item);
        if (item.children) {
          traverse(item.children);
        }
      }
    }

    traverse(items);
  }

  // Clear the item lookup map
  function invalidateCache() {
    itemLookupMap.value.clear();
  }

  // Actions
  function updateItem(currentDocument, updatedItem) {
    if (!currentDocument || !updatedItem || !updatedItem.identifier) {
      logger.warn('Cannot update item: missing document or item identifier');
      return false;
    }

    const updated = updateItemRecursively(currentDocument.items, updatedItem);
    if (updated) {
      logger.debug('Item updated successfully:', updatedItem.identifier);
      // Invalidate cache since item was modified
      invalidateCache();
    } else {
      logger.warn('Item not found for update:', updatedItem.identifier);
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
    const fastMatch = getItemByIdentifierFast(identifier);
    if (fastMatch) return fastMatch;

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
      logger.error('No current document to add item to');
      return false;
    }

    let targetArray = currentDocument.items;
    let sequenceNum = getMaxSequence(currentDocument.items) + 1;

    if (parentIdentifier) {
      const parent = findItemByIdentifier(currentDocument.items, parentIdentifier);
      if (!parent) {
        logger.error('Parent item not found:', parentIdentifier);
        return false;
      }
      targetArray = parent.children;
      sequenceNum = getMaxSequence(parent.children) + 1;
    }

    newItem.sequenceNumber = sequenceNum;
    targetArray.push(newItem);

    // Sort the target array by sequenceNumber
    targetArray.sort((a, b) => (a.sequenceNumber || 0) - (b.sequenceNumber || 0));

    logger.debug('Item added successfully:', newItem.identifier);
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

    const newParentIdentifier = position === 'inside'
      ? targetItem.identifier
      : (targetItem.parentIdentifier || currentDocument.id);

    try {
      const response = await api.post(`/framework/editor/item/${draggedItem.identifier}/move`, {
        newParentIdentifier: position === 'inside' ? targetItem.identifier : newParentIdentifier,
        targetItemIdentifier: position !== 'inside' ? targetItem.identifier : null,
        position,
        childOfAssociationIdentifier: draggedItem.childOfAssociationIdentifier || draggedItem.childOfAssocId || null,
      });

      const itemToMove = removeItemRecursively(currentDocument.items, draggedItem.identifier);
      if (!itemToMove) {
        return true;
      }

      let targetParentArray = currentDocument.items;
      if (position === 'inside') {
        if (!targetItem.children) targetItem.children = [];
        targetItem.children.push(itemToMove);
      } else {
        const findParentArray = (items, targetId, parent) => {
          for (const item of items) {
            if (item.identifier === targetId) return parent || items;
            if (item.children) {
              const found = findParentArray(item.children, targetId, item.children);
              if (found) return found;
            }
          }
          return null;
        };
        targetParentArray = findParentArray(currentDocument.items, targetItem.identifier) || currentDocument.items;
        const targetIndex = targetParentArray.findIndex(item => item.identifier === targetItem.identifier);
        if (position === 'before') {
          targetParentArray.splice(targetIndex, 0, itemToMove);
        } else if (position === 'after') {
          targetParentArray.splice(targetIndex + 1, 0, itemToMove);
        }
      }

      if (response?.childOfAssociationIdentifier) {
        itemToMove.childOfAssociationIdentifier = response.childOfAssociationIdentifier;
        itemToMove.childOfAssocId = response.childOfAssociationIdentifier;
      }

      const siblingArray = position === 'inside' ? targetItem.children : targetParentArray;
      if (response?.siblingSequenceNumbers && Array.isArray(siblingArray)) {
        for (const sibling of siblingArray) {
          const newSeq = response.siblingSequenceNumbers[sibling.identifier];
          if (newSeq !== undefined) {
            sibling.sequenceNumber = newSeq;
          }
        }
      } else if (response?.sequenceNumber !== undefined) {
        itemToMove.sequenceNumber = response.sequenceNumber;
      }

      if (position === 'inside') {
        itemToMove.parentIdentifier = targetItem.identifier;
      } else {
        itemToMove.parentIdentifier = targetItem.parentIdentifier || currentDocument.id;
      }

      invalidateCache();
      return true;
    } catch (error) {
      logger.error('Failed to persist item move:', error);
      throw error;
    }
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
