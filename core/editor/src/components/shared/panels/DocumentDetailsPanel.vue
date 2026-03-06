<template>
  <div class="document-details-panel">
    <!-- Document Header -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 d-flex align-items-center">
            <img :src="docIcon" class="me-2 item-icon" aria-hidden="true" />
            Document Details
        </h6>
        <div class="btn-group btn-group-sm" v-if="!isReadOnly">
          <button
            type="button"
            class="btn btn-outline-primary"
            @click="$emit('edit-document')"
            title="Edit document"
          >
            <i class="bi bi-pencil"></i>
          </button>
        </div>
        <div v-else class="text-muted small" title="Document is read-only">
          <i class="bi bi-lock-fill"></i> Read-only
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
          <strong>Subject: </strong>
          <div class="mt-1 d-inline-flex">
            <span v-for="subject in document.subject" :key="subject" class="badge bg-secondary me-1">
              {{ subject }}
            </span>
          </div>
        </div>

        <div v-if="document.publisher" class="mt-2">
          <strong>Publisher:</strong> {{ document.publisher }}
        </div>

        <div v-if="document.licenseURI" class="mt-2 text-truncate">
            <strong>License:</strong> <span class="ms-1">{{ licenseName }}</span>
        </div>

        <div v-if="document.officialSourceURL" class="mt-2">
          <strong>Source URL:</strong> <a :href="document.officialSourceURL" target="_blank" class="text-decoration-none">
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
        <div class="d-flex flex-wrap gap-2">
          <!-- Export - always available to all users -->
          <button type="button" class="btn btn-outline-secondary" @click="$emit('export-document')">
            <i class="bi bi-box-arrow-up-right"></i> Export
          </button>

          <!-- Editor-only actions -->
          <template v-if="!isReadOnly">
            <div class="btn-group">
              <button type="button" class="btn btn-outline-primary" @click="showModal('general')">
                <i class="bi bi-plus-circle"></i> Add Root Item
              </button>
              <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
              </button>
              <ul class="dropdown-menu">
                <li v-for="type in availableTypes" :key="type">
                  <a
                    class="dropdown-item"
                    @click="showModal(type)"
                    href="#"
                    :aria-label="`Add ${getDisplayName(type)}`"
                  >
                    Add {{ getDisplayName(type) }}
                  </a>
                </li>
              </ul>
            </div>
            <button type="button" class="btn btn-outline-secondary" @click="$emit('manage-association-groups')">
              <i class="bi bi-tags"></i> Manage Groups
            </button>
            <button type="button" class="btn btn-outline-secondary" @click="$emit('import-children')">
              <i class="bi bi-file-earmark-arrow-up"></i> Import Children
            </button>
            <button type="button" class="btn btn-outline-secondary" @click="$emit('update-framework')">
              <i class="bi bi-arrow-repeat"></i> Update Framework
            </button>
            <button type="button" class="btn btn-outline-secondary" @click="$emit('clone-framework')">
              <i class="bi bi-copy"></i> Clone Framework
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- Comments -->
    <CommentModule
      v-if="document?.identifier"
      item-type="document"
      :item-identifier="document.identifier"
    />
  </div>

  <!-- Dynamic Modal -->
  <div v-if="isModalVisible">
    <component
      :is="modalComponent"
      :parent-item="null"
      :show="isModalVisible"
      :item-type="selectedType"
      @created="handleCreated"
      @hidden="handleHidden"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useDynamicModal } from '../../../composables/useDynamicModal.js';
import CommentModule from '../CommentModule.vue';
import docIcon from '@/assets/icons/ph/graph-fill.svg';

const props = defineProps({
  document: {
    type: Object,
    required: true
  },
  associationGroups: {
    type: Array,
    default: () => []
  },
  isViewingDifferentFramework: {
    type: Boolean,
    default: false
  }
});

import { useSessionStore } from '../../../stores/sessionStore';
import { useCurrentDocumentStore } from '../../../stores/currentDocumentStore';
import { useEditorContextStore } from '../../../stores/editorContextStore';

const sessionStore = useSessionStore();
const isReadOnly = computed(() => props.isViewingDifferentFramework || props.document?.isReadOnly || !sessionStore.isAuthenticated);

// Get license name from definitions
const currentDocumentStore = useCurrentDocumentStore();
const contextStore = useEditorContextStore();
const licenseName = computed(() => {
  if (!props.document?.licenseURI?.identifier) {
    return null;
  }

  const licenseId = props.document.licenseURI.identifier;
  const licenses = currentDocumentStore.currentDocumentDefinitions?.CFLicenses || [];

  // Find license by identifier in definitions
  const licenseDef = licenses.find(lic => lic.identifier === licenseId);

  // Return license title if found, otherwise fall back to the URI
  if (licenseDef?.title) {
    return licenseDef.title;
  }

  // Fallback to the license URI or identifier
  return props.document.licenseURI.uri || props.document.licenseURI.identifier;
});

const emit = defineEmits([
  'edit-document',
  'add-root-item',
  'manage-association-groups',
  'import-children',
  'update-framework',
  'export-document',
  'clone-framework'
]);

const availableTypes = ['general', 'assessment', 'course', 'credential', 'job', 'organization', 'public_key', 'identifier'];

const { showModal, selectedType, isModalVisible, handleCreated, modalComponent, handleHidden } = useDynamicModal(
  null,
  (newItem) => { emit('add-root-item', newItem); },
  availableTypes
);

function getDisplayName(type) {
  const displayNames = {
    general: 'General Item',
    assessment: 'Assessment',
    course: 'Course',
    credential: 'Credential',
    job: 'Job',
    organization: 'Organization',
    'public_key': 'Public Key',
    identifier: 'Identifier'
  };
  return displayNames[type] || type;
}

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}

// Cached item count to avoid recomputation on every render
const cachedItemCount = ref(0);

// Recursive function to count items in the hierarchy, excluding cross-framework placeholders
function countItemsRecursively(items) {
  if (!items || !Array.isArray(items)) return 0;

  let count = 0;
  for (const item of items) {
    // Skip cross-framework placeholder items — they belong to other frameworks
    if (item.isCrossFramework) continue;
    count += 1;
    if (item.children && item.children.length > 0) {
      count += countItemsRecursively(item.children);
    }
  }
  return count;
}

// Watch for changes in document items and update cached item count
watch(
  () => props.document?.items,
  (newItems) => {
    cachedItemCount.value = countItemsRecursively(newItems);
  },
  { immediate: true, deep: true }
);

// Document statistics
const itemCount = computed(() => cachedItemCount.value);

// Count associations from the raw package data in the context store.
// The tree nodes don't carry associations, so we read CFAssociations from
// the loaded package and count non-isChildOf ones.
const associationCount = computed(() => {
  const docId = props.document?.identifier || props.document?.id;
  if (!docId) return 0;

  const pkg = contextStore.loadedPackages.get(docId);
  if (!pkg?.CFAssociations) return 0;

  return pkg.CFAssociations.filter(
    assoc => assoc.associationType !== 'isChildOf'
  ).length;
});

// Count actual association groupings from the store, not the synthetic groups
// (which always include 'All Groups' and 'Default Group').
const associationGroupCount = computed(() => {
  return currentDocumentStore.currentDocumentAssociationGroupings?.length || 0;
});
</script>

<style scoped>
/* Document details specific styles can be added here */
</style>
