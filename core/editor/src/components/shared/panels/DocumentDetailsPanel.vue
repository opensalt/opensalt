<template>
  <div class="document-details-panel">
    <!-- Document Header -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 d-flex align-items-center">
          <img
            :src="docIcon"
            class="me-2 item-icon"
            aria-hidden="true"
          >
          Document Details
        </h6>
        <div
          v-if="!isReadOnly"
          class="btn-group btn-group-sm"
        >
          <button
            type="button"
            class="btn btn-outline-primary"
            title="Edit document"
            @click="$emit('edit-document')"
          >
            <i class="bi bi-pencil" />
          </button>
          <button
            type="button"
            class="btn btn-outline-danger"
            title="Delete document"
            @click="$emit('delete-document')"
          >
            <i class="bi bi-trash" />
          </button>
        </div>
        <div
          v-else
          class="text-muted small"
          title="Document is read-only"
        >
          <i class="bi bi-lock-fill" /> Read-only
        </div>
      </div>
      <div class="card-body">
        <h5 class="card-title">
          <span class="badge bg-primary me-2">{{ document.status || 'Draft' }}</span>
          {{ document.title || 'Untitled Document' }}
        </h5>

        <div
          v-if="document.identifier"
          class="mb-3"
        >
          <strong>Identifier:</strong>
          <a
            :href="`/uri/${document.identifier}`"
            target="_blank"
            class="ms-1"
          >{{ document.identifier }}</a>
        </div>

        <div
          v-if="document.description"
          class="mb-3"
        >
          <strong>Description:</strong>
          <p class="mt-1">
            {{ document.description }}
          </p>
        </div>

        <div class="row">
          <div class="col-sm-6">
            <strong>Creator:</strong> {{ document.creator || 'Unknown' }}
          </div>
          <div
            v-if="document.language?.length"
            class="col-sm-6"
          >
            <strong>Language:</strong> {{ document.language || '' }}
          </div>
        </div>

        <div class="row mt-2">
          <div
            v-if="document.version?.length"
            class="col-sm-6"
          >
            <strong>Version:</strong> {{ document.version || '' }}
          </div>
          <div class="col-sm-6">
            <strong>Framework Type:</strong> {{ document.frameworkType || 'Standard' }}
          </div>
        </div>

        <div
          v-if="document.subject && document.subject.length > 0"
          class="mt-3"
        >
          <strong>Subject: </strong>
          <div class="mt-1 d-inline-flex">
            <span
              v-for="subject in document.subject"
              :key="subject"
              class="badge bg-secondary me-1"
            >
              {{ subject }}
            </span>
          </div>
        </div>

        <div
          v-if="document.publisher"
          class="mt-2"
        >
          <strong>Publisher:</strong> {{ document.publisher }}
        </div>

        <div
          v-if="false && isAdmin && document.orgName"
          class="mt-2"
        >
          <strong>Owning Access Group:</strong> {{ document.orgName }}
        </div>

        <div
          v-if="document.licenseURI"
          class="mt-2 text-truncate"
        >
          <strong>License:</strong> <span class="ms-1">{{ licenseName }}</span>
        </div>

        <div
          v-if="document.officialSourceURL"
          class="mt-2"
        >
          <strong>Source URL:</strong> <a
            :href="document.officialSourceURL"
            target="_blank"
            class="text-decoration-none"
          >
            {{ document.officialSourceURL }}
          </a>
        </div>

        <div
          v-if="document.notes"
          class="mt-3"
        >
          <strong>Notes:</strong>
          <p class="mt-1">
            {{ document.notes }}
          </p>
        </div>

        <div
          v-if="document.lastModified"
          class="mt-2"
        >
          <small class="text-muted">
            Last modified: {{ formatDate(document.lastModified) }}
          </small>
        </div>
      </div>

      <!-- Document Actions -->
      <div class="card mt-0 border-0">
        <div class="card-body pt-0 ms-auto">
          <div class="d-flex flex-wrap gap-2">
            <!-- Export - always available to all users -->
            <button
              type="button"
              class="btn btn-outline-secondary"
              @click="$emit('export-document')"
            >
              <i class="bi bi-box-arrow-up-right" /> Export
            </button>

            <!-- Editor-only actions -->
            <template v-if="!isReadOnly">
              <div class="btn-group">
                <button
                  type="button"
                  class="btn btn-outline-primary"
                  @click="showModal('general')"
                >
                  <i class="bi bi-plus-circle" /> Add Root Item
                </button>
                <button
                  type="button"
                  class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                >
                  <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                  <li
                    v-for="type in availableTypes"
                    :key="type"
                  >
                    <a
                      class="dropdown-item"
                      href="#"
                      :aria-label="`Add ${getDisplayName(type)}`"
                      @click="showModal(type)"
                    >
                      Add {{ getDisplayName(type) }}
                    </a>
                  </li>
                </ul>
              </div>
              <button
                type="button"
                class="btn btn-outline-secondary"
                @click="$emit('manage-association-groups')"
              >
                <i class="bi bi-tags" /> Manage Groups
              </button>

              <button
                type="button"
                class="btn btn-outline-secondary"
                @click="$emit('update-framework')"
              >
                <i class="bi bi-arrow-repeat" /> Update Framework
              </button>
              <button
                type="button"
                class="btn btn-outline-secondary"
                @click="$emit('clone-framework')"
              >
                <i class="bi bi-copy" /> Clone Framework
              </button>
              <button
                v-if="document?.identifier"
                type="button"
                class="btn btn-outline-secondary"
                title="Manage document access control"
                @click="manageAccess"
              >
                <i class="bi bi-shield-lock" /> Manage Access
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <ItemAssociationsCard
      :merged-associations="mergedAssociations"
      :is-processing-associations="isProcessingAssociations"
      :is-cross-framework-item="false"
      :association-groups="associationGroups"
      :item-identifier="document?.identifier"
      :is-read-only="isReadOnly"
      :can-edit-item="canManageAssociationActions"
      :can-manage-association-actions="canManageAssociationActions"
      :association-actions-read-only="!sessionStore.isAuthenticated"
      :is-viewing-different-framework="isViewingDifferentFramework"
      :current-document="document"
      :show-add-button="true"
      @add-association="$emit('add-association', document)"
      @edit-association="$emit('edit-association', $event)"
      @delete-association="$emit('delete-association', $event)"
    />



    <!-- Comments -->
    <CommentModule
      v-if="commentsEnabled && document?.identifier"
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
import { computed } from 'vue';
import { useDynamicModal } from '../../../composables/useDynamicModal.js';
import { useDocumentAssociations } from '../../../composables/useDocumentAssociations.js';
import { editorConfig } from '../../../config/editorConfig.js';
import CommentModule from '../CommentModule.vue';
import ItemAssociationsCard from './ItemAssociationsCard.vue';
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
const contextStore = useEditorContextStore();
const isReadOnly = computed(() => props.isViewingDifferentFramework || props.document?.isReadOnly || !sessionStore.isAuthenticated);
const isAdmin = computed(() => contextStore.isAdmin);
const commentsEnabled = editorConfig.features.comments;

