/**
 * useRelatedFrameworksQueue Composable
 *
 * Manages the queuing system for fetching related frameworks.
 * Implements priority-based queuing, batch processing, retry logic,
 * and fair scheduling to prevent queue starvation.
 */
import { ref, computed } from 'vue';

/* global setTimeout */
import { useDocumentStore } from '../stores/documentStore';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';

// Constants from design document
const BATCH_SIZE = 3;
const BATCH_DELAY_MS = 500;
const MAX_RETRIES = 3;
const RETRY_DELAYS = [1000, 2000, 4000]; // Exponential backoff: 1s, 2s, 4s
const FAIR_SCHEDULING_RATIO = { high: 2, normal: 1 }; // Process 2 high, then 1 normal

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

  // Queue state
  const queue = ref([]);
  const isRunning = ref(false);
  const isPaused = ref(false);
  const isProcessing = ref(false);

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

    // Check if already cached in documentStore
    if (documentStore.documentCache.has(identifier)) {
      logger.debug(`Document ${identifier} already cached, marking as completed`);
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
   * Get next batch of items to process
   * Implements fair scheduling: 2 high priority, 1 normal
   * @returns {Array} - Array of queue items to process
   */
  function getNextBatch() {
    if (queue.value.length === 0) {
      return [];
    }

    const batch = [];

    // Sort queue by priority (HIGH first) and then by retry count
    const sortedQueue = [...queue.value].sort((a, b) => {
      // Sort by priority first
      const priorityOrder = { [PRIORITY.HIGH]: 0, [PRIORITY.NORMAL]: 1 };
      if (priorityOrder[a.priority] !== priorityOrder[b.priority]) {
        return priorityOrder[a.priority] - priorityOrder[b.priority];
      }
      // Then by retry count (fewer retries first)
      return a.retryCount - b.retryCount;
    });

    // Count processed items in current batch for fair scheduling
    let highProcessed = 0;
    let normalProcessed = 0;

    // Select items for batch with fair scheduling
    for (const item of sortedQueue) {
      if (item.fetchStatus !== FETCH_STATUS.PENDING) {
        continue;
      }

      // Check fair scheduling ratio
      if (item.priority === PRIORITY.HIGH && highProcessed >= FAIR_SCHEDULING_RATIO.high) {
        continue;
      }
      if (item.priority === PRIORITY.NORMAL && normalProcessed >= FAIR_SCHEDULING_RATIO.normal) {
        continue;
      }

      batch.push(item);
      item.fetchStatus = FETCH_STATUS.LOADING;

      // Track processed count
      if (item.priority === PRIORITY.HIGH) {
        highProcessed++;
      } else {
        normalProcessed++;
      }

      if (batch.length >= BATCH_SIZE) {
        break;
      }
    }

    return batch;
  }

  /**
   * Fetch a single document
   * @param {Object} item - Queue item to fetch
   * @returns {Promise<void>}
   */
  async function fetchDocument(item) {
    const { identifier, retryCount } = item;

    try {
      updateFetchStatus(identifier, FETCH_STATUS.LOADING);
      item.lastAttempt = new Date();

      logger.debug(`Fetching document ${identifier} (attempt ${retryCount + 1}/${MAX_RETRIES})`);

      // Fetch document using documentStore
      await documentStore.fetchDocument(identifier);

      // Mark as completed
      updateFetchStatus(identifier, FETCH_STATUS.COMPLETED);
      logger.debug(`Successfully fetched document ${identifier}`);

      // Populate reactive associatedDocuments for ItemDetails display
      const cachedPkg = documentStore.documentCache.get(identifier);
      if (cachedPkg && !currentDocumentStore.associatedDocuments.has(identifier)) {
        const items = currentDocumentStore.transformCASEItems(
          cachedPkg.CFItems || [],
          cachedPkg.CFAssociations || [],
          identifier
        );
        currentDocumentStore.associatedDocuments.set(identifier, {
          id: cachedPkg.CFDocument?.identifier || identifier,
          title: cachedPkg.CFDocument?.title || identifier,
          items: items,
          cfAssociations: cachedPkg.CFAssociations || []
        });
      }

    } catch (error) {
      logger.error(`Failed to fetch document ${identifier}:`, error);

      // Check if we should retry
      if (retryCount < MAX_RETRIES) {
        item.retryCount++;
        item.fetchStatus = FETCH_STATUS.PENDING;
        updateFetchStatus(identifier, FETCH_STATUS.PENDING);

        const delay = RETRY_DELAYS[retryCount];
        logger.debug(`Scheduling retry for ${identifier} in ${delay}ms`);

        // Schedule retry
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
    }
  }

  /**
   * Process a batch of items
   * @param {Array} batch - Batch of items to process
   * @returns {Promise<void>}
   */
  async function processBatch(batch) {
    if (batch.length === 0) {
      return;
    }

    isProcessing.value = true;

    try {
      // Process items in parallel within the batch
      await Promise.all(batch.map(item => fetchDocument(item)));
    } finally {
      isProcessing.value = false;
    }
  }

  /**
   * Process the queue
   * @returns {Promise<void>}
   */
  async function processQueue() {
    if (!isRunning.value || isPaused.value || isProcessing.value) {
      return;
    }

    // Get next batch
    const batch = getNextBatch();

    if (batch.length === 0) {
      // No pending items, check if all are completed or errored
      const allProcessed = queue.value.every(
        item => item.fetchStatus === FETCH_STATUS.COMPLETED || item.fetchStatus === FETCH_STATUS.ERROR
      );

      if (allProcessed && queue.value.length > 0) {
        // All items processed, stop queue
        isRunning.value = false;
        logger.debug('Queue processing completed');
      }

      return;
    }

    // Process the batch
    await processBatch(batch);

    // Schedule next batch after delay
    if (isRunning.value && !isPaused.value) {
      setTimeout(() => {
        processQueue();
      }, BATCH_DELAY_MS);
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
    isProcessing.value = false;

    logger.debug('Queue cleared');
  }

  /**
   * Get associated document identifiers from current item's associations
   * Used to set HIGH priority for relevant frameworks
   * @returns {Array} - Array of document identifiers
   */
  function getAssociatedDocumentIdentifiers() {
    const currentItem = currentDocumentStore.currentItem;
    if (!currentItem) {
      return [];
    }

    const identifiers = new Set();

    // Get associations for the current item
    const associations = currentDocumentStore.getAssociationsForItem(currentItem.identifier);
    if (!associations) {
      return [];
    }

    // Extract document identifiers from associations
    associations.forEach(assoc => {
      const nodeURI = assoc.destinationNodeURI || assoc.destination;
      if (nodeURI && nodeURI.identifier) {
        // For now, we can't easily extract document identifier from item identifier
        // This would require looking up the item to find its document
        // For this implementation, we'll return an empty array
        // In a future enhancement, we could maintain an item->document mapping
      }
    });

    return Array.from(identifiers);
  }

  /**
   * Set HIGH priority for frameworks associated with current item
   */
  function setHighPriorityForAssociatedFrameworks() {
    const associatedIds = getAssociatedDocumentIdentifiers();

    associatedIds.forEach(identifier => {
      updateItemPriority(identifier, PRIORITY.HIGH);
    });

    logger.debug(`Set HIGH priority for ${associatedIds.length} associated frameworks`);
  }

  /**
   * Fetch related documents for a document and add to queue
   * @param {string} identifier - Document identifier
   * @returns {Promise<Array>} - Array of related documents
   */
  async function fetchAndQueueRelatedDocuments(identifier) {
    try {
      logger.debug(`Fetching related documents for ${identifier}`);
      logger.debug('About to call api.getRelatedDocuments');
      const relatedDocs = await api.getRelatedDocuments(identifier);
      logger.debug(`Related documents response:`, relatedDocs);

      if (!Array.isArray(relatedDocs)) {
        logger.warn('Related documents response is not an array:', relatedDocs);
        return [];
      }

      logger.debug(`Adding ${relatedDocs.length} related documents to queue`);
      // Add all related documents to queue with NORMAL priority
      relatedDocs.forEach(doc => {
        addToQueue({
          identifier: doc.identifier,
          uri: doc.uri,
          CFPackageURI: doc.CFPackageURI,
          title: doc.title || doc.identifier,
          priority: PRIORITY.NORMAL
        });
      });

      logger.debug(`fetchAndQueueRelatedDocuments completed for ${identifier}`);
      return relatedDocs;

    } catch (error) {
      logger.error(`Failed to fetch related documents for ${identifier}:`, error);
      return [];
    }
  }

  return {
    // State
    queue,
    isRunning,
    isPaused,
    isProcessing,
    queueStats,

    // Methods
    startQueue,
    pauseQueue,
    resumeQueue,
    clearQueue,
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
