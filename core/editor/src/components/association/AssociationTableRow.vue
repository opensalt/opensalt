<template>
  <tbody class="association-tbody" :class="{ 'cross-framework-tbody': isAssociationFromDifferentDisplayedFramework }">
    <!-- Main row -->
    <tr class="association-row">
      <!-- Origin Column -->
      <td class="py-3">
        <div class="item-display">
          <template v-if="originDisplay.isLoading">
            <span class="text-muted uri-display">{{ originDisplay.truncatedStatement }}</span>
            <span class="spinner-border spinner-border-sm text-secondary ms-2" role="status" aria-label="Loading item information">
              <span class="visually-hidden">Loading...</span>
            </span>
          </template>
          <template v-else>
            <div class="d-flex align-items-center flex-wrap gap-1">
              <a v-if="originLinkInfo.type === 'same-framework'" :href="originLinkInfo.href" class="association-title-link" @click.prevent="onOriginClick">
                <span v-if="originDisplay.humanCodingScheme" class="item-human-coding-scheme">{{ originDisplay.humanCodingScheme }}:</span>
                <span v-if="originDisplay.statement" class="item-statement">{{ originDisplay.truncatedStatement }}</span>
                <span v-if="!originDisplay.humanCodingScheme && !originDisplay.statement" class="text-muted">Unknown</span>
              </a>
              <a v-else-if="originLinkInfo.type === 'cross-framework'" :href="originLinkInfo.href" target="_blank" rel="noopener noreferrer" class="association-title-link">
                <span v-if="originDisplay.humanCodingScheme" class="item-human-coding-scheme">{{ originDisplay.humanCodingScheme }}:</span>
                <span v-if="originDisplay.statement" class="item-statement">{{ originDisplay.truncatedStatement }}</span>
                <span v-if="!originDisplay.humanCodingScheme && !originDisplay.statement" class="text-muted">Unknown</span>
              </a>
              <a v-else-if="originLinkInfo.type === 'external'" :href="originLinkInfo.href" target="_blank" rel="noopener noreferrer" class="association-title-link">
                <span v-if="originDisplay.humanCodingScheme" class="item-human-coding-scheme">{{ originDisplay.humanCodingScheme }}:</span>
                <span v-if="originDisplay.statement" class="item-statement">{{ originDisplay.truncatedStatement }}</span>
                <span v-if="!originDisplay.humanCodingScheme && !originDisplay.statement" class="text-muted">Unknown</span>
              </a>
              <template v-else>
                <span v-if="originDisplay.humanCodingScheme" class="item-human-coding-scheme">
                  {{ originDisplay.humanCodingScheme }}:
                </span>
                <span v-if="originDisplay.statement" class="item-statement">
                  {{ originDisplay.truncatedStatement }}
                </span>
                <span v-if="!originDisplay.humanCodingScheme && !originDisplay.statement" class="text-muted">
                  Unknown
                </span>
              </template>
              <!-- Framework badge for cross-framework CASE items -->
              <span v-if="originFrameworkTitle && !isOriginLoading && originTargetTypeInfo.isCase" class="badge framework-badge" :title="`From: ${originFrameworkTitle}`">
                <i class="bi bi-box-arrow-up-right me-1"></i>{{ originFrameworkTitle }}
              </span>
              <!-- Non-CASE item indicator -->
              <span v-if="!originTargetTypeInfo.isCase && isOriginCrossFramework" class="badge external-uri-badge" title="External URI">
                <i class="bi bi-link-45deg me-1"></i>External
              </span>
            </div>
            <!-- Source framework tag moved to Origin column -->
            <div v-if="sourceFrameworkTitle" class="mt-2 text-muted small border-top pt-1 border-opacity-25" style="max-width: 250px;">
              <i class="bi bi-folder2-open me-1"></i>Source: {{ sourceFrameworkTitle }}
            </div>
          </template>
        </div>
      </td>

    <!-- Association Type Column -->
    <td class="py-3">
      <div class="association-type-badge">
        <i :class="typeDisplay.icon" class="me-1" aria-hidden="true"></i>
        <span class="text-capitalize">{{ typeDisplay.formatted }}</span>
        <span
          v-if="isExtendedType"
          class="badge bg-warning text-dark ms-2"
          title="This is an extended association type"
        >
          Extended
        </span>
      </div>
    </td>

    <!-- Destination Column -->
    <td class="py-3">
      <div class="item-display">
        <template v-if="destinationDisplay.isLoading">
          <span class="text-muted uri-display">{{ destinationDisplay.truncatedStatement }}</span>
          <span class="spinner-border spinner-border-sm text-secondary ms-2" role="status" aria-label="Loading item information">
            <span class="visually-hidden">Loading...</span>
          </span>
        </template>
        <template v-else>
          <div class="d-flex align-items-center flex-wrap gap-1">
            <a v-if="destinationLinkInfo.type === 'same-framework'" :href="destinationLinkInfo.href" class="association-title-link" @click.prevent="onDestinationClick">
              <span v-if="destinationDisplay.humanCodingScheme" class="item-human-coding-scheme">{{ destinationDisplay.humanCodingScheme }}:</span>
              <span v-if="destinationDisplay.statement" class="item-statement">{{ destinationDisplay.truncatedStatement }}</span>
              <span v-if="!destinationDisplay.humanCodingScheme && !destinationDisplay.statement" class="text-muted">Unknown</span>
            </a>
            <a v-else-if="destinationLinkInfo.type === 'cross-framework'" :href="destinationLinkInfo.href" target="_blank" rel="noopener noreferrer" class="association-title-link">
              <span v-if="destinationDisplay.humanCodingScheme" class="item-human-coding-scheme">{{ destinationDisplay.humanCodingScheme }}:</span>
              <span v-if="destinationDisplay.statement" class="item-statement">{{ destinationDisplay.truncatedStatement }}</span>
              <span v-if="!destinationDisplay.humanCodingScheme && !destinationDisplay.statement" class="text-muted">Unknown</span>
            </a>
            <a v-else-if="destinationLinkInfo.type === 'external'" :href="destinationLinkInfo.href" target="_blank" rel="noopener noreferrer" class="association-title-link">
              <span v-if="destinationDisplay.humanCodingScheme" class="item-human-coding-scheme">{{ destinationDisplay.humanCodingScheme }}:</span>
              <span v-if="destinationDisplay.statement" class="item-statement">{{ destinationDisplay.truncatedStatement }}</span>
              <span v-if="!destinationDisplay.humanCodingScheme && !destinationDisplay.statement" class="text-muted">Unknown</span>
            </a>
            <template v-else>
              <span v-if="destinationDisplay.humanCodingScheme" class="item-human-coding-scheme">
                {{ destinationDisplay.humanCodingScheme }}:
              </span>
              <span v-if="destinationDisplay.statement" class="item-statement">
                {{ destinationDisplay.truncatedStatement }}
              </span>
              <span v-if="!destinationDisplay.humanCodingScheme && !destinationDisplay.statement" class="text-muted">
                Unknown
              </span>
            </template>
            <!-- Framework badge for cross-framework CASE items -->
            <span v-if="destinationFrameworkTitle && !isDestinationLoading && destinationTargetTypeInfo.isCase" class="badge framework-badge" :title="`From: ${destinationFrameworkTitle}`">
              <i class="bi bi-box-arrow-up-right me-1"></i>{{ destinationFrameworkTitle }}
            </span>
            <!-- Non-CASE item indicator -->
            <span v-if="!destinationTargetTypeInfo.isCase && isDestinationCrossFramework" class="badge external-uri-badge" title="External URI">
              <i class="bi bi-link-45deg me-1"></i>External
            </span>
          </div>
        </template>
      </div>
    </td>

    <!-- Actions Column -->
    <td class="py-3 text-end">
      <div v-if="canManageAssociation || canDeleteAssociation" class="btn-group btn-group-sm" role="group">
        <button
          v-if="canManageAssociation"
          type="button"
          class="btn btn-outline-primary"
          @click="$emit('edit', association)"
          :aria-label="`Edit association from ${originDisplay.humanCodingScheme || 'Unknown'} to ${destinationDisplay.humanCodingScheme || 'Unknown'}`"
          title="Edit association"
        >
          <i class="bi bi-pencil" aria-hidden="true"></i>
        </button>
        <button
          v-if="canDeleteAssociation"
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

    <tr v-if="hasAnnotation" class="annotation-row">
      <td colspan="4" class="py-2 px-3">
        <div class="annotation-content">
          <i class="bi bi-sticky me-2 text-muted" aria-hidden="true"></i>
          <strong class="text-muted small">Annotation:</strong>
          <span class="ms-2 text-muted small">{{ annotation }}</span>
        </div>
      </td>
    </tr>
  </tbody>
