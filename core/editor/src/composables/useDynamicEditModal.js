import { ref, computed } from 'vue';
import { defineAsyncComponent } from 'vue';

function determineType(item) {
    return item.extensions?.['salt:type'] || item.itemType || 'general';
}

export function useDynamicEditModal(onUpdate, onAdd, availableTypes = ['general', 'assessment', 'course', 'credential', 'job', 'organization', 'public_key', 'identifier']) {
  const editingItem = ref(null);
  const modalParentItem = ref(null);
  const selectedEditType = ref('');
  const isEditModalVisible = ref(false);

  const showEditModal = (item) => {
    editingItem.value = item;
    modalParentItem.value = null;
    selectedEditType.value = determineType(item);
    if (!availableTypes.includes(selectedEditType.value)) {
      selectedEditType.value = 'general';
    }
    isEditModalVisible.value = true;
  };

  const showAddModal = (parent, type = 'general') => {
    editingItem.value = null;
    modalParentItem.value = parent;
    selectedEditType.value = type;
    if (!availableTypes.includes(selectedEditType.value)) {
      selectedEditType.value = 'general';
    }
    isEditModalVisible.value = true;
  };

  const editModalComponent = computed(() => {
    const typeMap = {
      general: () => import('@/components/shared/modals/item-types/ChildModal.vue'),
      assessment: () => import('@/components/shared/modals/item-types/AssessmentModal.vue'),
      course: () => import('@/components/shared/modals/item-types/CourseModal.vue'),
      credential: () => import('@/components/shared/modals/item-types/CredentialModal.vue'),
      job: () => import('@/components/shared/modals/item-types/JobModal.vue'),
      organization: () => import('@/components/shared/modals/item-types/OrganizationModal.vue'),
      public_key: () => import('@/components/shared/modals/item-types/PublicKeyModal.vue'),
      identifier: () => import('@/components/shared/modals/item-types/IdentifierModal.vue')
    };
    const loader = typeMap[selectedEditType.value] || typeMap.general;
    return defineAsyncComponent(loader);
  });

  const handleUpdated = (updatedItem) => {
    onUpdate(updatedItem);
    isEditModalVisible.value = false;
    editingItem.value = null;
    modalParentItem.value = null;
  };

  const handleCreated = (newItem) => {
    if (onAdd) {
      onAdd(newItem, modalParentItem.value);
    }
    isEditModalVisible.value = false;
    editingItem.value = null;
    modalParentItem.value = null;
  };

  const handleEditHidden = () => {
    isEditModalVisible.value = false;
    editingItem.value = null;
    modalParentItem.value = null;
  };

  return {
    showEditModal,
    showAddModal,
    selectedEditType,
    isEditModalVisible,
    editingItem,
    modalParentItem,
    editModalComponent,
    handleUpdated,
    handleCreated,
    handleEditHidden
  };
}
