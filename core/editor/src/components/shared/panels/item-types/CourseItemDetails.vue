<template>
  <div class="course-item-details">
    <!-- Identifier link -->
    <div class="mb-3">
      <strong>Identifier:</strong>
      <a :href="`/uri/${item.identifier}`" target="_blank" class="ms-1">{{ item.identifier }}</a>
    </div>

    <!-- Course-specific fields -->
    <div v-if="item.fullStatement" class="mb-3">
      <strong>Course Name:</strong>
      <p class="mt-1">{{ item.fullStatement }}</p>
    </div>

    <div v-if="item.description" class="mb-3">
      <strong>Description:</strong>
      <p class="mt-1">{{ item.description }}</p>
    </div>

    <div v-if="item.codedNotation" class="mb-3">
      <strong>Coded Notation:</strong>
      <p class="mt-1">{{ item.codedNotation }}</p>
    </div>

    <div v-if="item.extensions && item.extensions['salt:inLanguage']" class="mb-3">
      <strong>Language:</strong>
      <p class="mt-1">{{ item.extensions['salt:inLanguage'] }}</p>
    </div>

    <div v-if="item.extensions && item.extensions['salt:deliveryType']" class="mb-3">
      <strong>Delivery Type:</strong>
      <p class="mt-1 capitalize">{{ item.extensions['salt:deliveryType'] }}</p>
    </div>

    <div v-if="item.uri" class="mb-3 text-truncate">
      <strong>Webpage:</strong>
      <a :href="item.uri" target="_blank" class="ms-1">{{ item.uri }}</a>
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
.course-item-details {
  padding: 0.5rem 0;
}

.course-item-details strong {
  color: #495057;
  font-weight: 600;
}

.course-item-details p {
  margin-bottom: 0.5rem;
  color: #212529;
}

.capitalize {
  text-transform: capitalize;
}
</style>