</template>

<script setup>
import { computed, ref, onMounted, toRef, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCrossFrameworkItem } from '../../composables/useCrossFrameworkItem';
import { useAssociationPermissions } from '../../composables/useAssociationPermissions';
import { formatAssociationType, getAssociationIcon } from '../../utils/associationHelpers.js';

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
    type: [String, null],
    default: null
  },
  isReadOnly: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['edit', 'delete']);

const route = useRoute();
const router = useRouter();
const treeNavigation = inject('treeNavigation', null);

const {
  isAssociationFromDifferentDisplayedFramework,
  canManageAssociation,
  canDeleteAssociation,
  sourceFrameworkTitle
} = useAssociationPermissions(toRef(props, 'association'), { isReadOnly: toRef(props, 'isReadOnly') });

// Use cross-framework item composable for origin (reversed direction)
const {
  itemData: originItemData,
  itemTitle: originTitle,
  isLoading: isOriginLoading,
  targetTypeInfo: originTargetTypeInfo,
  nodeURI: originNodeURI,
  frameworkTitle: originFrameworkTitle,
  isCrossFramework: isOriginCrossFramework,
  resolvedFrameworkId: originResolvedFrameworkId,
  displayedFrameworkId: originDisplayedFrameworkId,
  itemIdentifier: originItemIdentifier
} = useCrossFrameworkItem({
  association: toRef(props, 'association'),
  direction: 'reversed'
});

