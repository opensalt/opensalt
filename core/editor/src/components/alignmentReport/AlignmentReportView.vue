<template>
  <div class="alignment-report-view d-flex flex-column h-100 px-3 py-3 bg-light">
    <div class="d-flex align-items-center mb-2">
      <h1 class="h4 mb-0">
        Framework Alignment Report
      </h1>
    </div>

    <div class="row g-3 mb-2">
      <div class="col-md-6">
        <div class="form-label small mb-1">
          Select framework for alignment
        </div>
        <VueMultiselect
          v-model="leftDoc"
          class="framework-select"
          :options="groupedDocuments"
          group-label="creator"
          group-values="documents"
          :group-select="false"
          label="title"
          track-by="identifier"
          :searchable="true"
          :close-on-select="true"
          :clear-on-select="false"
          placeholder="Type to search frameworks…"
          select-label=""
          deselect-label=""
          selected-label=""
        >
          <template #singleLabel="{ option }">
            {{ option.title }} ({{ option.identifier }})
          </template>
          <template #option="{ option }">
            <template v-if="option.$isLabel">
              {{ option.$groupLabel }}
            </template>
            <template v-else>
              {{ option.title }} ({{ option.identifier }})
            </template>
          </template>
          <template #noOptions>
            No frameworks available
          </template>
        </VueMultiselect>
      </div>
      <div class="col-md-6">
        <div class="form-label small mb-1">
          Select aligned framework
        </div>
        <VueMultiselect
          v-model="rightDoc"
          class="framework-select"
          :options="groupedDocuments"
          group-label="creator"
          group-values="documents"
          :group-select="false"
          label="title"
          track-by="identifier"
          :searchable="true"
          :close-on-select="true"
          :clear-on-select="false"
          placeholder="Type to search frameworks…"
          :disabled="!leftId"
          select-label=""
          deselect-label=""
          selected-label=""
        >
          <template #singleLabel="{ option }">
            {{ option.title }} ({{ option.identifier }})
          </template>
          <template #option="{ option }">
            <template v-if="option.$isLabel">
              {{ option.$groupLabel }}
            </template>
            <template v-else>
              {{ option.title }} ({{ option.identifier }})
            </template>
          </template>
          <template #noOptions>
            No frameworks available
          </template>
        </VueMultiselect>
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
import VueMultiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
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

// Keep the left picker in sync on in-SPA navigation between frameworks.
watch(() => route.params.frameworkId, (id) => {
  const newId = typeof id === 'string' ? id : '';
  if (newId === leftId.value) {
    return;
  }
  leftId.value = newId;
  leftDoc.value = newId ? findDoc(newId) : null;
});

const allDocuments = computed(() => documentStore.documents);
const { groupedDocuments } = useDocumentGroups(allDocuments);

// Searchable pickers hold the whole document object; the ids stay the source of truth.
const leftDoc = ref(null);
const rightDoc = ref(null);
watch(leftDoc, (doc) => {
  leftId.value = doc?.identifier ?? '';
});
watch(rightDoc, (doc) => {
  rightId.value = doc?.identifier ?? '';
});

function findDoc(identifier) {
  return documentStore.documents.find((d) => d.identifier === identifier) ?? null;
}

onMounted(async () => {
  if (documentStore.documents.length === 0) {
    await documentStore.fetchDocuments();
  }
  // Show the URL-preselected framework in the picker once the list is loaded.
  // If it is not visible to this user (draft/private), the picker stays blank
  // while leftId still drives generation.
  if (routeLeftId && !leftDoc.value) {
    leftDoc.value = findDoc(routeLeftId);
  }
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
/* Match vue-multiselect to Bootstrap .form-select (see native selects elsewhere). */
/* Group headers render as disabled options; the library's disabled rule uses
   !important, so these overrides must too. */
.framework-select :deep(.multiselect__option--group),
.framework-select :deep(.multiselect__group) {
  font-weight: 700 !important;
  background: transparent !important;
  color: var(--bs-secondary-color) !important;
}
.framework-select :deep(.multiselect__element .multiselect__option) {
  padding-left: 1.625rem;
}
.framework-select :deep(.multiselect__element .multiselect__option--group) {
  padding-left: 0.625rem;
}
.framework-select :deep(.multiselect__tags) {
  min-height: calc(1.5em + 0.75rem + 2px);
  padding: 0.375rem 40px 0.375rem 0.75rem;
  border: 1px solid var(--bs-border-color);
  border-radius: var(--bs-border-radius);
  background-color: var(--bs-body-bg);
  font-size: 1rem;
}
.framework-select.multiselect--active :deep(.multiselect__tags) {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.framework-select :deep(.multiselect__single),
.framework-select :deep(.multiselect__input),
.framework-select :deep(.multiselect__placeholder) {
  font-size: 1rem;
  line-height: 1.5;
  margin-bottom: 0;
  padding: 0;
}
.framework-select :deep(.multiselect__single) {
  color: var(--bs-body-color);
}
.framework-select :deep(.multiselect__placeholder) {
  color: var(--bs-secondary-color);
}
.framework-select :deep(.multiselect__select)::before {
  border-color: var(--bs-body-color) transparent transparent;
  top: 70%;
}
.framework-select.multiselect--disabled :deep(.multiselect__tags) {
  background-color: var(--bs-secondary-bg);
  border-color: var(--bs-border-color);
}
.framework-select.multiselect--disabled :deep(.multiselect__single) {
  color: var(--bs-secondary-color);
}
.framework-select :deep(.multiselect__option--highlight) {
  background: var(--bs-primary);
  color: #fff;
}
.framework-select :deep(.multiselect__option--selected.multiselect__option--highlight) {
  background: var(--bs-primary);
}
.framework-select :deep(.multiselect__option--selected) {
  background: var(--bs-tertiary-bg);
  color: var(--bs-body-color);
  font-weight: 600;
}
</style>
