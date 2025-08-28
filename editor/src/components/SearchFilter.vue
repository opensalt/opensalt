<template>
  <div class="search-filter">
    <div class="input-group">
      <span class="input-group-text">
        <i class="bi bi-search"></i>
      </span>
      <input
        type="text"
        class="form-control"
        :placeholder="placeholder"
        v-model="searchQuery"
        @input="onSearchInput"
        @keydown="onKeyDown"
        ref="searchInput"
      >
      <button
        v-if="searchQuery"
        type="button"
        class="btn btn-outline-secondary"
        @click="clearSearch"
        title="Clear search"
      >
        <i class="bi bi-x"></i>
      </button>
    </div>

    <!-- Advanced filters -->
    <div v-if="showAdvancedFilters" class="mt-3">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Advanced Filters</h6>
          <button type="button" class="btn btn-sm btn-outline-secondary" @click="toggleAdvancedFilters">
            <i class="bi bi-chevron-up"></i>
          </button>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <!-- Filter by item type -->
            <div class="col-md-6">
              <label class="form-label">Item Type</label>
              <select class="form-select" v-model="filters.itemType" @change="applyFilters">
                <option value="">All Types</option>
                <option value="assessment">Assessment</option>
                <option value="course">Course</option>
                <option value="credential">Credential</option>
                <option value="job">Job</option>
                <option value="organization">Organization</option>
                <option value="identifier">Identifier</option>
                <option value="public_key">Public Key</option>
              </select>
            </div>

            <!-- Filter by subject -->
            <div class="col-md-6">
              <label class="form-label">Subject</label>
              <select class="form-select" v-model="filters.subject" @change="applyFilters">
                <option value="">All Subjects</option>
                <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">
                  {{ subject.title }}
                </option>
              </select>
            </div>

            <!-- Filter by association status -->
            <div class="col-md-6">
              <label class="form-label">Association Status</label>
              <select class="form-select" v-model="filters.associationStatus" @change="applyFilters">
                <option value="">All Items</option>
                <option value="has-associations">Has Associations</option>
                <option value="no-associations">No Associations</option>
              </select>
            </div>

            <!-- Filter by last changed date -->
            <div class="col-md-6">
              <label class="form-label">Modified Since</label>
              <select class="form-select" v-model="filters.modifiedSince" @change="applyFilters">
                <option value="">Any Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="year">This Year</option>
              </select>
            </div>
          </div>

          <div class="mt-3 d-flex justify-content-between align-items-center">
            <div class="form-check">
              <input
                type="checkbox"
                class="form-check-input"
                id="caseSensitive"
                v-model="filters.caseSensitive"
                @change="applyFilters"
              >
              <label class="form-check-label" for="caseSensitive">Case Sensitive</label>
            </div>

            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-secondary" @click="clearAllFilters">
                Clear All
              </button>
              <button type="button" class="btn btn-primary" @click="applyFilters">
                Apply Filters
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter summary -->
    <div v-if="hasActiveFilters" class="mt-2">
      <small class="text-muted">
        <strong>Active filters:</strong>
        <span v-for="(value, key) in activeFilters" :key="key" class="badge bg-secondary me-1">
          {{ getFilterLabel(key) }}: {{ getFilterValueLabel(key, value) }}
          <button type="button" class="btn-close btn-close-white ms-1" @click="removeFilter(key)" style="font-size: 10px;"></button>
        </span>
      </small>
    </div>

    <!-- Search results summary -->
    <div v-if="searchQuery && searchResults" class="mt-2">
      <small class="text-muted">
        Found {{ searchResults.matchedCount }} of {{ searchResults.totalCount }} items
        <span v-if="searchResults.hiddenCount > 0">
          ({{ searchResults.hiddenCount }} hidden)
        </span>
      </small>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue';

const props = defineProps({
  placeholder: {
    type: String,
    default: 'Search items...'
  },
  availableSubjects: {
    type: Array,
    default: () => []
  },
  showAdvancedFilters: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['search', 'filter', 'clear']);

const searchInput = ref(null);
const searchQuery = ref('');
const advancedFiltersExpanded = ref(false);
const searchTimeout = ref(null);

const filters = reactive({
  itemType: '',
  subject: '',
  associationStatus: '',
  modifiedSince: '',
  caseSensitive: false
});

const searchResults = ref(null);

// Computed properties
const hasActiveFilters = computed(() => {
  return Object.values(filters).some(value =>
    value !== '' && value !== false
  ) || searchQuery.value.trim() !== '';
});

const activeFilters = computed(() => {
  const active = {};
  Object.keys(filters).forEach(key => {
    if (filters[key] !== '' && filters[key] !== false) {
      active[key] = filters[key];
    }
  });
  if (searchQuery.value.trim()) {
    active.search = searchQuery.value;
  }
  return active;
});

// Methods
function onSearchInput() {
  // Debounce search
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }

  searchTimeout.value = setTimeout(() => {
    performSearch();
  }, 300);
}

function performSearch() {
  const query = searchQuery.value.trim();

  if (query) {
    // Emit search event with query and filters
    emit('search', {
      query,
      filters: { ...filters },
      caseSensitive: filters.caseSensitive
    });
  } else {
    emit('clear');
  }
}

function applyFilters() {
  performSearch();
}

function clearSearch() {
  searchQuery.value = '';
  emit('clear');
}

function clearAllFilters() {
  Object.keys(filters).forEach(key => {
    if (key === 'caseSensitive') {
      filters[key] = false;
    } else {
      filters[key] = '';
    }
  });
  clearSearch();
}

function removeFilter(key) {
  if (key === 'search') {
    clearSearch();
  } else {
    filters[key] = key === 'caseSensitive' ? false : '';
    applyFilters();
  }
}

function toggleAdvancedFilters() {
  advancedFiltersExpanded.value = !advancedFiltersExpanded.value;
}

function onKeyDown(event) {
  if (event.key === 'Escape') {
    clearSearch();
  }
}

function getFilterLabel(key) {
  const labels = {
    itemType: 'Type',
    subject: 'Subject',
    associationStatus: 'Associations',
    modifiedSince: 'Modified',
    caseSensitive: 'Case Sensitive',
    search: 'Search'
  };
  return labels[key] || key;
}

function getFilterValueLabel(key, value) {
  if (key === 'itemType') {
    return value.charAt(0).toUpperCase() + value.slice(1);
  }
  if (key === 'subject') {
    const subject = props.availableSubjects.find(s => s.id === value);
    return subject ? subject.title : value;
  }
  if (key === 'associationStatus') {
    return value === 'has-associations' ? 'Has Associations' :
           value === 'no-associations' ? 'No Associations' : value;
  }
  if (key === 'modifiedSince') {
    const labels = {
      today: 'Today',
      week: 'This Week',
      month: 'This Month',
      year: 'This Year'
    };
    return labels[value] || value;
  }
  if (key === 'caseSensitive') {
    return 'Yes';
  }
  return value;
}

// Focus search input when component mounts
nextTick(() => {
  if (searchInput.value) {
    searchInput.value.focus();
  }
});

// Cleanup timeout on unmount
watch(() => null, () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
});
</script>

<style scoped>
.search-filter {
  margin-bottom: 1rem;
}

.input-group-text {
  background-color: #f8f9fa;
  border-color: #dee2e6;
}

.form-control:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.card-header {
  padding: 0.5rem 1rem;
  background-color: #f8f9fa;
}

.card-body {
  padding: 1rem;
}

.badge {
  font-size: 0.75em;
}

.btn-close {
  padding: 0;
  margin: 0;
  width: 12px;
  height: 12px;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}
</style>
