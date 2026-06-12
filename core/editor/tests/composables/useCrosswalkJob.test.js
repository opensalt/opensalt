import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { ref } from 'vue';

let mockEventSourceInstance = null;

vi.mock('pinia', () => ({
  defineStore: vi.fn(),
  storeToRefs: vi.fn((store) => ({
    currentDocument: store.currentDocument
  }))
}));

vi.mock('../../src/stores/currentDocumentStore', () => ({
  useCurrentDocumentStore: vi.fn(() => ({
    currentDocument: ref({ id: '42', title: 'Test Framework' })
  }))
}));

describe('useCrosswalkJob', () => {
  beforeEach(() => {
    mockEventSourceInstance = {
      close: vi.fn(),
    };
    vi.stubGlobal('EventSource', vi.fn(function() { return mockEventSourceInstance; }));
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('initializes with idle state', async () => {
    const { useCrosswalkJob } = await import('../../src/composables/useCrosswalkJob.js');
    const { jobState, startJob } = useCrosswalkJob();

    expect(jobState.value.status).toBe('idle');
  });

  it('transitions to running on job start', async () => {
    const { useCrosswalkJob } = await import('../../src/composables/useCrosswalkJob.js');
    const { jobState, startJob } = useCrosswalkJob();

    startJob('test-job-id');
    expect(jobState.value.status).toBe('running');
    expect(jobState.value.jobId).toBe('test-job-id');
  });

  it('updates progress on Mercure message', async () => {
    const { useCrosswalkJob } = await import('../../src/composables/useCrosswalkJob.js');
    const { jobState, startJob } = useCrosswalkJob();

    startJob('test-job-id');

    mockEventSourceInstance.onmessage({
      data: JSON.stringify({
        jobId: 'test-job-id',
        status: 'running',
        progress: { total: 100, processed: 50, matched: 40, exact_match_items: 10, related_items: 30, skipped: 5, failed: 0 }
      })
    });

    expect(jobState.value.progress.processed).toBe(50);
    expect(jobState.value.progress.matched).toBe(40);
  });

  it('transitions to completed on completion message', async () => {
    const { useCrosswalkJob } = await import('../../src/composables/useCrosswalkJob.js');
    const { jobState, startJob } = useCrosswalkJob();

    startJob('test-job-id');

    mockEventSourceInstance.onmessage({
      data: JSON.stringify({
        jobId: 'test-job-id',
        status: 'completed',
        progress: { total: 100, processed: 100, matched: 85, exact_match_items: 20, related_items: 65, skipped: 10, failed: 0 }
      })
    });

    expect(jobState.value.status).toBe('completed');
  });
});
