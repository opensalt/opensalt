<template>
  <div class="singleselect" :class="{ 'singleselect--active': isOpen }">
    <div class="singleselect__tags" @click="toggleDropdown">
      <div class="singleselect__tags-wrap">
        <span v-if="!selectedItem" class="singleselect__placeholder">
          {{ placeholder }}
        </span>
        <span v-else class="singleselect__selected-value">
          {{ selectedLabel }}
        </span>
      </div>
      <div class="singleselect__spinner" v-if="isLoading"></div>
      <div class="singleselect__select"></div>
    </div>

    <div class="singleselect__content" v-show="isOpen">
      <div class="singleselect__content-wrapper">
        <div class="singleselect__search" v-if="searchable">
          <input
            type="text"
            class="singleselect__input"
            v-model="searchQuery"
            :placeholder="searchPlaceholder"
            @input="filterOptions"
          />
        </div>

        <ul class="singleselect__options">
          <li
            v-if="allowClear"
            class="singleselect__option singleselect__option--clear"
            @click="clearSelection"
          >
            <span class="singleselect__option-text">{{ clearText }}</span>
          </li>
          <li
            v-for="option in filteredOptions"
            :key="getOptionValue(option)"
            class="singleselect__option"
            :class="{ 'singleselect__option--selected': isSelected(option) }"
            @click="selectOption(option)"
          >
            <span class="singleselect__option-text">{{ getOptionLabel(option) }}</span>
          </li>
          <li v-if="filteredOptions.length === 0" class="singleselect__option singleselect__option--disabled">
            <span class="singleselect__option-text">{{ noResultsText }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number, null],
    default: null
  },
  options: {
    type: Array,
    default: () => []
  },
  placeholder: {
    type: String,
    default: 'Select an option'
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...'
  },
  optionValue: {
    type: String,
    default: 'id'
  },
  optionLabel: {
    type: String,
    default: 'text'
  },
  searchable: {
    type: Boolean,
    default: true
  },
  allowClear: {
    type: Boolean,
    default: true
  },
  clearText: {
    type: String,
    default: '— Clear selection —'
  },
  noResultsText: {
    type: String,
    default: 'No results found'
  }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const isLoading = ref(false)
const searchQuery = ref('')
const filteredOptions = ref([])

const getOptionValue = (option) => {
  if (option === null || option === undefined) return null
  if (typeof option === 'object') {
    return option[props.optionValue] ?? option.id ?? option.value ?? option
  }
  return option
}

const getOptionLabel = (option) => {
  if (option === null || option === undefined) return ''
  if (typeof option === 'object') {
    return option[props.optionLabel] ?? option.text ?? option.label ?? option.title ?? option
  }
  return option
}

const selectedItem = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
    return null
  }
  return props.options.find(opt => getOptionValue(opt) === props.modelValue) || null
})

const selectedLabel = computed(() => {
  return selectedItem.value ? getOptionLabel(selectedItem.value) : ''
})

const isSelected = (option) => {
  return getOptionValue(option) === props.modelValue
}

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    // Reset search when opening and sync with current options
    searchQuery.value = ''
    filteredOptions.value = [...props.options] // Create a new array to ensure reactivity
  }
}

const selectOption = (option) => {
  const value = getOptionValue(option)
  emit('update:modelValue', value)
  isOpen.value = false
  searchQuery.value = ''
}

const clearSelection = () => {
  emit('update:modelValue', null)
  isOpen.value = false
}

const filterOptions = () => {
  if (!searchQuery.value) {
    filteredOptions.value = props.options
    return
  }

  const query = searchQuery.value.toLowerCase()
  filteredOptions.value = props.options.filter(option => {
    const label = getOptionLabel(option).toLowerCase()
    return label.includes(query)
  })
}

const handleClickOutside = (event) => {
  const target = event.target
  const singleselect = target.closest('.singleselect')
  if (!singleselect) {
    isOpen.value = false
  }
}

// Also handle mousedown events to catch clicks that might be prevented
const handleMouseDownOutside = (event) => {
  const target = event.target
  const singleselect = target.closest('.singleselect')
  if (!singleselect) {
    isOpen.value = false
  }
}

watch(() => props.options, (newOptions) => {
  filteredOptions.value = newOptions
}, { immediate: true })

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('mousedown', handleMouseDownOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('mousedown', handleMouseDownOutside)
})
</script>

<style scoped>
.singleselect {
  position: relative;
  min-height: 38px;
}

.singleselect__tags {
  position: relative;
  padding: 6px 30px 6px 12px;
  border: 1px solid #ced4da;
  border-radius: 0.375rem;
  background-color: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  min-height: 38px;
}

.singleselect__tags-wrap {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.singleselect__placeholder {
  color: #6c757d;
  font-size: 0.875rem;
}

.singleselect__selected-value {
  font-size: 0.875rem;
  color: #212529;
}

.singleselect__spinner {
  position: absolute;
  right: 30px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  border: 2px solid #e9ecef;
  border-top-color: #0d6efd;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: translateY(-50%) rotate(360deg);
  }
}

.singleselect__select {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  width: 0;
  height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 4px solid #495057;
  transition: transform 0.2s;
}

.singleselect--active .singleselect__select {
  transform: translateY(-50%) rotate(180deg);
}

.singleselect__content {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background-color: #fff;
  border: 1px solid #ced4da;
  border-top: none;
  border-radius: 0 0 0.375rem 0.375rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  z-index: 1000;
  max-height: 300px;
  overflow: hidden;
}

.singleselect__content-wrapper {
  max-height: 300px;
  overflow-y: auto;
}

.singleselect__search {
  padding: 8px 12px;
  border-bottom: 1px solid #dee2e6;
}

.singleselect__input {
  width: 100%;
  padding: 6px 12px;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
  font-size: 0.875rem;
}

.singleselect__options {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 250px;
  overflow-y: auto;
}

.singleselect__option {
  padding: 8px 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
}

.singleselect__option:hover {
  background-color: #f8f9fa;
}

.singleselect__option--selected {
  background-color: #e3f2fd;
}

.singleselect__option--clear {
  color: #6c757d;
  font-style: italic;
  border-bottom: 1px solid #dee2e6;
}

.singleselect__option--clear:hover {
  background-color: #f8f9fa;
}

.singleselect__option--disabled {
  color: #6c757d;
  cursor: default;
}

.singleselect__option--disabled:hover {
  background-color: transparent;
}

.singleselect__option-text {
  flex: 1;
  font-size: 0.875rem;
}

/* Focus styles */
.singleselect__tags:focus-within {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
