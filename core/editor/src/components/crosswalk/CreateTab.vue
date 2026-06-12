<template>
  <div class="create-tab flex-grow-1 overflow-auto">
    <CrosswalkWizard
      v-if="state === 'wizard'"
      @create="onCreate"
    />
    <JobProgress
      v-else-if="state === 'progress'"
      :job-id="jobId"
      @complete="onComplete"
      @cancel="onCancel"
    />
    <div
      v-else-if="state === 'complete'"
      class="alert alert-success"
    >
      <h5><i class="bi bi-check-circle me-2" />Crosswalk Complete!</h5>
      <p>{{ summary }}</p>
      <button
        type="button"
        class="btn btn-primary"
        @click="openCrosswalk"
      >
        Open Crosswalk Framework
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '@/services/api.js';
import CrosswalkWizard from './CrosswalkWizard.vue';
import JobProgress from './JobProgress.vue';

const router = useRouter();
const state = ref('wizard');
const jobId = ref(null);
const summary = ref('');

async function onCreate(config) {
  try {
    const data = await api.post('/api/vector-search/crosswalk', config);
    jobId.value = data.job_id;
    state.value = 'progress';
  } catch (err) {
    console.error('Failed to create crosswalk:', err);
  }
}

function onComplete(result) {
  summary.value = `Crosswalk complete! ${result.matched} matches created from ${result.total} origin items.`;
  state.value = 'complete';
}

function onCancel() {
  state.value = 'wizard';
  jobId.value = null;
}

function openCrosswalk() {
  router.push(`/${jobId.value}/crosswalk?tab=review`);
}
</script>
