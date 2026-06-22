<template>
  <div class="public-key-item-details">
    <dl class="details-list">
      <!-- Public Key-specific fields -->
      <div
        v-if="item.fullStatement"
        class="details-entry--full"
      >
        <dt>Public Key:</dt>
        <dd>
          <code class="d-block p-2 bg-light border rounded">{{ item.fullStatement }}</code>
        </dd>
      </div>

      <ItemNotesField
        v-if="item.notes"
        :raw-notes="item.notes"
        :rendered-notes="renderedNotes"
      />

      <hr v-if="item.fullStatement || item.notes">

      <ItemIdentifierRow
        label="Item URI"
        :identifier="item.identifier"
      />
    </dl>
  </div>
</template>

<script setup>
import ItemIdentifierRow from '../ItemIdentifierRow.vue';
import ItemNotesField from '../ItemNotesField.vue';

defineProps({
  item: {
    type: Object,
    required: true,
  },
  renderedNotes: {
    type: String,
    default: '',
  },
});
</script>

<style scoped>
.public-key-item-details {
  padding: 0.5rem 0;
}

.public-key-item-details :deep(code) {
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.875rem;
  word-break: break-all;
  max-height: 200px;
  overflow-y: auto;
}
</style>
