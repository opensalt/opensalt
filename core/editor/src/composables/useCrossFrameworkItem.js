import { ref, computed, watch, toValue } from 'vue';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { useEditorContextStore } from '../stores/editorContextStore';
import { useViewStore } from '../stores/viewStore';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';
import { findItem as findItemInTree } from '../utils/tree.js';


export function findItemById(items, identifier) {
  if (!items || !Array.isArray(items)) return null;
  const found = findItemInTree(items, identifier);
  if (found) return found;
  for (const item of items) {
    if (item.id === identifier) return item;
    if (item.children) {
      const childFound = findItemById(item.children, identifier);
      if (childFound) return childFound;
    }
  }
  return null;
}

export function determineTargetType(targetType, associationType) {
  if (targetType) {
    return {
      isCase: targetType === 'item' || targetType === 'document' || targetType === 'CASE',
      isUnknown: false
    };
  }

  if (associationType === 'exemplar') {
    return { isCase: false, isUnknown: true };
  }

  return {
    isCase: true,
    isUnknown: false
  };
}

function isUnresolvedCrossFrameworkPlaceholder(item) {
  if (!item?.isCrossFramework) return false;

  const hasFrameworkIdentity = !!(
    item.documentId ||
    item.CFDocumentURI?.identifier ||
    item.CFDocumentURI?.uri
  );
  if (hasFrameworkIdentity) return false;

  const displayValue = item.fullStatement || item.abbreviatedStatement || item.title || '';
  return !displayValue || displayValue === 'Loading...';
}

function isUuidLike(value) {
  return typeof value === 'string' &&
    /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(value.trim());
}

function isMeaningfulTitle(value) {
  if (!value || typeof value !== 'string') return false;
  const normalized = value.trim();
  if (!normalized || normalized === 'Loading...') return false;
  const lower = normalized.toLowerCase();
  return lower !== 'origin node' && lower !== 'destination node';
}

function isLoadableAssociationUri(uri) {
  return typeof uri === 'string' && /^https?:\/\//i.test(uri.trim());
}

export function findInCachedFrameworks(identifierOrCandidates, contextStore) {
  const candidates = Array.isArray(identifierOrCandidates)
    ? identifierOrCandidates.filter(Boolean)
    : [identifierOrCandidates].filter(Boolean);

  if (candidates.length === 0) return null;

  for (const candidate of candidates) {
    const cachedDetails = contextStore.itemDetailsCache.get(candidate);
    if (cachedDetails) {
      const fwId = cachedDetails.documentIdentifier || null;
      const doc = fwId ? contextStore.documentRegistry.get(fwId) : null;
      return {
        item: cachedDetails,
        documentTitle: doc?.title,
        documentId: fwId
      };
    }

    const doc = contextStore.documentRegistry.get(candidate);
    if (doc) {
      return {
        item: { identifier: candidate, title: doc.title, fullStatement: doc.title },
        documentTitle: doc.title,
        documentId: doc.identifier
      };
    }
  }

  return null;
}

function extractUuidFromUri(uri) {
  if (!uri) return null;

  try {
    const url = new URL(uri);
    const segments = url.pathname.split('/').filter(Boolean);
    const lastSegment = segments[segments.length - 1];

    if (lastSegment && /^[0-9a-fA-F-]{36}$/.test(lastSegment)) {
      return lastSegment;
    }

    if (segments.length >= 2) {
      const possibleUuid = segments[segments.length - 1];
      if (/^[0-9a-fA-F-]{36}$/.test(possibleUuid)) {
        return possibleUuid;
      }
    }

    return null;
  } catch {
    const uuidMatch = uri.match(/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})/);
    return uuidMatch ? uuidMatch[1] : null;
  }
}

