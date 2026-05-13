/**
 * Recursively find an item by identifier in a tree structure
 * @param {Array} items - Array of tree items
 * @param {string} identifier - Item identifier to find
 * @returns {Object|null} - Found item or null
 */
export function findItem(items, identifier) {
  if (!Array.isArray(items)) return null;
  for (const item of items) {
    if (item.identifier === identifier) return item;
    if (item.children) {
      const found = findItem(item.children, identifier);
      if (found) return found;
    }
  }
  return null;
}

/**
 * Count items matching a predicate in a tree structure
 * @param {Array} items - Array of tree items
 * @param {Function} predicate - Function that returns true for items to count
 * @returns {number} - Number of matching items
 */
export function countMatches(items, predicate) {
  if (!Array.isArray(items)) return 0;
  return items.reduce((count, item) => {
    return count + (predicate(item) ? 1 : 0) + countMatches(item.children || [], predicate);
  }, 0);
}

/**
 * Find the path from root to a specific item
 * @param {Array} items - Array of tree items
 * @param {string} identifier - Target item identifier
 * @returns {Array|null} - Array of identifiers forming the path, or null
 */
export function findItemPath(items, identifier, path = []) {
  if (!Array.isArray(items)) return null;
  for (const item of items) {
    if (item.identifier === identifier) return [...path, item.identifier];
    if (item.children) {
      const found = findItemPath(item.children, identifier, [...path, item.identifier]);
      if (found) return found;
    }
  }
  return null;
}

function naturalCompare(a, b) {
  const ax = [];
  const bx = [];
  const rx = /(\d+)|(\D+)/g;
  let match;
  while ((match = rx.exec(a)) !== null) ax.push([match[1] !== undefined, match[0]]);
  while ((match = rx.exec(b)) !== null) bx.push([match[1] !== undefined, match[0]]);
  const len = Math.max(ax.length, bx.length);
  for (let i = 0; i < len; i++) {
    const segA = ax[i] || [false, ''];
    const segB = bx[i] || [false, ''];
    if (segA[0] && segB[0]) {
      const diff = parseInt(segA[1], 10) - parseInt(segB[1], 10);
      if (diff !== 0) return diff;
    } else {
      const cmp = segA[1].localeCompare(segB[1]);
      if (cmp !== 0) return cmp;
    }
  }
  return 0;
}

function compareSegmented(a, b) {
  const segA = a.split(/[\s.,\-/]+/).filter(Boolean);
  const segB = b.split(/[\s.,\-/]+/).filter(Boolean);
  const len = Math.max(segA.length, segB.length);
  for (let i = 0; i < len; i++) {
    const sA = segA[i] || '';
    const sB = segB[i] || '';
    const cmp = naturalCompare(sA, sB);
    if (cmp !== 0) return cmp;
  }
  return 0;
}

function compareFields(a, b, key) {
  const valA = a[key] ?? null;
  const valB = b[key] ?? null;
  if (valA !== null && valB !== null) {
    if (key === 'sequenceNumber') {
      return (valA - valB);
    }
    return compareSegmented(String(valA), String(valB));
  }
  if (valA !== null) return -1;
  if (valB !== null) return 1;
  return 0;
}

function treeItemComparator(a, b) {
  let cmp = compareFields(a, b, 'sequenceNumber');
  if (cmp !== 0) return cmp;
  cmp = compareFields(a, b, 'humanCodingScheme');
  if (cmp !== 0) return cmp;
  cmp = compareFields(a, b, 'listEnumeration');
  return cmp;
}

export function sortTreeNodes(items) {
  if (!Array.isArray(items)) return items;
  items.sort(treeItemComparator);
  for (const item of items) {
    if (item.children && item.children.length > 0) {
      sortTreeNodes(item.children);
    }
  }
  return items;
}
