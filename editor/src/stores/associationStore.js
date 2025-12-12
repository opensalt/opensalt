import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAssociationStore = defineStore('associations', () => {
  // State
  const selectedAssociationGroup = ref('all');

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

  return {
    selectedAssociationGroup,
    associationGroups,
    setSelectedAssociationGroup
  };
});
