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
      @review="openReviewTab"
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
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '@/services/api.js';
import { useCurrentDocumentStore } from '@/stores/currentDocumentStore';
import CrosswalkWizard from './CrosswalkWizard.vue';
import JobProgress from './JobProgress.vue';

const route = useRoute();
const router = useRouter();
const currentDocumentStore = useCurrentDocumentStore();
const state = ref('wizard');
const jobId = ref(null);
const summary = ref('');

const ACTIVE_STATUSES = ['queued', 'running'];

function storageKey() {
  return `salt:crosswalk:job:${route.params.frameworkId}`;
}

function rememberJob(id) {
  jobId.value = id;
  state.value = 'progress';
  try {
    sessionStorage.setItem(storageKey(), id);
  } catch (_e) {
    /* sessionStorage unavailable */
  }
}

function forgetJob() {
  jobId.value = null;
  try {
    sessionStorage.removeItem(storageKey());
  } catch (_e) {
    /* sessionStorage unavailable */
  }
}

async function onCreate(config) {
  try {
    const data = await api.post('/api/vector-search/crosswalk', {
      origin_identifier: config.originIdentifier,
      destination_identifier: config.destinationIdentifier,
      crosswalk_identifier: config.crosswalkIdentifier,
      threshold: config.threshold,
      exact_match_threshold: config.exactMatchThreshold,
      origin_leaf_only: config.originLeafOnly ?? false,
      destination_leaf_only: config.destinationLeafOnly ?? false,
    });
    rememberJob(data.job_id);
  } catch (err) {
    console.error('Failed to create crosswalk:', err);
  }
}

function invalidateAssociationCache() {
  const crosswalkDocId = route.params.frameworkId;
  if (crosswalkDocId) {
    currentDocumentStore.invalidateItemDetailsCache(crosswalkDocId);
  }
}

function onComplete(result) {
  summary.value = `Crosswalk complete! ${result.matched} matches created from ${result.total} origin items.`;
  invalidateAssociationCache();
  state.value = 'complete';
}

function onCancel() {
  forgetJob();
  state.value = 'wizard';
}

function openReviewTab() {
  router.push({ path: `/${route.params.frameworkId}/crosswalk`, query: { tab: 'review' } });
}

onMounted(async () => {
  let storedJobId = null;
  try {
    storedJobId = sessionStorage.getItem(storageKey());
  } catch (_e) {
    /* sessionStorage unavailable */
  }

  if (!storedJobId) {
    return;
  }

  try {
    const status = await api.get(`/api/vector-search/crosswalk/${storedJobId}`);
    if (ACTIVE_STATUSES.includes(status.status)) {
      jobId.value = storedJobId;
      state.value = 'progress';
    } else {
      forgetJob();
    }
  } catch (_err) {
    forgetJob();
  }
});
</script>
