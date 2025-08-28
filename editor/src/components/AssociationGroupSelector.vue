<template>
  <div class="mb-3">
    <label for="associationGroupSelect" class="form-label fw-bold">Association Group</label>
    <select
      id="associationGroupSelect"
      class="form-select"
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option
        v-for="group in associationGroups"
        :key="group.id"
        :value="group.id"
      >
        {{ group.title }}
      </option>
    </select>
    <div class="form-text">
      Filter items by their association group
    </div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: {
    type: String,
    required: true
  },
  associationGroups: {
    type: Array,
    default: () => []
  }
});

defineEmits(['update:modelValue']);
</script>

<style scoped>
/* Component-specific styles can be added here */
</style>
