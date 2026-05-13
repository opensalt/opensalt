<template>
  <!-- Render nothing if there are no field definitions -->
  <template v-if="fieldDefinitions && fieldDefinitions.length > 0">
    <div
      v-for="field in fieldDefinitions"
      :key="field.id || field.name"
      class="row mb-3"
    >
      <label
        :for="inputId(field)"
        class="col-sm-2 col-form-label"
        :class="{ 'required-label': field.typeInfo?.required }"
      >{{ field.displayName || field.name }}</label>
      <div class="col-sm-10">
        <input
          :id="inputId(field)"
          type="text"
          class="form-control"
          :name="field.name"
          :value="getFieldValue(field.name)"
          :required="field.typeInfo?.required || false"
          :disabled="disabled"
          :aria-required="field.typeInfo?.required || false"
          @input="onFieldInput(field.name, $event)"
        >
      </div>
    </div>
  </template>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({})
  },
  fieldDefinitions: {
    type: Array,
    default: () => []
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue']);

/**
 * Generate a unique, deterministic input ID for a field.
 * @param {Object} field - The field definition
 * @returns {string}
 */
function inputId(field) {
  return `additional_field_${field.name}`;
}

/**
 * Get the current value for a field from the modelValue object.
 * @param {string} fieldName
 * @returns {string}
 */
function getFieldValue(fieldName) {
  if (!props.modelValue || typeof props.modelValue !== 'object') {
    return '';
  }
  return props.modelValue[fieldName] ?? '';
}

/**
 * Handle input changes and emit the updated object.
 * @param {string} fieldName
 * @param {Event} event
 */
function onFieldInput(fieldName, event) {
  const updated = {
    ...props.modelValue,
    [fieldName]: event.target.value
  };
  emit('update:modelValue', updated);
}
</script>

<style scoped>
.required-label::before {
  content: "*";
  color: red;
}
</style>
