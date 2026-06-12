import { ref, onUnmounted } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';

export function useCrosswalkJob() {
  const jobState = ref({
    status: 'idle',
    jobId: null,
    error: null,
    progress: {
      total: 0,
      processed: 0,
      matched: 0,
      exactMatchItems: 0,
      relatedItems: 0,
      skipped: 0,
      failed: 0,
    },
  });

  let eventSource = null;
  let pollInterval = null;

  function connectToMercure(jobId) {
    if (typeof EventSource === 'undefined') {
      logger.warn('EventSource not supported, falling back to polling');
      startPolling(jobId);
      return;
    }

    const url = new URL(
      `${window.location.protocol}//${window.location.host}/.well-known/mercure`
    );
    url.searchParams.append('topic', `crosswalk-progress/${jobId}`);

    eventSource = new EventSource(url);

    eventSource.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        updateFromMercure(data);
      } catch (e) {
        logger.error('Failed to parse Mercure message:', e);
      }
    };

    eventSource.onerror = () => {
      logger.warn('Mercure connection error, falling back to polling');
      if (eventSource) {
        eventSource.close();
        eventSource = null;
      }
      startPolling(jobId);
    };
  }

  function updateFromMercure(data) {
    jobState.value.status = data.status;
    if (data.progress) {
      jobState.value.progress = data.progress;
    }
  }

  function startPolling(jobId) {
    stopPolling();
    pollInterval = setInterval(async () => {
      try {
        const data = await api.get(`/api/vector-search/crosswalk/${jobId}`);
        updateFromMercure(data);
        if (['completed', 'partial', 'failed', 'cancelled'].includes(data.status)) {
          stopPolling();
        }
      } catch (e) {
        logger.error('Polling error:', e);
      }
    }, 5000);
  }

  function stopPolling() {
    if (pollInterval) {
      clearInterval(pollInterval);
      pollInterval = null;
    }
  }

  function disconnect() {
    if (eventSource) {
      eventSource.close();
      eventSource = null;
    }
    stopPolling();
  }

  async function startJob(jobId) {
    jobState.value = {
      status: 'running',
      jobId,
      error: null,
      progress: { total: 0, processed: 0, matched: 0, exactMatchItems: 0, relatedItems: 0, skipped: 0, failed: 0 },
    };
    connectToMercure(jobId);
  }

  async function resumeJob(jobId) {
    try {
      const data = await api.get(`/api/vector-search/crosswalk/${jobId}`);
      jobState.value.jobId = jobId;
      updateFromMercure(data);
      if (!['completed', 'partial', 'failed', 'cancelled'].includes(data.status)) {
        connectToMercure(jobId);
      }
    } catch (e) {
      logger.error('Failed to resume job:', e);
    }
  }

  async function cancelJob() {
    if (!jobState.value.jobId) return;
    try {
      await api.delete(`/api/vector-search/crosswalk/${jobState.value.jobId}`);
      jobState.value.status = 'cancelled';
    } catch (e) {
      logger.error('Failed to cancel job:', e);
    }
    disconnect();
  }

  onUnmounted(disconnect);

  return {
    jobState,
    startJob,
    resumeJob,
    cancelJob,
  };
}
