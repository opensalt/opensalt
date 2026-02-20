<template>
  <div class="item-details">
    <!-- Item Header -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 d-flex align-items-center">
          <img :src="itemIconSrc" class="me-2 item-icon" aria-hidden="true" />
          Item Details
        </h6>
        <div class="btn-group btn-group-sm" v-if="!isReadOnly">
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
          <span v-if="item.humanCodingScheme" class="badge bg-secondary me-2">
            {{ item.humanCodingScheme }}
          </span>
          {{ item.abbreviatedStatement || '' }}
        </h5>

        <!-- Specialized Item Details -->
        <component
          v-if="itemDetailsComponent"
          :is="itemDetailsComponent"
          :item="item"
        />

        <!-- Default Item Details (for items without specialized component) -->
        <div v-else>
          <div v-if="item.fullStatement" class="mb-3">
            <strong>Full Statement:</strong>
            <div class="mt-1 markdown-content" v-html="renderedFullStatement"></div>
            <div v-if="hasMarkdownContent" class="mt-1">
              <small class="text-muted">
                <i class="bi bi-markdown"></i> Rendered as Markdown
              </small>
            </div>
          </div>

          <div v-if="item.abbreviatedStatement && item.abbreviatedStatement !== item.fullStatement" class="mb-3">
            <strong>Abbreviated Statement:</strong>
            <p class="mt-1">{{ item.abbreviatedStatement }}</p>
          </div>

          <div class="row">
            <div v-if="item.itemType" class="col-sm-6">
              <strong>Item Type:</strong> {{ item.itemType || 'General' }}
            </div>
            <div v-if="item.language" class="col-sm-6">
              <strong>Language:</strong> {{ item.language || 'en' }}
            </div>
          </div>

          <div v-if="item.educationLevel && item.educationLevel.length > 0" class="mt-2">
              <strong>Education Level:</strong>
              <div class="mt-1">
                  <span v-for="level in item.educationLevel" :key="level" class="badge bg-info text-dark me-1">
                      {{ level }}
                  </span>
              </div>
          </div>

          <div v-if="item.conceptKeywords && item.conceptKeywords.length > 0" class="mt-2">
              <strong>Keywords:</strong>
              <div class="mt-1">
                  <span v-for="keyword in item.conceptKeywords" :key="keyword" class="badge bg-secondary me-1">
                      {{ keyword }}
                  </span>
              </div>
          </div>

          <div v-if="item.licenseURI" class="mt-2 text-truncate">
              <strong>License:</strong> <span class="ms-1">{{ licenseName }}</span>
          </div>

          <div v-if="item.lastChanged" class="mt-2">
            <small class="text-muted">
              Last changed: {{ formatDate(item.lastChanged) }}
            </small>
          </div>

            <div v-if="item.notes" class="mt-3">
                <strong>Notes:</strong>
                <p class="mt-1 markdown-content" v-html="renderedNotes"></p>
            </div>
        </div>

          <!-- Actions -->
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
      <div v-if="groupedAssociations.length > 0" class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Associations</h6>
          <button v-if="!isReadOnly" type="button" class="btn btn-sm btn-outline-primary" @click="$emit('add-association', item)">
            <i class="bi bi-plus"></i> Add
          </button>
        </div>
        <div class="card-body">
          <AssociationGroupDisplay
            v-for="group in groupedAssociations"
            :key="`${group.type}-${group.direction}`"
            :association-type="group.type"
            :associations="group.associations"
            :association-groups="associationGroups"
            :direction="group.direction"
            :item-identifier="item.identifier"
            :is-read-only="isReadOnly"
            @edit-association="!isReadOnly ? $emit('edit-association', $event) : null"
            @delete-association="!isReadOnly ? $emit('delete-association', $event) : null"
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
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { Teleport } from 'vue';
import AssociationGroupDisplay from '../../association/AssociationGroupDisplay.vue';
import CommentModule from '../CommentModule.vue';
import { useDynamicModal } from '../../../composables/useDynamicModal.js';
import { useDynamicEditModal } from '../../../composables/useDynamicEditModal.js';

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

