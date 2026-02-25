<template>
  <div class="col-sm-5">
    <div
      class="ls-association-item-display border p-3 rounded"
      :id="elementId"
      :class="{ 'selected-item-highlight': isSelected }"
    >
      <div v-if="itemData" class="d-flex align-items-start flex-wrap gap-2">
        <div class="flex-grow-1">
          <strong>{{ displayText }}</strong>
          <div v-if="itemData.humanCodingScheme" class="text-muted small">
            {{ itemData.humanCodingScheme }}
          </div>
        </div>
        <!-- Loading spinner for cross-framework CASE items -->
        <span
          v-if="isLoading && targetTypeInfo.isCase"
          class="spinner-border spinner-border-sm text-secondary"
          role="status"
          aria-label="Loading"
        ></span>
        <!-- Framework badge for cross-framework CASE items -->
        <span
          v-if="frameworkTitle && !isLoading && targetTypeInfo.isCase"
          class="badge bg-info text-dark framework-badge"
        >
          <i class="bi bi-box-arrow-up-right me-1"></i>{{ frameworkTitle }}
        </span>
        <!-- Non-CASE item indicator -->
        <span
          v-if="!targetTypeInfo.isCase && itemData"
          class="badge bg-secondary external-uri-badge"
        >
          <i class="bi bi-link-45deg me-1"></i>External URI
        </span>
        <!-- Error indicator for failed fetches -->
        <span
          v-if="fetchError && targetTypeInfo.isCase"
          class="badge bg-warning text-dark error-badge"
          :title="fetchError.message"
        >
          <i class="bi bi-exclamation-triangle me-1"></i>
          {{ errorLabel }}
        </span>
      </div>
      <div v-else class="text-muted">
        {{ fallbackText }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

/**
 * AssociationItemDisplay Component
 *
 * Display component for showing an association endpoint (origin or destination).
 * Handles cross-framework items, loading states, and error states.
 */

const props = defineProps({
  /**
   * The item data to display
   */
  itemData: {
    type: Object,
    default: null
  },
  /**
   * Whether the item is currently loading (for cross-framework items)
   */
  isLoading: {
    type: Boolean,
    default: false
  },
  /**
   * The framework title for cross-framework items
   */
  frameworkTitle: {
    type: String,
    default: null
  },
  /**
   * Target type info (isCase, isUnknown)
   */
  targetTypeInfo: {
    type: Object,
    default: () => ({ isCase: true, isUnknown: false })
  },
  /**
   * Error from fetching cross-framework item
   */
  fetchError: {
    type: Object,
    default: null
  },
  /**
   * Whether this item is selected
   */
  isSelected: {
    type: Boolean,
    default: false
  },
  /**
   * Fallback text when no item data is available
   */
  fallbackText: {
    type: String,
    default: 'Item'
  },
  /**
   * Display text for the item
   */
  displayText: {
    type: String,
    default: ''
  },
  /**
   * Which side this item is on ('origin' or 'destination')
   */
  side: {
    type: String,
    default: 'origin',
    validator: (value) => ['origin', 'destination'].includes(value)
  }
});

// Compute the element ID based on side
const elementId = computed(() => {
  return props.side === 'origin'
    ? 'editLsAssociationOriginDisplay'
    : 'editLsAssociationDestinationDisplay';
});

// Compute error label based on error type
const errorLabel = computed(() => {
  if (!props.fetchError) return '';
  switch (props.fetchError.type) {
    case 'permission':
      return 'No access';
    case 'not_found':
      return 'Not found';
    default:
      return 'Load error';
  }
});
</script>

<style scoped>
.ls-association-item-display {
  min-height: 80px;
  background-color: #f8f9fa;
}

/* Selected item highlight styling */
.selected-item-highlight {
  border: 2px solid #0d6efd !important;
  background-color: #e7f1ff !important;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.framework-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
}

.framework-badge i {
  font-size: 0.85em;
}

.external-uri-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
}

.external-uri-badge i {
  font-size: 0.85em;
}

.error-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
}

.error-badge i {
  font-size: 0.85em;
}

.gap-2 {
  gap: 0.5rem;
}
</style>
