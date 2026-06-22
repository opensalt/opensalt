<template>
  <div class="details-entry--full">
    <dt>Notes:</dt>
    <dd>
      <div
        v-if="renderedNotes"
        class="markdown-body"
        v-html="renderedNotes"
      />
      <div v-else>{{ rawNotes }}</div>
    </dd>
  </div>
</template>

<script setup>
defineProps({
  renderedNotes: {
    type: String,
    default: '',
  },
  rawNotes: {
    type: String,
    default: '',
  },
});
</script>

<style scoped>
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
