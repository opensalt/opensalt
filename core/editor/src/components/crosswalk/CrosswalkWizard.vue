<template>
  <div class="crosswalk-wizard">
    <!-- Step 1: Origin & Destination -->
    <div class="mb-4">
      <h5>Step 1: Select Frameworks</h5>
      <div class="row g-3">
        <div class="col-md-6">
          <label
            for="originFramework"
            class="form-label"
          >Origin Framework</label>
          <select
            id="originFramework"
            v-model="form.originId"
            class="form-select"
          >
            <option
              v-for="doc in availableDocuments"
              :key="doc.id"
              :value="doc.id"
            >
              {{ doc.title }}
            </option>
          </select>
        </div>
        <div class="col-md-6">
          <label
            for="destinationFramework"
            class="form-label"
          >Destination Framework</label>
          <select
            id="destinationFramework"
            v-model="form.destinationId"
            class="form-select"
          >
            <option
              v-for="doc in availableDocuments"
              :key="doc.id"
              :value="doc.id"
            >
              {{ doc.title }}
            </option>
          </select>
        </div>
      </div>
      <small class="text-muted">
        Both frameworks should have vector embeddings generated. Items without embeddings will be skipped.
      </small>
    </div>

    <!-- Step 2: Crosswalk Framework & Thresholds -->
    <div class="mb-4">
      <h5>Step 2: Configure Crosswalk</h5>
      <div class="mb-3">
        <div class="form-check">
          <input
            id="createNewFramework"
            v-model="form.createNewFramework"
            type="radio"
            :value="true"
            class="form-check-input"
          >
          <label
            for="createNewFramework"
            class="form-check-label"
          >
            Create new crosswalk framework
          </label>
        </div>
        <div class="form-check">
          <input
            id="useExistingFramework"
            v-model="form.createNewFramework"
            type="radio"
            :value="false"
            class="form-check-input"
          >
          <label
            for="useExistingFramework"
            class="form-check-label"
          >
            Use existing framework
          </label>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label
            for="threshold"
            class="form-label"
          >Minimum Similarity Threshold: {{ (form.threshold * 100).toFixed(0) }}%</label>
          <input
            id="threshold"
            v-model.number="form.threshold"
            type="range"
            min="0.50"
            max="0.85"
            step="0.05"
            class="form-range"
          >
        </div>
        <div class="col-md-6">
          <label
            for="exactMatchThreshold"
            class="form-label"
          >Exact Match Threshold: {{ (form.exactMatchThreshold * 100).toFixed(0) }}%</label>
          <input
            id="exactMatchThreshold"
            v-model.number="form.exactMatchThreshold"
            type="range"
            min="0.80"
            max="0.98"
            step="0.01"
            class="form-range"
          >
        </div>
      </div>
    </div>

    <!-- Step 3: Preview & Submit -->
    <div class="mb-4">
      <h5>Step 3: Review & Create</h5>
      <div class="d-flex gap-2">
        <button
          type="button"
          class="btn btn-outline-secondary"
          data-testid="preview-btn"
          :disabled="!canPreview"
          @click="onPreview"
        >
          <i class="bi bi-eye me-1" />
          Preview Matches
        </button>
        <button
          type="button"
          class="btn btn-primary"
          data-testid="create-crosswalk-btn"
          :disabled="!canCreate"
          @click="onCreate"
        >
          <i class="bi bi-magic me-1" />
          Create Crosswalk
        </button>
      </div>
    </div>

    <!-- Estimation Results -->
    <EstimationResults
      v-if="estimateResult"
      :result="estimateResult"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useDocumentStore } from '@/stores/documentStore';
import { api } from '@/services/api.js';
import EstimationResults from './EstimationResults.vue';

const route = useRoute();
const documentStore = useDocumentStore();

const emit = defineEmits(['create']);

const availableDocuments = ref([]);
const estimateResult = ref(null);

const form = ref({
  originId: '',
  destinationId: '',
  createNewFramework: true,
  existingFrameworkId: null,
  threshold: 0.75,
  exactMatchThreshold: 0.90,
});

const canPreview = computed(() => {
  return form.value.originId && form.value.destinationId && form.value.originId !== form.value.destinationId;
});

const canCreate = computed(() => {
  return canPreview.value && form.value.exactMatchThreshold >= form.value.threshold;
});

onMounted(async () => {
  if (documentStore.documents.length === 0) {
    await documentStore.fetchDocuments();
  }
  availableDocuments.value = documentStore.documents;
  form.value.originId = route.params.frameworkId || '';
});

async function onPreview() {
  try {
    const data = await api.get(
      `/api/vector-search/crosswalk/estimate?origin=${form.value.originId}&destination=${form.value.destinationId}&threshold=${form.value.threshold}`
    );
    estimateResult.value = data;
  } catch (err) {
    console.error('Failed to get estimate:', err);
  }
}

function onCreate() {
  emit('create', {
    originId: form.value.originId,
    destinationId: form.value.destinationId,
    crosswalkId: form.value.createNewFramework ? null : form.value.existingFrameworkId,
    threshold: form.value.threshold,
    exactMatchThreshold: form.value.exactMatchThreshold,
  });
}
</script>
