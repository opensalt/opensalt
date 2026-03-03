<template>
  <div class="item-details">
    <!-- Cross-Framework Indicator -->
    <div v-if="isCrossFrameworkItem" class="alert alert-info mb-2" role="alert">
      <i class="bi bi-box-arrow-up-right me-2"></i>
      <strong>External Framework Item</strong>
      <!-- Loading state -->
      <span v-if="isLoadingCrossFramework" class="text-muted">
        <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>
        Loading...
      </span>
      <!-- Framework name from composable -->
      <span v-else-if="externalFrameworkTitle" class="text-muted"> - from {{ externalFrameworkTitle }}</span>
      <!-- Error state -->
      <span v-else-if="crossFrameworkFetchError" class="text-warning ms-2">
        <i class="bi bi-exclamation-triangle"></i>
        {{ crossFrameworkFetchError.type === 'permission' ? 'No access' : 'Load error' }}
      </span>
    </div>

    <!-- Item Header -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 d-flex align-items-center">
          <img :src="itemIconSrc" class="me-2 item-icon" aria-hidden="true" />
          Item Details
        </h6>
        <div class="btn-group btn-group-sm" v-if="!isReadOnly && !isCrossFrameworkItem">
          <button
            type="button"
            class="btn btn-outline-primary"
            @click="showEditModal(item)"
            title="Edit item"
          >
            <i class="bi bi-pencil"></i>
          </button>
          <button
            type="button"
            class="btn btn-outline-danger"
            @click="$emit('delete-item', item)"
            title="Delete item"
          >
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <!-- Item Title -->
        <h5 class="card-title">
          <span v-if="displayItem.humanCodingScheme" class="badge bg-secondary me-1">
            {{ displayItem.humanCodingScheme }}
          </span>
          {{ displayItem.abbreviatedStatement || '' }}
        </h5>

        <!-- Specialized Item Details -->
        <component
          v-if="itemDetailsComponent"
          :is="itemDetailsComponent"
          :item="displayItem"
        />

        <!-- Default Item Details (for items without specialized component) -->
        <div v-else>
          <div v-if="displayItem.fullStatement" class="mb-3">
            <strong>Full Statement:</strong>
            <div class="mt-1 markdown-content" v-html="renderedFullStatement"></div>
          </div>

          <div class="mt-2">
              <strong>Identifier:</strong> <span class="ms-1">{{ displayItem.identifier }}</span>
          </div>

          <div class="row mt-2">
            <div v-if="displayItem.itemType" class="col-sm-6">
              <strong>Item Type:</strong> {{ displayItem.itemType || 'General' }}
            </div>
            <div v-if="displayItem.language" class="col-sm-6">
              <strong>Language:</strong> {{ displayItem.language || 'en' }}
            </div>
          </div>

          <div v-if="displayItem.educationLevel && displayItem.educationLevel.length > 0" class="mt-2">
              <strong>Education Level:</strong>
              <span class="ms-1">
                  <span v-for="level in displayItem.educationLevel" :key="level" class="badge bg-info text-dark me-1">
                      {{ level }}
                  </span>
              </span>
          </div>

          <div v-if="displayItem.conceptKeywords && displayItem.conceptKeywords.length > 0" class="mt-2">
              <strong>Keywords:</strong>
              <span class="ms-1">
                  <span v-for="keyword in displayItem.conceptKeywords" :key="keyword" class="badge bg-secondary me-1">
                      {{ keyword }}
                  </span>
              </span>
          </div>

          <div v-if="displayItem.licenseURI" class="mt-2 text-truncate">
              <strong>License:</strong> <span class="ms-1">{{ licenseName }}</span>
          </div>

          <div v-if="displayItem.notes" class="mt-3">
              <strong>Notes:</strong>
              <p class="mt-1 markdown-content" v-html="renderedNotes"></p>
          </div>

          <div v-if="displayItem.lastChanged" class="mt-2">
            <small class="text-muted">
              Last changed: {{ formatDate(displayItem.lastChanged) }}
            </small>
          </div>
        </div>

          <!-- Actions - Available for all items (including cross-framework) -->
      <div v-if="!isReadOnly" class="card mt-3">
        <div class="card-header">
          <h6 class="mb-0">Actions</h6>
        </div>
        <div class="card-body mx-auto">
          <div class="d-flex gap-2">
            <div class="btn-group">
              <button type="button" class="btn btn-outline-primary" @click="showModal('general')">
                <i class="bi bi-plus-circle"></i> Add Child Item
              </button>
              <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
              </button>
              <ul class="dropdown-menu">
                <li v-for="type in availableTypes" :key="type">
                  <a
                    class="dropdown-item"
                    @click.prevent="handleDropdownClick(type)"
                    href="#"
                    :aria-label="`Add ${getTypeLabel(type)}`"
                  >
                    Add {{ getTypeLabel(type) }}
                  </a>
                </li>
              </ul>
            </div>
            <button type="button" class="btn btn-outline-secondary" @click="$emit('add-exemplar', item)">
              <i class="bi bi-link-45deg"></i> Add Exemplar
            </button>
          </div>
        </div>
      </div>

      <!-- Associations -->
      <div v-if="mergedAssociations.length > 0 || !isCrossFrameworkItem || isProcessingAssociations" class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">
            Associations
            <span v-if="isProcessingAssociations" class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>
          </h6>
          <button v-if="!isReadOnly" type="button" class="btn btn-sm btn-outline-primary" @click="$emit('add-association', item)">
            <i class="bi bi-plus"></i> Add
          </button>
        </div>
        <div class="card-body">
          <!-- Loading state for associations -->
          <div v-if="isProcessingAssociations && mergedAssociations.length === 0" class="text-center py-3">
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            <span class="text-muted">Loading associations...</span>
          </div>

          <AssociationGroupDisplay
            v-for="group in mergedAssociations"
            :key="`${group.type}-${group.direction}`"
            :association-type="group.type"
            :associations="group.associations"
            :association-groups="associationGroups"
            :direction="group.direction"
            :item-identifier="item.identifier"
            :is-read-only="isReadOnly"
            @edit-association="!isReadOnly && !isCrossFrameworkItem ? $emit('edit-association', $event) : null"
            @delete-association="handleDeleteAssociationRequest"
          />
        </div>
      </div>

      <!-- Comments -->
      <CommentModule
        v-if="item?.identifier"
        item-type="item"
        :item-identifier="item.identifier"
      />
      </div>
    </div>

  </div>

  <!-- Dynamic Modal -->
  <Teleport to="body">
    <div v-if="isModalVisible">
      <component
        :is="modalComponent"
        :parent-item="parentItem"
        :show="isModalVisible"
        :item-type="selectedType"
        @created="handleCreated"
        @hidden="handleHidden"
      />
    </div>
  </Teleport>

  <!-- Dynamic Edit Modal -->
  <Teleport to="body">
    <div v-if="isEditModalVisible">
      <component
        :is="editModalComponent"
        :parent-item="null"
        :show="isEditModalVisible"
        :item-type="selectedEditType"
        :item="editingItem"
        @updated="handleUpdated"
        @hidden="handleEditHidden"
      />
    </div>
  </Teleport>

  <!-- Delete Association Modal -->
  <DeleteAssociationModal
    v-model:show="showDeleteModal"
    :association="associationToDelete"
    @confirmed="handleDeleteConfirmed"
    @hidden="handleDeleteModalHidden"
  />
