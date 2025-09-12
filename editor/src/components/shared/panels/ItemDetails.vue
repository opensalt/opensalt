<template>
  <div class="item-details">
    <!-- Item Header -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Item Details</h6>
        <div class="btn-group btn-group-sm">
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
        <h5 class="card-title">
          <span v-if="item.humanCodingScheme" class="badge bg-secondary me-2">
            {{ item.humanCodingScheme }}
          </span>
          {{ item.abbreviatedStatement || '' }}
        </h5>

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

        <div v-if="item.notes" class="mb-3">
          <strong>Notes:</strong>
          <p class="mt-1">{{ item.notes }}</p>
        </div>

        <div class="row">
          <div class="col-sm-6">
            <strong>Item Type:</strong> {{ item.itemType || 'General' }}
          </div>
          <div class="col-sm-6">
            <strong>Language:</strong> {{ item.language || 'en' }}
          </div>
        </div>

        <div v-if="item.lastChanged" class="mt-2">
          <small class="text-muted">
            Last changed: {{ formatDate(item.lastChanged) }}
          </small>
        </div>

      <!-- Actions -->
      <div class="card mt-3">
        <div class="card-header">
          <h6 class="mb-0">Actions</h6>
        </div>
        <div class="card-body">
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
      <div v-if="groupedAssociations.length > 0" class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Associations</h6>
          <button type="button" class="btn btn-sm btn-outline-primary" @click="$emit('add-association', item)">
            <i class="bi bi-plus"></i> Add
          </button>
        </div>
        <div class="card-body">
          <AssociationGroupDisplay
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
import { computed } from 'vue';
import { Teleport } from 'vue';
import AssociationGroupDisplay from '../../association/AssociationGroupDisplay.vue';
import { renderMarkdown, hasMarkdown } from '../../../utils/markdownRenderer.js';
import render from '../../../utils/render-md.js';
import { useDynamicModal } from '../../../composables/useDynamicModal.js';
import { useDynamicEditModal } from '../../../composables/useDynamicEditModal.js';

const props = defineProps({
  item: {
    type: Object,
    required: true
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

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}

// Group associations by type (excluding isChildOf)
const groupedAssociations = computed(() => {
  if (!props.item?.associations) return [];

  const filtered = props.item.associations.filter(assoc =>
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

// Render fullStatement as markdown
const renderedFullStatement = computed(() => {
  if (!props.item?.fullStatement) return '';
  return render.block(props.item.fullStatement);
});

// Check if fullStatement contains markdown
const hasMarkdownContent = computed(() => {
  if (!props.item?.fullStatement) return false;
  return hasMarkdown(props.item.fullStatement);
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

function handleDropdownClick(type) {
  if (availableTypes.includes(type)) {
    showModal(type);
  } else {
    console.warn(`Invalid type: ${type}`);
  }
}
</script>

<style scoped>
.associations-list {
  max-height: 300px;
  overflow-y: auto;
}

/* Markdown content styling */
.markdown-content {
  padding: 0.75rem;
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
</style>
