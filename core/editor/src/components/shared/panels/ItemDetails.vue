<template>
  <div class="item-details">
    <!-- Status banners (cross-framework / read-only) -->
    <ItemCrossFrameworkBanner
      :is-cross-framework-item="isCrossFrameworkItem"
      :is-loading-cross-framework="isLoadingCrossFramework"
      :external-framework-title="externalFrameworkTitle"
      :cross-framework-fetch-error="crossFrameworkFetchError"
      :is-item-from-viewed-framework="isItemFromViewedFramework"
      :viewed-doc="viewedDoc"
    />

    <!-- Item Header Card + body content -->
    <ItemHeaderCard
      :item="item"
      :display-item="displayItem"
      :item-icon-src="itemIconSrc"
      :can-edit-item="canEditItem"
      :is-item-from-viewed-framework="isItemFromViewedFramework"
      @edit="showEditModal(item)"
      @delete="$emit('delete-item', item)"
    >
      <!-- Specialized Item Details -->
      <component v-if="itemDetailsComponent" :is="itemDetailsComponent" :item="displayItem" />

      <!-- Default Item Details -->
      <ItemDefaultDetails
        v-else
        :item="displayItem"
        :rendered-full-statement="renderedFullStatement"
        :rendered-notes="renderedNotes"
        :license-name="licenseName"
      />
    </ItemHeaderCard>

    <!-- Actions card (inside the outer card-body "shell", after the header card) -->
    <ItemActionsCard
      :can-edit-item="canEditItem"
      :is-item-from-viewed-framework="isItemFromViewedFramework"
      :is-read-only="isReadOnly"
      :available-types="availableTypes"
      @add-child="handleDropdownClick"
      @add-exemplar="$emit('add-exemplar', item)"
    />

    <!-- Associations card -->
    <ItemAssociationsCard
      :merged-associations="mergedAssociations"
      :is-processing-associations="isProcessingAssociations"
      :is-cross-framework-item="isCrossFrameworkItem"
      :association-groups="associationGroups"
      :item-identifier="item.identifier"
      :is-read-only="isReadOnly"
      :can-edit-item="canEditItem"
      :is-viewing-different-framework="isViewingDifferentFramework"
      :current-document="currentDocument"
      @add-association="$emit('add-association', item)"
      @edit-association="canEditItem ? $emit('edit-association', $event) : null"
      @delete-association="(assoc) => handleDeleteAssociationRequest(assoc, isCrossFrameworkItem, canEditItem)"
    />

    <!-- Comments -->
    <CommentModule
      v-if="item?.identifier"
      item-type="item"
      :item-identifier="item.identifier"
    />
  </div>

  <!-- Dynamic Add-Child Modal -->
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
    @confirmed="(assoc) => handleDeleteConfirmed(assoc, emit)"
    @hidden="handleDeleteModalHidden"
  />
</template>

<script setup>
/* global localStorage */
import { computed, ref, onMounted, watch } from 'vue';

// Sub-components
import ItemCrossFrameworkBanner from './ItemCrossFrameworkBanner.vue';
import ItemHeaderCard from './ItemHeaderCard.vue';
import ItemDefaultDetails from './ItemDefaultDetails.vue';
import ItemActionsCard from './ItemActionsCard.vue';
import ItemAssociationsCard from './ItemAssociationsCard.vue';
import CommentModule from '../CommentModule.vue';
import DeleteAssociationModal from '@/components/association/DeleteAssociationModal.vue';

// Composables
import { useItemAssociations } from '../../../composables/useItemAssociations.js';
import { useDynamicModal } from '../../../composables/useDynamicModal.js';
import { useDynamicEditModal } from '../../../composables/useDynamicEditModal.js';
import { useEditorContextStore } from '../../../stores/editorContextStore';
import { useDocumentStore } from '../../../stores/documentStore';
import { useSessionStore } from '../../../stores/sessionStore';
import { useCrossFrameworkItem } from '../../../composables/useCrossFrameworkItem';
import { useCurrentDocumentStore } from '../../../stores/currentDocumentStore';