</template>

<script setup>
/* global localStorage, console, requestIdleCallback, setTimeout */
import { computed, ref, shallowRef, onMounted, onUnmounted, watch, nextTick } from 'vue';
import AssociationGroupDisplay from '../../association/AssociationGroupDisplay.vue';
import CommentModule from '../CommentModule.vue';
import DeleteAssociationModal from '@/components/association/DeleteAssociationModal.vue';
import { useDynamicModal } from '../../../composables/useDynamicModal.js';
import { useDynamicEditModal } from '../../../composables/useDynamicEditModal.js';
import { useCurrentDocumentStore } from '../../../stores/currentDocumentStore';
import { useCrossFrameworkItem } from '../../../composables/useCrossFrameworkItem';

// Lazy-loaded markdown renderer with caching
let markdownRendererPromise = null;
let cachedRender = null;
let cachedHasMarkdown = null;

async function getMarkdownRenderer() {
  if (cachedRender && cachedHasMarkdown) {
    return { render: cachedRender, hasMarkdown: cachedHasMarkdown };
  }
  if (!markdownRendererPromise) {
    markdownRendererPromise = Promise.all([
      import('../../../utils/render-md.js'),
      import('../../../utils/markdownRenderer.js')
    ]).then(([renderModule, mdRendererModule]) => {
      cachedRender = renderModule.default;
      cachedHasMarkdown = mdRendererModule.hasMarkdown;
      return { render: cachedRender, hasMarkdown: cachedHasMarkdown };
    });
  }
  return markdownRendererPromise;
}

