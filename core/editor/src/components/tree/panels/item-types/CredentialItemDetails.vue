<template>
  <div class="credential-item-details">
    <dl class="details-list">
      <!-- Identifier link -->
      <ItemIdentifierRow :identifier="item.identifier" />

      <!-- Credential-specific fields -->
      <div v-if="item.fullStatement">
        <dt>Credential Name:</dt>
        <dd>
          <div
            v-if="renderedFullStatement"
            class="markdown-body"
            v-html="renderedFullStatement"
          />
          <div v-else>
            {{ item.fullStatement }}
          </div>
        </dd>
      </div>

      <div
        v-if="item.description"
        class="details-entry--full"
      >
        <dt>Description:</dt>
        <dd>{{ item.description }}</dd>
      </div>

      <div v-if="item.extensions && item.extensions['salt:credential']">
        <dt>Credential:</dt>
        <dd>{{ item.extensions['salt:credential'] }}</dd>
      </div>

      <div v-if="item.codedNotation">
        <dt>Coded Notation:</dt>
        <dd>{{ item.codedNotation }}</dd>
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
  renderedFullStatement: {
    type: String,
    default: '',
  },
  renderedNotes: {
    type: String,
    default: '',
  },
});

const webpage = computed(() => {
  return resolveItemWebpage(props.item.uri, null);
});
</script>

<style scoped>
.credential-item-details {
  padding: 0.5rem 0;
}

.markdown-body {
  padding: 0.75rem;
  background-color: #f8f9fa;
  border-radius: 0.375rem;
  border: 1px solid #dee2e6;
  font-size: 0.875rem;
  line-height: 1.5;
}

.markdown-body :deep(h1),
.markdown-body :deep(h2),
.markdown-body :deep(h3),
.markdown-body :deep(h4),
.markdown-body :deep(h5),
.markdown-body :deep(h6) {
  margin-top: 0;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #495057;
}

.markdown-body :deep(p) {
  margin-bottom: 0.75rem;
}

.markdown-body :deep(ul),
.markdown-body :deep(ol) {
  margin-bottom: 0.75rem;
  padding-left: 1.5rem;
}

.markdown-body :deep(li) {
  margin-bottom: 0.25rem;
}

.markdown-body :deep(blockquote) {
  border-left: 4px solid #dee2e6;
  padding-left: 1rem;
  margin: 1rem 0;
  color: #5a6268;
  font-style: italic;
}

.markdown-body :deep(code) {
  background-color: #e9ecef;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.8125rem;
  font-family: 'Courier New', monospace;
}

.markdown-body :deep(pre) {
  background-color: #e9ecef;
  padding: 0.75rem;
  border-radius: 0.375rem;
  overflow-x: auto;
  margin: 0.75rem 0;
}

.markdown-body :deep(a) {
  color: #0056b3;
  text-decoration: underline;
}

.markdown-body :deep(a:hover) {
  text-decoration: underline;
}

.markdown-body :deep(.katex) {
  font-size: 1em;
}

.markdown-body :deep(.katex-display) {
  margin: 1rem 0;
  text-align: center;
}
</style>
