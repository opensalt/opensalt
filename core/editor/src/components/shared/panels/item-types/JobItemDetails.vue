<template>
  <div class="job-item-details">
    <!-- Identifier link -->
    <div class="mb-3">
      <strong>Identifier:</strong>
      <a :href="`/uri/${item.identifier}`" target="_blank" class="ms-1">{{ item.identifier }}</a>
    </div>

    <!-- Job-specific fields -->
    <div v-if="item.fullStatement" class="mb-3">
      <strong>Job Title:</strong>
      <p class="mt-1">{{ item.fullStatement }}</p>
    </div>

    <div v-if="item.humanCodingLanguage" class="mb-3">
      <strong>Human Coding Language:</strong>
      <p class="mt-1">{{ item.humanCodingLanguage }}</p>
    </div>

    <div v-if="item.codedNotation" class="mb-3">
      <strong>Coded Notation:</strong>
      <p class="mt-1">{{ item.codedNotation }}</p>
    </div>

    <div v-if="item.keywords" class="mb-3">
      <strong>Keywords:</strong>
      <div class="mt-1">
        <span v-for="keyword in parsedKeywords" :key="keyword" class="badge bg-secondary me-1">
          {{ keyword }}
        </span>
      </div>
    </div>

    <div v-if="item.uri" class="mb-3 text-truncate">
      <strong>Webpage:</strong>
      <a :href="item.uri" target="_blank" class="ms-1">{{ item.uri }}</a>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  item: {
    type: Object,
    required: true
  }
});

// Parse keywords from comma-separated string or array
const parsedKeywords = computed(() => {
  if (!props.item.keywords) return [];

  if (Array.isArray(props.item.keywords)) {
    return props.item.keywords;
  }

  // If it's a string, split by comma
  return props.item.keywords.split(',').map(k => k.trim()).filter(k => k.length > 0);
});
</script>

<style scoped>
.job-item-details {
  padding: 0.5rem 0;
}

.job-item-details strong {
  color: #495057;
  font-weight: 600;
}

.job-item-details p {
  margin-bottom: 0.5rem;
  color: #212529;
}
</style>
