<template>
  <div class="col">
    <div
      :id="elementId"
      class="ls-association-item-display border p-3 rounded"
      :class="{ 'selected-item-highlight': isSelected }"
    >
      <div
        v-if="itemData"
        class="d-flex align-items-start flex-wrap gap-2"
      >
        <div class="flex-grow-1">
          <strong>{{ displayText }}</strong>
          <div
            v-if="itemData.humanCodingScheme"
            class="text-muted small"
          >
            {{ itemData.humanCodingScheme }}
          </div>
        </div>
        <!-- Loading spinner for cross-framework CASE items -->
        <span
          v-if="isLoading && targetTypeInfo.isCase"
          class="spinner-border spinner-border-sm text-secondary"
          role="status"
          aria-label="Loading"
        />
        <!-- Framework badge for cross-framework CASE items -->
        <span
          v-if="frameworkTitle && !isLoading && targetTypeInfo.isCase"
          class="badge framework-badge"
        >
          <i
            class="bi bi-box-arrow-up-right me-1"
            aria-hidden="true"
          />{{ frameworkTitle }}
        </span>
        <!-- Non-CASE item indicator -->
        <span
          v-if="!targetTypeInfo.isCase && itemData"
          class="badge external-uri-badge"
        >
          <i
            class="bi bi-link-45deg me-1"
            aria-hidden="true"
          />External URI
        </span>
        <!-- Error indicator for failed fetches -->
        <span
          v-if="fetchError && targetTypeInfo.isCase"
          class="badge error-badge"
          :title="fetchError.message"
        >
          <i
            class="bi bi-exclamation-triangle me-1"
            aria-hidden="true"
          />
          {{ errorLabel }}
        </span>
      </div>
      <div
        v-else
        class="text-muted"
      >
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

/* WCAG 2.1 AA compliant badge colors (4.5:1+ contrast ratio) */
.framework-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
  background-color: #0c63e4;
  color: #ffffff;
}

.framework-badge i {
  font-size: 0.85em;
}

.external-uri-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
  background-color: #495057;
  color: #ffffff;
}

.external-uri-badge i {
  font-size: 0.85em;
}

.error-badge {
  font-size: 0.7em;
  font-weight: 500;
  vertical-align: middle;
  background-color: #856404;
  color: #ffffff;
}

.error-badge i {
  font-size: 0.85em;
}

.gap-2 {
  gap: 0.5rem;
}
</style>
