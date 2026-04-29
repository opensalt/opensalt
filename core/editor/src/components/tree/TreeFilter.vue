<template>
  <div class="tree-filter mb-2">
    <div class="input-group input-group-sm">
      <span class="input-group-text">
        <i class="bi bi-search" />
      </span>
      <input
        ref="searchInput"
        type="text"
        class="form-control"
        :value="modelValue"
        placeholder="Filter tree..."
        aria-label="Filter tree items"
        @input="onInput"
        @keydown.escape="onClear"
      >
      <button
        v-if="modelValue"
        type="button"
        class="btn btn-outline-secondary"
        aria-label="Clear filter"
        @click="onClear"
      >
        <i class="bi bi-x-lg" />
      </button>
    </div>
    <div
      v-if="matchCount !== null && modelValue"
      class="filter-info mt-1"
    >
      <small class="text-muted">
        <span v-if="matchCount > 0">
          {{ matchCount }} item{{ matchCount !== 1 ? 's' : '' }} found
        </span>
        <span
          v-else
          class="text-warning"
        >
          No matches found
        </span>
      </small>
    </div>
  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue';

const _props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  matchCount: {
    type: Number,
    default: null
  }
});

const emit = defineEmits(['update:modelValue', 'clear']);

const searchInput = ref(null);

let debounceTimer = null;

function onInput(event) {
  const value = event.target.value;
  
  // Debounce the input to avoid excessive filtering
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
  
  debounceTimer = setTimeout(() => {
    emit('update:modelValue', value);
  }, 150);
}

function onClear() {
  emit('update:modelValue', '');
  emit('clear');
  searchInput.value?.focus();
}

// Clean up debounce timer on unmount
onUnmounted(() => {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
});

// Expose focus method for external use
defineExpose({
  focus: () => searchInput.value?.focus()
});
</script>

<style scoped>
.tree-filter {
  position: relative;
}

.tree-filter .input-group {
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.tree-filter .form-control:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.tree-filter .input-group-text {
  background-color: #f8f9fa;
  border-right: none;
}

.tree-filter .form-control {
  border-left: none;
}

.tree-filter .form-control:focus + .btn-outline-secondary {
  border-color: #86b7fe;
}

.filter-info {
  font-size: 0.75rem;
}
</style>