// Specialized item type detail components
import JobItemDetails from './item-types/JobItemDetails.vue';
import CourseItemDetails from './item-types/CourseItemDetails.vue';
import AssessmentItemDetails from './item-types/AssessmentItemDetails.vue';
import CredentialItemDetails from './item-types/CredentialItemDetails.vue';
import OrganizationItemDetails from './item-types/OrganizationItemDetails.vue';
import IdentifierItemDetails from './item-types/IdentifierItemDetails.vue';
import PublicKeyItemDetails from './item-types/PublicKeyItemDetails.vue';

// Icons
import itemIcon from '@/assets/icons/lucide/target.svg';
import assessmentIcon from '@/assets/icons/iconoir/learning.svg';
import courseIcon from '@/assets/icons/fluent-mdl2/learning-tools.svg';
import credentialIcon from '@/assets/icons/ph/certificate.svg';
import jobIcon from '@/assets/icons/eos-icons/role-binding.svg';
import organizationIcon from '@/assets/icons/f7/building-columns-fill.svg';
import identifierIcon from '@/assets/icons/lucide/id-card.svg';
import publicKeyIcon from '@/assets/icons/lucide/key-round.svg';

// ---------------------------------------------------------------------------
// Props / emits
// ---------------------------------------------------------------------------
const props = defineProps({
  item: { type: Object, required: true },
  currentDocument: { type: Object, default: null },
  associationGroups: { type: Array, default: () => [] },
});

const emit = defineEmits([
  'edit-item',
  'delete-item',
  'add-child',
  'add-exemplar',
  'add-association',
  'edit-association',
  'delete-association',
  'update-item',
]);

// ---------------------------------------------------------------------------
// Stores
// ---------------------------------------------------------------------------
const contextStore = useEditorContextStore();
const sessionStore = useSessionStore();
const documentStore = useDocumentStore();

// ---------------------------------------------------------------------------
// Item type constants
// ---------------------------------------------------------------------------
const availableTypes = ['general', 'assessment', 'course', 'credential', 'job', 'organization', 'public_key', 'identifier'];

// ---------------------------------------------------------------------------
// Cross-framework item detection & data loading
// ---------------------------------------------------------------------------
const isCrossFrameworkItem = computed(() => props.item?.isCrossFramework === true);

const localCrossFrameworkData = ref({});

// displayItem merges live prop data with any fetched cross-framework overrides
const displayItem = computed(() => ({
  ...props.item,
  ...localCrossFrameworkData.value,
}));

const crossFrameworkData = computed(() => {
  if (!isCrossFrameworkItem.value || !props.item?.crossFrameworkUri) return null;
  return {
    destinationNodeURI: {
      uri: props.item.crossFrameworkUri,
      identifier: props.item.identifier,
      title: props.item.title || props.item.abbreviatedStatement || props.item.fullStatement,
    },
    associationType: 'isChildOf',
  };
});

const {
  itemData: crossFrameworkItemData,
  isLoading: isLoadingCrossFramework,
  frameworkTitle: crossFrameworkFrameworkTitle,
  fetchError: crossFrameworkFetchError,
} = useCrossFrameworkItem({ association: crossFrameworkData, direction: 'normal' });

// Merge fetched cross-framework data into local state
watch(
  crossFrameworkItemData,
  (newData) => {
    if (newData && isCrossFrameworkItem.value) {
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
        extensions: newData.extensions || newData.CFItemExtensions,
      };

      let hasChanges = false;
      Object.keys(mergedData).forEach((key) => {
        if (mergedData[key] !== undefined && mergedData[key] !== props.item[key]) {
          localCrossFrameworkData.value[key] = mergedData[key];
          hasChanges = true;
        }
      });

      if (newData.title && !localCrossFrameworkData.value.title && newData.title !== props.item.title) {
        localCrossFrameworkData.value.title = newData.title;
        hasChanges = true;
      }

      if (hasChanges) {
        emit('update-item', { ...props.item, ...localCrossFrameworkData.value });
      }
    }
  },
  { immediate: true }
);

const externalFrameworkTitle = computed(() => {
  if (!isCrossFrameworkItem.value) return null;
  return crossFrameworkFrameworkTitle.value || displayItem.value?.externalFrameworkTitle || null;
});

// ---------------------------------------------------------------------------
// Read-only / edit permission
// ---------------------------------------------------------------------------
const isReadOnly = computed(() => props.currentDocument?.isReadOnly || !sessionStore.isAuthenticated);
const canEditItem = computed(() => {
  if (isReadOnly.value) return false;
  if (!props.item) return false;
  return contextStore.isEditable(props.item);
});

