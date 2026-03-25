<template>
  <div class="row mb-3">
    <label for="editAssociationFormType" class="col-sm-3 col-form-label required text-end">
      Association Type
    </label>
    <div class="col-sm-9">
      <select
        id="editAssociationFormType"
        class="form-select"
        :class="{ 'locked-field': isDisabled }"
        :value="modelValue"
        @change="handleChange"
        :disabled="isDisabled"
      >
        <option 
          v-for="type in types" 
          :key="type.value" 
          :value="type.value"
          :disabled="type.isSeparator"
        >
          {{ type.label }}
        </option>
      </select>
      <div v-if="false && isDisabled" class="form-text text-muted">
        <i class="bi bi-lock me-1"></i>Type is locked when adding an exemplar
      </div>
      <div v-if="showCustomTypeField" class="form-group mt-2">
        <label for="customType" class="form-label">Custom Association Type</label>
        <input
          id="customType"
          :value="customType"
          @input="$emit('update:customType', $event.target.value)"
          type="text"
          class="form-control"
          placeholder="ext:custom-type"
        />
        <div v-if="customType && !isValidCustomType" class="text-danger small mt-1">
          Invalid format. Must start with "ext:" followed by alphanumeric characters, dots, hyphens, or underscores.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ASSOCIATION_TYPES } from '../../composables/useAssociationForm';

/**
 * AssociationTypeSelector Component
 *
 * A dropdown component for selecting association types with support for custom types.
 */

const props = defineProps({
  /**
   * Currently selected type value (v-model)
   */
  modelValue: {
    type: String,
    default: ''
  },
  /**
   * Custom type value (for "other" type)
   */
  customType: {
    type: String,
    default: ''
  },
  /**
   * Whether the dropdown is disabled
   */
  isDisabled: {
    type: Boolean,
    default: false
  },
  /**
   * Available types for selection
   */
  types: {
    type: Array,
    default: () => ASSOCIATION_TYPES
  },
  /**
   * Whether the custom type is valid
   */
  isValidCustomType: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['update:modelValue', 'update:customType', 'change']);

// Whether to show the custom type input field
const showCustomTypeField = computed(() => {
  return props.modelValue === 'other';
});

/**
 * Handle type selection change
 */
function handleChange(event) {
  emit('update:modelValue', event.target.value);
  emit('change', event.target.value);
}
</script>

<style scoped>
.form-control:focus,
.form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Locked field styling for disabled dropdown */
.locked-field {
  background-color: #e9ecef;
  opacity: 0.65;
  cursor: not-allowed;
}
</style>