// Toggle state for extended info
const showExtendedInfo = ref(false);

// Load preference from localStorage on mount
onMounted(() => {
  const stored = localStorage.getItem('itemDetailsShowExtended');
  showExtendedInfo.value = stored === 'true';
});

// Toggle function with persistence
function toggleExtendedInfo() {
  showExtendedInfo.value = !showExtendedInfo.value;
  localStorage.setItem('itemDetailsShowExtended', showExtendedInfo.value.toString());
}

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}

// Group associations by type and direction (excluding isChildOf)
const groupedAssociations = computed(() => {
  if (!props.item?.associations) return [];

  const filtered = props.item.associations.filter(assoc =>
    assoc.associationType !== 'isChildOf' && assoc.type !== 'isChildOf'
  );

  // Group by association type and direction (normal vs reversed)
  const groups = {};
  filtered.forEach(assoc => {
    const type = assoc.associationType || assoc.type || 'unknown';

    // Determine if association is reversed (item is destination, not origin)
    const destId = (assoc.destinationNodeURI || assoc.destination)?.identifier;
    const isReversed = destId === props.item.identifier;
    const direction = isReversed ? 'reversed' : 'normal';

    const groupKey = `${type}-${direction}`;
    if (!groups[groupKey]) {
      groups[groupKey] = {
        type,
        direction,
        associations: []
      };
    }
    groups[groupKey].associations.push(assoc);
  });

  // Convert to array format for template
  return Object.values(groups).sort((a, b) => {
    // Sort by type first, then by direction (normal before reversed)
    const typeCompare = a.type.localeCompare(b.type);
    if (typeCompare !== 0) return typeCompare;
    return a.direction.localeCompare(b.direction);
  });
});

// Render fullStatement as markdown
const renderedFullStatement = computed(() => {
  if (!props.item?.fullStatement) return '';
  return render.value ? render.value.block(props.item.fullStatement) : props.item.fullStatement;
});

// Check if fullStatement contains markdown
const hasMarkdownContent = computed(() => {
  if (!props.item?.fullStatement) return false;
  return hasMarkdown.value ? hasMarkdown.value(props.item.fullStatement) : false;
});

// Render notes as markdown
const renderedNotes = computed(() => {
    if (!props.item?.notes) return '';
    return render.value ? render.value.block(props.item.notes) : props.item.notes;
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
  const type = props.item.extensions?.['salt:type'] || 'item';
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
  const type = props.item.extensions?.['salt:type'] || 'default';
  const componentMap = {
    job: JobItemDetails,
    course: CourseItemDetails,
    assessment: AssessmentItemDetails,
    credential: CredentialItemDetails,
    organization: OrganizationItemDetails,
    identifier: IdentifierItemDetails,
    public_key: PublicKeyItemDetails,
    default: null // Default uses the base implementation
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
import { useCurrentDocumentStore } from '../../../stores/currentDocumentStore';

const sessionStore = useSessionStore();
const isReadOnly = computed(() => props.currentDocument?.isReadOnly || !sessionStore.isAuthenticated);

// Get license name from definitions
const currentDocumentStore = useCurrentDocumentStore();
const licenseName = computed(() => {
  if (!props.item?.licenseURI?.identifier) {
    return null;
  }

  const licenseId = props.item.licenseURI.identifier;
  const licenses = currentDocumentStore.currentDocumentDefinitions?.CFLicenses || [];

  // Find license by identifier in definitions
  const licenseDef = licenses.find(lic => lic.identifier === licenseId);

  // Return license title if found, otherwise fall back to the URI
  if (licenseDef?.title) {
    return licenseDef.title;
  }

  // Fallback to the license URI or identifier
  return props.item.licenseURI.uri || props.item.licenseURI.identifier;
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

.markdown-content h1 { font-size: 1.25rem; }
.markdown-content h2 { font-size: 1.125rem; }
.markdown-content h3 { font-size: 1rem; }

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

.markdown-content pre code {
  background-color: transparent;
  padding: 0;
  border-radius: 0;
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