// Refs to store loaded renderer functions
const render = ref(null);
const hasMarkdown = ref(null);

// Load renderer on mount
onMounted(async () => {
  const renderer = await getMarkdownRenderer();
  render.value = renderer.render;
  hasMarkdown.value = renderer.hasMarkdown;
});

// Specialized item detail components
import JobItemDetails from './item-types/JobItemDetails.vue';
import CourseItemDetails from './item-types/CourseItemDetails.vue';
import AssessmentItemDetails from './item-types/AssessmentItemDetails.vue';
import CredentialItemDetails from './item-types/CredentialItemDetails.vue';
import OrganizationItemDetails from './item-types/OrganizationItemDetails.vue';
import IdentifierItemDetails from './item-types/IdentifierItemDetails.vue';
import PublicKeyItemDetails from './item-types/PublicKeyItemDetails.vue';

import itemIcon from '@/assets/icons/lucide/target.svg';
import assessmentIcon from '@/assets/icons/iconoir/learning.svg';
import courseIcon from '@/assets/icons/fluent-mdl2/learning-tools.svg';
import credentialIcon from '@/assets/icons/ph/certificate.svg';
import jobIcon from '@/assets/icons/eos-icons/role-binding.svg';
import organizationIcon from '@/assets/icons/f7/building-columns-fill.svg';
import identifierIcon from '@/assets/icons/lucide/id-card.svg';
import publicKeyIcon from '@/assets/icons/lucide/key-round.svg';

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  currentDocument: {
    type: Object,
    default: null
  },
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
  'update-item'
]);

// Access the store for association data and priority queue updates
const currentDocumentStore = useCurrentDocumentStore();

const availableTypes = ['general', 'assessment', 'course', 'credential', 'job', 'organization', 'public_key', 'identifier'];

const { showModal, selectedType, isModalVisible, handleCreated, modalComponent, handleHidden, parentItem } = useDynamicModal(
  props.item,
  (newItem) => {
    emit('add-child', newItem);
  },
  availableTypes
);

const {
  showEditModal,
  selectedEditType,
  isEditModalVisible,
  editingItem,
  editModalComponent,
  handleUpdated,
  handleEditHidden
} = useDynamicEditModal(
  (updatedItem) => {
    emit('update-item', updatedItem);
  },
  availableTypes
);

// Delete association modal state
const showDeleteModal = ref(false);
const associationToDelete = ref(null);
const showExtendedInfo = ref(false);

// Performance optimization: Cache for merged associations
// Using Map for O(1) lookup by item identifier
const mergedAssociationsCache = shallowRef(new Map());
const isProcessingAssociations = ref(false);
const lastProcessedItemId = ref(null);
const lastAssociatedDocumentsSize = ref(0);

// Cache size limit to prevent unbounded memory growth
const MAX_CACHE_SIZE = 50;

// Version counter for race condition prevention
const processingVersion = ref(0);

// Helper to build an item index for O(1) lookup instead of tree traversal
// This avoids the expensive recursive searchItems function
function buildItemIndex(items, index = new Map()) {
  if (!items || !Array.isArray(items)) return index;

  for (const item of items) {
    if (item?.identifier) {
      index.set(item.identifier, item);
    }
    if (item?.children?.length) {
      buildItemIndex(item.children, index);
    }
  }
  return index;
}

