<template>
  <div class="credential-item-details">
    <dl class="details-list">
      <!-- Identifier link -->
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

      <!-- Credential-specific fields -->
      <div v-if="item.fullStatement">
        <dt>Credential Name</dt>
        <dd>{{ item.fullStatement }}</dd>
      </div>

      <div v-if="item.description">
        <dt>Description</dt>
        <dd>{{ item.description }}</dd>
      </div>

      <div v-if="item.extensions && item.extensions['salt:credential']">
        <dt>Credential</dt>
        <dd>{{ item.extensions['salt:credential'] }}</dd>
      </div>

      <div v-if="item.codedNotation">
        <dt>Coded Notation</dt>
        <dd>{{ item.codedNotation }}</dd>
      </div>

      <div
        v-if="item.uri"
        class="text-truncate"
      >
        <dt>Webpage</dt>
        <dd>
          <a
            :href="item.uri"
            target="_blank"
            class="ms-1"
          >{{ item.uri }}<span class="visually-hidden"> (opens in new window)</span></a>
        </dd>
      </div>

      <div v-if="item.notes">
        <dt>Notes</dt>
        <dd>{{ item.notes }}</dd>
      </div>
    </dl>
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
.credential-item-details {
  padding: 0.5rem 0;
}
</style>
