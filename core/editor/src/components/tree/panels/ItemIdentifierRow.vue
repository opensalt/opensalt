<template>
  <div class="details-identifier item-identifier">
    <dt>{{ label }}:</dt>
    <dd v-if="identifier">
      <a
        :href="displayHref"
        target="_blank"
        class="ms-1"
      >{{ displayText }}<span class="visually-hidden"> (opens in new window)</span></a>
    </dd>
    <dd v-else>
      <span class="text-muted">—</span>
    </dd>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  identifier: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: 'Identifier',
  },
  href: {
    type: String,
    default: '',
  },
});

const displayText = computed(() => {
  if (!props.identifier) return '';
  if (props.identifier.startsWith('local:')) {
    const id = props.identifier.slice(6);
    const base = window.location.origin;
    return `${base.replace(/\/$/, '')}/uri/${id}`;
  }
  return props.identifier;
});

const displayHref = computed(() => {
  if (props.href) return props.href;
  if (!props.identifier) return '';
  if (props.identifier.startsWith('local:')) return displayText.value;
  return `/uri/${props.identifier}`;
});
</script>
