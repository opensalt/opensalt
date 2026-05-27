<template>
  <div class="job-item-details">
    <dl class="details-list">
      <!-- Identifier link -->
      <div class="details-identifier item-identifier">
        <dt>Identifier</dt>
        <dd>
          <a
            :href="`/uri/${item.identifier}`"
            target="_blank"
            class="ms-1"
          >{{ item.identifier }}<span class="visually-hidden"> (opens in new window)</span></a>
        </dd>
      </div>

      <!-- Job-specific fields -->
      <div v-if="item.fullStatement">
        <dt>Job Title</dt>
        <dd>{{ item.fullStatement }}</dd>
      </div>

      <div v-if="item.humanCodingLanguage">
        <dt>Human Coding Language</dt>
        <dd>{{ item.humanCodingLanguage }}</dd>
      </div>

      <div v-if="item.codedNotation">
        <dt>Coded Notation</dt>
        <dd>{{ item.codedNotation }}</dd>
      </div>

      <div v-if="item.keywords">
        <dt>Keywords</dt>
        <dd>
          <span
            v-for="keyword in parsedKeywords"
            :key="keyword"
            class="badge bg-secondary me-1"
          >
            {{ keyword }}
          </span>
        </dd>
      </div>

      <div
        v-if="item.uri"
        class="text-truncate"
      >
        <dt>Webpage</dt>
        <dd>
          <a
            :href="item.uri"
            target="_blank"
            class="ms-1"
          >{{ item.uri }}<span class="visually-hidden"> (opens in new window)</span></a>
        </dd>
      </div>

      <div v-if="item.notes">
        <dt>Notes</dt>
        <dd>{{ item.notes }}</dd>
      </div>
    </dl>
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
</style>
