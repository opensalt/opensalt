<template>
  <div class="association-item d-flex justify-content-between align-items-center p-2 border rounded" :class="{ 'cross-framework-assoc': isCrossFrameworkAssoc }">
    <div class="association-info flex-grow-1">
      <!--
      <div class="d-flex align-items-center mb-2">
        <span class="badge bg-primary me-2">{{ associationType }}</span>
        <span v-if="groupTitle" class="badge bg-secondary">{{ groupTitle }}</span>
      </div>
        -->

      <div class="association-details">
        <div class="mb-1 d-flex align-items-center flex-wrap gap-1">
          <strong v-if="false">{{ nodeLabel }}</strong>
          <!-- Show URI while loading, with a small loading indicator -->
          <template v-if="isLoading && targetTypeInfo.isCase">
            <span class="text-muted uri-display">{{ nodeUriString }}</span>
            <span class="spinner-border spinner-border-sm text-secondary ms-2" role="status" aria-label="Loading item information">
              <span class="visually-hidden">Loading...</span>
            </span>
          </template>
          <!-- Show display title when not loading -->
          <template v-else>
            <span class="ms-2" v-html="displayTitle"></span>
          </template>
          <!-- Framework badge for cross-framework CASE items -->
          <span v-if="frameworkTitle && !isLoading && targetTypeInfo.isCase" class="badge framework-badge ms-2">
            <i class="bi bi-box-arrow-up-right me-1"></i>{{ frameworkTitle }}
          </span>
          <!-- Source framework badge for associations from other frameworks -->
          <span v-if="sourceFrameworkTitle" class="badge source-framework-badge ms-2" :title="'Association defined in: ' + sourceFrameworkTitle">
            <i class="bi bi-folder2-open me-1"></i>Source: {{ sourceFrameworkTitle }}
          </span>
          <!-- Non-CASE item indicator -->
          <span v-if="!targetTypeInfo.isCase && isCrossFramework" class="badge external-uri-badge ms-2">
            <i class="bi bi-link-45deg me-1"></i>External URI
          </span>
          <!-- Error indicator for failed fetches -->
          <span v-if="fetchError && targetTypeInfo.isCase" class="badge error-badge ms-2" :title="fetchError.message">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ fetchError.type === 'permission' ? 'No access' : fetchError.type === 'not_found' ? 'Not found' : 'Load error' }}
          </span>
          <!-- Loading indicator for queued frameworks -->
          <span v-if="isDocumentQueued && !frameworkTitle && !isLoading && targetTypeInfo.isCase" class="badge loading-badge ms-2" title="Framework queued for loading">
            <i class="bi bi-arrow-repeat me-1" role="status" aria-hidden="true"></i>
            Queued
          </span>
        </div>

        <div v-if="notes" class="mb-1 ms-2">
          <strong>Annotation:</strong>
          <span class="ms-2 text-muted">{{ notes }}</span>
        </div>

        <div v-if="lastChangeDateTime && false" class="mb-1">
          <small class="text-muted">
            <strong>Last changed:</strong> {{ formatDate(lastChangeDateTime) }}
          </small>
        </div>
      </div>
    </div>

    <div class="association-actions btn-group btn-group-sm ms-3" v-if="!isReadOnly && !isCrossFrameworkAssoc">
      <button
        type="button"
        class="btn btn-outline-primary"
        @click="$emit('edit', association)"
        title="Edit association"
      >
        <i class="bi bi-pencil"></i>
      </button>
      <button
        type="button"
        class="btn btn-outline-danger"
        @click="$emit('delete', association)"
        title="Delete association"
      >
        <i class="bi bi-trash"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, toRef, watch } from 'vue';
import { useCrossFrameworkItem } from '../../composables/useCrossFrameworkItem';
import { useRelatedFrameworksQueue } from '../../composables/useRelatedFrameworksQueue.js';
import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';

// Lazy-loaded markdown renderer with caching
let markdownRendererPromise = null;
let cachedRender = null;

async function getMarkdownRenderer() {
  if (cachedRender) {
    return cachedRender;
  }
  if (!markdownRendererPromise) {
    markdownRendererPromise = import('../../utils/render-md.js').then(renderModule => {
      cachedRender = renderModule.default;
      return cachedRender;
    });
  }
  return markdownRendererPromise;
}

// Ref to store loaded renderer functions
const render = ref(null);

// Load renderer on mount
onMounted(async () => {
  render.value = await getMarkdownRenderer();
});

const props = defineProps({
  association: {
    type: Object,
    required: true
  },
  associationGroups: {
    type: Array,
    default: () => []
  },
  direction: {
    type: String,
    default: 'normal'
  },
  isReadOnly: {
    type: Boolean,
    default: false
  },
  itemIdentifier: {
    type: String,
    required: true
  }
});

