<template>
  <div class="organization-item-details">
    <!-- Identifier link -->
    <div class="mb-3">
      <strong>Identifier:</strong>
      <a
        :href="`/uri/${item.identifier}`"
        target="_blank"
        class="ms-1"
      >{{ item.identifier }}</a>
    </div>

    <!-- Organization-specific fields -->
    <div
      v-if="item.abbreviatedStatement"
      class="mb-3"
    >
      <strong>Organization Name:</strong>
      <p class="mt-1">
        {{ item.abbreviatedStatement }}
      </p>
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
      v-if="item.extensions && item.extensions['ceterms:agentType']"
      class="mb-3"
    >
      <strong>Type:</strong>
      <p class="mt-1">
        {{ item.extensions['ceterms:agentType'] }}
      </p>
    </div>

    <div
      v-if="item.extensions && item.extensions['salt:legalName']"
      class="mb-3"
    >
      <strong>Legal Name:</strong>
      <p class="mt-1">
        {{ item.extensions['salt:legalName'] }}
      </p>
    </div>

    <div
      v-if="item.extensions && item.extensions['salt:ctid']"
      class="mb-3"
    >
      <strong>CTID:</strong>
      <p class="mt-1">
        {{ item.extensions['salt:ctid'] }}
      </p>
    </div>

    <div
      v-if="item.extensions && item.extensions['ceterms:subjectWebpage']"
      class="mb-3 text-truncate"
    >
      <strong>Webpage:</strong>
      <a
        :href="item.uri"
        target="_blank"
        class="ms-1"
      >{{ item.extensions?.['ceterms:subjectWebpage'] }}</a>
    </div>

    <div
      v-if="item.extensions && item.extensions['ceterms:jurisdiction']"
      class="mb-3"
    >
      <strong>Jurisdiction:</strong>
      <p class="mt-1">
        {{ item.extensions['ceterms:jurisdiction'] }}
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
  </div>
</template>

<script setup>
const _props = defineProps({
  item: {
    type: Object,
    required: true
  }
});
</script>

<style scoped>
.organization-item-details {
  padding: 0.5rem 0;
}

.organization-item-details strong {
  color: #495057;
  font-weight: 600;
}

.organization-item-details p {
  margin-bottom: 0.5rem;
  color: #212529;
}
</style>
