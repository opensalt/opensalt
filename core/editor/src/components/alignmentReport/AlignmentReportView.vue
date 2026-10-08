<template>
  <div class="alignment-report-view d-flex flex-column h-100 px-3 py-3 bg-light">
    <div class="d-flex align-items-center mb-2">
      <h1 class="h4 mb-0">
        Framework Alignment Report
      </h1>
    </div>

    <div class="row g-3 mb-2">
      <div class="col-md-6">
        <span
          :id="sourceLabelId"
          class="form-label small mb-1"
        >
          Select framework for alignment
        </span>
        <SingleSelect
          id="alignment-source-framework"
          :model-value="leftId || null"
          :options="frameworkOptions"
          placeholder="Type to search frameworks…"
          option-value="id"
          option-label="text"
          :searchable="true"
          search-placeholder="Filter frameworks…"
          :allow-clear="false"
          no-results-text="No frameworks available"
          :labelledby-id="sourceLabelId"
          @update:model-value="onLeftChange"
        />
      </div>
      <div class="col-md-6">
        <span
          :id="targetLabelId"
          class="form-label small mb-1"
        >
          Select aligned framework
        </span>
        <SingleSelect
          id="alignment-target-framework"
          :model-value="rightId || null"
          :options="frameworkOptions"
          placeholder="Type to search frameworks…"
          option-value="id"
          option-label="text"
          :searchable="true"
          search-placeholder="Filter frameworks…"
          :allow-clear="false"
          no-results-text="No frameworks available"
          :labelledby-id="targetLabelId"
          :disabled="!leftId"
          @update:model-value="onRightChange"
        />
      </div>
    </div>

    <div class="d-flex align-items-center gap-2 mb-2">
      <button
        id="determineDifferencesBtn"
        type="button"
        class="btn btn-primary"
        :disabled="!leftId || !rightId || leftId === rightId || loading"
        @click="determineDifferences"
      >
        <span
          v-if="loading"
          class="spinner-border spinner-border-sm me-1"
          aria-hidden="true"
        />
        {{ loading ? 'Working…' : 'Determine Differences' }}
      </button>
      <button
        v-if="rows.length > 0"
        id="downloadExcelBtn"
        type="button"
        class="btn btn-outline-warning"
        :disabled="exporting"
        @click="downloadExcel"
      >
        <span
          v-if="exporting"
          class="spinner-border spinner-border-sm me-1"
          aria-hidden="true"
        />
        Download Excel Version
      </button>
      <span
        v-if="error"
        class="text-danger"
        role="alert"
      >{{ error }}</span>
      <span
        v-if="exportError"
        class="text-danger"
        role="alert"
      >{{ exportError }}</span>
    </div>

    <div
      v-if="loading"
      class="progress mb-2"
      role="progressbar"
      :aria-valuenow="progress"
      aria-valuemin="0"
      aria-valuemax="100"
      aria-label="Generating alignment report"
      style="height: 1.25rem;"
    >
      <div
        class="progress-bar progress-bar-striped progress-bar-animated"
        :style="{ width: progress + '%' }"
      >
        {{ progress > 0 ? progress + '%' : '' }}
      </div>
    </div>

    <div
      class="flex-grow-1 d-flex flex-column"
      style="min-height: 0;"
    >
      <AlignmentReportTable
        class="h-100"
        :rows="rows"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import SingleSelect from '../tree/SingleSelect.vue';
import { useDocumentStore } from '../../stores/documentStore';
import { useDocumentGroups } from '../../composables/useDocumentGroups.js';
import { useAlignmentReport } from '../../composables/useAlignmentReport.js';
import AlignmentReportTable from './AlignmentReportTable.vue';

const route = useRoute();
const documentStore = useDocumentStore();
const { rows, loading, error, progress, generate } = useAlignmentReport();

const exportError = ref(null);

// {uuid} in the URL preselects the LEFT framework; both stay editable.
const routeLeftId = typeof route.params.frameworkId === 'string' ? route.params.frameworkId : '';
const leftId = ref(routeLeftId);
const rightId = ref('');

// Static ids: this view renders one instance of each picker per page
const sourceLabelId = 'alignment-source-framework-label';
const targetLabelId = 'alignment-target-framework-label';

// Keep the left picker in sync on in-SPA navigation between frameworks.
watch(() => route.params.frameworkId, (id) => {
  leftId.value = typeof id === 'string' ? id : '';
});

const allDocuments = computed(() => documentStore.documents);
const { groupedDocuments } = useDocumentGroups(allDocuments);

// Creator groups with "Title (identifier)" options, matching the old picker
const frameworkOptions = computed(() =>
  groupedDocuments.value.map((group) => ({
    label: group.creator,
    options: group.documents.map((doc) => ({
      id: doc.identifier,
      text: `${doc.title} (${doc.identifier})`,
    })),
  }))
);

function onLeftChange(id) {
  leftId.value = id ?? '';
}

function onRightChange(id) {
  rightId.value = id ?? '';
}

function findDoc(identifier) {
  return documentStore.documents.find((d) => d.identifier === identifier) ?? null;
}

onMounted(async () => {
  if (documentStore.documents.length === 0) {
    await documentStore.fetchDocuments();
  }
  // If the URL framework is not visible to this user (draft/private), the
  // picker shows its placeholder while leftId still drives generation.
});

async function determineDifferences() {
  await generate({ leftId: leftId.value, rightId: rightId.value });
}

const exporting = ref(false);

async function downloadExcel() {
  exporting.value = true;
  exportError.value = null;
  try {
    const title = (id) => findDoc(id)?.title || id;
    // Dynamic import keeps @office-kit/xlsx out of the main editor bundle.
    const { exportAlignmentReportXlsx } = await import('../../services/alignmentReportExport.js');
    const { blob, filename } = await exportAlignmentReportXlsx({
      rows: rows.value,
      leftTitle: title(leftId.value),
      rightTitle: title(rightId.value),
    });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  } catch (err) {
    exportError.value = err?.message || String(err);
  } finally {
    exporting.value = false;
  }
}
</script>

<style scoped>
.form-label {
  display: block;
}
</style>
