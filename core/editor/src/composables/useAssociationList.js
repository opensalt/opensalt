import { ref, computed, watch, onUnmounted, toValue } from 'vue';
import { useEditorContextStore } from '../stores/editorContextStore';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { localFrameworkDb } from '../services/localFrameworkDb.js';
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

export function useAssociationList({ mode, item = null, displayItem = null, document = null }) {
  const contextStore = useEditorContextStore();
  const currentDocumentStore = useCurrentDocumentStore();

  const rawEntries = ref([]);
  const isProcessingAssociations = ref(false);
  const lastError = ref(null);

  let activeSubscription = null;
  let subscriptionVersion = 0;

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
  const itemUri = computed(() => (
    toValue(item)?.uri ||
    toValue(item)?.crossFrameworkUri ||
    toValue(displayItem)?.uri ||
    null
  ));
  const documentIdentifier = computed(() => (
    toValue(document)?.identifier ||
    toValue(document)?.id ||
    null
  ));
  const documentItemIds = computed(() => collectDocumentItemIds(toValue(document)));

  function getItemAssociationsFromRegistry(identifier, uri) {
    if (!identifier) return [];
    return contextStore.getAssociations(identifier, uri);
  }

  function getItemAssociationsFromItemData() {
    const source = toValue(displayItem) || toValue(item);
    const associations = source?.associations;
    if (!Array.isArray(associations) || associations.length === 0) return [];

    const sourceFrameworkId =
      source?.documentId ||
      source?.CFDocumentURI?.identifier ||
      displayedFrameworkId.value ||
      null;

    return associations.map((association) => ({
      association,
      frameworkId: sourceFrameworkId,
    }));
  }

  function mergeAndDedupeEntries(...entryGroups) {
    const merged = [];
    const seenIds = new Set();

    entryGroups.forEach((entries) => {
      (entries || []).forEach((entry) => {
        const assocId = entry?.association?.identifier;
        if (!assocId || seenIds.has(assocId)) return;
        seenIds.add(assocId);
        merged.push(entry);
      });
    });

    return merged;
  }

  function getDocumentAssociationsFromRegistry(docId, docItemIds) {
    if (!docId || !docItemIds?.size) return [];

    const result = [];
    const seenIds = new Set();

    contextStore.associationRegistry.forEach((entry) => {
      const assoc = entry.association;
      const assocId = assoc?.identifier;
      if (!assocId || seenIds.has(assocId)) return;

      const originId = getOriginId(assoc);
      const destinationId = getDestinationId(assoc);
      if (!originId || !destinationId) return;

      const originInDoc = docItemIds.has(originId);
      const destinationInDoc = docItemIds.has(destinationId);
      if (originInDoc === destinationInDoc) return;

      seenIds.add(assocId);
      result.push(entry);
    });

    return result;
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

    return Object.values(grouped);
  }

  function getRegistryEntries() {
    const itemDataEntries = mode === 'item' ? getItemAssociationsFromItemData() : [];
    return mode === 'item'
      ? mergeAndDedupeEntries(itemDataEntries, getItemAssociationsFromRegistry(itemIdentifier.value, itemUri.value))
      : getDocumentAssociationsFromRegistry(documentIdentifier.value, documentItemIds.value);
  }

  async function stop({ timeoutMs = 250 } = {}) {
    if (!activeSubscription) return;

    const subscriptionToStop = activeSubscription;
    activeSubscription = null;

    try {
      const unsubscribePromise = subscriptionToStop.unsubscribe?.();
      if (unsubscribePromise && typeof unsubscribePromise.then === 'function') {
        await Promise.race([
          unsubscribePromise,
          new Promise((resolve) => setTimeout(resolve, timeoutMs))
        ]);
      }
    } catch (error) {
      logger.warn('[useAssociationList] Failed to stop association subscription:', error);
    }
  }

  async function fetchSnapshotEntries() {
    if (!localFrameworkDb.isReady()) {
      return null;
    }

    if (mode === 'item') {
      return localFrameworkDb.getItemAssociations(
        itemIdentifier.value,
        null
      );
    }

    return localFrameworkDb.getDocumentAssociations(
      documentIdentifier.value,
      null
    );
  }

  async function fetchEntriesWithFallback(currentVersion) {
    const snapshotPromise = fetchSnapshotEntries();

    try {
      const result = await snapshotPromise;

      if (result !== null && result !== undefined) {
        return result;
      }

      return getRegistryEntries();
    } catch (error) {
      const fallbackEntries = getRegistryEntries();

      if (Array.isArray(fallbackEntries) && fallbackEntries.length > 0) {
        return fallbackEntries;
      }

      throw error;
    }
  }

  async function loadEntries({ clearEntries = true, setLoading = true } = {}) {
    subscriptionVersion += 1;
    const currentVersion = subscriptionVersion;

    await stop();
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

    // Prevent stale association rows from staying visible while switching context.
    if (clearEntries) {
      rawEntries.value = [];
    }
    if (setLoading) {
      isProcessingAssociations.value = true;
    }

    try {
      const snapshotEntries = await fetchEntriesWithFallback(currentVersion);
      if (currentVersion !== subscriptionVersion) return;
      rawEntries.value = Array.isArray(snapshotEntries) ? snapshotEntries : [];
      if (setLoading) {
        isProcessingAssociations.value = false;
      }
    } catch (error) {
      logger.error('[useAssociationList] Failed to load associations:', error);
      lastError.value = error;
      if (setLoading) {
        isProcessingAssociations.value = false;
      }
      if (rawEntries.value.length > 0) return;

      // Final fallback so we still show current-item associations if local DB calls stall/fail.
      try {
        const fallbackEntries = mode === 'item'
          ? getItemAssociationsFromRegistry(itemIdentifier.value, itemUri.value)
          : getDocumentAssociationsFromRegistry(documentIdentifier.value, documentItemIds.value);
        rawEntries.value = Array.isArray(fallbackEntries) ? fallbackEntries : [];
      } catch (fallbackError) {
        logger.warn('[useAssociationList] Registry fallback failed:', fallbackError);
      }
    }
  }

  async function refresh() {
    if (activeSubscription?.refresh) {
      try {
        await activeSubscription.refresh();
      } catch (error) {
        logger.warn('[useAssociationList] Failed to refresh subscription, restarting:', error);
        await loadEntries({ clearEntries: false, setLoading: false });
      }
      return;
    }
    await loadEntries({ clearEntries: false, setLoading: false });
  }

  const watchKey = computed(() => {
    if (mode === 'item') {
      const itemAssociations = toValue(displayItem)?.associations || toValue(item)?.associations || [];
      const assocFingerprint = Array.isArray(itemAssociations)
        ? itemAssociations.map(a => `${a?.identifier}:${a?.associationType}:${a?.groupId}`).join(',')
        : '';
      return [
        itemIdentifier.value || '',
        itemUri.value || '',
        displayedFrameworkId.value || '',
        assocFingerprint,
      ].join('|');
    }

    return [
      documentIdentifier.value || '',
      displayedFrameworkId.value || '',
      Array.from(documentItemIds.value).join('|'),
    ].join('|');
  });

  watch(watchKey, () => {
    void loadEntries({ clearEntries: true, setLoading: true });
  }, { immediate: true });

  localFrameworkDb.onReady(() => {
    if (itemIdentifier.value || documentIdentifier.value) {
      void loadEntries({ clearEntries: true, setLoading: false });
    }
  });

  watch(() => contextStore.registryVersion, () => {
    if (!itemIdentifier.value && !documentIdentifier.value) return;
    void refresh();
  });

  const mergedAssociations = computed(() => groupAssociations(rawEntries.value));

  onUnmounted(() => {
    subscriptionVersion += 1;
    void stop();
  });

  return {
    mergedAssociations,
    isProcessingAssociations,
    refresh,
    stop,
    lastError,
  };
}
