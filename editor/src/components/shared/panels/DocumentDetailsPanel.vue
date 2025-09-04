<template>
  <div class="document-details-panel">
    <!-- Document Header -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Document Details</h6>
        <div class="btn-group btn-group-sm">
          <button
            type="button"
            class="btn btn-outline-primary"
            @click="$emit('edit-document')"
            title="Edit document"
          >
            <i class="bi bi-pencil"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <h5 class="card-title">
          <span class="badge bg-primary me-2">{{ document.status || 'Draft' }}</span>
          {{ document.title || 'Untitled Document' }}
        </h5>

        <div v-if="document.description" class="mb-3">
          <strong>Description:</strong>
          <p class="mt-1">{{ document.description }}</p>
        </div>

        <div class="row">
          <div class="col-sm-6">
            <strong>Creator:</strong> {{ document.creator || 'Unknown' }}
          </div>
          <div class="col-sm-6" v-if="document.language?.length">
            <strong>Language:</strong> {{ document.language || '' }}
          </div>
        </div>

        <div class="row mt-2">
          <div class="col-sm-6" v-if="document.version?.length">
            <strong>Version:</strong> {{ document.version || '' }}
          </div>
          <div class="col-sm-6">
            <strong>Framework Type:</strong> {{ document.frameworkType || 'Standard' }}
          </div>
        </div>

        <div v-if="document.subject && document.subject.length > 0" class="mt-3">
          <strong>Subject:</strong>
          <div class="mt-1">
            <span v-for="subject in document.subject" :key="subject" class="badge bg-secondary me-1">
              {{ subject }}
            </span>
          </div>
        </div>

        <div v-if="document.publisher" class="mt-2">
          <strong>Publisher:</strong> {{ document.publisher }}
        </div>

        <div v-if="document.officialSourceURL" class="mt-2">
          <strong>Source URL:</strong>
          <a :href="document.officialSourceURL" target="_blank" class="text-decoration-none">
            {{ document.officialSourceURL }}
          </a>
        </div>

        <div v-if="document.notes" class="mt-3">
          <strong>Notes:</strong>
          <p class="mt-1">{{ document.notes }}</p>
        </div>

        <div v-if="document.lastModified" class="mt-2">
          <small class="text-muted">
            Last modified: {{ formatDate(document.lastModified) }}
          </small>
        </div>
      </div>
    </div>

    <!-- Document Statistics -->
    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0">Document Statistics</h6>
      </div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col-4">
            <div class="fs-4 fw-bold text-primary">{{ itemCount }}</div>
            <div class="text-muted small">Items</div>
          </div>
          <div class="col-4">
            <div class="fs-4 fw-bold text-success">{{ associationCount }}</div>
            <div class="text-muted small">Associations</div>
          </div>
          <div class="col-4">
            <div class="fs-4 fw-bold text-info">{{ associationGroupCount }}</div>
            <div class="text-muted small">Groups</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Document Actions -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">Actions</h6>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <button type="button" class="btn btn-outline-primary" @click="$emit('add-root-item')">
            <i class="bi bi-plus-circle"></i> Add Root Item
          </button>
          <button type="button" class="btn btn-outline-secondary" @click="$emit('manage-association-groups')">
            <i class="bi bi-tags"></i> Manage Groups
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  document: {
    type: Object,
    required: true
  },
  associationGroups: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits([
  'edit-document',
  'add-root-item',
  'manage-association-groups'
]);

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}

// Recursive function to count all items in the hierarchy
function countItemsRecursively(items) {
  if (!items || !Array.isArray(items)) return 0;

  let count = 0;
  for (const item of items) {
    count += 1; // Count this item
    if (item.children && item.children.length > 0) {
      count += countItemsRecursively(item.children); // Recursively count children
    }
  }
  return count;
}

// Document statistics
const itemCount = computed(() => {
  return countItemsRecursively(props.document?.items);
});

const associationCount = computed(() => {
  if (!props.document?.items) return 0;
  return props.document.items.reduce((total, item) => {
    return total + (item.associations?.filter(assoc =>
      assoc.type !== 'isChildOf'
    ).length || 0);
  }, 0);
});

const associationGroupCount = computed(() => {
  return props.associationGroups?.length || 0;
});
</script>

<style scoped>
/* Document details specific styles can be added here */
</style>
