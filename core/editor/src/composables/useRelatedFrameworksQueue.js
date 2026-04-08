/**
 * useRelatedFrameworksQueue Composable
 *
 * Manages the queuing system for fetching related frameworks.
 * Implements priority-based queuing, worker pool concurrency, retry logic,
 * and fair scheduling to prevent queue starvation.
 */
import { ref, computed } from 'vue';

import { useDocumentStore } from '../stores/documentStore';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { useEditorContextStore } from '../stores/editorContextStore';
import { api } from '../services/api.js';
import { localFrameworkDb } from '../services/localFrameworkDb.js';
import { logger } from '../utils/logger.js';

// Constants from design document
const MAX_CONCURRENT_REQUESTS = 3;
const MAX_RETRIES = 3;
const RETRY_DELAYS = [1000, 2000, 4000]; // Exponential backoff: 1s, 2s, 4s
const FAIR_SCHEDULING_RATIO = { high: 2, normal: 1 }; // Process 2 high, then 1 normal
const RESET_RATIO_AFTER = 3; // Reset ratio counters after processing 3 items (2 high + 1 normal)
const RELATED_DOCS_SESSION_TTL_MS = 5 * 60 * 1000;

// Priority levels
const PRIORITY = {
  HIGH: 'HIGH',
  NORMAL: 'NORMAL'
};

// Fetch status types
const FETCH_STATUS = {
  PENDING: 'pending',
  LOADING: 'loading',
  COMPLETED: 'completed',
  ERROR: 'error'
};

// Track frameworks whose related documents have already been fetched from the API this session
const sessionFetchedFrameworks = new Set();
const pendingRelatedDocumentRequests = new Map();
const sessionRelatedDocumentsCache = new Map();

/**
 * Queue item structure
 * @typedef {Object} QueueItem
 * @property {string} identifier - Document identifier
 * @property {string} uri - Document URI
 * @property {string} CFPackageURI - Package URI
 * @property {string} title - Document title
 * @property {string} priority - 'HIGH' or 'NORMAL'
 * @property {number} retryCount - Number of retry attempts
 * @property {Date|null} lastAttempt - Last attempt timestamp
 * @property {string} fetchStatus - Current fetch status
 */

/**
 * useRelatedFrameworksQueue Composable
 *
 * @param {Object} options - Configuration options
 * @returns {Object} Queue state and methods
 */