// Use cross-framework item composable for destination (normal direction)
const {
  itemData: destinationItemData,
  itemTitle: destinationTitle,
  isLoading: isDestinationLoading,
  targetTypeInfo: destinationTargetTypeInfo,
  nodeURI: destinationNodeURI,
  frameworkTitle: destinationFrameworkTitle,
  isCrossFramework: isDestinationCrossFramework,
  resolvedFrameworkId: destinationResolvedFrameworkId,
  displayedFrameworkId: destinationDisplayedFrameworkId,
  itemIdentifier: destinationItemIdentifier
} = useCrossFrameworkItem({
  association: toRef(props, 'association'),
  direction: 'normal'
});

function computeLinkInfo({ isLoading, targetTypeInfo, nodeURI, isCrossFramework, resolvedFrameworkId, displayedFrameworkId, itemIdentifier }) {
  if (isLoading.value) return { type: 'none' };

  const uri = nodeURI.value?.uri;
  const itemId = itemIdentifier.value;
  const nodeFwId = nodeURI.value?.documentIdentifier;
  const targetFwId = nodeFwId || resolvedFrameworkId.value;
  const currentFwId = displayedFrameworkId.value;

  if (targetTypeInfo.value.isCase) {
    if (!itemId) return { type: 'none' };

    const isSameFw = (targetFwId && currentFwId && targetFwId === currentFwId) || !isCrossFramework.value;

    if (isSameFw) {
      return { type: 'same-framework', itemId, href: currentFwId ? `/${currentFwId}/${itemId}` : `#item-${itemId}` };
    }

    if (targetFwId) {
      if (itemId === targetFwId) {
        return { type: 'cross-framework', href: `/editor/${targetFwId}` };
      }
      return { type: 'cross-framework', href: `/editor/${targetFwId}/${itemId}` };
    }

    return { type: 'none' };
  }

  if (uri && /^https?:\/\//i.test(uri)) {
    return { type: 'external', href: uri };
  }

  if (!isCrossFramework.value && itemId) {
    return { type: 'same-framework', itemId, href: currentFwId ? `/${currentFwId}/${itemId}` : `#item-${itemId}` };
  }

  return { type: 'none' };
}

const originLinkInfo = computed(() => computeLinkInfo({
  isLoading: isOriginLoading,
  targetTypeInfo: originTargetTypeInfo,
  nodeURI: originNodeURI,
  isCrossFramework: isOriginCrossFramework,
  resolvedFrameworkId: originResolvedFrameworkId,
  displayedFrameworkId: originDisplayedFrameworkId,
  itemIdentifier: originItemIdentifier,
}));

const destinationLinkInfo = computed(() => computeLinkInfo({
  isLoading: isDestinationLoading,
  targetTypeInfo: destinationTargetTypeInfo,
  nodeURI: destinationNodeURI,
  isCrossFramework: isDestinationCrossFramework,
  resolvedFrameworkId: destinationResolvedFrameworkId,
  displayedFrameworkId: destinationDisplayedFrameworkId,
  itemIdentifier: destinationItemIdentifier,
}));