// Get license name from definitions
const currentDocumentStore = useCurrentDocumentStore();

const {
  mergedAssociations,
  isProcessingAssociations,
} = useDocumentAssociations({
  document: computed(() => props.document),
});

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

const canManageAssociationActions = computed(() => {
  if (!sessionStore.isAuthenticated) return false;

  const activeDocumentId =
    contextStore.activeWriteDocumentId ||
    currentDocumentStore.currentDocument?.identifier ||
    currentDocumentStore.currentDocument?.id ||
    null;

  if (!activeDocumentId) return false;
  return contextStore.isEditable(activeDocumentId);
});

const emit = defineEmits([
  'edit-document',
  'delete-document',
  'add-root-item',
  'manage-association-groups',
  'update-framework',
  'export-document',
  'clone-framework',
  'edit-association',
  'delete-association',
  'add-association'
]);

const availableTypes = ['general', 'assessment', 'course', 'credential', 'job', 'organization', 'public_key', 'identifier'];

const { showModal, selectedType, isModalVisible, handleCreated, modalComponent, handleHidden } = useDynamicModal(
  props.document?.identifier || null,
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

function manageAccess() {
  if (props.document?.identifier) {
    window.location.href = `/cfdoc/${props.document.identifier}/acl`;
  }
}
</script>

<style scoped>
/* Document details specific styles can be added here */
</style>
