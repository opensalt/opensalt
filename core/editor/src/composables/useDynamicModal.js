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
      general: () => import('@/components/shared/modals/item-types/ChildModal.vue'),
      assessment: () => import('@/components/shared/modals/item-types/AssessmentModal.vue'),
      course: () => import('@/components/shared/modals/item-types/CourseModal.vue'),
      credential: () => import('@/components/shared/modals/item-types/CredentialModal.vue'),
      job: () => import('@/components/shared/modals/item-types/JobModal.vue'),
      organization: () => import('@/components/shared/modals/item-types/OrganizationModal.vue'),
      public_key: () => import('@/components/shared/modals/item-types/PublicKeyModal.vue'),
      identifier: () => import('@/components/shared/modals/item-types/IdentifierModal.vue')
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
