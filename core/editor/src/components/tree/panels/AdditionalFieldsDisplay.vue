<template>
  <div
    v-if="hasValues"
    class="mt-3 additional-fields-section"
  >
    <h6 class="mb-2">
      Additional Fields
    </h6>
    <dl class="details-list">
      <div
        v-for="field in fieldsWithValues"
        :key="field.id || field.name"
        class="row mb-1"
      >
        <dt class="col-sm-4 text-muted">
          {{ field.displayName || field.name }}:
        </dt>
        <dd class="col-sm-8">
          {{ field.value }}
        </dd>
      </div>
    </dl>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useAdditionalFields } from '../../../composables/useAdditionalFields.js';

const props = defineProps({
  additionalFields: {
    type: Object,
    required: true,
  },
  scope: {
    type: String,
    default: 'item',
  },
});

const { fieldDefinitions, fetchFields } = useAdditionalFields();

onMounted(() => {
  fetchFields(props.scope);
});

const fieldsWithValues = computed(() => {
  if (!fieldDefinitions.value?.length) return [];
  const af = props.additionalFields;
  return fieldDefinitions.value
    .filter(f => af?.[f.name])
    .map(f => ({ ...f, value: af[f.name] }));
});

const hasValues = computed(() => fieldsWithValues.value.length > 0);
</script>
