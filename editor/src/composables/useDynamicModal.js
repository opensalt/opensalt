import { ref, computed } from 'vue';
import { defineAsyncComponent } from 'vue';

export function useDynamicModal(parent, onCreate, types) {
  const parentItem = ref(null);
  const onCreated = ref(() => {});
  parentItem.value = parent;
  onCreated.value = onCreate;

  const selectedType = ref('general');
  const isModalVisible = ref(false);

  const showModal = (type) => {
    selectedType.value = type;
    isModalVisible.value = true;
  };

  const modalComponent = computed(() => {
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
    const loader = typeMap[selectedType.value] || typeMap.general;
    return defineAsyncComponent(loader);
  });

  const handleCreated = (newItem) => {
    onCreated.value(newItem);
    isModalVisible.value = false;
  };

  const handleHidden = () => {
    isModalVisible.value = false;
  };

  return {
    showModal,
    selectedType,
    isModalVisible,
    handleCreated,
    modalComponent,
    handleHidden,
    parentItem
  };
}
