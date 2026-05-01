<template>
  <!-- Item Header Card -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0 d-flex align-items-center">
        <img
          :src="itemIconSrc"
          class="me-2 item-icon"
          aria-hidden="true"
        >
        Item Details
        <!-- Read-only badge for viewed framework items -->
        <span
          v-if="isItemFromViewedFramework"
          class="badge bg-secondary ms-2"
          aria-label="Read-only item"
        >
          <i
            class="bi bi-lock"
            aria-hidden="true"
          /> Read-only
        </span>
      </h6>
      <div class="d-flex align-items-center gap-2">
        <slot name="header-actions" />
        <div
          v-if="canEditItem"
          class="btn-group btn-group-sm"
        >
          <button
            type="button"
            class="btn btn-outline-primary"
            title="Edit item"
            @click="$emit('edit', item)"
          >
            <i class="bi bi-pencil" />
          </button>
          <button
            type="button"
            class="btn btn-outline-danger"
            title="Delete item"
            @click="$emit('delete', item)"
          >
            <i class="bi bi-trash" />
          </button>
        </div>
      </div>
    </div>

    <div class="card-body">
      <!-- Item Title -->
      <h5 class="card-title">
        <span
          v-if="displayItem.humanCodingScheme"
          class="badge bg-secondary me-1"
        >
          {{ displayItem.humanCodingScheme }}
        </span>
        {{ displayItem.abbreviatedStatement || '' }}
      </h5>

      <!-- Specialized Item Details or Default Details slot -->
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  item: { type: Object, required: true },
  displayItem: { type: Object, required: true },
  itemIconSrc: { type: String, default: '' },
  canEditItem: { type: Boolean, default: false },
  isItemFromViewedFramework: { type: Boolean, default: false },
});
defineEmits(['edit', 'delete']);
</script>

<style scoped>
.item-icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}
</style>
