<template>
  <div class="course-item-details">
    <dl class="details-list">
      <!-- Identifier link -->
      <ItemIdentifierRow :identifier="item.identifier" />

      <!-- Course-specific fields -->
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

      <div v-if="item.extensions && item.extensions['salt:inLanguage']">
        <dt>Language:</dt>
        <dd>{{ item.extensions['salt:inLanguage'] }}</dd>
      </div>

      <div v-if="item.extensions && item.extensions['salt:deliveryType']">
        <dt>Delivery Type:</dt>
        <dd class="text-capitalize">
          {{ item.extensions['salt:deliveryType'] }}
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

const webpage = computed(() => {
  return resolveItemWebpage(props.item.uri, null);
});
</script>

<style scoped>
.course-item-details {
  padding: 0.5rem 0;
}
</style>
