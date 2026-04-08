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
