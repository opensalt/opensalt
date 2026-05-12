<template>
  <div
    id="logView"
    class="log-view h-100 d-flex flex-column bg-light"
  >
    <div
      v-if="loading"
      class="d-flex justify-content-center align-items-center flex-grow-1"
    >
      <div
        class="spinner-border text-primary"
        role="status"
      >
        <span class="visually-hidden">Loading logs...</span>
      </div>
    </div>
    <div
      v-else-if="isNotLoggedIn"
      class="alert alert-info m-4"
      role="alert"
    >
      <i class="bi bi-info-circle me-2" />
      You must be logged in to view the activity log.
    </div>
    <div
      v-else-if="error"
      class="alert alert-danger m-4"
      role="alert"
    >
      {{ error }}
    </div>
    <div
      v-else
      class="d-flex flex-grow-1 overflow-hidden"
      style="min-height: 0;"
    >
      <!-- Log Entries Panel -->
      <section
        class="col-8 log-entries-panel d-flex flex-column overflow-hidden"
        style="min-height: 0;"
      >
        <div
          class="p-4 flex-grow-1 d-flex flex-column overflow-hidden"
          style="min-height: 0;"
        >
          <div class="d-flex justify-content-between align-items-center mb-4 flex-shrink-0">
            <h3 class="mb-0">
              Activity Log
            </h3>
            <div id="logTable_filter">
              <input
                v-model="searchFilter"
                type="text"
                class="form-control form-control-sm"
                placeholder="Search logs..."
              >
            </div>
            <div
              v-if="false"
              class="btn-group"
              role="group"
            >
              <button
                type="button"
                class="btn btn-sm"
                :class="{ 'btn-primary': logFilter === 'all', 'btn-outline-primary': logFilter !== 'all' }"
                @click="logFilter = 'all'"
              >
                All
              </button>
              <button
                type="button"
                class="btn btn-sm"
                :class="{ 'btn-primary': logFilter === 'items', 'btn-outline-primary': logFilter !== 'items' }"
                @click="logFilter = 'items'"
              >
                Items
              </button>
              <button
                type="button"
                class="btn btn-sm"
                :class="{ 'btn-primary': logFilter === 'associations', 'btn-outline-primary': logFilter !== 'associations' }"
                @click="logFilter = 'associations'"
              >
                Associations
              </button>
            </div>
          </div>

          <div
            class="table-responsive border rounded-3 bg-white shadow-sm flex-grow-1 overflow-auto log-table-wrapper"
            style="min-height: 0;"
          >
            <table
              id="logTable"
              class="table table-hover align-middle mb-0 border-0"
            >
              <thead class="table-light sticky-top shadow-sm z-index-1">
                <tr>
                  <th
                    scope="col"
                    class="ps-4 py-3 border-0 text-muted small text-uppercase font-weight-bold"
                  >
                    Time
                  </th>
                  <th
                    scope="col"
                    class="py-3 border-0 text-muted small text-uppercase font-weight-bold"
                  >
                    Action
                  </th>
                  <th
                    scope="col"
                    class="py-3 border-0 text-muted small text-uppercase font-weight-bold"
                  >
                    Activity
                  </th>
                  <th
                    scope="col"
                    class="py-3 border-0 text-muted small text-uppercase font-weight-bold"
                  >
                    User
                  </th>
                </tr>
              </thead>
              <tbody class="border-0">
                <tr v-if="paginatedLogs.length === 0">
                  <td
                    colspan="5"
                    class="text-center py-5 text-muted border-0"
                  >
                    <div class="py-4">
                      <i class="bi bi-list-check fs-1 d-block mb-3 opacity-25" />
                      <p class="mb-0">
                        No log entries found.
                      </p>
                      <small>Log entries will appear here as you make changes to the document.</small>
                    </div>
                  </td>
                </tr>
                <tr
                  v-for="log in paginatedLogs"
                  :key="log.id"
                  class="log-row border-bottom transition-all"
                >
                  <td class="ps-4 py-3">
                    <small class="text-muted">{{ formatTimestamp(log.timestamp) }}</small>
                  </td>
                  <td class="py-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-medium">
                      <i
                        :class="getLogIcon(log.type)"
                        class="me-1"
                      />
                      {{ log.type }}
                    </span>
                  </td>
                  <td class="py-3">
                    <div class="activity-content">
                      <div
                        class="text-muted small text-truncate"
                        style="max-width: 500px;"
                        :title="log.description"
                      >
                        {{ log.description }}
                      </div>
                    </div>
                  </td>
                  <td class="py-3">
                    <span class="text-muted small">{{ log.user || 'Unknown' }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Controls -->
          <div
            v-if="totalPages > 1"
            class="d-flex justify-content-between align-items-center mt-3 bg-white p-3 border rounded shadow-sm flex-shrink-0"
          >
            <div class="text-muted small">
              Showing {{ startItem + 1 }} to {{ endItem }} of {{ filteredLogs.length }} log entries
            </div>
            <nav aria-label="Log pagination">
              <ul class="pagination pagination-sm mb-0">
                <li
                  class="page-item"
                  :class="{ disabled: currentPage === 1 }"
                >
                  <button
                    class="page-link"
                    aria-label="Previous"
                    @click="currentPage--"
                  >
                    <span aria-hidden="true">&laquo;</span>
                  </button>
                </li>

                <li
                  v-for="pageNum in displayedPages"
                  :key="pageNum"
                  class="page-item"
                  :class="{ active: currentPage === pageNum, disabled: pageNum === '...' }"
                >
                  <button
                    v-if="pageNum !== '...'"
                    class="page-link"
                    @click="currentPage = pageNum"
                  >
                    {{ pageNum }}
                  </button>
                  <span
                    v-else
                    class="page-link border-0"
                  >...</span>
                </li>

                <li
                  class="page-item"
                  :class="{ disabled: currentPage === totalPages }"
                >
                  <button
                    class="page-link"
                    aria-label="Next"
                    @click="currentPage++"
                  >
                    <span aria-hidden="true">&raquo;</span>
                  </button>
                </li>
              </ul>
            </nav>
            <div class="d-flex align-items-center gap-2 dataTables_wrapper">
              <label class="small text-muted mb-0">Per page:</label>
              <select
                v-model="pageSize"
                class="form-select form-select-sm"
                style="width: auto;"
              >
                <option :value="10">
                  10
                </option>
                <option :value="25">
                  25
                </option>
                <option :value="50">
                  50
                </option>
                <option :value="100">
                  100
                </option>
              </select>
            </div>
          </div>
        </div>
      </section>

      <!-- Log Summary Panel -->
      <section
        class="col-4 log-summary-panel d-flex flex-column overflow-hidden border-start"
        style="min-height: 0;"
      >
        <div
          class="p-4 flex-grow-1 d-flex flex-column overflow-hidden"
          style="min-height: 0;"
        >
          <h4 class="mb-3">
            Log Summary
          </h4>
          <div class="summary-container flex-grow-1 overflow-auto">
            <div class="summary-item mb-3">
              <div class="summary-label">
                Total Entries
              </div>
              <div class="summary-value h3 text-primary">
                {{ logs.length }}
              </div>
            </div>

            <div class="summary-item mb-3">
              <div class="summary-label">
                Activity Types
              </div>
              <div class="activity-breakdown mt-2">
                <div
                  v-for="[type, count] in activityTypes"
                  :key="type"
                  class="activity-item d-flex justify-content-between align-items-center mb-2"
                >
                  <div class="d-flex align-items-center">
                    <i
                      :class="getLogIcon(type)"
                      class="me-2"
                    />
                    <span class="text-capitalize">{{ type }}</span>
                  </div>
                  <span
                    class="badge"
                    :class="getActivityBadgeClass(type)"
                  >{{ count }}</span>
                </div>
              </div>
            </div>

            <div class="summary-item mb-3">
              <div class="summary-label">
                Recent Activity
              </div>
              <div class="recent-activity mt-2">
                <div
                  v-if="logs.length === 0"
                  class="text-muted small"
                >
                  No recent activity
                </div>
                <div
                  v-else
                  class="small"
                >
                  <div class="mb-1">
                    <strong>Last activity:</strong> {{ formatTimestamp(logs[0]?.timestamp) }}
                  </div>
                  <div>
                    <strong>Most active type:</strong> {{ getMostActiveType() }}
                  </div>
                </div>
              </div>
            </div>

            <div class="summary-item">
              <div class="summary-label">
                Export Options
              </div>
              <div class="export-options mt-2">
                <button
                  class="btn btn-sm btn-outline-primary me-2"
                  @click="exportLogs('json')"
                >
                  <i class="bi bi-download me-1" />JSON
                </button>
                <button
                  class="btn btn-sm btn-outline-primary"
                  @click="exportLogs('csv')"
                >
                  <a
                    class="btn-export-csv"
                    :href="csvExportUrl"
                    @click.prevent="exportLogs('csv')"
                  ><i class="bi bi-file-earmark-spreadsheet me-1" />CSV</a>
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

  <script setup>
  import { ref, computed, onMounted, watch } from 'vue';
  import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';
  import { logger } from '../../utils/logger.js';

  const currentDocumentStore = useCurrentDocumentStore();

  const loading = ref(false);
  const error = ref(null);
  const isNotLoggedIn = ref(false);
  const currentDocument = computed(() => currentDocumentStore.currentDocument);

  const logFilter = ref('all');
  const searchFilter = ref('');

  const csvExportUrl = computed(() => {
    const docIdentifier = currentDocument.value?.id;
    if (!docIdentifier) return '#';
    return `/cfdoc/identifier/${docIdentifier}/revisions/export`;
  });
  const logs = ref([]);
  const totalRecords = ref(0);

  // Pagination state
  const currentPage = ref(1);
  const pageSize = ref(25);

  // Retry state
  const retryCount = ref(0);
  const maxRetries = 3;

  const activityTypes = computed(() => {
    const types = new Map();
    logs.value.forEach(log => {
      types.set(log.type, (types.get(log.type) || 0) + 1);
    });
    return Array.from(types.entries()).sort((a, b) => b[1] - a[1]);
  });

  const filteredLogs = computed(() => {
    let result = logs.value;
    if (logFilter.value !== 'all') {
      result = result.filter(log => log.type === logFilter.value);
    }
    if (searchFilter.value) {
      const term = searchFilter.value.toLowerCase();
      result = result.filter(log =>
        (log.description || '').toLowerCase().includes(term) ||
        (log.username || '').toLowerCase().includes(term) ||
        (log.type || '').toLowerCase().includes(term) ||
        (log.timestamp || '').toLowerCase().includes(term)
      );
    }
    return result;
  });

  const startItem = computed(() => (currentPage.value - 1) * pageSize.value);
  const endItem = computed(() => Math.min(startItem.value + pageSize.value, filteredLogs.value.length));

  const paginatedLogs = computed(() => {
    return filteredLogs.value.slice(startItem.value, endItem.value);
  });

  const totalPages = computed(() => Math.ceil(filteredLogs.value.length / pageSize.value));

  const displayedPages = computed(() => {
    const delta = 2; // Number of pages to show before and after current page
    const left = currentPage.value - delta;
    const right = currentPage.value + delta + 1;
    const range = [];
    const rangeWithDots = [];
    let l;

    for (let i = 1; i <= totalPages.value; i++) {
      if (i === 1 || i === totalPages.value || (i >= left && i < right)) {
        range.push(i);
      }
    }

    for (const i of range) {
      if (l) {
        if (i - l === 2) {
          rangeWithDots.push(l + 1);
        } else if (i - l !== 1) {
          rangeWithDots.push('...');
        }
      }
      rangeWithDots.push(i);
      l = i;
    }

    return rangeWithDots;
  });

  function getLogIcon(type) {
    const icons = {
      create: 'bi bi-plus-circle text-success',
      update: 'bi bi-pencil text-primary',
      delete: 'bi bi-trash text-danger',
      associate: 'bi bi-link text-info'
    };
    return icons[type] || 'bi bi-circle text-secondary';
  }

  function getActivityBadgeClass(type) {
    const classes = {
      create: 'bg-success',
      update: 'bg-primary',
      delete: 'bg-danger',
      associate: 'bg-info'
    };
    return classes[type] || 'bg-secondary';
  }

  function formatTimestamp(timestamp) {
    if (!timestamp) return 'Unknown';
    const d = new Date(timestamp);
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
  }

  function getMostActiveType() {
    if (activityTypes.value.length === 0) return 'None';
    return activityTypes.value[0][0];
  }

  async function fetchLogs() {
    if (!currentDocument.value?.id) return;

    loading.value = true;
    error.value = null;
    isNotLoggedIn.value = false;

    try {
      const response = await fetch(`/cfdoc/identifier/${currentDocument.value.id}/revisions/0/1000`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });

      // Check for redirect (302) or 401 status which indicate not logged in
      if (response.status === 302 || response.status === 401) {
        isNotLoggedIn.value = true;
        logs.value = [];
        return;
      }

      if (!response.ok) {
        if (response.status === 429) {
          throw new Error('Too many requests. Please try again later.');
        } else if (response.status >= 500) {
          throw new Error('Server error. Please try again later.');
        } else if (response.status === 403) {
          throw new Error('Access denied. You may not have permission to view these logs.');
        } else {
          throw new Error(`Failed to load logs (HTTP ${response.status}). Please try again.`);
        }
      }

      const data = await response.json();

      if (!data.data || !Array.isArray(data.data)) {
        throw new Error('Invalid response format from server.');
      }

      logs.value = data.data.map(row => {
        // Try to determine type from description
        let type = 'update'; // default
        const desc = row.description.toLowerCase();
        if (desc.includes('created') || desc.includes('added')) {
          type = 'create';
        } else if (desc.includes('deleted') || desc.includes('removed')) {
          type = 'delete';
        } else if (desc.includes('associated') || desc.includes('linked')) {
          type = 'associate';
        }

        return {
          id: row.rev,
          timestamp: row.changed_at,
          type: type,
          title: row.description.substring(0, 50) + (row.description.length > 50 ? '...' : ''),
          description: row.description,
          user: row.username || 'Unknown',
          itemType: 'item',
          identifier: row.rev
        };
      });

      // For pagination, we need total count. Since API doesn't provide it, we'll use the actual count
      // In a real implementation, you'd want the API to return total count
      totalRecords.value = logs.value.length;

    } catch (err) {
      // Check if the error might be due to login redirect
      if (err.message.includes('Failed to fetch') || err.message.includes('302')) {
        isNotLoggedIn.value = true;
        logs.value = [];
      } else {
        error.value = err.message;
        logger.error('Failed to fetch logs:', err);
      }

      // Retry logic for network errors (but not for login errors)
      if (!isNotLoggedIn.value && retryCount.value < maxRetries && (err.message.includes('network') || err.message.includes('fetch'))) {
        retryCount.value++;
        logger.debug(`Retrying... (${retryCount.value}/${maxRetries})`);
        setTimeout(() => fetchLogs(), 1000 * retryCount.value);
        return;
      }

      retryCount.value = 0; // Reset retry count on final failure
    } finally {
      loading.value = false;
    }
  }

  function exportLogs(format) {
    if (format === 'csv') {
      // Use the CSV export endpoint
      const docIdentifier = currentDocument.value?.id;
      if (docIdentifier) {
        window.open(`/cfdoc/identifier/${docIdentifier}/revisions/export`, '_blank');
      }
    } else {
      // For JSON, export current filtered data
      const data = filteredLogs.value;
      const filename = `document-logs-${new Date().toISOString().split('T')[0]}`;
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      downloadBlob(blob, `${filename}.json`);
    }
  }

  function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  // Watch for filter changes to reset pagination
  watch([logFilter, pageSize], () => {
    currentPage.value = 1;
  });

  // Watch for document changes
  watch(currentDocument, (newDoc) => {
    if (newDoc?.id) {
      currentPage.value = 1;
      fetchLogs();
    }
  });

  // Fetch logs when component mounts
  onMounted(() => {
    if (currentDocument.value?.id) {
      fetchLogs();
    }
  });
  </script>

  <style scoped>
  .log-view {
    height: 100%;
  }

  .log-row:hover {
    background-color: #f8f9fa !important;
  }

  .activity-content {
    max-width: 500px;
  }

  .details-content {
    max-width: 200px;
  }

  .summary-container {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    height: 100%;
    overflow-y: auto;
  }

  .summary-item {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 0.75rem;
    margin-bottom: 1rem;
  }

  .summary-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
  }

  .summary-label {
    font-size: 0.9em;
    color: #6c757d;
    margin-bottom: 0.5rem;
    font-weight: 500;
  }

  .summary-value {
    margin: 0;
  }

  .activity-breakdown {
    max-height: 120px;
    overflow-y: auto;
  }

  .activity-item {
    padding: 0.25rem 0;
  }

  .recent-activity {
    background-color: #f8f9fa;
    padding: 0.5rem;
    border-radius: 4px;
  }

  .export-options {
    display: flex;
    gap: 0.5rem;
  }

  .transition-all {
    transition: all 0.2s ease-in-out;
  }

  .log-table-wrapper::-webkit-scrollbar {
    width: 6px;
    height: 6px;
  }

  .log-table-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
  }

  .log-table-wrapper::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 10px;
  }

  .log-table-wrapper::-webkit-scrollbar-thumb:hover {
    background: #bbb;
  }

  .z-index-1 {
    z-index: 1;
  }
  </style>