export function useRelatedFrameworksQueue() {
  const documentStore = useDocumentStore();
  const currentDocumentStore = useCurrentDocumentStore();
  const contextStore = useEditorContextStore();

  // Queue state
  const queue = ref([]);
  const isRunning = ref(false);
  const isPaused = ref(false);
  const activeRequestCount = ref(0);

  // Backward compatibility: isProcessing is true when any requests are active
  const isProcessing = computed(() => activeRequestCount.value > 0);

  // Fair scheduling state (instance-specific to avoid sharing between queue instances)
  let highPriorityProcessed = 0;
  let normalPriorityProcessed = 0;

  // Queue item cache to prevent duplication
  const queueItemCache = new Map();

  // Fetch status tracking for each document
  const fetchStatusMap = new Map();

  // Statistics
  const queueStats = computed(() => {
    const highPriorityCount = queue.value.filter(item => item.priority === PRIORITY.HIGH).length;
    const normalPriorityCount = queue.value.filter(item => item.priority === PRIORITY.NORMAL).length;
    const completedCount = queue.value.filter(item => item.fetchStatus === FETCH_STATUS.COMPLETED).length;
    const errorCount = queue.value.filter(item => item.fetchStatus === FETCH_STATUS.ERROR).length;

    return {
      total: queue.value.length,
      pending: queue.value.filter(item => item.fetchStatus === FETCH_STATUS.PENDING).length,
      loading: queue.value.filter(item => item.fetchStatus === FETCH_STATUS.LOADING).length,
      completed: completedCount,
      error: errorCount,
      highPriority: highPriorityCount,
      normalPriority: normalPriorityCount
    };
  });

  /**
   * Check if document is already in the queue
   * @param {string} identifier - Document identifier
   * @returns {boolean} - True if already queued
   */
  function isItemQueued(identifier) {
    return queueItemCache.has(identifier);
  }

  /**
   * Get fetch status for a document
   * @param {string} identifier - Document identifier
   * @returns {string|null} - Fetch status or null
   */
  function getFetchStatus(identifier) {
    return fetchStatusMap.get(identifier) || null;
  }

  /**
   * Update fetch status for a document
   * @param {string} identifier - Document identifier
   * @param {string} status - New status
   */
  function updateFetchStatus(identifier, status) {
    fetchStatusMap.set(identifier, status);

    // Also update queue item status
    const queueItem = queue.value.find(item => item.identifier === identifier);
    if (queueItem) {
      queueItem.fetchStatus = status;
    }
  }

  /**
   * Add a document to the queue
   * @param {Object} item - Document item to add
   * @param {string} item.identifier - Document identifier
   * @param {string} item.uri - Document URI
   * @param {string} item.CFPackageURI - Package URI
   * @param {string} item.title - Document title
   * @param {string} item.priority - 'HIGH' or 'NORMAL'
   * @returns {boolean} - True if added, false if already queued
   */
  function addToQueue(item) {
    const { identifier, uri, CFPackageURI, title, priority = PRIORITY.NORMAL } = item;

    // Check if already queued
    if (isItemQueued(identifier)) {
      logger.debug(`Document ${identifier} already in queue, skipping`);
      return false;
    }

    // Check if already fully loaded in contextStore (loadedPackages has the full package)
    // NOTE: Do NOT check documentRegistry here — it's populated at startup with metadata
    // for ALL documents via fetchDocuments(), which would cause ALL related frameworks
    // to be skipped even though their full packages haven't been loaded.
    if (contextStore.loadedPackages.has(identifier)) {
      logger.debug(`Document ${identifier} already loaded (full package), marking as completed`);
      updateFetchStatus(identifier, FETCH_STATUS.COMPLETED);
      queueItemCache.set(identifier, true);
      return false;
    }

    // Create queue item
    const queueItem = {
      identifier,
      uri,
      CFPackageURI,
      title,
      priority,
      retryCount: 0,
      lastAttempt: null,
      fetchStatus: FETCH_STATUS.PENDING
    };

    // Add to queue
    queue.value.push(queueItem);
    queueItemCache.set(identifier, true);
    fetchStatusMap.set(identifier, FETCH_STATUS.PENDING);

    logger.debug(`Added document ${identifier} to queue with ${priority} priority`);
    return true;
  }

  /**
   * Update priority of an existing queue item
   * @param {string} identifier - Document identifier
   * @param {string} newPriority - New priority ('HIGH' or 'NORMAL')
   * @returns {boolean} - True if updated, false if not found
   */
  function updateItemPriority(identifier, newPriority) {
    const item = queue.value.find(item => item.identifier === identifier);
    if (!item) {
      logger.warn(`Queue item ${identifier} not found for priority update`);
      return false;
    }

    const oldPriority = item.priority;
    item.priority = newPriority;
    logger.debug(`Updated priority for ${identifier}: ${oldPriority} -> ${newPriority}`);
    return true;
  }

  /**
   * Get next item to process using fair scheduling
   * Implements fair scheduling: maintains 2:1 ratio of high:normal priority
   * @returns {Object|null} - Queue item to process or null if none available
   */
  function getNextItem() {
    if (queue.value.length === 0) {
      return null;
    }

    // Get only pending items
    const pendingItems = queue.value.filter(item => item.fetchStatus === FETCH_STATUS.PENDING);

    if (pendingItems.length === 0) {
      return null;
    }

    // Sort by retry count (fewer retries first) to prioritize fresh items
    pendingItems.sort((a, b) => a.retryCount - b.retryCount);

    // Separate by priority
    const highPriorityItems = pendingItems.filter(item => item.priority === PRIORITY.HIGH);
    const normalPriorityItems = pendingItems.filter(item => item.priority === PRIORITY.NORMAL);

    // Determine which priority to select based on fair scheduling ratio
    // We want 2 high priority for every 1 normal priority
    const totalProcessed = highPriorityProcessed + normalPriorityProcessed;

    if (totalProcessed >= RESET_RATIO_AFTER) {
      // Reset counters after completing a full cycle
      highPriorityProcessed = 0;
      normalPriorityProcessed = 0;
    }

    let selectedItem = null;

    // Fair scheduling: prefer high priority until we've processed 2, then take 1 normal
    if (highPriorityProcessed < FAIR_SCHEDULING_RATIO.high && highPriorityItems.length > 0) {
      selectedItem = highPriorityItems[0];
      highPriorityProcessed++;
    } else if (normalPriorityItems.length > 0) {
      selectedItem = normalPriorityItems[0];
      normalPriorityProcessed++;
    } else if (highPriorityItems.length > 0) {
      // No normal priority items available, take high priority
      selectedItem = highPriorityItems[0];
      highPriorityProcessed++;
    }

    if (selectedItem) {
      // Mark as loading immediately to prevent duplicate selection
      selectedItem.fetchStatus = FETCH_STATUS.LOADING;
    }

    return selectedItem;
  }

  /**
   * Fetch a single document
   * @param {Object} item - Queue item to fetch
   * @returns {Promise<void>}
   */
  async function fetchDocument(item) {
    const { identifier, retryCount } = item;

    // Note: activeRequestCount is incremented by processQueue before calling this function
    // to ensure atomic slot reservation and prevent race conditions

    try {
      updateFetchStatus(identifier, FETCH_STATUS.LOADING);
      item.lastAttempt = new Date();

      // Fetch document using documentStore (which handles IndexedDB cache OR API load via contextStore)
      const pkg = await documentStore.loadPackage(identifier);
      if (!pkg) {
        throw new Error(`Failed to load package ${identifier}`);
      }

      // Mark as completed
      updateFetchStatus(identifier, FETCH_STATUS.COMPLETED);
      logger.debug(`Successfully fetched document ${identifier}`);

      fetchAndQueueRelatedDocuments(identifier).catch(error => {
        logger.warn(`Failed to recursively queue related frameworks for ${identifier}:`, error);
      });

      if (contextStore.activeWriteDocumentId) {
        currentDocumentStore.reloadActiveDocument();
      }

      // Note: loadPackage already populates registries. 
      // The associatedDocuments in currentDocumentStore is now secondary or can be removed in Phase 5.
      // For now, we keep it for compatibility if needed.

    } catch (error) {
      logger.error(`Failed to fetch document ${identifier}:`, error);

      // Check if we should retry
      if (retryCount < MAX_RETRIES) {
        item.retryCount++;
        item.fetchStatus = FETCH_STATUS.PENDING;
        updateFetchStatus(identifier, FETCH_STATUS.PENDING);

        const delay = RETRY_DELAYS[retryCount];
        logger.debug(`Scheduling retry for ${identifier} in ${delay}ms`);

        // Schedule retry - retry will be picked up by processQueue when slot is available
        setTimeout(() => {
          if (isRunning.value && !isPaused.value) {
            processQueue();
          }
        }, delay);

      } else {
        // Max retries reached, mark as error
        updateFetchStatus(identifier, FETCH_STATUS.ERROR);
        logger.error(`Max retries reached for document ${identifier}`);
      }
    } finally {
      // Decrement active request count and trigger next processing
      activeRequestCount.value--;
      logger.debug(`Document ${identifier} fetch completed, active requests: ${activeRequestCount.value}`);

      // Trigger processing of next item if queue is still running and has items
      if (isRunning.value && !isPaused.value) {
        // Use setTimeout to avoid deep recursion and allow other tasks to run
        setTimeout(() => {
          processQueue();
        }, 0);
      }
    }
  }

  /**
   * Process the queue using worker pool pattern
   * Continuously fills up to MAX_CONCURRENT_REQUESTS slots
   * @returns {void}
   */
  function processQueue() {
    if (!isRunning.value || isPaused.value) {
      return;
    }

    // Fill available slots up to MAX_CONCURRENT_REQUESTS
    while (activeRequestCount.value < MAX_CONCURRENT_REQUESTS) {
      // Get next item using fair scheduling
      const item = getNextItem();

      if (!item) {
        // No more pending items available
        break;
      }

      // Reserve slot before launching async operation to prevent race condition
      activeRequestCount.value++;

      // Start fetching (fetchDocument handles its own async execution)
      // We don't await here - let it run concurrently
      fetchDocument(item).catch(() => {
        // Ensure decrement and next processing on synchronous errors
        // (async errors are handled in fetchDocument's finally block)
      });
    }

    // Check if all items are processed
    const hasPendingItems = queue.value.some(item => item.fetchStatus === FETCH_STATUS.PENDING);
    const hasLoadingItems = queue.value.some(item => item.fetchStatus === FETCH_STATUS.LOADING);

    if (!hasPendingItems && !hasLoadingItems && queue.value.length > 0) {
      // All items processed, stop queue
      isRunning.value = false;
      // Reset fair scheduling counters
      highPriorityProcessed = 0;
      normalPriorityProcessed = 0;
      logger.debug('Queue processing completed');
    }
  }

  /**
   * Start the queue processing
   */
  function startQueue() {
    if (isRunning.value) {
      logger.debug('Queue already running');
      return;
    }

    isRunning.value = true;
    isPaused.value = false;
    logger.debug('Starting queue processing');

    processQueue();
  }

  /**
   * Pause the queue processing
   */
  function pauseQueue() {
    if (!isRunning.value) {
      logger.debug('Queue not running, cannot pause');
      return;
    }

    isPaused.value = true;
    logger.debug('Queue paused');
  }

  /**
   * Resume the queue processing
   */
  function resumeQueue() {
    if (!isRunning.value) {
      logger.debug('Queue not running, cannot resume');
      return;
    }

    if (!isPaused.value) {
      logger.debug('Queue not paused, cannot resume');
      return;
    }

    isPaused.value = false;
    logger.debug('Queue resumed');

    processQueue();
  }

  /**
   * Clear the queue
   */
  function clearQueue() {
    queue.value = [];
    queueItemCache.clear();
    fetchStatusMap.clear();
    isRunning.value = false;
    isPaused.value = false;
    activeRequestCount.value = 0;

    // Reset fair scheduling counters
    highPriorityProcessed = 0;
    normalPriorityProcessed = 0;

    logger.debug('Queue cleared');
  }

  /**
   * Get associated document identifiers from current item's associations
   * Used to set HIGH priority for relevant frameworks
   * @returns {Array} - Array of document identifiers
   */
  async function getAssociatedDocumentIdentifiers() {
    const currentItem = currentDocumentStore.currentItem;
    if (!currentItem) {
      return [];
    }

    const identifiers = new Set();

    // Get associations for the current item
    let associations = [];
    if (await localFrameworkDb.hasPersistentClient()) {
      associations = await localFrameworkDb.getItemAssociations(
        currentItem.identifier,
        null
      );
    }
    if (!Array.isArray(associations) || associations.length === 0) {
      associations = contextStore.getAssociations(currentItem.identifier);
    }

    // Extract document identifiers from associations
    associations.forEach(regAssoc => {
      const assoc = regAssoc.association;
      const destId = assoc.destinationNodeURI?.identifier;
      const originId = assoc.originNodeURI?.identifier;

      // If we're looking at currentItem, find the OTHER end of the association
      const otherId = (originId === currentItem.identifier) ? destId : originId;

      if (otherId) {
        // Find which framework this otherId belongs to in the registry
        const resolved = contextStore.resolveEndpoint(otherId);
        if (resolved && resolved.frameworkId && resolved.frameworkId !== contextStore.activeWriteDocumentId) {
          identifiers.add(resolved.frameworkId);
        }
      }
    });

    return Array.from(identifiers);
  }

  /**
   * Set HIGH priority for frameworks associated with current item
   */
  async function setHighPriorityForAssociatedFrameworks() {
    const associatedIds = await getAssociatedDocumentIdentifiers();

    associatedIds.forEach(identifier => {
      updateItemPriority(identifier, PRIORITY.HIGH);
    });

    logger.debug(`Set HIGH priority for ${associatedIds.length} associated frameworks`);
  }

function getSessionRelatedDocuments(identifier) {
  const cached = sessionRelatedDocumentsCache.get(identifier);
  if (!cached) return null;

    if ((Date.now() - cached.cachedAt) > RELATED_DOCS_SESSION_TTL_MS) {
      sessionRelatedDocumentsCache.delete(identifier);
      return null;
    }

  return cached.data;
}

function setSessionRelatedDocuments(identifier, docs) {
    sessionRelatedDocumentsCache.set(identifier, {
      data: docs,
      cachedAt: Date.now()
    });
  }

  function queueRelatedDocuments(relatedDocs) {
    if (!Array.isArray(relatedDocs) || relatedDocs.length === 0) return;

    relatedDocs.forEach(doc => {
      addToQueue({
        identifier: doc.identifier,
        uri: doc.uri,
        CFPackageURI: doc.CFPackageURI,
        title: doc.title || doc.identifier,
        priority: PRIORITY.NORMAL
      });
    });

    startQueue();
  }

  async function fetchAndQueueRelatedDocuments(identifier) {
    try {
      logger.debug(`Fetching related documents for ${identifier}`);

      const sessionDocs = sessionFetchedFrameworks.has(identifier)
        ? getSessionRelatedDocuments(identifier)
        : null;
      if (sessionDocs) {
        logger.debug(`Using session-cached related documents for ${identifier}`);
        queueRelatedDocuments(sessionDocs);
        return sessionDocs;
      }

      // 1. Check cache first and queue if found to allow immediate processing
      const cachedDocs = await localFrameworkDb.getRelatedFrameworks(identifier);
      if (cachedDocs && Array.isArray(cachedDocs)) {
        logger.debug(`Found ${cachedDocs.length} cached related documents for ${identifier}`);
        queueRelatedDocuments(cachedDocs);
      }

      // 2. If already fetched from API this session, no revalidation needed — return immediately
      if (sessionFetchedFrameworks.has(identifier)) {
        logger.debug(`Already verified related documents for ${identifier} in this session, using cache`);
        return cachedDocs || [];
      }

      // 3. If we have cached data, fire revalidation in the background (don't block the caller)
      //    so the queue can start processing immediately with fresh-enough data.
      if (pendingRelatedDocumentRequests.has(identifier)) {
        logger.debug(`Related document fetch already in flight for ${identifier}, reusing existing request`);
        return await pendingRelatedDocumentRequests.get(identifier);
      }

      if (cachedDocs && cachedDocs.length > 0) {
        logger.debug(`Cache hit for ${identifier} — revalidating in background`);
        const request = revalidateRelatedDocuments(identifier, cachedDocs).finally(() => {
          pendingRelatedDocumentRequests.delete(identifier);
        });
        pendingRelatedDocumentRequests.set(identifier, request);
        request.catch(err => {
          logger.warn(`Background revalidation failed for ${identifier}:`, err);
        });
        return cachedDocs;
      }

      // 4. No cache — must fetch synchronously so the caller gets useful data
      logger.debug('No cache found, fetching api.getRelatedDocuments synchronously for:', identifier);
      const request = revalidateRelatedDocuments(identifier, null).finally(() => {
        pendingRelatedDocumentRequests.delete(identifier);
      });
      pendingRelatedDocumentRequests.set(identifier, request);
      return await request;

    } catch (error) {
      logger.error(`Error in fetchAndQueueRelatedDocuments for ${identifier}:`, error);
      return [];
    }
  }

  /**
   * Fetch fresh related documents from the API and update the cache/queue.
   * This runs asynchronously when called from a cache-hit path.
   * @param {string} identifier - Document identifier
   * @param {Array|null} existingCachedDocs - Previously cached docs (for fallback on error)
   * @returns {Promise<Array>}
   */
  async function revalidateRelatedDocuments(identifier, existingCachedDocs) {
    try {
      logger.debug('About to call api.getRelatedDocuments for fresh data');
      const relatedDocs = await api.getRelatedDocuments(identifier);
      logger.debug(`Related documents response:`, relatedDocs);

      if (Array.isArray(relatedDocs)) {
        // Mark as fetched from API during this session
        sessionFetchedFrameworks.add(identifier);
        setSessionRelatedDocuments(identifier, relatedDocs);

        // Update cache with fresh data
        await localFrameworkDb.setRelatedFrameworks(identifier, relatedDocs);

        queueRelatedDocuments(relatedDocs);
        return relatedDocs;
      } else {
        logger.warn('Related documents response is not an array:', relatedDocs);
        return existingCachedDocs || [];
      }
    } catch (err) {
      // If revalidation fails, we keep using what we already have from cache
      logger.warn(`Failed to fetch fresh related documents for ${identifier}, continuing with cache:`, err);
      return existingCachedDocs || [];
    }
  }

  /**
   * Clean up session caches and pending requests.
   * Should be called on framework switch or when the composable is no longer needed.
   */
  function cleanup() {
    clearQueue();
    sessionRelatedDocumentsCache.clear();
    pendingRelatedDocumentRequests.clear();
    // Note: sessionFetchedFrameworks is intentionally NOT cleared here
    // as it tracks what has been fetched from the API this session
    // and should persist across framework switches.
  }

  return {
    // State
    queue,
    isRunning,
    isPaused,
    isProcessing,
    activeRequestCount,
    queueStats,

    // Methods
    startQueue,
    pauseQueue,
    resumeQueue,
    clearQueue,
    cleanup,
    addToQueue,
    updateItemPriority,
    setHighPriorityForAssociatedFrameworks,
    fetchAndQueueRelatedDocuments,
    getFetchStatus,
    getQueueStatus: getFetchStatus
  };
}

// Export constants for use in other modules
export { PRIORITY, FETCH_STATUS };
