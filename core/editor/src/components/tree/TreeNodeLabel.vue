<template>
  <ItemStatementPopover
    :statement="statement"
    :disabled="popoverDisabled || !statement"
  >
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
        <i
          class="bi bi-box-arrow-up-right me-1"
          aria-hidden="true"
        />{{ externalFrameworkTitle }}
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
  </ItemStatementPopover>
</template>

<script setup>
import ItemStatementPopover from '@/components/common/ItemStatementPopover.vue';

defineProps({
  isCrossFrameworkItem: { type: Boolean, default: false },
  isLoadingCrossFramework: { type: Boolean, default: false },
  externalFrameworkTitle: { type: String, default: null },
  displayHumanCodingScheme: { type: String, default: null },
  searchQuery: { type: String, default: '' },
  hasMatch: { type: Boolean, default: false },
  highlightedTitle: { type: String, default: '' },
  displayTitle: { type: String, default: '' },
  statement: { type: String, default: '' },
  popoverDisabled: { type: Boolean, default: false },
});
</script>

<style scoped>
.label-text {
  display: block;
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
</style>
