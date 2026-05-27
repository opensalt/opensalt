<template>
  <!-- Cross-Framework Indicator -->
  <div
    v-if="isCrossFrameworkItem"
    class="alert alert-info mb-2"
    role="alert"
  >
    <i
      class="bi bi-box-arrow-up-right me-2"
      aria-hidden="true"
    />
    <strong>External Framework Item</strong>
    <!-- Loading state -->
    <span
      v-if="isLoadingCrossFramework"
      class="text-muted"
    >
      <span
        class="spinner-border spinner-border-sm ms-2"
        role="status"
        aria-hidden="true"
      />
      Loading...
    </span>
    <!-- Framework name from composable -->
    <span
      v-else-if="externalFrameworkTitle"
      class="text-muted"
    > - from {{ externalFrameworkTitle }}</span>
    <!-- Error state -->
    <span
      v-else-if="crossFrameworkFetchError"
      class="text-warning-on-light ms-2"
    >
      <i
        class="bi bi-exclamation-triangle"
        aria-hidden="true"
      />
      {{ crossFrameworkFetchError.type === 'permission' ? 'No access' : 'Load error' }}
    </span>
  </div>

  <!-- Read-Only Indicator for Viewed Framework Items -->
  <div
    v-else-if="isItemFromViewedFramework"
    class="alert alert-secondary mb-2"
    role="alert"
  >
    <i
      class="bi bi-eye me-2"
      aria-hidden="true"
    />
    <strong>Viewing Item</strong>
    <span class="text-muted"> from {{ viewedDoc?.title || 'external framework' }}</span>
    <span class="d-block mt-1 small text-muted">
      <i
        class="bi bi-lock me-1"
        aria-hidden="true"
      />
      This item is read-only. Edits cannot be made to viewed framework items.
    </span>
  </div>
</template>

<script setup>
defineProps({
  isCrossFrameworkItem: { type: Boolean, default: false },
  isLoadingCrossFramework: { type: Boolean, default: false },
  externalFrameworkTitle: { type: String, default: null },
  crossFrameworkFetchError: { type: Object, default: null },
  isItemFromViewedFramework: { type: Boolean, default: false },
  viewedDoc: { type: Object, default: null },
});
</script>

<style scoped>
/* WCAG 1.4.3: .text-warning (#ffc107) fails contrast on light backgrounds.
   Use #664d03 (~7.0:1 contrast on white) instead. */
.text-warning-on-light {
  color: #664d03;
}
</style>
