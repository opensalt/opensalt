<template>
  <div class="organization-item-details">
    <dl class="details-list">
      <!-- Identifier link -->
      <ItemIdentifierRow :identifier="item.identifier" />

      <!-- Organization-specific fields -->
      <div v-if="item.abbreviatedStatement">
        <dt>Organization Name:</dt>
        <dd>{{ item.abbreviatedStatement }}</dd>
      </div>

      <div
        v-if="item.fullStatement"
        class="details-entry--full"
      >
        <dt>Description:</dt>
        <dd>{{ item.fullStatement }}</dd>
      </div>

      <div v-if="item.extensions && item.extensions['ceterms:agentType']">
        <dt>Type:</dt>
        <dd>{{ item.extensions['ceterms:agentType'] }}</dd>
      </div>

      <div v-if="legalName">
        <dt>Legal Name:</dt>
        <dd>{{ legalName }}</dd>
      </div>

      <div v-if="ctid">
        <dt>CTID:</dt>
        <dd>{{ ctid }}</dd>
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

      <div v-if="item.extensions && item.extensions['ceterms:jurisdiction']">
        <dt>Jurisdiction:</dt>
        <dd>{{ item.extensions['ceterms:jurisdiction'] }}</dd>
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

// Dual-key check for extension resilience
const legalName = computed(() => {
  return props.item.extensions?.['salt:legalName']
    || props.item.extensions?.['sdo:legalName']
    || '';
});

const ctid = computed(() => {
  return props.item.extensions?.['salt:ctid']
    || props.item.extensions?.['ceterms:ctid']
    || '';
});

const webpage = computed(() => {
  return resolveItemWebpage(
    props.item.uri,
    props.item.extensions?.['ceterms:subjectWebpage'],
  );
});
</script>

<style scoped>
.organization-item-details {
  padding: 0.5rem 0;
}
</style>
