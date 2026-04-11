import { ref, computed } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';
import { useEditorContextStore } from '../stores/editorContextStore';

const FETCH_STATUS = {
  PENDING: 'pending',
  LOADING: 'loading',
  COMPLETED: 'completed',
  ERROR: 'error'
};

const PRIORITY = {
  HIGH: 'HIGH',
  NORMAL: 'NORMAL'
};

const relatedDocumentsCache = new Map();

export function useRelatedFrameworksQueue() {
  const queue = ref([]);
  const isRunning = ref(false);
  const isPaused = ref(false);
  const activeRequestCount = ref(0);
  const isProcessing = computed(() => activeRequestCount.value > 0);

  const queueStats = computed(() => ({
    total: queue.value.length,
    pending: 0,
    loading: 0,
    completed: queue.value.length,
    error: 0,
    highPriority: 0,
    normalPriority: queue.value.length
  }));

  function isItemQueued(_identifier) {
    return false;
  }

  function getFetchStatus(_identifier) {
    return null;
  }

  function addToQueue(_item) {
    return false;
  }

  function updateItemPriority(_identifier, _newPriority) {
    return false;
  }

  function startQueue() {}

  function pauseQueue() {}

  function resumeQueue() {}

  function clearQueue() {
    queue.value = [];
    isRunning.value = false;
    isPaused.value = false;
    activeRequestCount.value = 0;
  }

  async function getAssociatedDocumentIdentifiers() {
    return [];
  }

  async function setHighPriorityForAssociatedFrameworks() {}

  async function fetchAndQueueRelatedDocuments(identifier) {
    if (!identifier) return [];

    try {
      const cached = relatedDocumentsCache.get(identifier);
      if (cached) {
        return cached;
      }

      activeRequestCount.value++;
      const relatedDocs = await api.getRelatedDocuments(identifier);

      if (Array.isArray(relatedDocs)) {
        relatedDocumentsCache.set(identifier, relatedDocs);
        queue.value = relatedDocs.map(doc => ({
          identifier: doc.identifier,
          title: doc.title || doc.identifier,
          fetchStatus: FETCH_STATUS.COMPLETED
        }));

        const contextStore = useEditorContextStore();
        for (const doc of relatedDocs) {
          if (doc.identifier && !contextStore.documentRegistry.has(doc.identifier)) {
            contextStore.registerDocumentMetadata({
              identifier: doc.identifier,
              uri: doc.uri || '',
              title: doc.title || doc.identifier,
              frameworkId: doc.identifier,
            });
          }
        }

        return relatedDocs;
      }

      return [];
    } catch (error) {
      logger.error(`Failed to fetch related documents for ${identifier}:`, error);
      return [];
    } finally {
      activeRequestCount.value--;
    }
  }

  function cleanup() {
    clearQueue();
    relatedDocumentsCache.clear();
  }

  return {
    queue,
    isRunning,
    isPaused,
    isProcessing,
    activeRequestCount,
    queueStats,

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

export { PRIORITY, FETCH_STATUS };
