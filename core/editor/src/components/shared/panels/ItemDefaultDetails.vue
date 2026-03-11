<template>
  <div>
    <div v-if="item.fullStatement" class="mb-3">
      <strong>Full Statement:</strong>
      <div class="mt-1 markdown-content" v-html="renderedFullStatement"></div>
    </div>

    <div class="mt-2">
      <strong>Identifier:</strong> <a :href="`/uri/${item.identifier}`" target="_blank" class="ms-1">{{ item.identifier }}</a>
    </div>

    <div class="row mt-2">
      <div v-if="item.itemType" class="col-sm-6">
        <strong>Item Type:</strong> {{ item.itemType || 'General' }}
      </div>
      <div v-if="item.language" class="col-sm-6">
        <strong>Language:</strong> {{ item.language || 'en' }}
      </div>
    </div>

    <div v-if="item.educationLevel && item.educationLevel.length > 0" class="mt-2">
      <strong>Education Level:</strong>
      <span class="ms-1">
        <span v-for="level in item.educationLevel" :key="level" class="badge bg-info text-dark me-1">
          {{ level }}
        </span>
      </span>
    </div>

    <div v-if="item.conceptKeywords && item.conceptKeywords.length > 0" class="mt-2">
      <strong>Keywords:</strong>
      <span class="ms-1">
        <span v-for="keyword in item.conceptKeywords" :key="keyword" class="badge bg-secondary me-1">
          {{ keyword }}
        </span>
      </span>
    </div>

    <div v-if="item.licenseURI" class="mt-2 text-truncate">
      <strong>License:</strong> <span class="ms-1">{{ licenseName }}</span>
    </div>

    <div v-if="item.notes" class="mt-3">
      <strong>Notes:</strong>
      <p class="mt-1 markdown-content" v-html="renderedNotes"></p>
    </div>

    <div v-if="item.lastChanged" class="mt-2">
      <small class="text-muted">
        Last changed: {{ formatDate(item.lastChanged) }}
      </small>
    </div>
  </div>
</template>

<script setup>
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
/* Markdown content styling */
.markdown-content {
  padding: 0.75rem;
  padding-bottom: 0;
  background-color: #f8f9fa;
  border-radius: 0.375rem;
  border: 1px solid #dee2e6;
  font-size: 0.875rem;
  line-height: 1.5;
}

.markdown-content h1,
.markdown-content h2,
.markdown-content h3,
.markdown-content h4,
.markdown-content h5,
.markdown-content h6 {
  margin-top: 0;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #495057;
}

.markdown-content p {
  margin-bottom: 0.75rem;
}

.markdown-content ul,
.markdown-content ol {
  margin-bottom: 0.75rem;
  padding-left: 1.5rem;
}

.markdown-content li {
  margin-bottom: 0.25rem;
}

.markdown-content blockquote {
  border-left: 4px solid #dee2e6;
  padding-left: 1rem;
  margin: 1rem 0;
  color: #6c757d;
  font-style: italic;
}

.markdown-content code {
  background-color: #e9ecef;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.8125rem;
  font-family: 'Courier New', monospace;
}

.markdown-content pre {
  background-color: #e9ecef;
  padding: 0.75rem;
  border-radius: 0.375rem;
  overflow-x: auto;
  margin: 0.75rem 0;
}

.markdown-content table {
  width: 100%;
  margin-bottom: 0.75rem;
  border-collapse: collapse;
}

.markdown-content th,
.markdown-content td {
  padding: 0.375rem 0.75rem;
  border: 1px solid #dee2e6;
  text-align: left;
}

.markdown-content th {
  background-color: #f8f9fa;
  font-weight: 600;
}

.markdown-content a {
  color: #0d6efd;
  text-decoration: none;
}

.markdown-content a:hover {
  text-decoration: underline;
}

/* KaTeX styling */
.markdown-content .katex {
  font-size: 1em;
}

.markdown-content .katex-display {
  margin: 1rem 0;
  text-align: center;
}
</style>
