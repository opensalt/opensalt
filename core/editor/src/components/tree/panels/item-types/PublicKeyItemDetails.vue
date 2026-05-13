<template>
  <div class="public-key-item-details">
    <!-- Identifier link -->
    <div class="mb-3 details-identifier item-identifier">
      <strong>Identifier:</strong>
      <a
        :href="`/uri/${item.identifier}`"
        target="_blank"
        class="ms-1"
      >{{ item.identifier }}</a>
    </div>

    <!-- Public Key-specific fields -->
    <div
      v-if="item.fullStatement"
      class="mb-3"
    >
      <strong>Public Key:</strong>
      <div class="mt-1">
        <code class="d-block p-2 bg-light border rounded">{{ item.fullStatement }}</code>
      </div>
    </div>

    <div
      v-if="item.notes"
      class="mb-3"
    >
      <strong>Notes:</strong>
      <p class="mt-1">
        {{ item.notes }}
      </p>
    </div>
  </div>
</template>

<script setup>
const _props = defineProps({
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
