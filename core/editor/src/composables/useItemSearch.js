import { ref, computed } from 'vue';

/**
 * Composable for item search functionality
 * Handles recursive search and match counting
 */
export function useItemSearch() {
  /**
   * Count matching items in a tree based on search query
   */
  function countMatches(items, query) {
    if (!query) return 0;
    const searchQuery = query.toLowerCase();
    let count = 0;
    
    for (const item of items) {
      const searchableText = [
        item.humanCodingScheme,
        item.abbreviatedStatement,
        item.fullStatement,
        item.title,
        item.identifier
      ].filter(Boolean).join(' ').toLowerCase();

      if (searchableText.includes(searchQuery)) {
        count++;
      }
      if (item.children) {
        count += countMatches(item.children, searchQuery);
      }
    }
    return count;
  }

  /**
   * Get matching items based on search query
   */
  function getMatchingItems(items, query) {
    if (!query) return [];
    const searchQuery = query.toLowerCase();
    const results = [];
    
    function traverse(itemList) {
      for (const item of itemList) {
        const searchableText = [
          item.humanCodingScheme,
          item.abbreviatedStatement,
          item.fullStatement,
          item.title,
          item.identifier
        ].filter(Boolean).join(' ').toLowerCase();

        if (searchableText.includes(searchQuery)) {
          results.push(item);
        }
        if (item.children) {
          traverse(item.children);
        }
      }
    }
    
    traverse(items);
    return results;
  }

  /**
   * Search within items with multiple field support
   */
  function searchItems(items, query, fields = ['fullStatement', 'title', 'humanCodingScheme']) {
    if (!query) return items;
    const searchQuery = query.toLowerCase();
    
    function filterItemList(itemList) {
      return itemList.filter(item => {
        const matches = fields.some(field => {
          const value = item[field];
          return value && value.toLowerCase().includes(searchQuery);
        });
        return matches;
      }).map(item => ({
        ...item,
        children: item.children ? filterItemList(item.children) : []
      }));
    }
    
    return filterItemList(items);
  }

  return {
    countMatches,
    getMatchingItems,
    searchItems
  };
}
