<template>
  <div class="association-item d-flex justify-content-between align-items-center p-2 border rounded">
    <div class="association-info flex-grow-1">
      <!--
      <div class="d-flex align-items-center mb-2">
        <span class="badge bg-primary me-2">{{ associationType }}</span>
        <span v-if="groupTitle" class="badge bg-secondary">{{ groupTitle }}</span>
      </div>
        -->

      <div class="association-details">
        <div class="mb-1">
          <strong v-if="false">{{ nodeLabel }}</strong>
          <span class="ms-2" v-html="nodeTitle"></span>
        </div>

        <div v-if="notes" class="mb-1">
          <strong>Notes:</strong>
          <span class="ms-2 text-muted">{{ notes }}</span>
        </div>

        <div v-if="lastChangeDateTime && false" class="mb-1">
          <small class="text-muted">
            <strong>Last changed:</strong> {{ formatDate(lastChangeDateTime) }}
          </small>
        </div>
      </div>
    </div>

    <div class="association-actions btn-group btn-group-sm ms-3" v-if="!isReadOnly">
      <button
        type="button"
        class="btn btn-outline-primary"
        @click="$emit('edit', association)"
        title="Edit association"
      >
        <i class="bi bi-pencil"></i>
      </button>
      <button
        type="button"
        class="btn btn-outline-danger"
        @click="$emit('delete', association)"
        title="Delete association"
      >
        <i class="bi bi-trash"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';
import render from '../../utils/render-md';

const props = defineProps({
  association: {
    type: Object,
    required: true
  },
  associationGroups: {
    type: Array,
    default: () => []
  },
  direction: {
    type: String,
    default: 'normal'
  },
  isReadOnly: {
    type: Boolean,
    default: false
  },
  itemIdentifier: {
    type: String,
    required: true
  }
});

const emit = defineEmits(['edit', 'delete']);
const currentDocumentStore = useCurrentDocumentStore();

// Determine if association is reversed (item is destination, not origin)
const isReversed = computed(() => {
    return props.direction === 'reversed';
});

const associationType = computed(() => {
  return props.association.associationType || props.association.type || 'Unknown';
});

// Get the appropriate node identifier based on direction
const nodeIdentifier = computed(() => {
  if (isReversed.value) {
    // Show origin when reversed
    const origin = props.association.originNodeURI || props.association.origin;
    return origin?.identifier;
  }

  // Show destination when normal
  const dest = props.association.destinationNodeURI || props.association.destination;
  return dest?.identifier;
});

// Get the appropriate node display (human coding scheme and abbreviated statement) based on direction
const nodeTitle = computed(() => {
  const identifier = nodeIdentifier.value;
  if (!identifier) return 'Unknown';

  // Try to find the item in current document's items
  const items = currentDocumentStore.currentDocument?.items;
  if (items) {
    const item = items.find(i => i.identifier === identifier || i.id === identifier);
    if (item) {
      // Display human coding scheme and abbreviated statement instead of title
      const parts = [];
      if (item.humanCodingScheme) {
        parts.push('<strong>' + render.escaped(item.humanCodingScheme) + '</strong>');
      }
      if (item.abbreviatedStatement) {
        parts.push(render.escaped(item.abbreviatedStatement));
      }
      // Fall back to fullStatement if neither is available
      if (parts.length === 0) {
        return render.inline(item.fullStatement) || item.identifier;
      }
      return parts.join(' ');
    }
  }

  // Fall back to title from nodeURI or identifier
  if (isReversed.value) {
    const origin = props.association.originNodeURI || props.association.origin;
    return render.escaped(origin?.title || origin?.identifier || 'Unknown');
  }
  const dest = props.association.destinationNodeURI || props.association.destination;
  return render.escaped(dest?.title || dest?.identifier || 'Unknown');
});

const nodeLabel = computed(() => {
  return isReversed.value ? 'Origin:' : 'Destination:';
});

const notes = computed(() => {
  return props.association.notes || '';
});

const lastChangeDateTime = computed(() => {
  return props.association.lastChangeDateTime || '';
});

const groupTitle = computed(() => {
  if (!props.association.CFAssociationGroupingURI) return '';

  const groupId = props.association.CFAssociationGroupingURI.identifier;
  const group = props.associationGroups.find(g => g.id === groupId);
  return group?.title || '';
});

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}
</script>

<style scoped>
.association-item {
  transition: background-color 0.2s ease;
}

.association-item:hover {
  background-color: #f8f9fa;
}

.association-info {
  min-width: 0; /* Allow text to wrap */
}

.association-details {
  font-size: 0.875rem;
}

.badge {
  font-size: 0.75em;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}
</style>
