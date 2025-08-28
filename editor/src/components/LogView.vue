<template>
  <div class="log-view">
    <div v-if="loading" class="d-flex justify-content-center align-items-center" style="height: 80vh;">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading logs...</span>
      </div>
    </div>
    <div v-else-if="error" class="alert alert-danger my-4" role="alert">
      {{ error }}
    </div>
    <div v-else class="row g-0" style="height: 80vh;">
      <!-- Log Entries Panel -->
      <section class="col-8 log-entries-panel">
        <div class="p-3 h-100">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Activity Log</h3>
            <div class="btn-group" role="group">
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

          <div class="log-container border rounded p-3" style="height: calc(100% - 60px); overflow-y: auto;">
            <div v-if="filteredLogs.length === 0" class="text-center text-muted py-5">
              <i class="bi bi-list-check fs-1 mb-3"></i>
              <p>No log entries found.</p>
              <small>Log entries will appear here as you make changes to the document.</small>
            </div>
            <div v-else class="log-entries">
              <div
                v-for="log in filteredLogs"
                :key="log.id"
                class="log-entry mb-3 p-3 border rounded"
                :class="getLogEntryClass(log.type)"
              >
                <div class="d-flex align-items-start">
                  <div class="log-icon me-3">
                    <i :class="getLogIcon(log.type)" class="fs-5"></i>
                  </div>
                  <div class="log-content flex-grow-1">
                    <div class="log-header d-flex justify-content-between align-items-start">
                      <h6 class="log-title mb-1">{{ log.title }}</h6>
                      <small class="text-muted">{{ formatTimestamp(log.timestamp) }}</small>
                    </div>
                    <p class="log-description mb-2">{{ log.description }}</p>
                    <div class="log-details">
                      <small class="text-muted">
                        <span v-if="log.user">User: {{ log.user }} • </span>
                        <span v-if="log.itemType">Type: {{ log.itemType }} • </span>
                        <span v-if="log.identifier">ID: {{ log.identifier }}</span>
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Log Summary Panel -->
      <section class="col-4 log-summary-panel">
        <div class="p-3 h-100">
          <h4 class="mb-3">Log Summary</h4>
          <div class="summary-container">
            <div class="summary-item mb-3">
              <div class="summary-label">Total Entries</div>
              <div class="summary-value h3 text-primary">{{ logs.length }}</div>
            </div>

            <div class="summary-item mb-3">
              <div class="summary-label">Activity Types</div>
              <div class="activity-breakdown mt-2">
                <div v-for="[type, count] in activityTypes" :key="type" class="activity-item d-flex justify-content-between align-items-center mb-2">
                  <div class="d-flex align-items-center">
                    <i :class="getLogIcon(type)" class="me-2"></i>
                    <span class="text-capitalize">{{ type }}</span>
                  </div>
                  <span class="badge" :class="getActivityBadgeClass(type)">{{ count }}</span>
                </div>
              </div>
            </div>

            <div class="summary-item mb-3">
              <div class="summary-label">Recent Activity</div>
              <div class="recent-activity mt-2">
                <div v-if="logs.length === 0" class="text-muted small">
                  No recent activity
                </div>
                <div v-else class="small">
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
              <div class="summary-label">Export Options</div>
              <div class="export-options mt-2">
                <button class="btn btn-sm btn-outline-primary me-2" @click="exportLogs('json')">
                  <i class="bi bi-download me-1"></i>JSON
                </button>
                <button class="btn btn-sm btn-outline-primary" @click="exportLogs('csv')">
                  <i class="bi bi-file-earmark-spreadsheet me-1"></i>CSV
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
import { ref, computed, onMounted } from 'vue';
import { useFrameworkStore } from '../stores/frameworkStore';

const frameworkStore = useFrameworkStore();

const loading = computed(() => frameworkStore.loading);
const error = computed(() => frameworkStore.error);
const currentDocument = computed(() => frameworkStore.currentDocument);

const logFilter = ref('all');

// Mock log data - in a real app, this would come from an API
const logs = ref([
  // Sample log entries - these would be populated from actual document changes
]);

const filteredLogs = computed(() => {
  if (logFilter.value === 'all') return logs.value;
  return logs.value.filter(log => log.type === logFilter.value);
});

const activityTypes = computed(() => {
  const types = new Map();
  logs.value.forEach(log => {
    types.set(log.type, (types.get(log.type) || 0) + 1);
  });
  return Array.from(types.entries()).sort((a, b) => b[1] - a[1]);
});

function getLogEntryClass(type) {
  const classes = {
    create: 'border-success bg-success-subtle',
    update: 'border-primary bg-primary-subtle',
    delete: 'border-danger bg-danger-subtle',
    associate: 'border-info bg-info-subtle'
  };
  return classes[type] || 'border-secondary bg-light';
}

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
  return new Date(timestamp).toLocaleString();
}

function getMostActiveType() {
  if (activityTypes.value.length === 0) return 'None';
  return activityTypes.value[0][0];
}

function exportLogs(format) {
  // Mock export functionality
  const data = filteredLogs.value;
  const filename = `document-logs-${new Date().toISOString().split('T')[0]}`;

  if (format === 'json') {
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    downloadBlob(blob, `${filename}.json`);
  } else if (format === 'csv') {
    const csv = convertToCSV(data);
    const blob = new Blob([csv], { type: 'text/csv' });
    downloadBlob(blob, `${filename}.csv`);
  }
}

function convertToCSV(data) {
  if (data.length === 0) return '';

  const headers = ['Timestamp', 'Type', 'Title', 'Description', 'User', 'Item Type', 'Identifier'];
  const rows = data.map(log => [
    log.timestamp,
    log.type,
    log.title,
    log.description,
    log.user || '',
    log.itemType || '',
    log.identifier || ''
  ]);

  return [headers, ...rows].map(row => row.map(field => `"${field}"`).join(',')).join('\n');
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

// Generate some sample log data based on the current document
onMounted(() => {
  if (currentDocument.value) {
    generateSampleLogs();
  }
});

function generateSampleLogs() {
  const sampleLogs = [];
  const now = new Date();

  // Generate sample logs based on document items
  if (currentDocument.value?.items) {
    currentDocument.value.items.slice(0, 5).forEach((item, index) => {
      sampleLogs.push({
        id: `log-${index + 1}`,
        timestamp: new Date(now.getTime() - (index * 3600000)).toISOString(), // 1 hour apart
        type: ['create', 'update', 'associate'][index % 3],
        title: `${item.title || 'Item'} ${['created', 'updated', 'associated'][index % 3]}`,
        description: `Item "${item.title || 'Untitled'}" was ${['created', 'updated', 'associated'][index % 3]} in the document.`,
        user: 'System',
        itemType: item.itemType || 'item',
        identifier: item.identifier
      });
    });
  }

  logs.value = sampleLogs;
}
</script>

<style scoped>
.log-view {
  height: 100%;
}

.log-container {
  background-color: #f8f9fa;
}

.log-entries {
  max-height: 100%;
}

.log-entry {
  transition: box-shadow 0.2s ease;
}

.log-entry:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.log-icon {
  min-width: 40px;
  text-align: center;
}

.log-title {
  margin: 0;
  font-weight: 600;
}

.log-description {
  margin: 0;
  color: #495057;
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
</style>
