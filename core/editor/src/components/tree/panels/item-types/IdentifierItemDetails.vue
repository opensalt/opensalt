<template>
  <div class="identifier-item-details">
    <dl class="details-list">
      <div v-if="item.extensions && item.extensions['salt:idType']">
        <dt>Identifier Type:</dt>
        <dd>{{ item.extensions['salt:idType'] }}</dd>
      </div>

      <div
        v-if="item.fullStatement"
        class="details-entry--full"
      >
        <dt>Description:</dt>
        <dd>{{ item.fullStatement }}</dd>
      </div>

      <div v-if="item.codedNotation">
        <dt>Coded Notation:</dt>
        <dd>{{ item.codedNotation }}</dd>
      </div>

      <ItemNotesField
        v-if="item.notes"
        :raw-notes="item.notes"
        :rendered-notes="renderedNotes"
      />

      <hr v-if="hasContentAbove">

      <ItemIdentifierRow
        label="Item URI"
        :identifier="item.identifier"
      />
    </dl>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ItemIdentifierRow from '../ItemIdentifierRow.vue';
import ItemNotesField from '../ItemNotesField.vue';

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

const hasContentAbove = computed(() => {
  return (
    (props.item.extensions && props.item.extensions['salt:idType']) ||
    props.item.fullStatement ||
    props.item.codedNotation ||
    props.item.notes
  );
});
</script>

<style scoped>
.identifier-item-details {
  padding: 0.5rem 0;
}
</style>
