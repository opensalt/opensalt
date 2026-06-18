<template>
  <!-- Item Header Card -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0 d-flex align-items-center">
        <img
          :src="itemIconSrc"
          class="me-2 item-icon"
          aria-hidden="true"
          alt=""
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
      </h5>
      <div class="d-flex align-items-center gap-2">
        <slot name="header-actions" />
        <div
          v-if="canEditItem && !isAdopted"
          class="btn-group btn-group-sm"
        >
          <button
            type="button"
            class="btn btn-outline-primary"
            title="Edit item"
            aria-label="Edit item"
            @click="$emit('edit', item)"
          >
            <i
              class="bi bi-pencil"
              aria-hidden="true"
            />
          </button>
          <button
            id="deleteItemBtn"
            type="button"
            class="btn btn-outline-danger"
            title="Delete item"
            aria-label="Delete item"
            @click="$emit('delete', item)"
          >
            <i
              class="bi bi-trash"
              aria-hidden="true"
            />
          </button>
        </div>
        <slot name="header-actions-end" />
      </div>
    </div>

    <div class="card-body">
      <!-- Item Title -->
      <h4
        v-if="displayItem.humanCodingScheme || displayItem.abbreviatedStatement"
        id="item-detail-heading"
        class="card-title ms-0 itemTitle"
      >
        <span
          v-if="displayItem.humanCodingScheme"
          class="badge bg-secondary me-1"
        >{{ displayItem.humanCodingScheme }}</span>
        <span class="itemTitleSpan">{{ displayItem.abbreviatedStatement || '' }}</span>
      </h4>

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
  isAdopted: { type: Boolean, default: false },
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
