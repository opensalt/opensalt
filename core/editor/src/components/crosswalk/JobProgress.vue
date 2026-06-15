<template>
  <div class="job-progress">
    <!-- Queued: waiting for the async worker to pick up the job -->
    <div
      v-if="jobState.status === 'queued'"
      class="d-flex align-items-center gap-2 mb-3"
    >
      <div
        class="spinner-border spinner-border-sm text-primary"
        role="status"
      />
      <span>Queued — waiting for processing to start…</span>
    </div>

    <div
      v-else-if="jobState.status === 'running'"
      class="mb-3"
    >
      <div class="d-flex justify-content-between mb-1">
        <small>Processing item {{ jobState.progress.processed }} of {{ jobState.progress.total }}</small>
        <small>{{ percentage }}%</small>
      </div>
      <div
        class="progress"
        style="height: 24px;"
      >
        <div
          class="progress-bar progress-bar-striped progress-bar-animated"
          :class="progressBarClass"
          role="progressbar"
          :style="{ width: percentage + '%' }"
          :aria-valuenow="percentage"
          aria-valuemin="0"
          aria-valuemax="100"
        >
          {{ percentage }}%
        </div>
      </div>
      <p class="mt-2 text-muted">
        <span>Matches created: {{ jobState.progress.matched }}</span>
        <span v-if="jobState.progress.exact_match_items">
          ({{ jobState.progress.exact_match_items }} exact, {{ jobState.progress.related_items }} related)
        </span>
      </p>
      <p
        v-if="jobState.progress.skipped_no_embedding"
        class="text-muted small"
      >
        Items skipped (no embedding): {{ jobState.progress.skipped_no_embedding }}
      </p>
      <button
        type="button"
        class="btn btn-outline-danger btn-sm"
        @click="onCancel"
      >
        Cancel
      </button>
    </div>

    <div
      v-else-if="['completed', 'partial'].includes(jobState.status)"
      class="alert alert-success"
    >
      <h6><i class="bi bi-check-circle me-2" />Crosswalk Complete!</h6>
      <p class="mb-2">
        {{ jobState.progress.matched }} matches created from {{ jobState.progress.total }} origin items
        ({{ jobState.progress.exact_match_items }} exact, {{ jobState.progress.related_items }} related).
      </p>
      <p
        v-if="jobState.status === 'partial' && jobState.error"
        class="mb-2 text-warning small"
      >
        Completed with warnings: {{ jobState.error }}
      </p>
      <button
        type="button"
        class="btn btn-primary btn-sm"
        @click="emit('review')"
      >
        <i class="bi bi-check2-square me-1" />
        Review Crosswalk
      </button>
    </div>

    <div
      v-else-if="jobState.status === 'failed'"
      class="alert alert-danger"
    >
      <h6><i class="bi bi-exclamation-triangle me-2" />Crosswalk Failed</h6>
      <p class="mb-0">
        {{ jobState.error || 'An error occurred during processing.' }}
      </p>
    </div>

    <div
      v-else-if="jobState.status === 'cancelled'"
      class="alert alert-warning"
    >
      <h6><i class="bi bi-x-circle me-2" />Crosswalk Cancelled</h6>
      <p class="mb-0">
        Processing was cancelled. {{ jobState.progress.matched }} matches were created before cancellation.
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { useCrosswalkJob } from '@/composables/useCrosswalkJob.js';

const props = defineProps({
  jobId: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['complete', 'cancel', 'review']);

const { jobState, resumeJob, cancelJob } = useCrosswalkJob();

const percentage = computed(() => {
  if (!jobState.value.progress.total) return 0;
  return Math.round((jobState.value.progress.processed / jobState.value.progress.total) * 100);
});

const progressBarClass = computed(() => {
  if (percentage.value >= 75) return 'bg-success';
  if (percentage.value >= 40) return 'bg-info';
  return 'bg-primary';
});

onMounted(() => {
  if (props.jobId) {
    resumeJob(props.jobId);
  }
});

watch(
  () => jobState.value.status,
  (status) => {
    if (['completed', 'partial'].includes(status)) {
      emit('complete', { ...jobState.value.progress });
    }
  }
);

function onCancel() {
  cancelJob();
  emit('cancel');
}
</script>
