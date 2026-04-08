import { ref, computed, watch, toValue } from 'vue';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { useEditorContextStore } from '../stores/editorContextStore';
import { useDocumentStore } from '../stores/documentStore';
import { useViewStore } from '../stores/viewStore';
import { logger } from '../utils/logger.js';
import { findItem as findItemInTree } from '../utils/tree.js';


// Cross-framework item cache is now handled by editorContextStore registries

/**
 * Find an item recursively in a tree by identifier
 * @param {Array} items - Array of items to search
 * @param {string} identifier - The identifier to find
 * @returns {Object|null} - The found item or null
 */
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

/**
 * Determine if an item is a CASE item based on targetType and association type
 * For exemplar associations without explicit targetType, treat as unknown (non-CASE)
 *
 * @param {string|undefined} targetType - The targetType from the node URI
 * @param {string} associationType - The association type
 * @returns {Object} - { isCase: boolean, isUnknown: boolean }
 */
export function determineTargetType(targetType, associationType) {
  // Exemplar associations have unknown target type unless explicitly specified
  if (associationType === 'exemplar' && !targetType) {
    return { isCase: false, isUnknown: true };
  }

  // Default to CASE if targetType is not specified or is explicitly 'CASE'
  return {
    isCase: !targetType || targetType === 'CASE',
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

/**
 * Find an item in all cached frameworks using centralized registry
 * @param {string} identifier - The item identifier to find
 * @param {Object} contextStore - The editor context store
 * @returns {Object|null} - { item, documentTitle, documentId } or null
 */
export function findInCachedFrameworks(identifierOrCandidates, contextStore) {
  const candidates = Array.isArray(identifierOrCandidates)
    ? identifierOrCandidates.filter(Boolean)
    : [identifierOrCandidates].filter(Boolean);

  if (candidates.length === 0) return null;

  for (const candidate of candidates) {
    const resolved = contextStore.resolveEndpoint(candidate);
    if (!resolved) continue;

    if (resolved.entityType === 'item') {
      if (isUnresolvedCrossFrameworkPlaceholder(resolved.entity)) {
        continue;
      }
      const doc = contextStore.documentRegistry.get(resolved.frameworkId);
      return {
        item: resolved.entity,
        documentTitle: doc?.title,
        documentId: resolved.frameworkId
      };
    }

    if (resolved.entityType === 'document') {
      return {
        item: { identifier: candidate, title: resolved.entity.title, fullStatement: resolved.entity.title },
        documentTitle: resolved.entity.title,
        documentId: resolved.entity.identifier
      };
    }
  }

  return null;
}

/**
 * Extract UUID from a CASE URI
 * The UUID is typically the last segment of the URI path
 * @param {string} uri - The full URI
 * @returns {string|null} - The extracted UUID or null
 */
function extractUuidFromUri(uri) {
  if (!uri) return null;

  try {
    // Try to parse as URL
    const url = new URL(uri);
    // Get the last segment of the pathname
    const segments = url.pathname.split('/').filter(Boolean);
    const lastSegment = segments[segments.length - 1];

    // Check if it looks like a UUID (basic check for hex characters and dashes)
    if (lastSegment && /^[0-9a-fA-F-]{36}$/.test(lastSegment)) {
      return lastSegment;
    }

    // Also check if the second-to-last segment might be an identifier type
    // (e.g., /CFItems/uuid or /items/uuid)
    if (segments.length >= 2) {
      const possibleUuid = segments[segments.length - 1];
      if (/^[0-9a-fA-F-]{36}$/.test(possibleUuid)) {
        return possibleUuid;
      }
    }

    return null;
  } catch {
    // If URL parsing fails, try regex extraction
    const uuidMatch = uri.match(/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})/);
    return uuidMatch ? uuidMatch[1] : null;
  }
}

// Direct fetch functions are now in editorContextStore.ts

/**
 * Composable for resolving items that may be in different frameworks
 *
 * This composable handles:
 * 1. Looking up items in the current document first
 * 2. Checking cached frameworks for the item
 * 3. For CASE items: fetching the item directly by URI, then fetching the document
 * 4. For non-CASE items: displaying the URI without fetching
 * 5. Caching fetched items and documents
 *
 * @param {Object} options - Configuration options
 * @param {import('vue').Ref<Object>|Object} options.association - The association object
 * @param {import('vue').Ref<string>|string} options.direction - 'normal' or 'reversed'
 * @returns {Object} - Composable return values
 */
