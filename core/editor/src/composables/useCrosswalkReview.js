import { ref, computed } from 'vue';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore.ts';

export function useCrosswalkReview() {
  const currentDocumentStore = useCurrentDocumentStore();

  const filters = ref({
    search: '',
    confidenceMin: 0,
    confidenceMax: 1,
    status: 'all',
    associationType: 'all',
  });

  const selectedIds = ref(new Set());

  const associations = computed(() => {
    return (currentDocumentStore.currentDocumentAssociations || []).filter((assoc) => {
      return assoc.extensions && assoc.extensions['crosswalk:confidence'] !== undefined;
    });
  });

  const filteredAssociations = computed(() => {
    return associations.value.filter((assoc) => {
      const ext = assoc.extensions;
      const confidence = ext['crosswalk:confidence'] || 0;

      if (confidence < filters.value.confidenceMin || confidence > filters.value.confidenceMax) {
        return false;
      }

      if (filters.value.status !== 'all' && ext['crosswalk:status'] !== filters.value.status) {
        return false;
      }

      if (filters.value.associationType !== 'all' && assoc.associationType !== filters.value.associationType) {
        return false;
      }

      if (filters.value.search) {
        const search = filters.value.search.toLowerCase();
        const originTitle = (assoc.originNodeURI?.title || '').toLowerCase();
        const destTitle = (assoc.destinationNodeURI?.title || '').toLowerCase();
        if (!originTitle.includes(search) && !destTitle.includes(search)) {
          return false;
        }
      }

      return true;
    });
  });

  const stats = computed(() => {
    const all = associations.value;
    return {
      total: all.length,
      pending: all.filter(a => a.extensions['crosswalk:status'] === 'pending').length,
      approved: all.filter(a => a.extensions['crosswalk:status'] === 'approved').length,
      rejected: all.filter(a => a.extensions['crosswalk:status'] === 'rejected').length,
      modified: all.filter(a => a.extensions['crosswalk:status'] === 'modified').length,
    };
  });

  async function updateAssociationStatus(assocId, status) {
    const assoc = associations.value.find(a => (a.id || a.identifier) === assocId);
    if (assoc) {
      assoc.extensions['crosswalk:status'] = status;
    }
  }

  async function approveAssociation(assocId) {
    await updateAssociationStatus(assocId, 'approved');
  }

  async function rejectAssociation(assocId) {
    await updateAssociationStatus(assocId, 'rejected');
  }

  async function bulkApprove() {
    filteredAssociations.value.forEach((assoc) => {
      assoc.extensions['crosswalk:status'] = 'approved';
    });
  }

  async function bulkReject() {
    filteredAssociations.value.forEach((assoc) => {
      assoc.extensions['crosswalk:status'] = 'rejected';
    });
  }

  function clearFilters() {
    filters.value = {
      search: '',
      confidenceMin: 0,
      confidenceMax: 1,
      status: 'all',
      associationType: 'all',
    };
  }

  return {
    associations,
    filteredAssociations,
    filters,
    stats,
    selectedIds,
    approveAssociation,
    rejectAssociation,
    bulkApprove,
    bulkReject,
    clearFilters,
  };
}
