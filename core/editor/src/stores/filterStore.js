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
  const selectedAssociationGroup = ref('default');

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
    const hasSearch = !!searchQuery;
    const hasTypeFilter = !!filters.itemType;
    const hasSubjectFilter = !!filters.subject;
    const hasStatusFilter = !!filters.associationStatus;
    const hasDateFilter = !!filters.modifiedSince;
    const hasGroupFilter = selectedAssociationGroup !== 'all';

    const isFiltering = hasSearch || hasTypeFilter || hasSubjectFilter || hasStatusFilter || hasDateFilter || hasGroupFilter;

    if (!isFiltering) {
      return items;
    }

    const filtered = [];
    let anyChange = false;

    for (const item of items) {
      let matches = true;

      // Apply search filter
      if (hasSearch) {
        const query = searchQuery.toLowerCase();
        const title = (item.title || '').toLowerCase();
        const abbreviatedTitle = (item.abbreviatedTitle || '').toLowerCase();
        const humanCodingScheme = (item.humanCodingScheme || '').toLowerCase();

        if (!title.includes(query) && !abbreviatedTitle.includes(query) && !humanCodingScheme.includes(query)) {
          matches = false;
        }
      }

      // Apply item type filter
      if (matches && hasTypeFilter && item.itemType !== filters.itemType) {
        matches = false;
      }

      // Apply subject filter
      if (matches && hasSubjectFilter && item.subject !== filters.subject) {
        matches = false;
      }

      // Apply association status filter
      if (matches && hasStatusFilter) {
        const hasAssociationsCount = item.associations?.length || 0;
        if (filters.associationStatus === 'has-associations' && hasAssociationsCount === 0) {
          matches = false;
        }
        if (filters.associationStatus === 'no-associations' && hasAssociationsCount > 0) {
          matches = false;
        }
      }

      // Apply modified date filter
      if (matches && hasDateFilter && item.lastChanged) {
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

        if (cutoffDate && itemDate < cutoffDate) {
          matches = false;
        }
      }

      // Apply association group filter - Use pre-calculated groupIds Set
      if (matches && hasGroupFilter) {
        if (!item.groupIds?.has(selectedAssociationGroup)) {
          matches = false;
        }
      }

      // Process children
      const originalChildren = item.children || [];
      const filteredChildren = originalChildren.length > 0
        ? filterItemsRecursively(originalChildren, searchQuery, filters, selectedAssociationGroup)
        : originalChildren;

      const childrenChanged = filteredChildren !== originalChildren;

      // If item matches OR has matching children, include it
      if (matches || (filteredChildren && filteredChildren.length > 0)) {
        if (!matches || childrenChanged) {
          // Something changed (either filtered out children or item itself doesn't match but child does)
          anyChange = true;
          filtered.push({
            ...item,
            children: filteredChildren
          });
        } else {
          // Item matches and children are untouched
          filtered.push(item);
        }
      } else {
        // Item and its children were filtered out
        anyChange = true;
      }
    }

    // Return the original array if nothing was filtered out and nothing changed referentially
    if (filtered.length === items.length && !anyChange) {
      return items;
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
