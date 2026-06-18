<template>
  <section
    ref="detailRef"
    class="item-details"
    aria-labelledby="item-detail-heading"
  >
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
      :is-adopted="isAdopted"
      :is-item-from-viewed-framework="isItemFromViewedFramework"
      @edit="$emit('edit-item', item)"
      @delete="$emit('delete-item', item)"
    >
      <!-- Specialized Item Details -->
      <component
        :is="itemDetailsComponent"
        v-if="itemDetailsComponent"
        :item="displayItem"
      />

      <!-- Default Item Details -->
      <ItemDefaultDetails
        v-else
        :item="displayItem"
        :rendered-full-statement="renderedFullStatement"
        :rendered-notes="renderedNotes"
        :license-name="licenseName"
      />

      <!-- Actions card (inside the outer card-body "shell", after the header card) -->
      <ItemActionsCard
        :can-edit-item="canEditItem"
        :is-adopted="isAdopted"
        :is-item-from-viewed-framework="isItemFromViewedFramework"
        :is-read-only="isReadOnly"
        :available-types="availableTypes"
        @add-child="handleDropdownClick"
        @add-exemplar="$emit('add-exemplar', item)"
      />

      <template #header-actions>
        <ItemMovementCard
          :item="item"
          :current-document="currentDocument"
          :can-edit-item="canEditItem"
          :is-item-from-viewed-framework="isItemFromViewedFramework"
          :is-read-only="isReadOnly"
        />
      </template>
      <template #header-actions-end>
        <!-- View JSON button -->
        <button
          type="button"
          class="btn btn-outline-secondary btn-sm"
          title="View JSON"
          aria-label="View JSON"
          @click="$emit('view-json', { type: 'item', identifier: displayItem.identifier })"
        >
          <i
            class="bi bi-code-slash"
            aria-hidden="true"
          />
        </button>
      </template>
    </ItemHeaderCard>

    <!-- Associations card -->
    <ItemAssociationsCard
      :merged-associations="mergedAssociations"
      :is-processing-associations="isProcessingAssociations"
      :is-cross-framework-item="isCrossFrameworkItem"
      :association-groups="associationGroups"
      :item-identifier="item.identifier"
      :is-read-only="isReadOnly"
      :can-edit-item="canEditItem"
      :can-manage-association-actions="canManageAssociationActions"
      :association-actions-read-only="!sessionStore.isAuthenticated"
      :is-viewing-different-framework="isViewingDifferentFramework"
      :current-document="currentDocument"
      @add-association="$emit('add-association', item)"
      @edit-association="$emit('edit-association', $event)"
      @delete-association="$emit('delete-association', $event)"
    />

    <!-- Comments -->
    <CommentModule
      v-if="commentsEnabled && item?.identifier"
      item-type="item"
      :item-identifier="item.identifier"
    />
  </section>
</template>

<script setup>
/* global localStorage */
import { computed, ref, onMounted, watch, nextTick } from 'vue';
import { editorConfig } from '../../../config/editorConfig.js';

// Sub-components
import ItemCrossFrameworkBanner from './ItemCrossFrameworkBanner.vue';
import ItemHeaderCard from './ItemHeaderCard.vue';
import ItemDefaultDetails from './ItemDefaultDetails.vue';
import ItemActionsCard from './ItemActionsCard.vue';
import ItemMovementCard from './ItemMovementCard.vue';
import ItemAssociationsCard from './ItemAssociationsCard.vue';
import CommentModule from '../CommentModule.vue';

import { useItemAssociations } from '../../../composables/useItemAssociations.js';
import { useEditorContextStore } from '../../../stores/editorContextStore';
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
  'view-json',
]);

// ---------------------------------------------------------------------------
// Template ref & focus management
// ---------------------------------------------------------------------------
const detailRef = ref(null);

watch(() => props.item, (newItem) => {
  if (newItem) {
    nextTick(() => {
      const heading = detailRef.value?.querySelector('#item-detail-heading');
      if (heading) {
        heading.setAttribute('tabindex', '-1');
        heading.focus();
      }
    });
  }
});

// ---------------------------------------------------------------------------
// Stores
// ---------------------------------------------------------------------------
const contextStore = useEditorContextStore();
const sessionStore = useSessionStore();
const commentsEnabled = editorConfig.features.comments;

// ---------------------------------------------------------------------------
// Item type constants
// ---------------------------------------------------------------------------
const availableTypes = ['general', 'assessment', 'course', 'credential', 'job', 'organization', 'public_key', 'identifier'];

// ---------------------------------------------------------------------------
// Cross-framework item detection & data loading
// ---------------------------------------------------------------------------
const isCrossFrameworkItem = computed(() => props.item?.isCrossFramework === true);

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

// displayItem merges live prop data with any fetched cross-framework overrides
const displayItem = computed(() => ({
  ...props.item,
  ...(isCrossFrameworkItem.value ? crossFrameworkItemData.value || {} : {}),
}));

const externalFrameworkTitle = computed(() => {
  if (!isCrossFrameworkItem.value) return null;
  return crossFrameworkFrameworkTitle.value || displayItem.value?.externalFrameworkTitle || null;
});

// ---------------------------------------------------------------------------
// Read-only / edit permission
// ---------------------------------------------------------------------------
const isViewingDifferentFramework = computed(() => contextStore.isViewingDifferentFramework);

const isItemFromViewedFramework = computed(() => {
  if (!isViewingDifferentFramework.value) return false;

  const itemDocId = props.item?.documentId || props.item?.CFDocumentURI?.identifier;
  return itemDocId === contextStore.viewedDocumentId;
});

const canEditItem = computed(() => {
  if (!sessionStore.isAuthenticated) return false;
  if (!contextStore.canEdit) return false;
  if (!props.item) return false;

  return contextStore.isEditable(props.item.identifier);
});

const isReadOnly = computed(() => !canEditItem.value);

const canManageAssociationActions = computed(() => canEditItem.value);

const isAdopted = computed(() => props.currentDocument?.adoptionStatus === 'Adopted');

const viewedDoc = computed(() => {
  if (!contextStore.viewedDocumentId) return null;
  return contextStore.documentRegistry.get(contextStore.viewedDocumentId);
});

function handleDropdownClick(type) {
  if (availableTypes.includes(type)) {
    emit('add-child', props.item, type);
  }
}

// ---------------------------------------------------------------------------
// Association management (delegated to composable)
// ---------------------------------------------------------------------------
const {
  mergedAssociations,
  isProcessingAssociations,
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