const emit = defineEmits(['edit', 'delete']);

// Use the related frameworks queue composable
const { getQueueStatus } = useRelatedFrameworksQueue();

// Access current document store for resolving source framework titles
const currentDocumentStore = useCurrentDocumentStore();

// Check if this association comes from a different framework (has CFDocumentURI set by mergedAssociations)
const isCrossFrameworkAssoc = computed(() => {
  return !!props.association.CFDocumentURI;
});

// Resolve the source framework title from the associatedDocuments cache
const sourceFrameworkTitle = computed(() => {
  const frameworkId = props.association.CFDocumentURI;
  if (!frameworkId) return null;

  const doc = currentDocumentStore.associatedDocuments.get(frameworkId);
  return doc?.title || null;
});

// Use the cross-framework item composable
const {
  itemData,
  itemTitle,
  frameworkTitle,
  isLoading,
  isCrossFramework,
  targetTypeInfo,
  fetchError,
  nodeURI
} = useCrossFrameworkItem({
  association: toRef(props, 'association'),
  direction: toRef(props, 'direction')
});

// Determine if association is reversed (item is destination, not origin)
const isReversed = computed(() => {
    return props.direction === 'reversed';
});

// Display title with markdown rendering support
const displayTitle = computed(() => {
  const title = itemTitle.value;
  const item = itemData.value;

  if (item) {
    // Build display with markdown rendering
    const parts = [];
    if (item.humanCodingScheme) {
      parts.push('<strong>' + (render.value ? render.value.escaped(item.humanCodingScheme) : item.humanCodingScheme) + '</strong>');
    }
    if (item.abbreviatedStatement) {
      parts.push(render.value ? render.value.escaped(item.abbreviatedStatement) : item.abbreviatedStatement);
    } else if (item.fullStatement) {
      const statement = item.fullStatement;
      const truncated = statement.length > 100 ? statement.substring(0, 100) + '...' : statement;
      parts.push(render.value ? render.value.inline(truncated) : truncated);
    }

    if (parts.length > 0) {
      return parts.join(' ');
    }
  }

  // Fallback to itemTitle from composable (which includes nodeURI title fallback)
  return render.value ? render.value.escaped(title) : title;
});

const nodeLabel = computed(() => {
  return isReversed.value ? 'Origin:' : 'Destination:';
});

// Get URI string for display during loading
const nodeUriString = computed(() => {
  const uri = nodeURI.value?.uri;
  return uri || props.itemIdentifier || 'Loading...';
});

const notes = computed(() => {
  return props.association.notes || '';
});

const lastChangeDateTime = computed(() => {
  return props.association.lastChangeDateTime || '';
});

// Get queue status for the document
const queueFetchStatus = computed(() => {
  const uri = nodeURI.value?.uri;
  if (!uri) return 'not_queued';
  return getQueueStatus(uri);
});

// Check if document is queued
const isDocumentQueued = computed(() => {
  return queueFetchStatus.value === 'queued';
});

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}

// Watch for changes in fetch status to trigger re-renders when pre-fetched documents become available
watch(
  () => [queueFetchStatus.value, frameworkTitle.value],
  () => {
    // When fetch status changes, component should re-render
    // This ensures framework badges show immediately when pre-fetched documents are loaded
  }
);
</script>

<style scoped>
.association-item {
  transition: background-color 0.2s ease;
}

.association-item:hover {
  background-color: #f8f9fa;
}

/* Cross-framework association background */
.cross-framework-assoc {
  background-color: #e8f0fe;
  border-color: #a8c7fa !important;
}

.cross-framework-assoc:hover {
  background-color: #d3e3fd;
}

.association-info {
  min-width: 0; /* Allow text to wrap */
}

.association-details {
  font-size: 0.875rem;
}

.badge {
  font-size: 0.75em;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

/* WCAG 2.1 AA compliant badge colors (4.5:1+ contrast ratio) */
.framework-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
  background-color: #0c63e4;
  color: #ffffff;
}

.framework-badge i {
  font-size: 0.85em;
}

.source-framework-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
  background-color: #1a5276;
  color: #ffffff;
}

.source-framework-badge i {
  font-size: 0.85em;
}

.external-uri-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
  background-color: #495057;
  color: #ffffff;
}

.external-uri-badge i {
  font-size: 0.85em;
}

.error-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
  background-color: #856404;
  color: #ffffff;
}

.error-badge i {
  font-size: 0.85em;
}

.loading-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
  background-color: #495057;
  color: #ffffff;
}

.loading-badge i {
  font-size: 0.85em;
}

.gap-1 {
  gap: 0.25rem;
}

.uri-display {
  font-family: monospace;
  font-size: 0.85em;
  word-break: break-all;
}
</style>
