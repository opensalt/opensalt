import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useFilterStore = defineStore('filters', () => {
  // State
  const searchQuery = ref('');
  const selectedFilters = ref({
    itemType: '',
    subject: '',
    associationStatus: '',
    modifiedSince: ''
  });
  const selectedAssociationGroup = ref('all');

  // Getters
  const availableSubjects = computed(() => {
    // This will be populated from documents in the future
    return [];
  });

  // Actions
  function setSearchQuery(query) {
    searchQuery.value = query;
  }

  function setFilters(filters) {
    selectedFilters.value = { ...selectedFilters.value, ...filters };
  }

  function clearFilters() {
    selectedFilters.value = {
      itemType: '',
      subject: '',
      associationStatus: '',
      modifiedSince: ''
    };
  }

  function setSelectedAssociationGroup(groupId) {
    selectedAssociationGroup.value = groupId;
  }

  function filterItemsRecursively(items, searchQuery, filters, selectedAssociationGroup = 'all') {
    const filtered = [];

    for (const item of items) {
      let matches = true;

      // Apply search filter
      if (searchQuery) {
        const query = searchQuery.toLowerCase();
        const title = item.title?.toLowerCase() || '';
        const abbreviatedTitle = item.abbreviatedTitle?.toLowerCase() || '';
        const humanCodingScheme = item.humanCodingScheme?.toLowerCase() || '';

        if (!title.includes(query) && !abbreviatedTitle.includes(query) && !humanCodingScheme.includes(query)) {
          matches = false;
        }
      }

      // Apply item type filter
      if (matches && filters.itemType && item.itemType !== filters.itemType) {
        matches = false;
      }

      // Apply subject filter
      if (matches && filters.subject && item.subject !== filters.subject) {
        matches = false;
      }

      // Apply association status filter
      if (matches && filters.associationStatus) {
        const hasAssociations = item.associations && item.associations.length > 0;
        if (filters.associationStatus === 'has-associations' && !hasAssociations) {
          matches = false;
        }
        if (filters.associationStatus === 'no-associations' && hasAssociations) {
          matches = false;
        }
      }

      // Apply modified date filter
      if (matches && filters.modifiedSince && item.lastChanged) {
        const itemDate = new Date(item.lastChanged);
        const now = new Date();
        let cutoffDate;

        switch (filters.modifiedSince) {
          case 'today':
            cutoffDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            break;
          case 'week':
            cutoffDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            break;
          case 'month':
            cutoffDate = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
            break;
          case 'year':
            cutoffDate = new Date(now.getTime() - 365 * 24 * 60 * 60 * 1000);
            break;
        }

        if (itemDate < cutoffDate) {
          matches = false;
        }
      }

      // Apply association group filter
      if (matches && selectedAssociationGroup !== 'all') {
        // Check if item has associations in the selected group
        const hasAssociationInGroup = item.associations && item.associations.some(assoc =>
          assoc.groupId === selectedAssociationGroup
        );
        if (!hasAssociationInGroup) {
          matches = false;
        }
      }

      // If item matches, include it and recursively filter its children
      if (matches) {
        const filteredItem = { ...item };
        if (item.children && item.children.length > 0) {
          filteredItem.children = filterItemsRecursively(item.children, searchQuery, filters, selectedAssociationGroup);
        }
        filtered.push(filteredItem);
      } else if (item.children && item.children.length > 0) {
        // If item doesn't match but has children, check if any children match
        const filteredChildren = filterItemsRecursively(item.children, searchQuery, filters, selectedAssociationGroup);
        if (filteredChildren.length > 0) {
          const filteredItem = { ...item, children: filteredChildren };
          filtered.push(filteredItem);
        }
      }
    }

    return filtered;
  }

  return {
    searchQuery,
    selectedFilters,
    selectedAssociationGroup,
    availableSubjects,
    setSearchQuery,
    setFilters,
    clearFilters,
    setSelectedAssociationGroup,
    filterItemsRecursively
  };
});
