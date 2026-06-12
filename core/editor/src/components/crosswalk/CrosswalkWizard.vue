<template>
  <div class="crosswalk-wizard">
    <!-- Step 1: Origin & Destination Selection -->
    <div class="mb-4">
      <h5>Step 1: Select Frameworks</h5>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label
            for="originFramework"
            class="form-label"
          >Origin Framework</label>
          <select
            id="originFramework"
            v-model="form.originIdentifier"
            class="form-select"
          >
            <option value="">— Select origin framework —</option>
            <optgroup
              v-for="group in groupedDocuments"
              :key="'origin-' + group.creator"
              :label="group.creator"
            >
              <option
                v-for="doc in group.documents"
                :key="'origin-' + doc.identifier"
                :value="doc.identifier"
              >
                {{ doc.title || 'Unknown Name' }} ({{ doc.identifier || 'No Identifier' }})
              </option>
            </optgroup>
          </select>
        </div>
        <div class="col-md-6">
          <label
            for="destinationFramework"
            class="form-label"
          >Destination Framework</label>
          <select
            id="destinationFramework"
            v-model="form.destinationIdentifier"
            class="form-select"
          >
            <option value="">— Select destination framework —</option>
            <optgroup
              v-for="group in groupedDocuments"
              :key="'dest-' + group.creator"
              :label="group.creator"
            >
              <option
                v-for="doc in group.documents"
                :key="'dest-' + doc.identifier"
                :value="doc.identifier"
              >
                {{ doc.title || 'Unknown Name' }} ({{ doc.identifier || 'No Identifier' }})
              </option>
            </optgroup>
          </select>
        </div>
      </div>
      <div class="alert alert-info small mb-0">
        <i class="bi bi-info-circle me-1" />
        Crosswalk associations will be stored in: <strong>{{ crosswalkFrameworkTitle }}</strong>.
        Both frameworks should have vector embeddings generated. Items without embeddings will be skipped.
      </div>
    </div>

    <!-- Step 2: Thresholds & Preview -->
    <div class="mb-4">
      <h5>Step 2: Configure Thresholds</h5>
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
      <h5>Step 3: Review &amp; Create</h5>
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
import { useDocumentGroups } from '@/composables/useDocumentGroups.js';
import { api } from '@/services/api.js';
import EstimationResults from './EstimationResults.vue';

const route = useRoute();
const documentStore = useDocumentStore();

const emit = defineEmits(['create']);

const estimateResult = ref(null);

const crosswalkIdentifier = computed(() => {
  return route.params.frameworkId || null;
});

const crosswalkFrameworkTitle = computed(() => {
  const doc = documentStore.documents.find(d => d.identifier === crosswalkIdentifier.value);
  return doc?.title || 'Current Framework';
});

const allDocuments = computed(() => documentStore.documents);
const { groupedDocuments } = useDocumentGroups(allDocuments);

const form = ref({
  originIdentifier: '',
  destinationIdentifier: '',
  threshold: 0.75,
  exactMatchThreshold: 0.90,
});

const canPreview = computed(() => {
  return (
    form.value.originIdentifier &&
    form.value.destinationIdentifier &&
    form.value.originIdentifier !== form.value.destinationIdentifier &&
    crosswalkIdentifier.value
  );
});

const canCreate = computed(() => {
  return canPreview.value && form.value.exactMatchThreshold >= form.value.threshold;
});

onMounted(async () => {
  if (documentStore.documents.length === 0) {
    await documentStore.fetchDocuments();
  }
});

async function onPreview() {
  try {
    const data = await api.get(
      `/api/vector-search/crosswalk/estimate?origin=${form.value.originIdentifier}&destination=${form.value.destinationIdentifier}&threshold=${form.value.threshold}`
    );
    estimateResult.value = data;
  } catch (err) {
    console.error('Failed to get estimate:', err);
  }
}

function onCreate() {
  emit('create', {
    originIdentifier: form.value.originIdentifier,
    destinationIdentifier: form.value.destinationIdentifier,
    crosswalkIdentifier: crosswalkIdentifier.value,
    threshold: form.value.threshold,
    exactMatchThreshold: form.value.exactMatchThreshold,
  });
}
</script>