// Async processing function to avoid blocking the UI
// Uses requestIdleCallback when available, falls back to setTimeout
// @param {string} itemIdentifier - The item identifier to process
// @param {number} version - The processing version for race condition prevention
// @param {boolean} background - If true, process in background without showing loading state
// @param {boolean} force - If true, bypass the cache check and force re-processing
async function processAssociationsAsync(itemIdentifier, version, background = false, force = false) {
  if (!itemIdentifier) return Promise.resolve();

  // Check if already cached (unless force is true)
  if (!force && mergedAssociationsCache.value.has(itemIdentifier)) {
    return Promise.resolve();
  }

  // Only show loading state for non-background updates
  if (!background) {
    isProcessingAssociations.value = true;
  }

  // Use nextTick to allow UI to update before processing
  await nextTick();

  // Schedule heavy processing during idle time
  // Use requestIdleCallback with a robust fallback for Safari < 16.5
  const scheduleTask = typeof requestIdleCallback !== 'undefined'
    ? (cb) => {
        try {
          return requestIdleCallback(cb, { timeout: 100 });
        } catch (e) {
          return setTimeout(cb, 0);
        }
      }
    : (cb) => setTimeout(cb, 0);

  return new Promise((resolve) => {
    scheduleTask(() => {
      // Check if this is still the latest request (race condition prevention)
      if (version !== processingVersion.value) {
        resolve();
        return;
      }

      try {
        const result = computeMergedAssociations(itemIdentifier);

        // Create a new Map to trigger Vue reactivity (shallowRef requires replacing .value)
        const newCache = new Map(mergedAssociationsCache.value);

        // Enforce cache size limit with LRU eviction
        if (newCache.size >= MAX_CACHE_SIZE) {
          const firstKey = newCache.keys().next().value;
          newCache.delete(firstKey);
        }

        newCache.set(itemIdentifier, result);
        mergedAssociationsCache.value = newCache;
      } catch (error) {
        console.error('Error processing associations:', error);
        // Create a new Map to trigger Vue reactivity (shallowRef requires replacing .value)
        const errorCache = new Map(mergedAssociationsCache.value);
        errorCache.set(itemIdentifier, []);
        mergedAssociationsCache.value = errorCache;
      } finally {
        // Only clear loading state if this is still the latest request
        if (version === processingVersion.value) {
          isProcessingAssociations.value = false;
        }
        resolve();
      }
    });
  });
}

// Clear cache when associated documents change
function clearAssociationsCache() {
  // Create new Map to trigger reactivity
  mergedAssociationsCache.value = new Map();
  lastProcessedItemId.value = null;
}

// The actual computation logic (extracted from the original computed property)
function computeMergedAssociations(itemIdentifier) {
  if (!itemIdentifier) return [];

  // Get current item's associations
  const currentAssociations = props.item.associations || [];

  // Collect cross-framework associations from the reactive associatedDocuments map
  const crossFrameworkAssociations = [];
  const seenIds = new Set(currentAssociations.map(a => a.identifier));

  // Iterate over all reactive associatedDocuments (populated by the queue as frameworks load)
  for (const [frameworkId, associatedDoc] of currentDocumentStore.associatedDocuments) {
    const associations = associatedDoc.cfAssociations || [];
    for (const assoc of associations) {
      const originId = assoc.originNodeURI?.identifier;
      const destId = assoc.destinationNodeURI?.identifier;

      // Check if this association involves current item and isn't a duplicate
      if ((originId === itemIdentifier || destId === itemIdentifier) &&
          !seenIds.has(assoc.identifier)) {
        seenIds.add(assoc.identifier);
        crossFrameworkAssociations.push({
          ...assoc,
          CFDocumentURI: frameworkId
        });
      }
    }
  }

  // Build item index once for O(1) lookup instead of recursive tree traversal
  const items = currentDocumentStore.currentDocument?.items;
  const itemIndex = buildItemIndex(items);

  // Merge current and cross-framework associations
  // For cross-framework items, include isChildOf associations so they can be deleted
  const allAssociations = [...currentAssociations, ...crossFrameworkAssociations]
    .filter(a => {
      const assocType = a.associationType || a.type;
      // Show isChildOf if item is cross-framework or if parent (destination) is external
      if (assocType === 'isChildOf') {
        if (isCrossFrameworkItem.value) return true;

        const destId = a.destinationNodeURI?.identifier;
        if (destId) {
          const destItem = itemIndex.get(destId);
          // If destination is not found locally or is marked as cross-framework, show it
          if (!destItem || destItem.isCrossFramework) {
            return true;
          }
        }
        return false;
      }
      return true;
    });

  // Group associations by type and determine direction
  const groupedAssociations = {};

  allAssociations.forEach(assoc => {
    const associationType = assoc.associationType || assoc.type || assoc.association?.type || 'unknown';

    // Determine direction based on origin/destination
    const destId = assoc.destinationNodeURI?.identifier;
    let direction = 'normal';

    if (destId === itemIdentifier) {
      direction = 'reversed';
    }

    // Create group key
    const groupKey = `${associationType}-${direction}`;

    // Initialize group if not exists
    if (!groupedAssociations[groupKey]) {
      groupedAssociations[groupKey] = {
        type: associationType,
        direction: direction,
        associations: []
      };
    }

    // Add association to group
    groupedAssociations[groupKey].associations.push(assoc);
  });

  // Convert to array and return
  return Object.values(groupedAssociations);
}

