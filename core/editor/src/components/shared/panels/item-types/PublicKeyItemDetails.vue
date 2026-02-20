<template>
  <div class="public-key-item-details">
    <!-- Public Key-specific fields -->
    <div v-if="item.fullStatement" class="mb-3">
      <strong>Public Key:</strong>
      <div class="mt-1">
        <code class="d-block p-2 bg-light border rounded">{{ item.fullStatement }}</code>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  item: {
    type: Object,
    required: true
  }
});
</script>

<style scoped>
.public-key-item-details {
  padding: 0.5rem 0;
}

.public-key-item-details strong {
  color: #495057;
  font-weight: 600;
}

.public-key-item-details code {
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.875rem;
  word-break: break-all;
  max-height: 200px;
  overflow-y: auto;
}
</style>
