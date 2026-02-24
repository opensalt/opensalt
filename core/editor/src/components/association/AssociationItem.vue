<template>
  <div class="association-item d-flex justify-content-between align-items-center p-2 border rounded">
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
          <span v-if="frameworkTitle && !isLoading && targetTypeInfo.isCase" class="badge bg-info text-dark ms-2 framework-badge">
            <i class="bi bi-box-arrow-up-right me-1"></i>{{ frameworkTitle }}
          </span>
          <!-- Non-CASE item indicator -->
          <span v-if="!targetTypeInfo.isCase && isCrossFramework" class="badge bg-secondary ms-2 external-uri-badge">
            <i class="bi bi-link-45deg me-1"></i>External URI
          </span>
          <!-- Error indicator for failed fetches -->
          <span v-if="fetchError && targetTypeInfo.isCase" class="badge bg-warning text-dark ms-2 error-badge" :title="fetchError.message">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ fetchError.type === 'permission' ? 'No access' : fetchError.type === 'not_found' ? 'Not found' : 'Load error' }}
          </span>
        </div>

        <div v-if="notes" class="mb-1">
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

    <div class="association-actions btn-group btn-group-sm ms-3" v-if="!isReadOnly">
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
import { computed, ref, onMounted, toRef } from 'vue';
import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';
import { useCrossFrameworkItem } from '../../composables/useCrossFrameworkItem';

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
const currentDocumentStore = useCurrentDocumentStore();

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

const associationType = computed(() => {
  return props.association.associationType || props.association.type || 'Unknown';
});

// Get the appropriate node identifier based on direction
const nodeIdentifier = computed(() => {
  if (isReversed.value) {
    // Show origin when reversed
    const origin = props.association.originNodeURI || props.association.origin;
    return origin?.identifier;
  }

  // Show destination when normal
  const dest = props.association.destinationNodeURI || props.association.destination;
  return dest?.identifier;
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

// Get the URI string for display during loading
const nodeUriString = computed(() => {
  const uri = nodeURI.value?.uri;
  return uri || itemIdentifier.value || 'Loading...';
});

const notes = computed(() => {
  return props.association.notes || '';
});

const lastChangeDateTime = computed(() => {
  return props.association.lastChangeDateTime || '';
});

const groupTitle = computed(() => {
  if (!props.association.CFAssociationGroupingURI) return '';

  const groupId = props.association.CFAssociationGroupingURI.identifier;
  const group = props.associationGroups.find(g => g.id === groupId);
  return group?.title || '';
});

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}
</script>

<style scoped>
.association-item {
  transition: background-color 0.2s ease;
}

.association-item:hover {
  background-color: #f8f9fa;
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

.framework-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
}

.framework-badge i {
  font-size: 0.85em;
}

.external-uri-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
}

.external-uri-badge i {
  font-size: 0.85em;
}

.error-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
}

.error-badge i {
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