function navigateToItem(itemId) {
  if (treeNavigation?.navigateToItem) {
    treeNavigation.navigateToItem(itemId);
  } else {
    const frameworkId = route.params.frameworkId;
    if (frameworkId && itemId) {
      router.push(`/${frameworkId}/${itemId}`);
    }
  }
}

function onOriginClick() {
  if (originLinkInfo.value.type === 'same-framework') {
    navigateToItem(originLinkInfo.value.itemId);
  }
}

function onDestinationClick() {
  if (destinationLinkInfo.value.type === 'same-framework') {
    navigateToItem(destinationLinkInfo.value.itemId);
  }
}

// Helper function to truncate text
function truncateText(text, maxLength = 50) {
  if (!text) return '';
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
}

// Origin item display
const originDisplay = computed(() => {
  const item = originItemData.value;
  const title = originTitle.value;
  const isLoading = isOriginLoading.value;
  const nodeUri = originNodeURI.value;

  // If loading, show URI
  if (isLoading && originTargetTypeInfo.value.isCase) {
    return {
      humanCodingScheme: '',
      statement: nodeUri?.uri || 'Loading...',
      truncatedStatement: nodeUri?.uri || 'Loading...',
      identifier: nodeUri?.identifier,
      isLoading: true
    };
  }

  // If we have item data, use it
  if (item) {
    return {
      humanCodingScheme: item.humanCodingScheme || '',
      statement: item.abbreviatedStatement || item.fullStatement || '',
      truncatedStatement: truncateText(item.abbreviatedStatement || item.fullStatement || ''),
      identifier: item.identifier,
      isLoading: false
    };
  }

  // Fall back to title (which includes URI for exemplars)
  return {
    humanCodingScheme: '',
    statement: title || '',
    truncatedStatement: truncateText(title || ''),
    identifier: nodeUri?.identifier,
    isLoading: false
  };
});

// Destination item display
const destinationDisplay = computed(() => {
  const item = destinationItemData.value;
  const title = destinationTitle.value;
  const isLoading = isDestinationLoading.value;
  const nodeUri = destinationNodeURI.value;

  // If loading, show URI
  if (isLoading && destinationTargetTypeInfo.value.isCase) {
    return {
      humanCodingScheme: '',
      statement: nodeUri?.uri || 'Loading...',
      truncatedStatement: nodeUri?.uri || 'Loading...',
      identifier: nodeUri?.identifier,
      isLoading: true
    };
  }

  // If we have item data, use it
  if (item) {
    return {
      humanCodingScheme: item.humanCodingScheme || '',
      statement: item.abbreviatedStatement || item.fullStatement || '',
      truncatedStatement: truncateText(item.abbreviatedStatement || item.fullStatement || ''),
      identifier: item.identifier,
      isLoading: false
    };
  }

  // Fall back to title (which includes URI for exemplars)
  return {
    humanCodingScheme: '',
    statement: title || '',
    truncatedStatement: truncateText(title || ''),
    identifier: nodeUri?.identifier,
    isLoading: false
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


</script>

<style scoped>
.association-tbody > tr > td {
  transition: background-color 0.15s ease-in-out;
}

/* Grouped hover effect: target all tds in both rows uniformly */
.association-tbody:hover > tr > td {
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
  margin-right: 0.25rem;
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

.annotation-row td {
  border-top: none !important;
}

/* Remove bottom border from main row when followed by annotation row */
.association-tbody:has(.annotation-row) .association-row td {
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

.uri-display {
  font-family: monospace;
  font-size: 0.85em;
  word-break: break-all;
}

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

/* Cross-framework row highlighting using Bootstrap CSS variables */
.cross-framework-tbody {
  --bs-table-bg: #f8f9fa;
}

.cross-framework-tbody > tr > td:first-child {
  border-left: 3px solid #6c757d;
}

/* Cross-framework grouped hover effect overriding the standard hover */
.cross-framework-tbody:hover > tr > td {
  --bs-table-bg: #e9ecef;
  background-color: #e9ecef !important;
}

.gap-1 {
  gap: 0.25rem;
}

.association-title-link {
  color: inherit;
  text-decoration: none;
}

.association-title-link:hover {
  text-decoration: underline;
  color: #0c63e4;
}

.association-title-link:hover .item-human-coding-scheme,
.association-title-link:hover .item-statement {
  color: #0c63e4;
}
</style>
