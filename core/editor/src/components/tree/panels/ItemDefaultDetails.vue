<template>
  <div>
    <dl class="details-list">
      <div
        v-if="item.fullStatement"
        class="mb-3 details-entry--full"
      >
        <dt>Full Statement</dt>
        <dd>
          <div
            class="mt-1 markdown-body"
            v-html="renderedFullStatement"
          />
        </dd>
      </div>

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

      <div
        v-if="item.itemType"
        class="col-sm-6"
      >
        <dt>Item Type</dt>
        <dd>{{ item.itemType || 'General' }}</dd>
      </div>
      <div
        v-if="item.language"
        class="col-sm-6"
      >
        <dt>Language</dt>
        <dd>{{ item.language || 'en' }}</dd>
      </div>

      <div
        v-if="item.listEnumeration"
        class="mt-2"
      >
        <dt>List Enumeration</dt>
        <dd>{{ item.listEnumeration }}</dd>
      </div>

      <div
        v-if="item.educationLevel && item.educationLevel.length > 0"
        class="mt-2"
      >
        <dt>Education Level</dt>
        <dd>
          <span class="ms-1">
            <span
              v-for="level in item.educationLevel"
              :key="level"
              class="badge bg-info text-dark me-1"
            >
              {{ level }}
            </span>
          </span>
        </dd>
      </div>

      <div
        v-if="item.conceptKeywords && item.conceptKeywords.length > 0"
        class="mt-2"
      >
        <dt>Keywords</dt>
        <dd>
          <span class="ms-1">
            <span
              v-for="keyword in item.conceptKeywords"
              :key="keyword"
              class="badge bg-secondary me-1"
            >
              {{ keyword }}
            </span>
          </span>
        </dd>
      </div>

      <div
        v-if="item.subjectURI && item.subjectURI.length > 0"
        class="mt-2"
      >
        <dt>Subject</dt>
        <dd>
          <span class="ms-1">
            <span
              v-for="subject in item.subjectURI"
              :key="subject.identifier"
              class="badge bg-secondary me-1"
            >
              {{ subject.title }}
            </span>
          </span>
        </dd>
      </div>

      <div
        v-if="item.licenseURI"
        class="mt-2 text-truncate"
      >
        <dt>License</dt>
        <dd><span class="ms-1">{{ licenseName }}</span></dd>
      </div>

      <div
        v-if="item.notes"
        class="mt-3"
      >
        <dt>Notes</dt>
        <dd>
          <div
            class="mt-1 markdown-body"
            v-html="renderedNotes"
          />
        </dd>
      </div>
    </dl>

    <div
      v-if="item.lastChanged"
      class="mt-2"
    >
      <small class="text-muted">
        Last changed: {{ formatDate(item.lastChanged) }}
      </small>
    </div>

    <!-- Additional Fields (read-only) -->
    <div
      v-if="hasAdditionalFieldValues"
      class="mt-3 additional-fields-section"
    >
      <h6 class="mb-2">
        Additional Fields
      </h6>
      <dl class="details-list">
        <div
          v-for="field in fieldDefinitions"
          :key="field.id || field.name"
          class="row mb-1"
        >
          <template v-if="getDisplayValue(field.name)">
            <dt class="col-sm-4 text-muted">
              {{ field.displayName || field.name }}
            </dt>
            <dd class="col-sm-8">
              {{ getDisplayValue(field.name) }}
            </dd>
          </template>
        </div>
      </dl>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useAdditionalFields } from '../../../composables/useAdditionalFields.js';

const props = defineProps({
  item: { type: Object, required: true },
  renderedFullStatement: { type: String, default: '' },
  renderedNotes: { type: String, default: '' },
  licenseName: { type: String, default: null },
});

const { fieldDefinitions, fetchFields } = useAdditionalFields();

onMounted(() => {
  fetchFields('item');
});

const hasAdditionalFieldValues = computed(() => {
  if (!fieldDefinitions.value?.length) return false;
  const af = props.item?.additionalFields;
  return fieldDefinitions.value.some(f => af?.[f.name]);
});

function getDisplayValue(fieldName) {
  const af = props.item?.additionalFields;
  if (!af || typeof af !== 'object') return undefined;
  return af[fieldName] || undefined;
}

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}
</script>

<style scoped>
/* Markdown content styling */
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

/* KaTeX styling */
.markdown-body :deep(.katex) {
  font-size: 1em;
}

.markdown-body :deep(.katex-display) {
  margin: 1rem 0;
  text-align: center;
}
</style>
