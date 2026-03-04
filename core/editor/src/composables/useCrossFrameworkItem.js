import { ref, computed, watch, toValue } from 'vue';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { useDocumentStore } from '../stores/documentStore';
import { logger } from '../utils/logger.js';

/* global URL, fetch */

/**
 * Cross-framework item cache for storing fetched items
 * Key: item identifier (UUID)
 * Value: { item, documentId, documentTitle, fetchedAt }
 */
const crossFrameworkItemCache = new Map();

/**
 * Pending requests for deduplication
 * Key: item URI
 * Value: Promise
 */
const pendingRequests = new Map();

/**
 * Find an item recursively in a tree by identifier
 * @param {Array} items - Array of items to search
 * @param {string} identifier - The identifier to find
 * @returns {Object|null} - The found item or null
 */
export function findItemById(items, identifier) {
  if (!items || !Array.isArray(items)) return null;

  for (const item of items) {
    if (item.identifier === identifier || item.id === identifier) {
      return item;
    }
    if (item.children) {
      const found = findItemById(item.children, identifier);
      if (found) return found;
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

/**
 * Find an item in all cached frameworks
 * @param {string} identifier - The item identifier to find
 * @param {Object} currentDocumentStore - The current document store
 * @param {Object} documentStore - The document store
 * @returns {Object|null} - { item, documentTitle, documentId } or null
 */
export function findInCachedFrameworks(identifier, currentDocumentStore, documentStore) {
  if (!identifier) return null;

  // Check if identifier matches the current document itself (by identifier, id, or uri)
  const isCurrentDoc = currentDocumentStore.currentDocument &&
    (currentDocumentStore.currentDocument.identifier === identifier ||
      currentDocumentStore.currentDocument.id === identifier ||
      currentDocumentStore.currentDocument.uri === identifier);

  if (isCurrentDoc) {
    const title = currentDocumentStore.currentDocument.title || identifier;
    return {
      item: { identifier, title, fullStatement: title },
      documentTitle: title,
      documentId: currentDocumentStore.currentDocument.id || identifier
    };
  }

  // Check associatedDocuments cache
  for (const [docId, doc] of currentDocumentStore.associatedDocuments) {
    // Check if identifier matches the document itself
    if (doc.id === identifier || doc.identifier === identifier || doc.uri === identifier) {
      return {
        item: { identifier, title: doc.title, fullStatement: doc.title },
        documentTitle: doc.title,
        documentId: docId
      };
    }
    const found = findItemById(doc.items, identifier);
    if (found) {
      return {
        item: found,
        documentTitle: doc.title,
        documentId: docId
      };
    }
  }

  // Check documentCache
  for (const [docId, pkg] of documentStore.documentCache) {
    // Check if identifier matches this document
    const isDocMatch = pkg?.CFDocument &&
      (pkg.CFDocument.identifier === identifier ||
        pkg.CFDocument.id === identifier ||
        pkg.CFDocument.uri === identifier);

    if (isDocMatch) {
      const title = pkg.CFDocument.title || identifier;
      return {
        item: { identifier, title, fullStatement: title },
        documentTitle: title,
        documentId: docId
      };
    }
    if (pkg && pkg.CFItems) {
      const item = pkg.CFItems.find(i => i.identifier === identifier);
      if (item) {
        return {
          item,
          documentTitle: pkg.CFDocument?.title,
          documentId: docId
        };
      }
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

/**
 * Fetch a CASE item directly by its URI with local server fallback
 * First tries the local server endpoint, then falls back to the original URI
 * @param {string} uri - The item URI
 * @returns {Promise<Object>} - The item data
 */
async function fetchItemByUri(uri) {
  // Check for pending request to deduplicate
  if (pendingRequests.has(uri)) {
    return pendingRequests.get(uri);
  }

  const request = (async () => {
    try {
      // Extract UUID from the URI
      const uuid = extractUuidFromUri(uri);

      // If we have a UUID, try local server first
      if (uuid) {
        try {
          const localUrl = `/ims/case/v1p1/CFItems/${uuid}`;
          const localResponse = await fetch(localUrl, {
            headers: { 'Accept': 'application/json' }
          });

          if (localResponse.ok) {
            pendingRequests.delete(uri);
            return localResponse.json();
          }

          // Log the local fetch failure but don't throw - we'll try the original URI
          logger.debug(`Local fetch failed for item ${uuid}, trying original URI`);
        } catch (localError) {
          // Local fetch failed, continue to try original URI
          logger.debug(`Local fetch error for item ${uuid}:`, localError);
        }
      }

      // Fall back to the original URI
      const response = await fetch(uri, {
        headers: { 'Accept': 'application/json' }
      });

      pendingRequests.delete(uri);

      if (!response.ok) {
        const error = new Error(`Failed to fetch item: ${response.status}`);
        error.status = response.status;
        throw error;
      }

      return response.json();
    } catch (error) {
      pendingRequests.delete(uri);
      throw error;
    }
  })();

  pendingRequests.set(uri, request);
  return request;
}

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
    return nodeURI.value?.identifier;
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

  // Check if this is a cross-framework reference
  const isCrossFramework = computed(() => {
    // If we found the item in the current document, it's NOT cross-framework
    if (itemInCurrentDocument.value) return false;

    // If we don't have a node URI, we can't determine if it's cross-framework
    if (!nodeURI.value?.uri) return false;

    // If we have an item identifier but couldn't find it locally, it's cross-framework
    return !!itemIdentifier.value;
  });

  // The resolved item data (from current doc, cache, or external fetch)
  const itemData = computed(() => {
    // First check current document
    if (itemInCurrentDocument.value) {
      return itemInCurrentDocument.value;
    }

    // Then check external item data we've loaded
    if (externalItemData.value) {
      return externalItemData.value;
    }

    return null;
  });

  // The display title for the item
  const itemTitle = computed(() => {
    // For exemplar associations, always show the actual URI, not the title
    const assoc = toValue(association);
    if (assoc?.associationType === 'exemplar') {
      return nodeURI.value?.uri || 'Unknown URI';
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

    if (item) {
      // Build display text from item data
      const parts = [];
      const abbr = item.abbreviatedStatement || item.CFItemAbbreviatedStatement;
      const full = item.fullStatement || item.CFItemFullStatement;

      if (abbr) {
        parts.push(abbr);
      } else if (full) {
        // Truncate long statements
        parts.push(full.length > 100 ? full.substring(0, 100) + '...' : full);
      }

      if (parts.length > 0) {
        return parts.join(' ');
      }
      return item.title || item.identifier || 'Unknown';
    }

    // Fallback to node URI title
    return nodeURI.value?.title || nodeURI.value?.identifier || 'Unknown';
  });

  // The framework title (for badge display)
  const frameworkTitle = computed(() => {
    if (!isCrossFramework.value) return null;

    // For non-CASE items, no framework title
    if (!targetTypeInfo.value.isCase) return null;

    // If we have external framework data, use its title
    if (externalFrameworkTitle.value) {
      return externalFrameworkTitle.value;
    }

    // Otherwise indicate we're loading or don't have the info
    return isLoading.value ? 'Loading...' : 'External Framework';
  });

  /**
   * Load the external item for CASE items
   */
  async function loadExternalItem() {
    const identifier = itemIdentifier.value;
    const uri = nodeURI.value?.uri;

    if (!identifier || !uri) return;
    if (itemInCurrentDocument.value) return; // Already found in current doc

    // For non-CASE items, don't fetch
    if (!targetTypeInfo.value.isCase) return;

    // Check cross-framework item cache first
    if (crossFrameworkItemCache.has(identifier)) {
      const cached = crossFrameworkItemCache.get(identifier);
      externalItemData.value = cached.item;
      externalFrameworkTitle.value = cached.documentTitle;
      return;
    }

    // Check cached frameworks
    const cachedResult = findInCachedFrameworks(identifier, currentDocumentStore, documentStore);
    if (cachedResult) {
      externalItemData.value = cachedResult.item;
      externalFrameworkTitle.value = cachedResult.documentTitle;

      // Also add to cross-framework cache
      crossFrameworkItemCache.set(identifier, {
        item: cachedResult.item,
        documentId: cachedResult.documentId,
        documentTitle: cachedResult.documentTitle,
        fetchedAt: new Date()
      });
      return;
    }

    // Check if already loading
    if (isLoading.value) return;

    isLoading.value = true;
    fetchError.value = null;

    try {
      // Fetch the item directly by URI
      let itemData = null;
      let docId = null;
      let docTitle = null;

      try {
        itemData = await fetchItemByUri(uri);
        // Extract document info from CFDocumentURI
        const documentUri = itemData?.CFDocumentURI;
        docTitle = documentUri?.title || null;
        docId = documentUri?.identifier || null;
      } catch (err) {
        logger.warn(`[CrossFramework] Failed to fetch external item details directly:`, err);
        fetchError.value = err;
        externalItemData.value = null;
        isLoading.value = false;
        return;
      }

      // If item data didn't have doc ID but association did, use association's
      if (!docId && associationCFDocumentURI.value) {
        docId = associationCFDocumentURI.value.identifier;
        docTitle = associationCFDocumentURI.value.title || docTitle;
      }

      if (itemData) {
        // Cache the item
        crossFrameworkItemCache.set(identifier, {
          item: itemData,
          documentId: docId,
          documentTitle: docTitle,
          fetchedAt: new Date()
        });

        externalItemData.value = itemData;
      }

      // If we have document info, fetch and cache the document
      if (docId) {
        try {
          const packageData = await documentStore.fetchDocument(docId);
          if (packageData && packageData.CFDocument) {
            docTitle = packageData.CFDocument.title;
            externalFrameworkTitle.value = docTitle;

            // Transform items and cache in associatedDocuments
            const items = currentDocumentStore.transformCASEItems(
              packageData.CFItems || [],
              packageData.CFAssociations || [],
              packageData.CFDocument.identifier
            );
            currentDocumentStore.associatedDocuments.set(packageData.CFDocument.identifier, {
              ...packageData.CFDocument,
              items: items.items
            });

            // If we didn't get item data directly, try to extract it from the newly fetched document
            if (!itemData) {
              const found = findItemById(items.items, identifier);
              if (found) {
                itemData = found;
                crossFrameworkItemCache.set(identifier, {
                  item: itemData,
                  documentId: docId,
                  documentTitle: docTitle,
                  fetchedAt: new Date()
                });
                externalItemData.value = itemData;
              }
            }
          }
        } catch (docErr) {
          console.error(`Failed to fetch associated document:`, docErr);
        }
      }

      if (!itemData) {
        fetchError.value = "Failed to load item details";
      }

    } catch (error) {
      console.warn(`Unexpected error in loadExternalItem:`, error);
      fetchError.value = {
        type: error?.status === 403 ? 'permission' :
          error?.status === 404 ? 'not_found' : 'network',
        message: error?.message || "Failed to load item details",
        status: error?.status
      };
    } finally {
      isLoading.value = false;
    }
  }

  /**
   * Force reload the external item
   */
  async function reload() {
    externalItemData.value = null;
    externalFrameworkTitle.value = null;
    fetchError.value = null;

    // Clear from cache
    const identifier = itemIdentifier.value;
    if (identifier) {
      crossFrameworkItemCache.delete(identifier);
    }

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
      if (isCrossFramework.value && !itemInCurrentDocument.value && targetTypeInfo.value.isCase) {
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
 * Clear the cross-framework item cache
 */
export function clearCrossFrameworkItemCache() {
  crossFrameworkItemCache.clear();
}

/**
 * Get cache size
 */
export function getCrossFrameworkItemCacheSize() {
  return crossFrameworkItemCache.size;
}

export default useCrossFrameworkItem;
