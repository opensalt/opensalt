<template>
  <div class="job-progress">
    <div class="progress" style="height: 24px;">
      <div
        class="progress-bar progress-bar-striped progress-bar-animated"
        role="progressbar"
        :style="{ width: progress + '%' }"
        :aria-valuenow="progress"
        aria-valuemin="0"
        aria-valuemax="100"
      >
        {{ progress }}%
      </div>
    </div>
    <p class="mt-2 text-muted">{{ status }}</p>
    <button
      type="button"
      class="btn btn-outline-danger btn-sm mt-2"
      @click="$emit('cancel')"
    >
      Cancel
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  jobId: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['complete', 'cancel']);

const progress = ref(0);
const status = ref('Starting...');
let pollInterval = null;

onMounted(() => {
  pollInterval = setInterval(async () => {
    try {
      const { api } = await import('@/services/api.js');
      const data = await api.get(`/api/vector-search/crosswalk/job/${props.jobId}`);
      progress.value = data.progress || 0;
      status.value = data.status || 'Processing...';
      if (data.progress >= 100 || data.status === 'complete') {
        clearInterval(pollInterval);
        emit('complete', data);
      }
    } catch (err) {
      console.error('Failed to poll job status:', err);
    }
  }, 2000);
});

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval);
  }
});
</script>
