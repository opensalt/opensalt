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
        @click="openReviewTab"
      >
        Review Crosswalk
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '@/services/api.js';
import CrosswalkWizard from './CrosswalkWizard.vue';
import JobProgress from './JobProgress.vue';

const route = useRoute();
const router = useRouter();
const state = ref('wizard');
const jobId = ref(null);
const summary = ref('');

async function onCreate(config) {
  try {
    const data = await api.post('/api/vector-search/crosswalk', {
      origin_identifier: config.originIdentifier,
      destination_identifier: config.destinationIdentifier,
      crosswalk_identifier: config.crosswalkIdentifier,
      threshold: config.threshold,
      exact_match_threshold: config.exactMatchThreshold,
    });
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

function openReviewTab() {
  router.push({ path: `/${route.params.frameworkId}/crosswalk`, query: { tab: 'review' } });
}
</script>
