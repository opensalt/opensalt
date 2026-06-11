<template>
  <div class="identifier-item-details">
    <div
      v-if="item.extensions && item.extensions['salt:idType']"
      class="mb-3"
    >
      <strong>Identifier Type:</strong>
      <span class="ms-1">{{ item.extensions['salt:idType'] }}</span>
    </div>

    <div
      v-if="item.fullStatement"
      class="mb-3"
    >
      <strong>Description:</strong>
      <p class="mt-1">
        {{ item.fullStatement }}
      </p>
    </div>

    <div
      v-if="item.codedNotation"
      class="mb-3"
    >
      <strong>Coded Notation:</strong>
      <p class="mt-1">
        {{ item.codedNotation }}
      </p>
    </div>

    <div
      v-if="item.notes"
      class="mb-3"
    >
      <strong>Notes:</strong>
      <p class="mt-1">
        {{ item.notes }}
      </p>
    </div>

    <hr
      v-if="hasContentAbove"
      class="my-3"
    >

    <div class="mb-3 details-identifier item-identifier">
      <strong>Item URI:</strong>
      <a
        v-if="item.identifier"
        :href="`/uri/${item.identifier}`"
        target="_blank"
        class="ms-1"
      >{{ item.identifier }}<span class="visually-hidden"> (opens in new window)</span></a>
      <span
        v-else
        class="text-muted ms-1"
      >—</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
const _props = defineProps({
  item: {
    type: Object,
    required: true
  }
});

const hasContentAbove = computed(() => {
  return (
    (_props.item.extensions && _props.item.extensions['salt:idType']) ||
    _props.item.fullStatement ||
    _props.item.codedNotation ||
    _props.item.notes
  );
});
</script>

<style scoped>
.identifier-item-details {
  padding: 0.5rem 0;
}

.identifier-item-details strong {
  color: #495057;
  font-weight: 600;
}

.identifier-item-details p {
  margin-bottom: 0.5rem;
  color: #212529;
}
</style>