// Track processed items to prevent duplicate processing
const processingItemId = ref(null);

// Watch for item changes to trigger async processing
// This consolidates store updates and association processing in a single watcher
watch(
  () => props.item?.identifier,
  async (newItemId, oldItemId) => {
    if (newItemId && newItemId !== oldItemId) {
      // Update store selection first
      currentDocumentStore.setSelectedItem(props.item);

      // Clear cache if associated documents have changed significantly
      const currentDocsSize = currentDocumentStore.associatedDocuments?.size || 0;
      if (currentDocsSize !== lastAssociatedDocumentsSize.value) {
        clearAssociationsCache();
        lastAssociatedDocumentsSize.value = currentDocsSize;
      }

      // Prevent duplicate processing of the same item
      if (processingItemId.value === newItemId) {
        return;
      }

      // Increment version counter for race condition prevention
      const version = ++processingVersion.value;

      // Process associations asynchronously with version check
      processingItemId.value = newItemId;
      lastProcessedItemId.value = newItemId;
      await processAssociationsAsync(newItemId, version);
      processingItemId.value = null;
    }
  },
  { immediate: true }
);

// Watch for changes in the current item's associations (e.g., after add/delete)
watch(
  () => props.item?.associations?.length,
  () => {
    if (props.item?.identifier) {
      // Clear the cache for this item to force re-computation
      // Create new Map to trigger reactivity
      const newCache = new Map(mergedAssociationsCache.value);
      newCache.delete(props.item.identifier);
      mergedAssociationsCache.value = newCache;
      // Re-process if this is the current item
      if (lastProcessedItemId.value === props.item.identifier) {
        const version = ++processingVersion.value;
        processAssociationsAsync(props.item.identifier, version);
      }
    }
  }
);

// Watch for changes in associated documents
// When new documents are added, we process them in the background WITHOUT
// clearing the cache. This ensures the UI keeps showing existing associations
// while new ones are being computed, avoiding the "Loading associations..." flash.
watch(
  () => currentDocumentStore.associatedDocuments?.size,
  (newSize, oldSize) => {
    // Only re-process if documents were added (not removed)
    if (newSize > oldSize && lastProcessedItemId.value) {
      // IMPORTANT: Do NOT clear the cache here. Instead, keep showing the
      // cached associations while we compute the new ones in the background.
      // This prevents the "Loading associations..." message from appearing.
      // The cache will be atomically updated when processAssociationsAsync completes.

      const version = ++processingVersion.value;
      // Pass background=true to avoid showing loading state during incremental updates
      // Pass force=true to bypass the cache check and re-process with new documents
      processAssociationsAsync(lastProcessedItemId.value, version, true, true);
    }
  }
);

// Load preference from localStorage on mount
onMounted(() => {
  const stored = localStorage.getItem('itemDetailsShowExtended');
  showExtendedInfo.value = stored === 'true';
});

// Cleanup on unmount to prevent stale processing state
onUnmounted(() => {
  // Increment version to invalidate any in-flight processing
  processingVersion.value++;
  isProcessingAssociations.value = false;
  processingItemId.value = null;
});

// Delete association modal handlers
function handleDeleteAssociationRequest(association) {
  if (isReadOnly.value) return;

  // For cross-framework items, only allow deletion of isChildOf associations
  // that link the item to the current framework
  if (isCrossFrameworkItem.value) {
    const associationType = association.associationType || association.type;
    const isChildOfAssoc = associationType === 'isChildOf';

    // Only allow deletion if this is an isChildOf association
    if (!isChildOfAssoc) return;
  }

  associationToDelete.value = association;
  showDeleteModal.value = true;
}

function handleDeleteConfirmed(association) {
  emit('delete-association', association);
  showDeleteModal.value = false;
}

function handleDeleteModalHidden() {
  associationToDelete.value = null;
}

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}

// Optimized mergedAssociations using cache
// The heavy computation is done asynchronously in processAssociationsAsync
const mergedAssociations = computed(() => {
  if (!props.item?.identifier) return [];

  const itemId = props.item.identifier;

  // Return cached result if available
  if (mergedAssociationsCache.value.has(itemId)) {
    return mergedAssociationsCache.value.get(itemId);
  }

  // Return empty array while processing (loading state will be shown)
  // Async processing is triggered by the watcher on props.item?.identifier
  return [];
});

