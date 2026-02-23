<template>
  <!-- Main row -->
  <tr class="association-row">
    <!-- Origin Column -->
    <td class="py-3">
      <div class="item-display">
        <span v-if="originDisplay.humanCodingScheme" class="item-human-coding-scheme">
          {{ originDisplay.humanCodingScheme }}
        </span>
        <span v-if="originDisplay.statement" class="item-statement">
          {{ originDisplay.truncatedStatement }}
        </span>
        <span v-if="!originDisplay.humanCodingScheme && !originDisplay.statement" class="text-muted">
          Unknown
        </span>
      </div>
    </td>

    <!-- Association Type Column -->
    <td class="py-3">
      <div class="association-type-badge">
        <i :class="typeDisplay.icon" class="me-1" aria-hidden="true"></i>
        <span class="text-capitalize">{{ typeDisplay.formatted }}</span>
        <span
          v-if="isExtendedType"
          class="badge bg-warning ms-2"
          title="This is an extended association type"
        >
          Extended
        </span>
      </div>
    </td>

    <!-- Destination Column -->
    <td class="py-3">
      <div class="item-display">
        <span v-if="destinationDisplay.humanCodingScheme" class="item-human-coding-scheme">
          {{ destinationDisplay.humanCodingScheme }}
        </span>
        <span v-if="destinationDisplay.statement" class="item-statement">
          {{ destinationDisplay.truncatedStatement }}
        </span>
        <span v-if="!destinationDisplay.humanCodingScheme && !destinationDisplay.statement" class="text-muted">
          Unknown
        </span>
      </div>
    </td>

    <!-- Actions Column -->
    <td class="py-3 text-end">
      <div v-if="!isReadOnly && association.associationType !== 'isChildOf' && association.type !== 'isChildOf'" class="btn-group btn-group-sm" role="group">
        <button
          type="button"
          class="btn btn-outline-primary"
          @click="$emit('edit', association)"
          :aria-label="`Edit association from ${originDisplay.humanCodingScheme || 'Unknown'} to ${destinationDisplay.humanCodingScheme || 'Unknown'}`"
          title="Edit association"
        >
          <i class="bi bi-pencil" aria-hidden="true"></i>
        </button>
        <button
          type="button"
          class="btn btn-outline-danger"
          @click="$emit('delete', association)"
          :aria-label="`Delete association from ${originDisplay.humanCodingScheme || 'Unknown'} to ${destinationDisplay.humanCodingScheme || 'Unknown'}`"
          title="Delete association"
        >
          <i class="bi bi-trash" aria-hidden="true"></i>
        </button>
      </div>
    </td>
  </tr>

  <!-- Annotation Row (if notes exist) -->
  <tr v-if="hasAnnotation" class="annotation-row">
    <td colspan="4" class="py-2 px-3">
      <div class="annotation-content">
        <i class="bi bi-sticky me-2 text-muted" aria-hidden="true"></i>
        <strong class="text-muted small">Annotation:</strong>
        <span class="ms-2 text-muted small">{{ annotation }}</span>
      </div>
    </td>
  </tr>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
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
  itemIdentifier: {
    type: String,
    default: null
  },
  isReadOnly: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['edit', 'delete']);

const currentDocumentStore = useCurrentDocumentStore();

// Helper function to find item by identifier
function findItemById(items, identifier) {
  if (!items || !identifier) return null;
  return items.find(i => i.identifier === identifier || i.id === identifier);
}

// Helper function to truncate text
function truncateText(text, maxLength = 50) {
  if (!text) return '';
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
}

// Origin item display
const originDisplay = computed(() => {
  const identifier = props.association.originNodeURI?.identifier ||
                     props.association.origin?.identifier;
  const items = currentDocumentStore.currentDocument?.items;
  const item = findItemById(items, identifier);

  if (item) {
    return {
      humanCodingScheme: item.humanCodingScheme || '',
      statement: item.abbreviatedStatement || item.fullStatement || '',
      truncatedStatement: truncateText(item.abbreviatedStatement || item.fullStatement || ''),
      identifier: identifier
    };
  }

  // Fall back to title from nodeURI
  const origin = props.association.originNodeURI || props.association.origin;
  return {
    humanCodingScheme: '',
    statement: origin?.title || '',
    truncatedStatement: truncateText(origin?.title || ''),
    identifier: identifier
  };
});

// Destination item display
const destinationDisplay = computed(() => {
  const identifier = props.association.destinationNodeURI?.identifier ||
                     props.association.destination?.identifier;
  const items = currentDocumentStore.currentDocument?.items;
  const item = findItemById(items, identifier);

  if (item) {
    return {
      humanCodingScheme: item.humanCodingScheme || '',
      statement: item.abbreviatedStatement || item.fullStatement || '',
      truncatedStatement: truncateText(item.abbreviatedStatement || item.fullStatement || ''),
      identifier: identifier
    };
  }

  // Fall back to title from nodeURI
  const dest = props.association.destinationNodeURI || props.association.destination;
  return {
    humanCodingScheme: '',
    statement: dest?.title || '',
    truncatedStatement: truncateText(dest?.title || ''),
    identifier: identifier
  };
});

// Association type display
const typeDisplay = computed(() => {
  const type = props.association.associationType || props.association.type || 'unknown';
  return {
    raw: type,
    formatted: formatAssociationType(type),
    icon: getAssociationIcon(type)
  };
});

// Check if this is an extended type
const isExtendedType = computed(() => {
  const type = props.association.associationType || props.association.type || '';
  return type.match(/^ext:/i);
});

// Annotation
const hasAnnotation = computed(() => !!props.association.notes);
const annotation = computed(() => props.association.notes || '');

// Helper functions
function formatAssociationType(type) {
  if (!type) return 'Unknown';

  // Remove ext: prefix for display
  let displayType = type;
  if (type.match(/^ext:/)) {
    displayType = type.replace(/^ext:/, '');
  }

  // Convert camelCase to readable format
  return displayType
    .replace(/([A-Z])/g, ' $1') // Add space before capital letters
    .replace(/^./, str => str.toUpperCase()) // Capitalize first letter
    .trim();
}

function getAssociationIcon(type) {
  const iconMap = {
    'isChildOf': 'bi bi-diagram-3',
    'isPeerOf': 'bi bi-share',
    'isPartOf': 'bi bi-puzzle',
    'exactMatchOf': 'bi bi-check-circle',
    'precedes': 'bi bi-arrow-right',
    'isRelatedTo': 'bi bi-link',
    'replacedBy': 'bi bi-arrow-clockwise',
    'exemplar': 'bi bi-star',
    'hasSkillLevel': 'bi bi-bar-chart',
    'isTranslationOf': 'bi bi-translate'
  };

  return iconMap[type] || 'bi bi-link-45deg';
}
</script>

<style scoped>
.association-row {
  transition: background-color 0.2s ease;
}

.association-row:hover {
  background-color: #f8f9fa;
}

.item-display {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.item-human-coding-scheme {
  font-weight: 600;
  color: #495057;
  font-size: 0.875rem;
}

.item-statement {
  color: #6c757d;
  font-size: 0.8125rem;
  line-height: 1.4;
}

.association-type-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.875rem;
}

.annotation-row {
  background-color: #f8f9fa;
  border-top: none !important;
}

/* Remove top border from annotation row cell */
.annotation-row td {
  border-top: none !important;
}

/* Remove bottom border from main row when followed by annotation row */
.association-row:has(+ .annotation-row) td {
  border-bottom: none !important;
}

.annotation-content {
  display: flex;
  align-items: flex-start;
  flex-wrap: wrap;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}
</style>
