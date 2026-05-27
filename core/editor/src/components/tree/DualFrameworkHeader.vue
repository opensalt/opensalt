<template>
  <header
    v-if="false /* TODO */"
    class="dual-framework-header p-2 border-bottom bg-light"
  >
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="edited-framework">
        <span class="badge bg-primary">
          <i
            class="bi bi-pencil-square me-1"
            aria-hidden="true"
          />Editing
        </span>
        <span class="ms-2 fw-bold">{{ currentDoc.title || 'Untitled' }}</span>
      </div>
      <div
        v-if="isViewingDifferentFramework"
        class="viewed-framework"
      >
        <span class="badge bg-secondary">
          <i
            class="bi bi-eye me-1"
            aria-hidden="true"
          />Viewing
        </span>
        <span class="text-muted"> from {{ viewedDoc?.title || 'external framework' }}</span>
      </div>
    </div>
  </header>
</template>

<script setup>
defineProps({
  currentDoc: { type: Object, required: true },
  isViewingDifferentFramework: { type: Boolean, default: false },
  viewedDoc: { type: Object, default: null },
});
</script>

<style scoped>
.dual-framework-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  flex-shrink: 0;
}

.edited-framework,
.viewed-framework {
  display: flex;
  align-items: center;
}
</style>
