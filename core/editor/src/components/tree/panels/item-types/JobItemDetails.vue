<template>
  <div class="job-item-details">
    <dl class="details-list">
      <!-- Identifier link -->
      <ItemIdentifierRow :identifier="item.identifier" />

      <!-- Job-specific fields -->
      <div v-if="item.fullStatement">
        <dt>Job Title:</dt>
        <dd>{{ item.fullStatement }}</dd>
      </div>

      <div v-if="item.humanCodingLanguage">
        <dt>Human Coding Language:</dt>
        <dd>{{ item.humanCodingLanguage }}</dd>
      </div>

      <div v-if="item.codedNotation">
        <dt>Coded Notation:</dt>
        <dd>{{ item.codedNotation }}</dd>
      </div>

      <div v-if="item.keywords">
        <dt>Keywords:</dt>
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
        v-if="webpage.href"
        class="text-truncate"
      >
        <dt>Webpage:</dt>
        <dd>
          <a
            :href="webpage.href"
            target="_blank"
            class="ms-1"
          >{{ webpage.display }}<span class="visually-hidden"> (opens in new window)</span></a>
        </dd>
      </div>

      <ItemNotesField
        v-if="item.notes"
        :raw-notes="item.notes"
        :rendered-notes="renderedNotes"
      />
    </dl>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ItemIdentifierRow from '../ItemIdentifierRow.vue';
import ItemNotesField from '../ItemNotesField.vue';
import { resolveItemWebpage } from '../../../../utils/resolveItemWebpage.js';

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  renderedNotes: {
    type: String,
    default: '',
  },
});

const parsedKeywords = computed(() => {
  if (!props.item.keywords) return [];
  if (Array.isArray(props.item.keywords)) return props.item.keywords;
  return props.item.keywords.split(',').map(k => k.trim()).filter(k => k.length > 0);
});

const webpage = computed(() => {
  return resolveItemWebpage(props.item.uri, null);
});
</script>

<style scoped>
.job-item-details {
  padding: 0.5rem 0;
}
</style>
