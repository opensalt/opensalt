import { ref, computed } from 'vue';
import { defineAsyncComponent } from 'vue';

export function useDynamicModal(parent, onCreate, _types) {
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
      general: () => import('@/components/tree/modals/item-types/ChildModal.vue'),
      assessment: () => import('@/components/tree/modals/item-types/AssessmentModal.vue'),
      course: () => import('@/components/tree/modals/item-types/CourseModal.vue'),
      credential: () => import('@/components/tree/modals/item-types/CredentialModal.vue'),
      job: () => import('@/components/tree/modals/item-types/JobModal.vue'),
      organization: () => import('@/components/tree/modals/item-types/OrganizationModal.vue'),
      public_key: () => import('@/components/tree/modals/item-types/PublicKeyModal.vue'),
      identifier: () => import('@/components/tree/modals/item-types/IdentifierModal.vue')
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