export function useCrossFrameworkItem(options) {
  const { association, direction } = options;

  const currentDocumentStore = useCurrentDocumentStore();
  const documentStore = useDocumentStore();
  const contextStore = useEditorContextStore();
  const viewStore = useViewStore();

  // State
  const isLoading = ref(false);
  const externalFrameworkTitle = ref(null);
  const externalItemData = ref(null);
  const fetchError = ref(null);

  // Get the node URI based on direction
  const nodeURI = computed(() => {
    const assoc = toValue(association);
    const dir = toValue(direction);

    if (!assoc) return null;

    if (dir === 'reversed') {
      return assoc.originNodeURI || assoc.origin;
    }
    return assoc.destinationNodeURI || assoc.destination;
  });

  // Get the item identifier from the node URI
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

  // Candidate lookup keys for registry/cache resolution
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
      const resolved = contextStore.resolveEndpoint(candidate);
      if (resolved?.entityType === 'item') {
        if (isUnresolvedCrossFrameworkPlaceholder(resolved.entity)) {
          continue;
        }
        return resolved.entity;
      }
    }

    return null;
  });

  const resolvedLocalFrameworkId = computed(() => {
    const registryItem = registryItemData.value;
    if (registryItem) {
      return (
        registryItem.documentId ||
        registryItem.CFDocumentURI?.identifier ||
        contextStore.itemRegistry.get(registryItem.identifier)?.frameworkId ||
        contextStore.resolveEndpoint(registryItem.uri || registryItem.identifier)?.frameworkId ||
        null
      );
    }

    for (const candidate of lookupCandidates.value) {
      const resolved = contextStore.resolveEndpoint(candidate);
      if (!resolved) continue;

      if (resolved.entityType === 'item') {
        return resolved.frameworkId || null;
      }

      if (resolved.entityType === 'document') {
        return resolved.entity.identifier || resolved.frameworkId || null;
      }
    }

    return null;
  });

  // Determine target type info (CASE vs non-CASE vs unknown)
  const targetTypeInfo = computed(() => {
    const targetType = nodeURI.value?.targetType;
    const assoc = toValue(association);
    const associationType = assoc?.associationType;

    return determineTargetType(targetType, associationType);
  });

  const itemInCurrentDocument = computed(() => {
    const identifier = itemIdentifier.value;
    if (!identifier) return null;

    // Check if it's the document itself (by identifier, id, or uri)
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

  // Check if this is a cross-framework reference relative to the viewed framework
  const isCrossFramework = computed(() => {
    if (!itemIdentifier.value && !nodeURI.value?.uri) {
      return false;
    }

    // If we have an item identifier, check if it belongs to the framework being viewed in the tree.
    if (lookupCandidates.value.length > 0) {
      const cachedResult = findInCachedFrameworks(lookupCandidates.value, contextStore);
      if (cachedResult?.documentId) {
        return cachedResult.documentId !== displayedFrameworkId.value;
      }
    }

    // If we found the item in the current document, it's NOT cross-framework
    if (itemInCurrentDocument.value) return false;

    if (resolvedLocalFrameworkId.value) {
      return resolvedLocalFrameworkId.value !== displayedFrameworkId.value;
    }

    // If URI is missing but we have an identifier not found locally, still treat as cross-framework.
    if (!nodeURI.value?.uri) {
      return !!itemIdentifier.value;
    }

    return !!itemIdentifier.value;
  });

  // The resolved item data (from current doc, cache, or external fetch)
  const itemData = computed(() => {
    // First check current document
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
    return (
      item?.documentId ||
      item?.CFDocumentURI?.identifier ||
      contextStore.itemRegistry.get(item?.identifier)?.frameworkId ||
      contextStore.resolveEndpoint(item?.uri || item?.identifier || nodeURI.value?.uri || itemIdentifier.value)?.frameworkId ||
      null
    );
  });

  // The display title for the item
  const itemTitle = computed(() => {
    // For exemplar associations, always show the actual URI, not the title
    const assoc = toValue(association);
    if (assoc?.associationType === 'exemplar') {
      return nodeURI.value?.uri || 'Unknown URI';
    }

    if (hasNonLoadableUri.value) {
      return nodeURI.value?.uri || nodeURI.value?.title || itemIdentifier.value || 'Unknown URI';
    }

    // For non-CASE or unknown items, display the URI
    if (!targetTypeInfo.value.isCase && !targetTypeInfo.value.isUnknown) {
      return nodeURI.value?.uri || 'Unknown URI';
    }

    // For unknown items (like exemplar without targetType), show URI or title
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

  // The framework title (for badge display)
  const frameworkTitle = computed(() => {
    if (!isCrossFramework.value) return null;
    if (hasNonLoadableUri.value) return null;

    // For non-CASE items, no framework title
    if (!targetTypeInfo.value.isCase) return null;

    // If we have external framework data, use its title
    if (externalFrameworkTitle.value) {
      return externalFrameworkTitle.value;
    }

    const documentTitle =
      itemData.value?.CFDocumentURI?.title ||
      (resolvedFrameworkId.value ? contextStore.documentRegistry.get(resolvedFrameworkId.value)?.title : null);
    if (documentTitle) {
      return documentTitle;
    }

    // Otherwise indicate we're loading or don't have the info
    return isLoading.value || (itemIdentifier.value && viewStore.getViewState(itemIdentifier.value).loading) ? 'Loading...' : 'External Framework';
  });

  async function ensureFrameworkMetadata(item) {
    if (!item) return null;

    const frameworkId =
      item.documentId ||
      item.CFDocumentURI?.identifier ||
      contextStore.itemRegistry.get(item.identifier)?.frameworkId ||
      contextStore.resolveEndpoint(item.uri || item.identifier)?.frameworkId ||
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

    if (typeof documentStore.fetchDocumentMetadata === 'function') {
      const metadata = await documentStore.fetchDocumentMetadata(frameworkId);
      if (metadata?.title) {
        contextStore.registerDocumentMetadata({
          identifier: metadata.identifier || frameworkId,
          uri: metadata.uri || existingDoc?.uri || '',
          title: metadata.title,
          frameworkId
        });
        return metadata.title;
      }
    }

    return null;
  }

  /**
   * Load the external item for CASE items
   */
  async function loadExternalItem() {
    const identifier = itemIdentifier.value;
    const uri = nodeURI.value?.uri;
    const loadingKey = identifier || uri;

    if (!loadingKey) return;
    if (itemInCurrentDocument.value) return;

    // For non-CASE items, don't fetch
    if (!targetTypeInfo.value.isCase) return;
    if (hasNonLoadableUri.value) return;

    // Check cached frameworks/registries first
    const cachedResult = findInCachedFrameworks(lookupCandidates.value, contextStore);
    if (cachedResult && !isUnresolvedCrossFrameworkPlaceholder(cachedResult.item)) {
      externalItemData.value = cachedResult.item;
      externalFrameworkTitle.value = cachedResult.documentTitle || await ensureFrameworkMetadata(cachedResult.item);
      return;
    }

    // Check if already loading
    if (isLoading.value) return;

    isLoading.value = true;
    viewStore.setLoading(loadingKey, true);
    fetchError.value = null;

    try {
      const fetchReference = uri || identifier;
      const result = await contextStore.fetchExternalItemData(fetchReference);
      const fetchedItem = result?.item || (result?.identifier ? result : null);
      if (fetchedItem) {
        // If it was a package, it's already registered.
        // If it was a single item, fetchExternalItemData might have registered it too.

        externalItemData.value = fetchedItem;

        // Try to get document title from registry
        const fwId = contextStore.itemRegistry.get(fetchedItem.identifier)?.frameworkId
          || contextStore.resolveEndpoint(fetchedItem.uri || fetchedItem.identifier)?.frameworkId;
        if (fwId) {
          const doc = contextStore.documentRegistry.get(fwId);
          externalFrameworkTitle.value = doc?.title || await ensureFrameworkMetadata(fetchedItem) || 'External Framework';
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

  /**
   * Force reload the external item
   */
  async function reload() {
    externalItemData.value = null;
    externalFrameworkTitle.value = null;
    fetchError.value = null;

    // Cache is now handled by editorContextStore, so no need to clear manually
    await loadExternalItem();
  }

  // Watch for changes to association or direction
  watch(
    [() => toValue(association), () => toValue(direction)],
    () => {
      // Reset state when association changes
      externalItemData.value = null;
      externalFrameworkTitle.value = null;
      fetchError.value = null;

      // Load external item if needed
      if (isCrossFramework.value && !itemInCurrentDocument.value && targetTypeInfo.value.isCase && !hasNonLoadableUri.value) {
        loadExternalItem();
      }
    },
    { immediate: true }
  );

  return {
    // State
    isLoading,
    fetchError,

    // Computed
    itemData,
    itemTitle,
    frameworkTitle,
    isCrossFramework,
    itemIdentifier,
    targetTypeInfo,
    nodeURI,

    // Methods
    loadExternalItem,
    reload
  };
}

/**
 * Clear the cross-framework item cache (now handled by editorContextStore)
 */
export function clearCrossFrameworkItemCache() {
  logger.warn('crossFrameworkItemCache is now handled by editorContextStore registries');
}

/**
 * Get cache size (now handled by editorContextStore)
 */
export function getCrossFrameworkItemCacheSize() {
  logger.warn('crossFrameworkItemCache is now handled by editorContextStore registries');
  return 0;
}

export default useCrossFrameworkItem;
