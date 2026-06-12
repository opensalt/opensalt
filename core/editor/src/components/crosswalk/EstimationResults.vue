<template>
  <div class="estimation-results alert alert-info">
    <h6><i class="bi bi-info-circle me-1" /> Estimation</h6>
    <p v-if="result.origin_items_with_embeddings">
      <strong>{{ result.origin_items_with_embeddings }}</strong> origin items have embeddings.
      <span v-if="result.destination_items_with_embeddings">
        <strong>{{ result.destination_items_with_embeddings }}</strong> destination items have embeddings.
      </span>
    </p>
    <p class="mb-0 text-muted">{{ result.note }}</p>
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