export function useCrossFrameworkItem(options) {
  const { association, direction } = options;

  const currentDocumentStore = useCurrentDocumentStore();
  const contextStore = useEditorContextStore();
  const viewStore = useViewStore();

  const isLoading = ref(false);
  const externalFrameworkTitle = ref(null);
  const externalItemData = ref(null);
  const fetchError = ref(null);

  const nodeURI = computed(() => {
    const assoc = toValue(association);
    const dir = toValue(direction);

    if (!assoc) return null;

    if (dir === 'reversed') {
      return assoc.originNodeURI || assoc.origin;
    }
    return assoc.destinationNodeURI || assoc.destination;
  });

  const itemIdentifier = computed(() => {
    return nodeURI.value?.identifier || extractUuidFromUri(nodeURI.value?.uri || '');
  });

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

  const lookupCandidates = computed(() => {
    const uri = nodeURI.value?.uri;
    const identifier = itemIdentifier.value;
    const fromUri = extractUuidFromUri(uri || '');
    return [identifier, uri, fromUri].filter(Boolean);
  });

  const hasNonLoadableUri = computed(() => {
    const uri = nodeURI.value?.uri;
    return !!uri && !isLoadableAssociationUri(uri);
  });

  const registryItemData = computed(() => {
    for (const candidate of lookupCandidates.value) {
      const cached = contextStore.itemDetailsCache.get(candidate);
      if (cached) {
        return cached;
      }
    }
    return null;
  });

  const resolvedLocalFrameworkId = computed(() => {
    const registryItem = registryItemData.value;
    if (registryItem) {
      return registryItem.documentIdentifier || null;
    }

    for (const candidate of lookupCandidates.value) {
      const doc = contextStore.documentRegistry.get(candidate);
      if (doc) {
        return doc.identifier || doc.frameworkId || null;
      }
    }

    return null;
  });

  const targetTypeInfo = computed(() => {
    const targetType = nodeURI.value?.targetType;
    const assoc = toValue(association);
    const associationType = assoc?.associationType;

    return determineTargetType(targetType, associationType);
  });

  const itemInCurrentDocument = computed(() => {
    const identifier = itemIdentifier.value;
    if (!identifier) return null;

    const isCurrentDoc = currentDocumentStore.currentDocument &&
      (currentDocumentStore.currentDocument.identifier === identifier ||
        currentDocumentStore.currentDocument.id === identifier ||
        currentDocumentStore.currentDocument.uri === identifier);

    if (isCurrentDoc) {
      const title = currentDocumentStore.currentDocument.title || identifier;
      return {
        identifier,
        title,
        fullStatement: title,
        humanCodingScheme: ''
      };
    }

    const items = currentDocumentStore.currentDocument?.items;
    if (!items) return null;

    const found = findItemById(items, identifier);
    if (found && found.isCrossFramework) {
      return null;
    }
    return found;
  });

  const isCrossFramework = computed(() => {
    if (!itemIdentifier.value && !nodeURI.value?.uri) {
      return false;
    }

    if (lookupCandidates.value.length > 0) {
      const cachedResult = findInCachedFrameworks(lookupCandidates.value, contextStore);
      if (cachedResult?.documentId) {
        return cachedResult.documentId !== displayedFrameworkId.value;
      }
    }

    if (itemInCurrentDocument.value) return false;

    if (resolvedLocalFrameworkId.value) {
      return resolvedLocalFrameworkId.value !== displayedFrameworkId.value;
    }

    if (!nodeURI.value?.uri) {
      return !!itemIdentifier.value;
    }

    return !!itemIdentifier.value;
  });

  const itemData = computed(() => {
    if (itemInCurrentDocument.value) {
      return itemInCurrentDocument.value;
    }

    if (hasNonLoadableUri.value) {
      return null;
    }

    if (registryItemData.value && externalItemData.value) {
      return {
        ...registryItemData.value,
        ...externalItemData.value
      };
    }

    if (registryItemData.value) {
      return registryItemData.value;
    }

    if (externalItemData.value) {
      return externalItemData.value;
    }

    return null;
  });

  const resolvedFrameworkId = computed(() => {
    const item = itemData.value || externalItemData.value || registryItemData.value;
    if (!item) return null;

    return (
      item.documentId ||
      item.documentIdentifier ||
      item.CFDocumentURI?.identifier ||
      contextStore.itemDetailsCache.get(item.identifier)?.documentIdentifier ||
      null
    );
  });

  const itemTitle = computed(() => {
    const assoc = toValue(association);
    if (assoc?.associationType === 'exemplar') {
      return nodeURI.value?.uri || 'Unknown URI';
    }

    if (hasNonLoadableUri.value) {
      return nodeURI.value?.title || nodeURI.value?.uri || itemIdentifier.value || 'Unknown URI';
    }

    if (!targetTypeInfo.value.isCase && !targetTypeInfo.value.isUnknown) {
      return nodeURI.value?.uri || 'Unknown URI';
    }

    if (targetTypeInfo.value.isUnknown) {
      return nodeURI.value?.title || nodeURI.value?.uri || 'Unknown';
    }

    const item = itemData.value;
    const fallbackTitle = isMeaningfulTitle(nodeURI.value?.title) ? nodeURI.value.title : null;

    if (item) {
      const abbr = item.abbreviatedStatement || item.CFItemAbbreviatedStatement;
      const full = item.fullStatement || item.CFItemFullStatement;
      const humanCoding = item.humanCodingScheme || item.CFItemHumanCodingScheme;
      const directTitle = item.title;

      if (abbr) {
        return abbr;
      }
      if (full) {
        return full.length > 100 ? full.substring(0, 100) + '...' : full;
      }
      if (fallbackTitle && (!directTitle || directTitle === item.identifier || isUuidLike(directTitle))) {
        return fallbackTitle;
      }
      if (humanCoding) {
        return humanCoding;
      }
      if (isMeaningfulTitle(directTitle)) {
        return directTitle;
      }
      return item.identifier || fallbackTitle || 'Unknown';
    }

    if (fallbackTitle) {
      return fallbackTitle;
    }

    return itemIdentifier.value || nodeURI.value?.uri || 'Unknown';
  });

  const frameworkTitle = computed(() => {
    if (!isCrossFramework.value) return null;

    if (!targetTypeInfo.value.isCase) return null;

    if (externalFrameworkTitle.value) {
      return externalFrameworkTitle.value;
    }

    const documentTitle =
      itemData.value?.CFDocumentURI?.title ||
      (resolvedFrameworkId.value ? contextStore.documentRegistry.get(resolvedFrameworkId.value)?.title : null);
    if (documentTitle) {
      return documentTitle;
    }

    const nodeFwId = nodeURI.value?.documentIdentifier;
    if (nodeFwId) {
      const doc = contextStore.documentRegistry.get(nodeFwId);
      if (doc?.title) return doc.title;
    }

    if (hasNonLoadableUri.value) return null;

    return isLoading.value || (itemIdentifier.value && viewStore.getViewState(itemIdentifier.value).loading) ? 'Loading...' : 'External Framework';
  });

  async function ensureFrameworkMetadata(item) {
    if (!item) return null;

    const frameworkId =
      item.documentId ||
      item.documentIdentifier ||
      item.CFDocumentURI?.identifier ||
      contextStore.itemDetailsCache.get(item.identifier)?.documentIdentifier ||
      null;

    if (!frameworkId) {
      return null;
    }

    const existingDoc = contextStore.documentRegistry.get(frameworkId);
    if (existingDoc?.title) {
      return existingDoc.title;
    }

    const linkTitle = item.CFDocumentURI?.title;
    if (linkTitle) {
      contextStore.registerDocumentMetadata({
        identifier: frameworkId,
        uri: item.CFDocumentURI?.uri || existingDoc?.uri || '',
        title: linkTitle,
        frameworkId
      });
      return linkTitle;
    }

    return null;
  }

  async function loadExternalItem() {
    const identifier = itemIdentifier.value;
    const uri = nodeURI.value?.uri;
    const loadingKey = identifier || uri;

    if (!loadingKey) return;
    if (itemInCurrentDocument.value) return;

    if (!targetTypeInfo.value.isCase) return;
    if (hasNonLoadableUri.value) return;

    const cachedResult = findInCachedFrameworks(lookupCandidates.value, contextStore);
    if (cachedResult && !isUnresolvedCrossFrameworkPlaceholder(cachedResult.item)) {
      externalItemData.value = cachedResult.item;
      externalFrameworkTitle.value = cachedResult.documentTitle || await ensureFrameworkMetadata(cachedResult.item);
      return;
    }

    if (isLoading.value) return;

    isLoading.value = true;
    viewStore.setLoading(loadingKey, true);
    fetchError.value = null;

    try {
      const response = await api.get(`/framework/editor/item/${identifier}/details`);
      const fetchedItem = response?.data || response;

      if (fetchedItem && fetchedItem.identifier) {
        contextStore.itemDetailsCache.set(fetchedItem.identifier, fetchedItem);

        externalItemData.value = fetchedItem;

        const fwId = fetchedItem.documentIdentifier || null;
        if (fwId) {
          const doc = contextStore.documentRegistry.get(fwId);
          externalFrameworkTitle.value = doc?.title || fetchedItem.CFDocumentURI?.title || 'External Framework';
        } else {
          externalFrameworkTitle.value = await ensureFrameworkMetadata(fetchedItem);
        }
      } else {
        fetchError.value = {
          type: 'not_found',
          message: 'Failed to load item details'
        };
      }
    } catch (error) {
      logger.warn(`Unexpected error in loadExternalItem:`, error);
      fetchError.value = {
        type: error?.status === 403 ? 'permission' :
          error?.status === 404 ? 'not_found' : 'network',
        message: error?.message || "Failed to load item details",
        status: error?.status
      };
    } finally {
      isLoading.value = false;
      viewStore.setLoading(loadingKey, false);
    }
  }

  async function reload() {
    externalItemData.value = null;
    externalFrameworkTitle.value = null;
    fetchError.value = null;

    await loadExternalItem();
  }

  watch(
    [() => toValue(association), () => toValue(direction)],
    () => {
      externalItemData.value = null;
      externalFrameworkTitle.value = null;
      fetchError.value = null;

      if (isCrossFramework.value && !itemInCurrentDocument.value && targetTypeInfo.value.isCase && !hasNonLoadableUri.value) {
        loadExternalItem();
      }
    },
    { immediate: true }
  );

  return {
    isLoading,
    fetchError,

    itemData,
    itemTitle,
    frameworkTitle,
    isCrossFramework,
    itemIdentifier,
    targetTypeInfo,
    nodeURI,
    resolvedFrameworkId,
    displayedFrameworkId,

    loadExternalItem,
    reload
  };
}

export function clearCrossFrameworkItemCache() {
  logger.warn('crossFrameworkItemCache is now handled by editorContextStore registries');
}

export function getCrossFrameworkItemCacheSize() {
  logger.warn('crossFrameworkItemCache is now handled by editorContextStore registries');
  return 0;
}

export default useCrossFrameworkItem;
