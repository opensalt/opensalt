<template>
  <div :class="compact ? 'document-selector document-selector-compact mb-2' : 'document-selector card mt-0 mb-3'">
    <div
      v-if="!compact"
      class="card-header d-flex justify-content-between align-items-center"
    >
      <h6 class="mb-0">
        {{ label }}
      </h6>
      <span
        v-if="isViewingDifferentFramework"
        class="badge bg-warning text-dark"
        role="status"
        aria-live="polite"
        title="You are viewing a different framework than the one being edited"
      >
        <i
          class="bi bi-eye me-1"
          aria-hidden="true"
        />Read-only
      </span>
      <button
        type="button"
        class="btn btn-sm btn-outline-primary"
        aria-label="Change document"
        title="Change document"
        @click="changeDocument"
      >
        <i
          class="bi bi-arrow-repeat"
          aria-hidden="true"
        />
      </button>
    </div>

    <div :class="compact ? 'mx-2' : 'card-body'">
      <!-- eslint-disable-next-line vuejs-accessibility/label-has-for -->
      <label
        v-if="compact"
        class="form-label fw-semibold small mb-0"
      >
        <select
          :id="selectorId"
          v-model="selectedDoc"
          class="form-select form-select-sm"
          :class="{ 'viewing-different-framework': isViewingDifferentFramework }"
          @change="onDocumentChange"
        >
          <optgroup
            v-if="mappedFrameworks.length > 0"
            label="Mapped Frameworks"
          >
            <option
              v-for="doc in mappedFrameworks"
              :key="doc.identifier"
              :value="doc.identifier"
              :class="doc.identifier === (currentDoc?.identifier || currentDoc?.id) ? 'text-primary fw-semibold' : ''"
            >
              {{ doc.identifier === (currentDoc?.identifier || currentDoc?.id) ? 'Main framework - ' : '' }}
              {{ doc.title || 'Unknown Name' }} ({{ doc.identifier || 'No Identifier' }})
              {{ (doc.identifier === selectedDoc && isViewingDifferentFramework) ? ' - Read-only' : '' }}
            </option>
          </optgroup>
          <optgroup
            v-for="group in otherGroupedDocuments"
            :key="group.creator"
            :label="group.creator"
          >
            <option
              v-for="doc in group.documents"
              :key="doc.identifier"
              :value="doc.identifier"
              :class="doc.identifier === (currentDoc?.identifier || currentDoc?.id) ? 'text-primary fw-semibold' : ''"
            >
              {{ doc.identifier === (currentDoc?.identifier || currentDoc?.id) ? 'Main framework - ' : '' }}
              {{ doc.title || 'Unknown Name' }} ({{ doc.identifier || 'No Identifier' }})
              {{ (doc.identifier === selectedDoc && isViewingDifferentFramework) ? ' - Read-only' : '' }}
            </option>
          </optgroup>
        </select>
      </label>
      <select
        v-else
        :id="selectorId"
        v-model="selectedDoc"
        class="form-select"
        :class="{ 'viewing-different-framework': isViewingDifferentFramework }"
        @change="onDocumentChange"
      >
        <option value="">
          Select a document...
        </option>
        <optgroup
          v-if="mappedFrameworks.length > 0"
          label="Mapped Frameworks"
        >
          <option
            v-for="doc in mappedFrameworks"
            :key="doc.identifier"
            :value="doc.identifier"
            :class="doc.identifier === (currentDoc?.identifier || currentDoc?.id) ? 'text-primary fw-semibold' : ''"
          >
            {{ doc.identifier === (currentDoc?.identifier || currentDoc?.id) ? 'Main Document - ' : '' }}
            {{ doc.title || 'Unknown Name' }} ({{ doc.identifier || 'No Identifier' }})
          </option>
        </optgroup>
        <optgroup
          v-for="group in otherGroupedDocuments"
          :key="group.creator"
          :label="group.creator"
        >
          <option
            v-for="doc in group.documents"
            :key="doc.identifier"
            :value="doc.identifier"
            :class="doc.identifier === (currentDoc?.identifier || currentDoc?.id) ? 'text-primary fw-semibold' : ''"
          >
            {{ doc.identifier === (currentDoc?.identifier || currentDoc?.id) ? 'Main Document - ' : '' }}
            {{ doc.title || 'Unknown Name' }} ({{ doc.identifier || 'No Identifier' }})
          </option>
        </optgroup>
        <optgroup
          v-if="!hideExternal"
          label="External Documents"
        >
          <option value="external">
            Load external document...
          </option>
        </optgroup>
      </select>

      <div
        v-if="!compact && isViewingDifferentFramework && viewedDoc"
        class="mt-2 small text-muted"
      >
        <i class="bi bi-info-circle me-1" />
        Viewed framework: <strong>{{ viewedDoc.title || 'Untitled' }}</strong>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, useId } from 'vue';