const isViewingDifferentFramework = computed(() => contextStore.isViewingDifferentFramework);

const viewedDoc = computed(() => {
  if (!contextStore.viewedDocumentId) return null;
  return contextStore.documentRegistry.get(contextStore.viewedDocumentId);
});

const isItemFromViewedFramework = computed(() => {
  if (!isViewingDifferentFramework.value) return false;
  const itemDocId = props.item?.documentId || props.item?.CFDocumentURI?.identifier;
  return itemDocId === contextStore.viewedDocumentId;
});

// ---------------------------------------------------------------------------
// Dynamic modals
// ---------------------------------------------------------------------------
const { showModal, selectedType, isModalVisible, handleCreated, modalComponent, handleHidden, parentItem } =
  useDynamicModal(
    props.item,
    (newItem) => { emit('add-child', newItem); },
    availableTypes
  );

const {
  showEditModal,
  selectedEditType,
  isEditModalVisible,
  editingItem,
  editModalComponent,
  handleUpdated,
  handleEditHidden,
} = useDynamicEditModal(
  (updatedItem) => { emit('update-item', updatedItem); },
  availableTypes
);

function handleDropdownClick(type) {
  if (availableTypes.includes(type)) {
    showModal(type);
  }
}

// ---------------------------------------------------------------------------
// Association management (delegated to composable)
// ---------------------------------------------------------------------------
const {
  mergedAssociations,
  isProcessingAssociations,
  showDeleteModal,
  associationToDelete,
  handleDeleteAssociationRequest,
  handleDeleteConfirmed,
  handleDeleteModalHidden,
} = useItemAssociations({ item: computed(() => props.item), displayItem });

// ---------------------------------------------------------------------------
// Markdown rendering (lazy-loaded)
// ---------------------------------------------------------------------------
let markdownRendererPromise = null;
let cachedRender = null;
let cachedHasMarkdown = null;

async function getMarkdownRenderer() {
  if (cachedRender && cachedHasMarkdown) return { render: cachedRender, hasMarkdown: cachedHasMarkdown };
  if (!markdownRendererPromise) {
    markdownRendererPromise = Promise.all([
      import('../../../utils/render-md.js'),
      import('../../../utils/markdownRenderer.js'),
    ]).then(([renderModule, mdRendererModule]) => {
      cachedRender = renderModule.default;
      cachedHasMarkdown = mdRendererModule.hasMarkdown;
      return { render: cachedRender, hasMarkdown: cachedHasMarkdown };
    });
  }
  return markdownRendererPromise;
}

const render = ref(null);
const hasMarkdown = ref(null);

onMounted(async () => {
  const renderer = await getMarkdownRenderer();
  render.value = renderer.render;
  hasMarkdown.value = renderer.hasMarkdown;

  // Restore extended info preference
  const stored = localStorage.getItem('itemDetailsShowExtended');
  // (UI toggle not currently exposed but preference preserved)
  void stored;
});

const renderedFullStatement = computed(() => {
  if (!displayItem.value?.fullStatement) return '';
  return render.value ? render.value.block(displayItem.value.fullStatement) : displayItem.value.fullStatement;
});

const renderedNotes = computed(() => {
  if (!displayItem.value?.notes) return '';
  return render.value ? render.value.block(displayItem.value.notes) : displayItem.value.notes;
});

// ---------------------------------------------------------------------------
// Icon & specialized component resolution
// ---------------------------------------------------------------------------
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
    item: itemIcon,
  };
  return iconMap[type] || itemIcon;
});

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
    default: null,
  };
  return componentMap[type] || null;
});

// ---------------------------------------------------------------------------
// License name resolution
// ---------------------------------------------------------------------------
const currentDocumentStore = useCurrentDocumentStore();

const licenseName = computed(() => {
  if (!displayItem.value?.licenseURI?.identifier) return null;
  const licenseId = displayItem.value.licenseURI.identifier;
  const licenses = currentDocumentStore.currentDocumentDefinitions?.CFLicenses || [];
  const licenseDef = licenses.find((lic) => lic.identifier === licenseId);
  if (licenseDef?.title) return licenseDef.title;
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
</style>
