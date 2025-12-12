import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useItemStore = defineStore('items', () => {
  // Actions
  function updateItem(currentDocument, updatedItem) {
    if (!currentDocument || !updatedItem || !updatedItem.identifier) {
      console.warn('Cannot update item: missing document or item identifier');
      return false;
    }

    const updated = updateItemRecursively(currentDocument.items, updatedItem);
    if (updated) {
      console.log('Item updated successfully:', updatedItem.identifier);
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
    return true;
  }

  return {
    updateItem,
    addItem,
    findItemByIdentifier,
    getMaxSequence
  };
});
