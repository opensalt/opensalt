import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';

export const useAssociationStore = defineStore('associations', () => {
  // State
  const selectedAssociationGroup = ref('all');
  const loading = ref(false);
  const error = ref(null);

  // Getters
  const associationGroups = computed(() => {
    // This will be populated from current document in the future
    const defaultGroups = [
      { id: 'all', title: 'All Groups', description: 'Show items from all association groups' },
      { id: 'default', title: 'Default Group', description: 'Default association group' }
    ];
    return defaultGroups;
  });

  // Actions
  function setSelectedAssociationGroup(groupId) {
    selectedAssociationGroup.value = groupId;
  }

  async function fetchAssociationGroups(documentId) {
    loading.value = true;
    error.value = null;

    try {
      const data = await api.get(`/cftree/association_groupings/${documentId}`);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch association groups';
      logger.error('Error fetching association groups:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function createAssociationGroup(documentId, groupData) {
    loading.value = true;
    error.value = null;

    try {
      const data = await api.post(`/cftree/association_grouping/new/${documentId}`, groupData);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to create association group';
      logger.error('Error creating association group:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateAssociationGroup(groupId, groupData) {
    loading.value = true;
    error.value = null;

    try {
      const data = await api.post(`/cftree/association_grouping/update/${groupId}`, groupData);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to update association group';
      logger.error('Error updating association group:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function deleteAssociationGroup(groupId) {
    loading.value = true;
    error.value = null;

    try {
      await api.post(`/cftree/association_grouping/delete/${groupId}`);
      return true;
    } catch (err) {
      error.value = err.message || 'Failed to delete association group';
      logger.error('Error deleting association group:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchAssociations(documentId, filters = {}) {
    loading.value = true;
    error.value = null;

    try {
      // Build query string manually to avoid URLSearchParams dependency
      const queryParams = Object.keys(filters)
        .map(key => `${key}=${encodeURIComponent(filters[key])}`)
        .join('&');
      
      const url = queryParams 
        ? `/cftree/associations/${documentId}?${queryParams}`
        : `/cftree/associations/${documentId}`;
      
      const data = await api.get(url);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch associations';
      logger.error('Error fetching associations:', err);
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function clearError() {
    error.value = null;
  }

  return {
    selectedAssociationGroup,
    associationGroups,
    loading,
    error,
    setSelectedAssociationGroup,
    fetchAssociationGroups,
    createAssociationGroup,
    updateAssociationGroup,
    deleteAssociationGroup,
    fetchAssociations,
    clearError
  };
});