// Render fullStatement as markdown
const renderedFullStatement = computed(() => {
  if (!displayItem.value?.fullStatement) return '';
  return render.value ? render.value.block(displayItem.value.fullStatement) : displayItem.value.fullStatement;
});



// Render notes as markdown
const renderedNotes = computed(() => {
    if (!displayItem.value?.notes) return '';
    return render.value ? render.value.block(displayItem.value.notes) : displayItem.value.notes;
});

function getTypeLabel(type) {
  const labels = {
    general: 'General Item',
    assessment: 'Assessment',
    course: 'Course',
    credential: 'Credential',
    job: 'Job',
    organization: 'Organization',
    'public_key': 'Public Key',
    identifier: 'Identifier'
  };
  return labels[type] || type;
}

// Compute icon based on item type
const itemIconSrc = computed(() => {
  const type = displayItem.value.extensions?.['salt:type'] || 'item';
  const iconMap = {
    assessment: assessmentIcon,
    course: courseIcon,
    credential: credentialIcon,
    job: jobIcon,
    organization: organizationIcon,
    identifier: identifierIcon,
    public_key: publicKeyIcon,
    item: itemIcon
  };
  return iconMap[type] || itemIcon;
});

// Determine which specialized item details component to use
const itemDetailsComponent = computed(() => {
  const type = displayItem.value.extensions?.['salt:type'] || 'default';
  const componentMap = {
    job: JobItemDetails,
    course: CourseItemDetails,
    assessment: AssessmentItemDetails,
    credential: CredentialItemDetails,
    organization: OrganizationItemDetails,
    identifier: IdentifierItemDetails,
    public_key: PublicKeyItemDetails,
    default: null // Default uses base implementation
  };
  return componentMap[type] || null;
});

function handleDropdownClick(type) {
  if (availableTypes.includes(type)) {
    showModal(type);
  } else {
    console.warn(`Invalid type: ${type}`);
  }
}

import { useSessionStore } from '../../../stores/sessionStore';

const sessionStore = useSessionStore();
const isReadOnly = computed(() => props.currentDocument?.isReadOnly || !sessionStore.isAuthenticated);

// Detect if this is a cross-framework item
// Use props.item directly to avoid circular dependency with displayItem
const isCrossFrameworkItem = computed(() => props.item?.isCrossFramework === true);

// Setup cross-framework item loading for external items
// Structure matches what useCrossFrameworkItem expects (like AssociationItem.vue)
const crossFrameworkData = computed(() => {
  if (!isCrossFrameworkItem.value || !displayItem.value?.crossFrameworkUri) {
    return null;
  }
  return {
    destinationNodeURI: {
      uri: displayItem.value.crossFrameworkUri,
      identifier: displayItem.value.identifier,
      title: displayItem.value.title || displayItem.value.abbreviatedStatement || displayItem.value.fullStatement
    },
    associationType: 'isChildOf'
  };
});

// Use the composable for cross-framework items (like AssociationItem.vue)
const {
  itemData: crossFrameworkItemData,
  isLoading: isLoadingCrossFramework,
  frameworkTitle: crossFrameworkFrameworkTitle,
  fetchError: crossFrameworkFetchError
} = useCrossFrameworkItem({
  association: crossFrameworkData,
  direction: 'normal'
});

// Local reactive state for cross-framework item data to avoid prop mutation
const localCrossFrameworkData = ref({});

// Computed property that merges props.item with localCrossFrameworkData
// This ensures fetched cross-framework data is displayed in the template
const displayItem = computed(() => ({
  ...props.item,
  ...localCrossFrameworkData.value
}));

