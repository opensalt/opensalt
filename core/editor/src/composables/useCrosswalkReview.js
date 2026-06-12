import { ref, computed, shallowRef } from 'vue';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore.ts';
import { useDocumentStore } from '../stores/documentStore.ts';

export function useCrosswalkReview() {
  const currentDocumentStore = useCurrentDocumentStore();
  const documentStore = useDocumentStore();

  const filters = ref({
    search: '',
    confidenceMin: 0,
    confidenceMax: 1,
    matchStatus: 'all',
  });

  const selectedIds = ref(new Set());

  const loadingOriginItems = ref(false);
  const loadingDestinationItems = ref(false);
  const originItemsError = ref(null);
  const destinationItemsError = ref(null);

  const originFrameworkId = shallowRef(null);
  const destinationFrameworkId = shallowRef(null);
  const originItems = shallowRef([]);
  const destinationItems = shallowRef([]);

  const allAssociations = computed(() => {
    const assocs = currentDocumentStore.currentDocumentAssociations || [];
    return assocs.filter((assoc) => {
      return assoc.extensions && assoc.extensions['crosswalk:confidence'] !== undefined;
    });
  });

  const matchedPairs = computed(() => {
    const pairs = [];
    for (const assoc of allAssociations.value) {
      const originId = assoc.originNodeURI?.identifier;
      const destId = assoc.destinationNodeURI?.identifier;
      if (!originId || !destId) continue;

      const originItem = originItems.value.find(i => i.identifier === originId);
      const destItem = destinationItems.value.find(i => i.identifier === destId);
      if (originItem && destItem) {
        pairs.push({
          id: assoc.identifier || assoc.id,
          originItem,
          destinationItem: destItem,
          association: assoc,
          confidence: assoc.extensions?.['crosswalk:confidence'] || 0,
          status: assoc.extensions?.['crosswalk:status'] || 'pending',
          type: assoc.associationType,
          subtype: assoc.extensions?.['crosswalk:subtype'] || 'related',
        });
      }
    }
    return pairs;
  });

  const matchedOriginIds = computed(() => {
    const ids = new Set();
    for (const pair of matchedPairs.value) {
      ids.add(pair.originItem.identifier);
    }
    return ids;
  });

  const matchedDestinationIds = computed(() => {
    const ids = new Set();
    for (const pair of matchedPairs.value) {
      ids.add(pair.destinationItem.identifier);
    }
    return ids;
  });

  const unmatchedOriginItems = computed(() => {
    return originItems.value.filter(item => !matchedOriginIds.value.has(item.identifier));
  });

  const unmatchedDestinationItems = computed(() => {
    return destinationItems.value.filter(item => !matchedDestinationIds.value.has(item.identifier));
  });

  function matchesSearch(item, search) {
    if (!search) return true;
    const s = search.toLowerCase();
    const title = (item.fullStatement || item.title || '').toLowerCase();
    const scheme = (item.humanCodingScheme || '').toLowerCase();
    return title.includes(s) || scheme.includes(s);
  }

  const filteredPairs = computed(() => {
    let pairs = matchedPairs.value;

    if (filters.value.matchStatus === 'unmatched-origin' || filters.value.matchStatus === 'unmatched-destination') {
      pairs = [];
    }

    if (filters.value.confidenceMin > 0 || filters.value.confidenceMax < 1) {
      pairs = pairs.filter(p =>
        p.confidence >= filters.value.confidenceMin &&
        p.confidence <= filters.value.confidenceMax
      );
    }

    const search = filters.value.search;
    if (search) {
      pairs = pairs.filter(p =>
        matchesSearch(p.originItem, search) || matchesSearch(p.destinationItem, search)
      );
    }

    return pairs;
  });

  const filteredUnmatchedOrigin = computed(() => {
    if (filters.value.matchStatus === 'matched' || filters.value.matchStatus === 'unmatched-destination') {
      return [];
    }
    let items = unmatchedOriginItems.value;
    const search = filters.value.search;
    if (search) {
      items = items.filter(item => matchesSearch(item, search));
    }
    return items;
  });

  const filteredUnmatchedDestination = computed(() => {
    if (filters.value.matchStatus === 'matched' || filters.value.matchStatus === 'unmatched-origin') {
      return [];
    }
    let items = unmatchedDestinationItems.value;
    const search = filters.value.search;
    if (search) {
      items = items.filter(item => matchesSearch(item, search));
    }
    return items;
  });

  const stats = computed(() => {
    const all = allAssociations.value;
    return {
      originTotal: originItems.value.length,
      destinationTotal: destinationItems.value.length,
      matched: matchedPairs.value.length,
      unmatchedOrigin: unmatchedOriginItems.value.length,
      unmatchedDestination: unmatchedDestinationItems.value.length,
      pending: all.filter(a => (a.extensions?.['crosswalk:status'] || 'pending') === 'pending').length,
      approved: all.filter(a => a.extensions?.['crosswalk:status'] === 'approved').length,
      rejected: all.filter(a => a.extensions?.['crosswalk:status'] === 'rejected').length,
      modified: all.filter(a => a.extensions?.['crosswalk:status'] === 'modified').length,
    };
  });

  function extractFrameworkIds() {
    const assocs = allAssociations.value;
    for (const assoc of assocs) {
      const originDoc = assoc.originNodeURI?.documentIdentifier;
      const destDoc = assoc.destinationNodeURI?.documentIdentifier;
      if (originDoc && !originFrameworkId.value) {
        originFrameworkId.value = originDoc;
      }
      if (destDoc && !destinationFrameworkId.value) {
        destinationFrameworkId.value = destDoc;
      }
      if (originFrameworkId.value && destinationFrameworkId.value) break;
    }
  }

  async function loadOriginAndDestinationItems() {
    extractFrameworkIds();

    if (originFrameworkId.value) {
      loadingOriginItems.value = true;
      originItemsError.value = null;
      try {
        const treeResponse = await documentStore.fetchTree(originFrameworkId.value);
        originItems.value = flattenTree(treeResponse.tree || []);
      } catch (err) {
        originItemsError.value = err.message || 'Failed to load origin items';
        console.error('Failed to load origin items:', err);
      } finally {
        loadingOriginItems.value = false;
      }
    }

    if (destinationFrameworkId.value) {
      loadingDestinationItems.value = true;
      destinationItemsError.value = null;
      try {
        const treeResponse = await documentStore.fetchTree(destinationFrameworkId.value);
        destinationItems.value = flattenTree(treeResponse.tree || []);
      } catch (err) {
        destinationItemsError.value = err.message || 'Failed to load destination items';
        console.error('Failed to load destination items:', err);
      } finally {
        loadingDestinationItems.value = false;
      }
    }
  }

  function flattenTree(nodes) {
    const items = [];
    for (const node of nodes) {
      items.push(node);
      if (node.children && node.children.length > 0) {
        items.push(...flattenTree(node.children));
      }
    }
    return items;
  }

  async function updateAssociationStatus(assocId, status) {
    const assoc = allAssociations.value.find(a => (a.id || a.identifier) === assocId);
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

  // TODO: Wire to API endpoint for persistence
  async function bulkApprove() {
    for (const pair of filteredPairs.value) {
      pair.association.extensions['crosswalk:status'] = 'approved';
    }
  }

  // TODO: Wire to API endpoint for persistence
  async function bulkReject() {
    for (const pair of filteredPairs.value) {
      pair.association.extensions['crosswalk:status'] = 'rejected';
    }
  }

  function clearFilters() {
    filters.value = {
      search: '',
      confidenceMin: 0,
      confidenceMax: 1,
      matchStatus: 'all',
    };
  }

  return {
    allAssociations,
    matchedPairs,
    unmatchedOriginItems,
    unmatchedDestinationItems,
    filteredPairs,
    filteredUnmatchedOrigin,
    filteredUnmatchedDestination,
    filters,
    stats,
    selectedIds,
    loadingOriginItems,
    loadingDestinationItems,
    originItemsError,
    destinationItemsError,
    originFrameworkId,
    destinationFrameworkId,
    approveAssociation,
    rejectAssociation,
    bulkApprove,
    bulkReject,
    clearFilters,
    loadOriginAndDestinationItems,
  };
}
