import { ref, computed, shallowRef, watch } from 'vue';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore.ts';
import { useDocumentStore } from '../stores/documentStore.ts';
import { api } from '../services/api.js';

export function useCrosswalkReview() {
  const currentDocumentStore = useCurrentDocumentStore();
  const documentStore = useDocumentStore();

  const filters = ref({
    search: '',
    confidenceMin: 0,
    confidenceMax: 1,
    matchStatus: 'all',
    hideApproved: false,
  });

  const selectedIds = ref(new Set());

  const loadingOriginItems = ref(false);
  const loadingDestinationItems = ref(false);
  const loadingAssociations = ref(false);
  const originItemsError = ref(null);
  const destinationItemsError = ref(null);

  const originFrameworkId = shallowRef(null);
  const destinationFrameworkId = shallowRef(null);
  const originItems = shallowRef([]);
  const destinationItems = shallowRef([]);

  // Use a deep ref so mutating an association's extension (e.g. crosswalk:status)
  // reactively updates the table and stats.
  const associations = ref([]);

  const allAssociations = computed(() => associations.value);

  async function loadCrosswalkAssociations() {
    const crosswalkDocId = currentDocumentStore.currentDocument?.identifier;
    if (!crosswalkDocId) {
      associations.value = [];
      return;
    }

    loadingAssociations.value = true;
    try {
      // Ensure we see associations created by a (possibly just-finished) job.
      currentDocumentStore.invalidateItemDetailsCache(crosswalkDocId);
      const data = await currentDocumentStore.fetchFrameworkAssociations(crosswalkDocId);
      const list = Array.isArray(data) ? data : [];
      associations.value = list.filter((assoc) => {
        return assoc.extensions && assoc.extensions['crosswalk:confidence'] !== undefined;
      });
    } catch (err) {
      console.error('Failed to load crosswalk associations:', err);
      associations.value = [];
    } finally {
      loadingAssociations.value = false;
    }
  }

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

    if (filters.value.hideApproved) {
      pairs = pairs.filter(p => p.status !== 'approved');
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

  // Unified, ordered row list (matched pairs first, then unmatched) for paginated rendering.
  const allRows = computed(() => {
    const rows = [];
    for (const pair of filteredPairs.value) {
      rows.push({ type: 'matched', id: pair.id, pair });
    }
    for (const item of filteredUnmatchedOrigin.value) {
      rows.push({ type: 'unmatched-origin', id: 'unmatched-origin-' + item.identifier, item });
    }
    for (const item of filteredUnmatchedDestination.value) {
      rows.push({ type: 'unmatched-destination', id: 'unmatched-dest-' + item.identifier, item });
    }
    return rows;
  });

  const currentPage = ref(1);
  const itemsPerPage = ref(25);
  const totalRows = computed(() => allRows.value.length);
  const totalPages = computed(() => Math.max(1, Math.ceil(totalRows.value / itemsPerPage.value)));

  // Keep currentPage valid if the list shrinks.
  watch(totalPages, (pages) => {
    if (currentPage.value > pages) {
      currentPage.value = pages;
    }
  });

  // Reset to the first page whenever the filters change.
  watch(
    () => [filters.value.search, filters.value.matchStatus, filters.value.confidenceMin, filters.value.confidenceMax, filters.value.hideApproved],
    () => { currentPage.value = 1; }
  );

  const pagedRows = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return allRows.value.slice(start, start + itemsPerPage.value);
  });

  function goToPage(page) {
    currentPage.value = Math.min(Math.max(1, page), totalPages.value);
  }

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
    await loadCrosswalkAssociations();
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
    if (!assoc) {
      return;
    }

    // Optimistic in-memory update (reactive via the deep ref).
    if (!assoc.extensions) {
      assoc.extensions = {};
    }
    assoc.extensions['crosswalk:status'] = status;

    // Persist the status extension on the association.
    try {
      await currentDocumentStore.updateAssociation(assocId, {
        extensions: { 'crosswalk:status': status },
      });
      const crosswalkDocId = currentDocumentStore.currentDocument?.identifier;
      if (crosswalkDocId) {
        currentDocumentStore.invalidateItemDetailsCache(crosswalkDocId);
      }
    } catch (err) {
      console.error('Failed to persist association status:', err);
      assoc.extensions['crosswalk:status'] = 'pending';
    }
  }

  async function approveAssociation(assocId) {
    await updateAssociationStatus(assocId, 'approved');
  }

  /**
   * Persist an edited association from the EditAssociationModal, then reload.
   * @param {object} updatedAssoc - partial association data with identifier
   */
  async function editAssociation(updatedAssoc) {
    const assocId = updatedAssoc.identifier || updatedAssoc.id;
    if (!assocId) return;

    const payload = { ...updatedAssoc };
    delete payload.identifier;
    delete payload.id;

    try {
      await currentDocumentStore.updateAssociation(assocId, payload);
      const crosswalkDocId = currentDocumentStore.currentDocument?.identifier;
      if (crosswalkDocId) {
        currentDocumentStore.invalidateItemDetailsCache(crosswalkDocId);
      }
    } catch (err) {
      console.error('Failed to persist association edit:', err);
    }
    await loadCrosswalkAssociations();
  }

  /**
   * Remove a crosswalk association from the framework entirely.
   * Used by the Review tab "Reject" action after confirmation.
   */
  async function deleteAssociation(assocId) {
    await currentDocumentStore.removeAssociation(assocId);
    const crosswalkDocId = currentDocumentStore.currentDocument?.identifier;
    if (crosswalkDocId) {
      currentDocumentStore.invalidateItemDetailsCache(crosswalkDocId);
    }
    await loadCrosswalkAssociations();
  }

  /**
   * Approve every selected matched-pair association.
   * Only IDs that correspond to real associations (not unmatched rows) are processed.
   * @param {Set<string>|string[]} ids
   */
  async function bulkApprove(ids) {
    const idSet = ids instanceof Set ? ids : new Set(ids);
    const pairIds = new Set(matchedPairs.value.map(p => p.id));
    for (const id of idSet) {
      if (pairIds.has(id)) {
        await updateAssociationStatus(id, 'approved');
      }
    }
  }

  /**
   * Delete every selected matched-pair association from the framework, then reload.
   * @param {Set<string>|string[]} ids
   */
  async function bulkReject(ids) {
    const idSet = ids instanceof Set ? ids : new Set(ids);
    const pairIds = new Set(matchedPairs.value.map(p => p.id));
    for (const id of idSet) {
      if (pairIds.has(id)) {
        await currentDocumentStore.removeAssociation(id);
      }
    }
    const crosswalkDocId = currentDocumentStore.currentDocument?.identifier;
    if (crosswalkDocId) {
      currentDocumentStore.invalidateItemDetailsCache(crosswalkDocId);
    }
    await loadCrosswalkAssociations();
  }

  /**
   * Search the opposite framework for candidate matches for an item.
   * @param {string} itemIdentifier
   * @param {string} frameworkIdentifier
   * @param {boolean} leafOnly
   * @returns {Promise<Array>}
   */
  async function findMatches(itemIdentifier, frameworkIdentifier, leafOnly = false) {
    const params = new URLSearchParams({
      item: itemIdentifier,
      framework: frameworkIdentifier,
      limit: '5',
    });
    if (leafOnly) {
      params.set('leaf_only', '1');
    }
    const data = await api.get(`/api/vector-search/crosswalk/match?${params.toString()}`);
    return Array.isArray(data?.results) ? data.results : [];
  }

  /**
   * Persist a manually chosen match as a crosswalk association, then reload.
   * @param {object} opts
   */
  async function createMatch({ originIdentifier, destinationIdentifier, similarity }) {
    const crosswalkDocId = currentDocumentStore.currentDocument?.identifier;
    if (!crosswalkDocId) {
      throw new Error('No active crosswalk framework.');
    }

    const exactMatchThreshold = 0.90;
    const isExact = similarity >= exactMatchThreshold;
    const subtype = isExact ? 'exact' : 'related';
    const type = isExact ? 'exactMatchOf' : 'isRelatedTo';

    await currentDocumentStore.addAssociation(crosswalkDocId, {
      origin: { identifier: originIdentifier },
      dest: { identifier: destinationIdentifier },
      type,
      extensions: {
        'crosswalk:confidence': similarity,
        'crosswalk:subtype': subtype,
        'crosswalk:status': 'pending',
        'crosswalk:jobId': 'manual',
      },
    });

    await loadCrosswalkAssociations();
  }

  function clearFilters() {
    filters.value = {
      search: '',
      confidenceMin: 0,
      confidenceMax: 1,
      matchStatus: 'all',
      hideApproved: false,
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
    allRows,
    pagedRows,
    currentPage,
    itemsPerPage,
    totalRows,
    totalPages,
    goToPage,
    filters,
    stats,
    selectedIds,
    loadingAssociations,
    loadingOriginItems,
    loadingDestinationItems,
    originItemsError,
    destinationItemsError,
    originFrameworkId,
    destinationFrameworkId,
    approveAssociation,
    editAssociation,
    deleteAssociation,
    bulkApprove,
    bulkReject,
    findMatches,
    createMatch,
    clearFilters,
    loadCrosswalkAssociations,
    loadOriginAndDestinationItems,
  };
}
