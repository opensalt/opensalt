<template>
  <div
    v-if="rows.length"
    class="mt-3 extension-display-rows"
  >
    <dl class="details-list">
      <div
        v-for="row in rows"
        :key="row.key"
      >
        <dt>
          {{ row.label }}
        </dt>
        <dd>
          {{ row.displayValue }}
        </dd>
      </div>
    </dl>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  config: {
    type: Object,
    default: () => ({})
  },
  extensions: {
    type: Object,
    default: () => ({})
  }
});

function serialize(value) {
  if (value === null || value === undefined) return null;
  if (Array.isArray(value)) return value.join(', ');
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
}

const rows = computed(() => {
  const cfg = props.config && typeof props.config === 'object' ? props.config : {};
  const ext = props.extensions && typeof props.extensions === 'object' ? props.extensions : {};
  const result = [];
  for (const [key, label] of Object.entries(cfg)) {
    if (!Object.prototype.hasOwnProperty.call(ext, key)) continue;
    const displayValue = serialize(ext[key]);
    if (displayValue === null) continue;
    result.push({
      key,
      label: label || key,
      displayValue
    });
  }
  return result;
});
</script>

<style scoped>
</style>
