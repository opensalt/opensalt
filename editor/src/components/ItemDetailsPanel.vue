<template>
  <div class="item-details-panel">
    <!-- Document Details (when no item selected) -->
    <div v-if="!selectedItem && currentDocument" class="document-details">
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
            <span class="badge bg-primary me-2">{{ currentDocument.status || 'Draft' }}</span>
            {{ currentDocument.title || 'Untitled Document' }}
          </h5>

          <div v-if="currentDocument.description" class="mb-3">
            <strong>Description:</strong>
            <p class="mt-1">{{ currentDocument.description }}</p>
          </div>

          <div class="row">
            <div class="col-sm-6">
              <strong>Creator:</strong> {{ currentDocument.creator || 'Unknown' }}
            </div>
            <div class="col-sm-6">
              <strong>Language:</strong> {{ currentDocument.language || 'en' }}
            </div>
          </div>

          <div class="row mt-2">
            <div class="col-sm-6">
              <strong>Version:</strong> {{ currentDocument.version || '1.0' }}
            </div>
            <div class="col-sm-6">
              <strong>Framework Type:</strong> {{ currentDocument.frameworkType || 'Standard' }}
            </div>
          </div>

          <div v-if="currentDocument.subject && currentDocument.subject.length > 0" class="mt-3">
            <strong>Subject:</strong>
            <div class="mt-1">
              <span v-for="subject in currentDocument.subject" :key="subject" class="badge bg-secondary me-1">
                {{ subject }}
              </span>
            </div>
          </div>

          <div v-if="currentDocument.publisher" class="mt-2">
            <strong>Publisher:</strong> {{ currentDocument.publisher }}
          </div>

          <div v-if="currentDocument.officialSourceURL" class="mt-2">
            <strong>Source URL:</strong>
            <a :href="currentDocument.officialSourceURL" target="_blank" class="text-decoration-none">
              {{ currentDocument.officialSourceURL }}
            </a>
          </div>

          <div v-if="currentDocument.notes" class="mt-3">
            <strong>Notes:</strong>
            <p class="mt-1">{{ currentDocument.notes }}</p>
          </div>

          <div v-if="currentDocument.lastModified" class="mt-2">
            <small class="text-muted">
              Last modified: {{ formatDate(currentDocument.lastModified) }}
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

    <!-- No Document Loaded -->
    <div v-else-if="!selectedItem && !currentDocument" class="text-center text-muted p-4">
      <i class="bi bi-file-earmark fs-1 mb-3"></i>
      <p>No document loaded</p>
    </div>

    <!-- Item Details (when item selected) -->
    <div v-else class="item-details">
      <!-- Item Header -->
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Item Details</h6>
          <div class="btn-group btn-group-sm">
            <button
              type="button"
              class="btn btn-outline-primary"
              @click="$emit('edit-item', selectedItem)"
              title="Edit item"
            >
              <i class="bi bi-pencil"></i>
            </button>
            <button
              type="button"
              class="btn btn-outline-danger"
              @click="$emit('delete-item', selectedItem)"
              title="Delete item"
            >
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
        <div class="card-body">
          <h5 class="card-title">
            <span v-if="selectedItem.humanCodingScheme" class="badge bg-secondary me-2">
              {{ selectedItem.humanCodingScheme }}
            </span>
            {{ selectedItem.title || selectedItem.abbreviatedTitle || selectedItem.identifier }}
          </h5>

          <div v-if="selectedItem.fullStatement" class="mb-3">
            <strong>Full Statement:</strong>
            <p class="mt-1">{{ selectedItem.fullStatement }}</p>
          </div>

          <div v-if="selectedItem.abbreviatedStatement && selectedItem.abbreviatedStatement !== selectedItem.fullStatement" class="mb-3">
            <strong>Abbreviated Statement:</strong>
            <p class="mt-1">{{ selectedItem.abbreviatedStatement }}</p>
          </div>

          <div v-if="selectedItem.notes" class="mb-3">
            <strong>Notes:</strong>
            <p class="mt-1">{{ selectedItem.notes }}</p>
          </div>

          <div class="row">
            <div class="col-sm-6">
              <strong>Item Type:</strong> {{ selectedItem.itemType || 'General' }}
            </div>
            <div class="col-sm-6">
              <strong>Language:</strong> {{ selectedItem.language || 'en' }}
            </div>
          </div>

          <div v-if="selectedItem.lastChanged" class="mt-2">
            <small class="text-muted">
              Last changed: {{ formatDate(selectedItem.lastChanged) }}
            </small>
          </div>
        </div>
      </div>

      <!-- Associations -->
      <div v-if="groupedAssociations.length > 0" class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Associations</h6>
          <button type="button" class="btn btn-sm btn-outline-primary" @click="$emit('add-association', selectedItem)">
            <i class="bi bi-plus"></i> Add
          </button>
        </div>
        <div class="card-body">
          <GroupedAssocitationDisplay
            v-for="group in groupedAssociations"
            :key="group.type"
            :association-type="group.type"
            :associations="group.associations"
            :association-groups="associationGroups"
            @edit-association="$emit('edit-association', $event)"
            @delete-association="$emit('delete-association', $event)"
          />
        </div>
      </div>

      <!-- Actions -->
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0">Actions</h6>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <button type="button" class="btn btn-outline-primary" @click="$emit('add-child', selectedItem)">
              <i class="bi bi-plus-circle"></i> Add Child Item
            </button>
            <button type="button" class="btn btn-outline-secondary" @click="$emit('add-exemplar', selectedItem)">
              <i class="bi bi-link-45deg"></i> Add Exemplar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import AssociationItem from './AssociationItem.vue';
import GroupedAssocitationDisplay from './GroupedAssociationDisplay.vue';

const props = defineProps({
  selectedItem: Object,
  currentDocument: Object,
  associationGroups: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits([
  'edit-item',
  'delete-item',
  'add-child',
  'add-exemplar',
  'add-association',
  'edit-association',
  'delete-association',
  'edit-document',
  'add-root-item',
  'manage-association-groups'
]);

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}

// Group associations by type (excluding isChildOf)
const groupedAssociations = computed(() => {
  if (!props.selectedItem?.associations) return [];

  const filtered = props.selectedItem.associations.filter(assoc =>
    assoc.associationType !== 'isChildOf' && assoc.type !== 'isChildOf'
  );

  // Group by association type
  const groups = {};
  filtered.forEach(assoc => {
    const type = assoc.associationType || assoc.type || 'unknown';
    if (!groups[type]) {
      groups[type] = [];
    }
    groups[type].push(assoc);
  });

  // Convert to array format for template
  return Object.keys(groups).map(type => ({
    type,
    associations: groups[type]
  })).sort((a, b) => a.type.localeCompare(b.type));
});

// Document statistics
const itemCount = computed(() => {
  return props.currentDocument?.items?.length || 0;
});

const associationCount = computed(() => {
  if (!props.currentDocument?.items) return 0;
  return props.currentDocument.items.reduce((total, item) => {
    return total + (item.associations?.filter(assoc =>
      assoc.associationType !== 'isChildOf' && assoc.type !== 'isChildOf'
    ).length || 0);
  }, 0);
});

const associationGroupCount = computed(() => {
  return props.associationGroups?.filter(group => group.id !== 'all' && group.id !== 'default').length || 0;
});
</script>

<style scoped>
.associations-list {
  max-height: 300px;
  overflow-y: auto;
}
</style>