// Watch for fetched data and merge into the local item object
watch(crossFrameworkItemData, (newData) => {
  if (newData && isCrossFrameworkItem.value) {
    // Merge fetched data into local state to enable proper display
    const mergedData = {
      fullStatement: newData.fullStatement || newData.CFItemFullStatement,
      abbreviatedStatement: newData.abbreviatedStatement || newData.CFItemAbbreviatedStatement,
      humanCodingScheme: newData.humanCodingScheme || newData.CFItemHumanCodingScheme,
      itemType: newData.itemType || newData.CFItemType,
      notes: newData.notes || newData.CFItemNotes,
      language: newData.language || newData.CFItemLanguage,
      educationLevel: newData.educationLevel || newData.CFItemEducationLevel,
      conceptKeywords: newData.conceptKeywords || newData.CFItemConceptKeywords,
      licenseURI: newData.licenseURI || newData.CFItemLicenseURI,
      lastChanged: newData.lastChanged || newData.CFItemLastChangeDateTime,
      extensions: newData.extensions || newData.CFItemExtensions
    };

    // Only assign defined values to local state
    let hasChanges = false;
    Object.keys(mergedData).forEach(key => {
      if (mergedData[key] !== undefined && mergedData[key] !== props.item[key]) {
        localCrossFrameworkData.value[key] = mergedData[key];
        hasChanges = true;
      }
    });

    // Also update the title display property if available
    if (newData.title && !localCrossFrameworkData.value.title && newData.title !== props.item.title) {
      localCrossFrameworkData.value.title = newData.title;
      hasChanges = true;
    }

    // Emit event to parent only when data actually changes
    if (hasChanges) {
      emit('update-item', { ...props.item, ...localCrossFrameworkData.value });
    }
  }
}, { immediate: true });

// Get external framework title for cross-framework items
// Uses the framework title from the composable if available
const externalFrameworkTitle = computed(() => {
  if (!isCrossFrameworkItem.value) {
    return null;
  }
  // First check the composable's framework title
  if (crossFrameworkFrameworkTitle.value) {
    return crossFrameworkFrameworkTitle.value;
  }
  // Fall back to any previously stored title
  return displayItem.value?.externalFrameworkTitle || null;
});

// Get license name from definitions
const licenseName = computed(() => {
  if (!displayItem.value?.licenseURI?.identifier) {
    return null;
  }

  const licenseId = displayItem.value.licenseURI.identifier;
  const licenses = currentDocumentStore.currentDocumentDefinitions?.CFLicenses || [];

  // Find license by identifier in definitions
  const licenseDef = licenses.find(lic => lic.identifier === licenseId);

  // Return license title if found, otherwise fall back to URI
  if (licenseDef?.title) {
    return licenseDef.title;
  }

  // Fallback to license URI or identifier
  return displayItem.value.licenseURI.uri || displayItem.value.licenseURI.identifier;
});
</script>

<style scoped>
.item-details {
  min-height: 0;
  overflow-y: auto;
}

.associations-list {
  min-height: 0;
  overflow-y: auto;
}

/* Markdown content styling */
.markdown-content {
  padding: 0.75rem;
  padding-bottom: 0;
  background-color: #f8f9fa;
  border-radius: 0.375rem;
  border: 1px solid #dee2e6;
  font-size: 0.875rem;
  line-height: 1.5;
}

.markdown-content h1,
.markdown-content h2,
.markdown-content h3,
.markdown-content h4,
.markdown-content h5,
.markdown-content h6 {
  margin-top: 0;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #495057;
}

.markdown-content p {
  margin-bottom: 0.75rem;
}

.markdown-content ul,
.markdown-content ol {
  margin-bottom: 0.75rem;
  padding-left: 1.5rem;
}

.markdown-content li {
  margin-bottom: 0.25rem;
}

.markdown-content blockquote {
  border-left: 4px solid #dee2e6;
  padding-left: 1rem;
  margin: 1rem 0;
  color: #6c757d;
  font-style: italic;
}

.markdown-content code {
  background-color: #e9ecef;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.8125rem;
  font-family: 'Courier New', monospace;
}

.markdown-content pre {
  background-color: #e9ecef;
  padding: 0.75rem;
  border-radius: 0.375rem;
  overflow-x: auto;
  margin: 0.75rem 0;
}

.markdown-content table {
  width: 100%;
  margin-bottom: 0.75rem;
  border-collapse: collapse;
}

.markdown-content th,
.markdown-content td {
  padding: 0.375rem 0.75rem;
  border: 1px solid #dee2e6;
  text-align: left;
}

.markdown-content th {
  background-color: #f8f9fa;
  font-weight: 600;
}

.markdown-content a {
  color: #0d6efd;
  text-decoration: none;
}

.markdown-content a:hover {
  text-decoration: underline;
}

/* KaTeX styling */
.markdown-content .katex {
  font-size: 1em;
}

.markdown-content .katex-display {
  margin: 1rem 0;
  text-align: center;
}

.item-icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}
</style>
