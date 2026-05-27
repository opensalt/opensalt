<template>
  <div class="document-selector card mt-0 mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0">
        {{ label }}
      </h6>
      <!-- Visual indicator when viewing a different framework -->
      <span
        v-if="isViewingDifferentFramework"
        class="badge bg-warning text-dark"
        title="You are viewing a different framework than the one being edited"
      >
        <i
          class="bi bi-eye me-1"
          aria-hidden="true"
        />Viewing
      </span>
      <button
        type="button"
        class="btn btn-sm btn-outline-primary"
        title="Change document"
        @click="changeDocument"
      >
        <i
          class="bi bi-arrow-repeat"
          aria-hidden="true"
        />
      </button>
    </div>
    <div class="card-body">
      <select
        v-model="selectedDoc"
        class="form-select"
        :class="{ 'viewing-different-framework': isViewingDifferentFramework }"
        @change="onDocumentChange"
      >
        <option value="">
          Select a document...
        </option>
        <optgroup
          v-for="group in groupedDocuments"
          :key="group.creator"
          :label="group.creator"
        >
          <option
            v-for="doc in group.documents"
            :key="doc.identifier"
            :value="doc.identifier"
            :selected="doc.identifier === (currentDoc?.identifier || currentDoc?.id)"
            :style="(doc.identifier === (currentDoc?.identifier || currentDoc?.id)) ? 'color: blue;' : ''"
          >
            {{ doc.identifier === (currentDoc?.identifier || currentDoc?.id) ? '** Current Document ** - ' : '' }}
            {{ doc.title || 'Unknown Name' }} ({{ doc.identifier || 'No Identifier' }})
          </option>
        </optgroup>
        <optgroup label="External Documents">
          <option value="external">
            Load external document...
          </option>
        </optgroup>
      </select>
      <!-- Viewing indicator text -->
      <div
        v-if="isViewingDifferentFramework && viewedDoc"
        class="mt-2 small text-muted"
      >
        <i class="bi bi-info-circle me-1" />
        Viewing: <strong>{{ viewedDoc.title || 'Untitled' }}</strong>
      </div>
    </div>

    <!-- External Document Modal (Global check needed or move to parent?) -->
    <!-- Ideally, this modal should be at the page level, but for now we keep it here or handle it via event -->
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useDocumentStore } from '@/stores/documentStore';

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
    type: String, // 'left' or 'right'
    default: 'left'
  },
  // NEW: Props for dual framework edit/view separation
  viewedDoc: {
    type: Object,
    default: null
  },
  isViewingDifferentFramework: {
    type: Boolean,
    default: false
  }
});

// Use the document store
const documentStore = useDocumentStore();

// Get grouped documents
const groupedDocuments = computed(() => {
  const grouped = new Map();

  // Group documents by creator
  documentStore.documents.forEach(doc => {
    const creator = doc.creator || 'Unknown Creator';
    if (!grouped.has(creator)) {
      grouped.set(creator, []);
    }
    grouped.get(creator).push(doc);
  });

  // Sort creators alphabetically
  const sortedCreators = Array.from(grouped.keys()).sort();

  // Sort documents within each creator group alphabetically by title
  const result = [];
  sortedCreators.forEach(creator => {
    const docs = grouped.get(creator).sort((a, b) => a.title.localeCompare(b.title));
    result.push({
      creator,
      documents: docs
    });
  });

  return result;
});

// NEW: Changed from 'document-changed' to 'viewed-document-changed' for dual framework edit/view separation
const emit = defineEmits(['viewed-document-changed', 'external-document-requested']);

const selectedDoc = ref('');

function getDocumentId(document) {
  return document?.identifier || document?.id || '';
}

watch(() => props.currentDoc, (newDoc) => {
  selectedDoc.value = getDocumentId(newDoc);
  if (selectedDoc.value) {
    emit('viewed-document-changed', {
      side: props.side,
      documentId: selectedDoc.value
    });
  }
}, { immediate: true });

// NEW: Watch for viewed document changes to update selection
watch(() => props.viewedDoc, (newViewedDoc) => {
  const viewedDocumentId = getDocumentId(newViewedDoc);
  if (viewedDocumentId) {
    selectedDoc.value = viewedDocumentId;
  }
});

function onDocumentChange() {
  const selectedValue = selectedDoc.value;

  if (selectedValue === 'external') {
    // Emit event to request external document loading UI
    emit('external-document-requested', { side: props.side });

    // Reset selection
    selectedDoc.value = getDocumentId(props.currentDoc);
  } else if (selectedValue) {
    // NEW: Emit 'viewed-document-changed' instead of 'document-changed'
    // This supports the dual framework edit/view separation feature
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
.document-selector {
  /* margin-bottom: 1rem; */
}
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
