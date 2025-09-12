import { ref, computed } from 'vue';
import { defineAsyncComponent } from 'vue';

function determineType(item) {
    return item.extensions?.['salt:type'] || item.itemType || 'general';
}

export function useDynamicEditModal(onUpdate, availableTypes = ['general', 'assessment', 'course', 'credential', 'job', 'organization', 'public_key']) {
  const editingItem = ref(null);
  const selectedEditType = ref('');
  const isEditModalVisible = ref(false);

  const showEditModal = (item) => {
    editingItem.value = item;
    selectedEditType.value = determineType(item);
    if (!availableTypes.includes(selectedEditType.value)) {
      selectedEditType.value = 'general';
    }
    isEditModalVisible.value = true;
  };

  const editModalComponent = computed(() => {
    const typeMap = {
      general: () => import('@/components/shared/modals/AddNewChildModal.vue'),
      assessment: () => import('@/components/shared/modals/AddNewAssessmentModal.vue'),
      course: () => import('@/components/shared/modals/AddNewCourseModal.vue'),
      credential: () => import('@/components/shared/modals/AddNewCredentialModal.vue'),
      job: () => import('@/components/shared/modals/AddNewJobModal.vue'),
      organization: () => import('@/components/shared/modals/AddNewOrganizationModal.vue'),
      public_key: () => import('@/components/shared/modals/AddNewPublicKeyModal.vue'),
      identifier: () => import('@/components/shared/modals/AddNewIdentifierModal.vue')
    };
    const loader = typeMap[selectedEditType.value] || typeMap.general;
    return defineAsyncComponent(loader);
  });

  const handleUpdated = (updatedItem) => {
    onUpdate(updatedItem);
    isEditModalVisible.value = false;
    editingItem.value = null;
  };

  const handleEditHidden = () => {
    isEditModalVisible.value = false;
    editingItem.value = null;
  };

  return {
    showEditModal,
    selectedEditType,
    isEditModalVisible,
    editingItem,
    editModalComponent,
    handleUpdated,
    handleEditHidden
  };
}
