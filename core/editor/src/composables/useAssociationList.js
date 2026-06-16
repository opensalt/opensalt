import { ref, computed, watch, onUnmounted, toValue } from 'vue';
import { useEditorContextStore } from '../stores/editorContextStore';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';

function normalizeGroupId(association) {
  return (
    association?.CFAssociationGroupingURI?.identifier ||
    (typeof association?.CFAssociationGroupingURI === 'string'
      ? association.CFAssociationGroupingURI
      : null)
  );
}

function getOriginId(association) {
  return association?.originNodeURI?.identifier || association?.originNodeIdentifier || null;
}

function getDestinationId(association) {
  return association?.destinationNodeURI?.identifier || association?.destinationNodeIdentifier || null;
}

function collectDocumentItemIds(document) {
  const ids = new Set();
  if (!document) return ids;

  if (document.identifier) {
    ids.add(document.identifier);
  }

  (function collect(items) {
    if (!Array.isArray(items)) return;
    items.forEach((item) => {
      if (!item?.identifier) return;
      ids.add(item.identifier);
      if (item.children?.length) collect(item.children);
    });
  })(document.items || []);

  return ids;
}

export function useAssociationList({ mode, item = null, displayItem: _displayItem = null, document = null }) {
  const contextStore = useEditorContextStore();
  const currentDocumentStore = useCurrentDocumentStore();

  const rawEntries = ref([]);
  const isProcessingAssociations = ref(false);
  const lastError = ref(null);

  let loadVersion = 0;

  const displayedFrameworkId = computed(() => (
    contextStore.isViewingDifferentFramework
      ? (
        contextStore.viewedDocumentId ||
        currentDocumentStore.currentDocument?.identifier ||
        currentDocumentStore.currentDocument?.id ||
        null
      )
      : (
        contextStore.activeWriteDocumentId ||
        currentDocumentStore.currentDocument?.identifier ||
        currentDocumentStore.currentDocument?.id ||
        null
      )
  ));

  const itemIdentifier = computed(() => toValue(item)?.identifier || null);
  const documentIdentifier = computed(() => (
    toValue(document)?.identifier ||
    toValue(document)?.id ||
    null
  ));
  const documentItemIds = computed(() => collectDocumentItemIds(toValue(document)));

  function mapApiAssociationsToEntries(apiData, queryDocId) {
    if (!Array.isArray(apiData)) return [];

    return apiData.map((assoc) => {
      const entry = {
        association: assoc,
        frameworkId: assoc.associationDocumentIdentifier || null,
      };

      if (mode === 'document' && queryDocId) {
        entry.originInDocument = assoc.originNodeURI?.documentIdentifier === queryDocId;
        entry.destinationInDocument = assoc.destinationNodeURI?.documentIdentifier === queryDocId;
      }

      return entry;
    });
  }

  function groupAssociations(entries) {
    const grouped = {};

    entries.forEach((entry) => {
      const association = entry.association || null;
      if (!association) return;

      const sourceFrameworkId = entry.frameworkId || null;
      const associationType = association.associationType || association.type || 'unknown';

      if (associationType === 'isChildOf') {
        if (!sourceFrameworkId) return;
        if (displayedFrameworkId.value && sourceFrameworkId === displayedFrameworkId.value) return;
      }

      const originId = getOriginId(association);
      const destinationId = getDestinationId(association);

      let direction = 'normal';
      if (mode === 'item') {
        direction = destinationId === itemIdentifier.value ? 'reversed' : 'normal';
      } else if (mode === 'document') {
        const hasDocumentFlags = typeof entry.originInDocument === 'boolean' && typeof entry.destinationInDocument === 'boolean';
        const originInDoc = hasDocumentFlags
          ? entry.originInDocument
          : documentItemIds.value.has(originId);
        const destinationInDoc = hasDocumentFlags
          ? entry.destinationInDocument
          : documentItemIds.value.has(destinationId);
        if (originInDoc && !destinationInDoc) {
          direction = 'normal';
        } else if (destinationInDoc && !originInDoc) {
          direction = 'reversed';
        } else {
          return;
        }
      }

      const enriched = {
        ...association,
        _sourceFrameworkId: sourceFrameworkId,
        groupId: normalizeGroupId(association),
      };

      const key = `${associationType}-${direction}`;
      if (!grouped[key]) {
        grouped[key] = {
          type: associationType,
          direction,
          associations: []
        };
      }
      grouped[key].associations.push(enriched);
    });

    // Sort associations within each group: the displayed framework's own
    // associations first, then others. This surfaces crosswalk-owned
    // associations at the top of each group.
    for (const key of Object.keys(grouped)) {
      const group = grouped[key];
      if (!group.associations || group.associations.length <= 1) continue;
      const displayedId = displayedFrameworkId.value;
      if (!displayedId) continue;

      group.associations.sort((a, b) => {
        const aIsOwned = (a._sourceFrameworkId || a.associationDocumentIdentifier) === displayedId ? 0 : 1;
        const bIsOwned = (b._sourceFrameworkId || b.associationDocumentIdentifier) === displayedId ? 0 : 1;
        return aIsOwned - bIsOwned;
      });
    }

    return Object.values(grouped);
  }

  async function fetchAssociationsFromApi() {
    if (mode === 'item' && itemIdentifier.value) {
      const response = await api.get(`/framework/editor/associations/item/${itemIdentifier.value}`);
      const associations = response?.data;
      if (!Array.isArray(associations)) return [];
      return mapApiAssociationsToEntries(associations, null);
    }

    if (mode === 'document' && documentIdentifier.value) {
      const response = await api.get(`/framework/editor/associations/document/${documentIdentifier.value}`);
      const associations = response?.data;
      if (!Array.isArray(associations)) return [];
      return mapApiAssociationsToEntries(associations, documentIdentifier.value);
    }

    return [];
  }

  async function stop() {
    loadVersion++;
  }

  async function loadEntries({ clearEntries = true, setLoading = true } = {}) {
    loadVersion++;
    const currentVersion = loadVersion;

    lastError.value = null;

    if (mode === 'item' && !itemIdentifier.value) {
      rawEntries.value = [];
      isProcessingAssociations.value = false;
      return;
    }
    if (mode === 'document' && !documentIdentifier.value) {
      rawEntries.value = [];
      isProcessingAssociations.value = false;
      return;
    }

    if (clearEntries) {
      rawEntries.value = [];
    }
    if (setLoading) {
      isProcessingAssociations.value = true;
    }

    try {
      const entries = await fetchAssociationsFromApi();
      if (currentVersion !== loadVersion) {
        if (setLoading) isProcessingAssociations.value = false;
        return;
      }
      rawEntries.value = Array.isArray(entries) ? entries : [];
      if (setLoading) isProcessingAssociations.value = false;
    } catch (error) {
      logger.error('[useAssociationList] Failed to load associations:', error);
      lastError.value = error;
      if (currentVersion !== loadVersion) return;
      rawEntries.value = [];
      if (setLoading) isProcessingAssociations.value = false;
    }
  }

  async function refresh() {
    await loadEntries({ clearEntries: false, setLoading: false });
  }

  const watchKey = computed(() => {
    if (mode === 'item') {
      return [
        itemIdentifier.value || '',
        displayedFrameworkId.value || '',
      ].join('|');
    }

    return [
      documentIdentifier.value || '',
      displayedFrameworkId.value || '',
    ].join('|');
  });

  watch(watchKey, () => {
    void loadEntries({ clearEntries: true, setLoading: true });
  }, { immediate: true });

  const mergedAssociations = computed(() => groupAssociations(rawEntries.value));

  onUnmounted(() => {
    loadVersion++;
  });

  return {
    mergedAssociations,
    isProcessingAssociations,
    refresh,
    stop,
    lastError,
  };
}
