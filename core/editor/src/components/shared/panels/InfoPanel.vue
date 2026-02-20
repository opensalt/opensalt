<template>
  <section class="col-7 p-4" style="background: #e3f6fd;" aria-label="Item details">
    <div v-if="selectedItem" class="card shadow-sm" style="background: #e3f6fd;">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <i class="bi bi-record-circle fs-2 me-3" style="color: #1976d2;" aria-hidden="true"></i>
          <div>
            <h2 class="fs-4 fw-bold mb-1">{{ selectedItem.title }}</h2>
            <p class="text-muted mb-0">{{ selectedItem.humanCodingScheme }}</p>
          </div>
        </div>
        <dl class="mb-2">
          <dt class="fw-bold">Identifier:</dt>
          <dd>
            <a :href="identifierUrl(selectedItem.identifier)" target="_blank" rel="noopener noreferrer">
              {{ selectedItem.identifier }}
              <span class="visually-hidden">(opens in new window)</span>
            </a>
          </dd>
        </dl>
        <dl class="mb-2">
          <dt class="fw-bold">Last Changed:</dt>
          <dd>{{ selectedItem.lastChanged || '—' }}</dd>
        </dl>
        <!-- Associations and other info can go here -->
      </div>
    </div>
    <div v-else class="text-muted text-center mt-5" role="status" aria-live="polite">
      <em>Select a node from the tree to see details.</em>
    </div>
  </section>
</template>

<script setup>
const props = defineProps({
  selectedItem: Object
});

function identifierUrl(id) {
  return `/uri/${id}`;
}
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
