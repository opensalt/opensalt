<template>
  <div class="estimation-results alert alert-info">
    <h6><i class="bi bi-info-circle me-1" /> Estimation</h6>
    <p v-if="result.origin_items_with_embeddings !== undefined">
      <strong>{{ result.origin_items_with_embeddings }}</strong> of {{ result.origin_items_with_embeddings + (result.origin_items_without_embeddings || 0) }} origin items have embeddings.
      <span v-if="result.origin_items_without_embeddings > 0" class="text-warning">({{ result.origin_items_without_embeddings }} without)</span>
    </p>
    <p v-if="result.destination_items_with_embeddings !== undefined">
      <strong>{{ result.destination_items_with_embeddings }}</strong> destination items have embeddings.
    </p>
    <p class="mb-0 text-muted">
      {{ result.note }}
    </p>
  </div>
</template>

<script setup>
defineProps({
  result: {
    type: Object,
    required: true,
  },
});
</script>
