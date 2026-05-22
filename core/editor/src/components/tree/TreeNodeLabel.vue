<template>
  <span class="label-text fancytree-title">
    <!-- Loading spinner for cross-framework items -->
    <span
      v-if="isCrossFrameworkItem && isLoadingCrossFramework"
      class="loading-spinner"
      aria-hidden="true"
    >
      <i class="bi bi-arrow-repeat spin" />
    </span>
    <!-- External link icon for cross-framework items -->
    <span
      v-else-if="isCrossFrameworkItem"
      class="cross-framework-badge"
      aria-hidden="true"
      title="External framework item"
    >
      <i class="bi bi-box-arrow-up-right" />
    </span>
    <span
      v-if="isCrossFrameworkItem && externalFrameworkTitle"
      class="badge bg-primary text-white me-2 ms-1"
    >
      <i class="bi bi-box-arrow-up-right me-1" />{{ externalFrameworkTitle }}
    </span>
    <span
      v-if="displayHumanCodingScheme"
      class="coding-scheme item-humanCodingScheme"
    >
      {{ displayHumanCodingScheme }}
    </span>
    <span>&nbsp;</span>
    <span
      v-if="searchQuery && hasMatch"
      class="display-title"
      v-html="highlightedTitle"
    />
    <span
      v-else
      class="display-title"
    >
      {{ displayTitle }}
    </span>
  </span>
  <div
    v-if="showPopover && fullStatementHtml"
    class="popover"
    role="tooltip"
    v-html="fullStatementHtml"
  />
</template>

<script setup>
defineProps({
  isCrossFrameworkItem: { type: Boolean, default: false },
  isLoadingCrossFramework: { type: Boolean, default: false },
  externalFrameworkTitle: { type: String, default: null },
  displayHumanCodingScheme: { type: String, default: null },
  searchQuery: { type: String, default: '' },
  hasMatch: { type: Boolean, default: false },
  highlightedTitle: { type: String, default: '' },
  displayTitle: { type: String, default: '' },
  showPopover: { type: Boolean, default: false },
  fullStatementHtml: { type: String, default: '' },
});
</script>

<style scoped>
.label-text {
  flex: 1 1 0;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.search-highlight,
:deep(.search-highlight) {
  background-color: #fff3cd;
  padding: 0 2px;
  border-radius: 2px;
  font-weight: 600;
}

.popover {
  position: absolute;
  z-index: 9999;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 8px;
  max-width: 400px;
  max-height: 300px;
  overflow-y: auto;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  white-space: pre-wrap;
  word-wrap: break-word;
  top: 100%;
  left: 0;
  margin-top: 5px;
  pointer-events: none;
}

.popover::before {
  content: '';
  position: absolute;
  top: -6px;
  left: 12px;
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-bottom: 6px solid #ddd;
  pointer-events: none;
}

.popover::after {
  content: '';
  position: absolute;
  top: -5px;
  left: 13px;
  border-left: 5px solid transparent;
  border-right: 5px solid transparent;
  border-bottom: 5px solid white;
  pointer-events: none;
}
</style>