import { useDocumentStore } from '@/stores/documentStore';
import { useDocumentGroups } from '@/composables/useDocumentGroups.js';

const props = defineProps({
  currentDoc: {
    type: Object,
    default: null
  },
  availableDocuments: {
    type: Array,
    default: () => []
  },
  label: {
    type: String,
    default: 'Document'
  },
  side: {
    type: String,
    default: 'left'
  },
  viewedDoc: {
    type: Object,
    default: null
  },
  isViewingDifferentFramework: {
    type: Boolean,
    default: false
  },
  hideExternal: {
    type: Boolean,
    default: false
  },
  compact: {
    type: Boolean,
    default: false
  },
  /**
   * Set of framework identifiers that are referenced by the current crosswalk
   * framework's associations. When non-empty, these frameworks are shown in a
   * "Mapped Frameworks" optgroup at the top of the selector.
   */
  relatedFrameworkIds: {
    type: Set,
    default: () => new Set()
  }
});

const documentStore = useDocumentStore();
const allDocuments = computed(() =>
  props.availableDocuments?.length ? props.availableDocuments : documentStore.documents,
);

// Split documents into "mapped" (related to crosswalk) and "other" groups
const mappedFrameworks = computed(() => {
  if (props.relatedFrameworkIds.size === 0) return [];
  const docs = allDocuments.value.filter(doc =>
    props.relatedFrameworkIds.has(doc.identifier)
  );
  return docs.sort((a, b) => (a.title || '').localeCompare(b.title || ''));
});

const otherDocuments = computed(() => {
  if (props.relatedFrameworkIds.size === 0) return allDocuments.value;
  return allDocuments.value.filter(doc =>
    !props.relatedFrameworkIds.has(doc.identifier)
  );
});

const { groupedDocuments: otherGroupedDocuments } = useDocumentGroups(otherDocuments);

const emit = defineEmits(['viewed-document-changed', 'external-document-requested']);

const selectedDoc = ref('');

function getDocumentId(document) {
  return document?.identifier || document?.id || '';
}

// Unique per-instance suffix so co-rendered selectors never collide on id
const instanceId = useId();
const selectorId = computed(() => `documentSelector-${props.side}-${instanceId}`);

watch(() => props.currentDoc, (newDoc) => {
  // Don't clobber an active viewed-framework selection when the edited doc updates
  if (getDocumentId(props.viewedDoc)) return;
  selectedDoc.value = getDocumentId(newDoc);
  if (!props.compact && selectedDoc.value) {
    emit('viewed-document-changed', {
      side: props.side,
      documentId: selectedDoc.value
    });
  }
}, { immediate: true });

watch(() => props.viewedDoc, (newViewedDoc) => {
  const viewedDocumentId = getDocumentId(newViewedDoc);
  if (viewedDocumentId) {
    selectedDoc.value = viewedDocumentId;
  } else {
    // Falling back to the edited framework keeps the dropdown in sync
    selectedDoc.value = getDocumentId(props.currentDoc);
  }
}, { immediate: true });

function onDocumentChange() {
  const selectedValue = selectedDoc.value;

  if (selectedValue === 'external') {
    emit('external-document-requested', { side: props.side });
    selectedDoc.value = getDocumentId(props.currentDoc);
  } else if (selectedValue) {
    emit('viewed-document-changed', {
      side: props.side,
      documentId: selectedValue
    });
  }
}

function changeDocument() {
  selectedDoc.value = '';
}
</script>

<style scoped>
.card-header {
  padding: 0.5rem 1rem;
}
.card-body {
  padding: 1rem;
}
.form-select {
  font-size: 0.875rem;
}
</style>
