<template>
  <div class="default-item-details">
    <dl class="details-list">
      <div
        v-if="item.fullStatement"
        class="details-entry--full"
      >
        <dt>Full Statement:</dt>
        <dd>
          <div
            class="markdown-body"
            v-html="renderedFullStatement"
          />
        </dd>
      </div>

      <ItemIdentifierRow :identifier="item.identifier" />

      <div v-if="item.itemType">
        <dt>Item Type:</dt>
        <dd>{{ item.itemType || 'General' }}</dd>
      </div>
      <div v-if="item.language">
        <dt>Language:</dt>
        <dd>{{ item.language || 'en' }}</dd>
      </div>

      <div v-if="item.listEnumeration">
        <dt>List Enumeration:</dt>
        <dd>{{ item.listEnumeration }}</dd>
      </div>

      <div v-if="item.educationLevel && item.educationLevel.length > 0">
        <dt>Education Level:</dt>
        <dd>
          <span
            v-for="level in item.educationLevel"
            :key="level"
            class="badge bg-info text-dark me-1"
          >
            {{ level }}
          </span>
        </dd>
      </div>

      <div v-if="item.conceptKeywords && item.conceptKeywords.length > 0">
        <dt>Keywords:</dt>
        <dd>
          <span
            v-for="keyword in item.conceptKeywords"
            :key="keyword"
            class="badge bg-secondary me-1"
          >
            {{ keyword }}
          </span>
        </dd>
      </div>

      <div v-if="item.subjectURI && item.subjectURI.length > 0">
        <dt>Subject:</dt>
        <dd>
          <span
            v-for="subject in item.subjectURI"
            :key="subject.identifier"
            class="badge bg-secondary me-1"
          >
            {{ subject.title }}
          </span>
        </dd>
      </div>

      <div
        v-if="item.licenseURI"
        class="text-truncate"
      >
        <dt>License:</dt>
        <dd>{{ licenseName }}</dd>
      </div>

      <ItemNotesField
        v-if="item.notes"
        :raw-notes="item.notes"
        :rendered-notes="renderedNotes"
      />
    </dl>

    <div v-if="item.lastChanged">
      <small class="text-muted">
        Last changed: {{ formatDate(item.lastChanged) }}
      </small>
    </div>

    <!-- Additional Fields (read-only) -->
    <AdditionalFieldsDisplay
      :additional-fields="item.additionalFields || {}"
      scope="item"
    />
  </div>
</template>

<script setup>
import ItemIdentifierRow from './ItemIdentifierRow.vue';
import ItemNotesField from './ItemNotesField.vue';
import AdditionalFieldsDisplay from './AdditionalFieldsDisplay.vue';

defineProps({
  item: { type: Object, required: true },
  renderedFullStatement: { type: String, default: '' },
  renderedNotes: { type: String, default: '' },
  licenseName: { type: String, default: null },
});

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}
</script>

<style scoped>
.default-item-details {
  padding: 0.5rem 0;
}

/* Keep markdown-body for Full Statement display (not moved to ItemNotesField
   since this component still renders fullStatement as markdown inline) */
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
