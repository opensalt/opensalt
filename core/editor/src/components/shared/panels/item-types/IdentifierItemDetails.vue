<template>
  <div class="identifier-item-details">
    <!-- Identifier link -->
    <div class="mb-3">
      <strong>Identifier:</strong>
      <a :href="`/uri/${item.identifier}`" target="_blank" class="ms-1">{{ item.identifier }}</a>
    </div>

    <!-- Identifier-specific fields -->
    <div v-if="item.fullStatement" class="mb-3">
      <strong>Identifier Name:</strong>
      <p class="mt-1">{{ item.fullStatement }}</p>
    </div>

    <div v-if="item.description" class="mb-3">
      <strong>Description:</strong>
      <p class="mt-1">{{ item.description }}</p>
    </div>

    <div v-if="item.extensions && item.extensions['salt:identifier']" class="mb-3">
      <strong>Identifier:</strong>
      <p class="mt-1">{{ item.extensions['salt:identifier'] }}</p>
    </div>

    <div v-if="item.codedNotation" class="mb-3">
      <strong>Coded Notation:</strong>
      <p class="mt-1">{{ item.codedNotation }}</p>
    </div>

    <div v-if="item.notes" class="mb-3">
      <strong>Notes:</strong>
      <p class="mt-1">{{ item.notes }}</p>
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
.identifier-item-details {
  padding: 0.5rem 0;
}

.identifier-item-details strong {
  color: #495057;
  font-weight: 600;
}

.identifier-item-details p {
  margin-bottom: 0.5rem;
  color: #212529;
}
</style>
