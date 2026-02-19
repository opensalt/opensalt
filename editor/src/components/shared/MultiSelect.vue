<template>
  <div class="multiselect" :class="{ 'multiselect--active': isOpen }">
    <div class="multiselect__tags" @click="toggleDropdown">
      <div class="multiselect__tags-wrap">
        <span v-if="selectedItems.length === 0" class="multiselect__placeholder">
          {{ placeholder }}
        </span>
        <div v-else class="multiselect__selected-values">
          <span
            v-for="value in selectedItems.slice(0, showCount)"
            :key="value"
            class="multiselect__selected-value"
          >
            {{ getSelectedLabel(value) }}
          </span>
          <span v-if="selectedItems.length > showCount" class="multiselect__more">
            +{{ selectedItems.length - showCount }} more
          </span>
        </div>
      </div>
      <div class="multiselect__spinner" v-if="isLoading"></div>
      <div class="multiselect__select"></div>
    </div>

    <div class="multiselect__content" v-show="isOpen">
      <div class="multiselect__content-wrapper">
        <div class="multiselect__search">
          <input
            type="text"
            class="multiselect__input"
            v-model="searchQuery"
            :placeholder="searchPlaceholder"
            @input="filterOptions"
          />
        </div>

        <div class="multiselect__actions">
          <button v-if="showSelectAll" type="button" class="multiselect__action" @click="selectAll">
            Select All
          </button>
          <button type="button" class="multiselect__action" @click="selectNone">
            Select None
          </button>
        </div>

        <ul class="multiselect__options">
          <li
            v-for="option in filteredOptions"
            :key="getOptionValue(option)"
            class="multiselect__option"
            :class="{ 'multiselect__option--selected': isSelected(option) }"
            @click="toggleOption(option)"
          >
            <input
              type="checkbox"
              :checked="isSelected(option)"
              @change="toggleOption(option)"
              class="multiselect__checkbox"
            />
            <span class="multiselect__option-text">{{ getOptionLabel(option) }}</span>
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
    type: Array,
    default: () => []
  },
  options: {
    type: Array,
    default: () => []
  },
  placeholder: {
    type: String,
    default: 'Select options'
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...'
  },
  optionValue: {
    type: String,
    default: 'value'
  },
  optionLabel: {
    type: String,
    default: 'label'
  },
  searchable: {
    type: Boolean,
    default: true
  },
  showSelectAll: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const isLoading = ref(false)
const searchQuery = ref('')
const filteredOptions = ref([])
const showCount = ref(10);

const selectedItems = computed(() => {
  return props.modelValue || []
})

const getOptionValue = (option) => {
  if (typeof option === 'object') {
    return option[props.optionValue] || option.value || option
  }
  return option
}

const getOptionLabel = (option) => {
  if (typeof option === 'object') {
    // Try the specified label property first, then common fallbacks
    if (option[props.optionLabel]) return option[props.optionLabel]
    // Try common label properties as fallbacks
    return option.label || option.text || option.title || option.name || String(option)
  }
  return option
}

const isSelected = (option) => {
  const value = getOptionValue(option)
  return selectedItems.value.includes(value)
}

const getSelectedLabel = (value) => {
  // If value is an object, try to get its label directly
  if (typeof value === 'object' && value !== null) {
    return getOptionLabel(value)
  }
  // Otherwise find the matching option
  const option = props.options.find(opt => getOptionValue(opt) === value)
  return option ? getOptionLabel(option) : value
}

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const toggleOption = (option) => {
  const value = getOptionValue(option)
  const currentValues = [...selectedItems.value]
  const index = currentValues.indexOf(value)

  if (index > -1) {
    currentValues.splice(index, 1)
  } else {
    currentValues.push(value)
  }

  emit('update:modelValue', currentValues)
}

const selectAll = () => {
  const allValues = filteredOptions.value.map(option => getOptionValue(option))
  emit('update:modelValue', allValues)
}

const selectNone = () => {
  emit('update:modelValue', [])
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
  const multiselect = target.closest('.multiselect')
  if (!multiselect) {
    isOpen.value = false
  }
}

// Also handle mousedown events to catch clicks that might be prevented
const handleMouseDownOutside = (event) => {
  const target = event.target
  const multiselect = target.closest('.multiselect')
  if (!multiselect) {
    isOpen.value = false
  }
}

watch(() => props.options, () => {
  filteredOptions.value = props.options
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
.multiselect {
  position: relative;
  min-height: 38px;
}

.multiselect__tags {
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

.multiselect__tags-wrap {
  flex: 1;
}

.multiselect__placeholder {
  color: #6c757d;
  font-size: 0.875rem;
}

.multiselect__selected-values {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  align-items: center;
}

.multiselect__selected-value {
  background-color: #e9ecef;
  color: #495057;
  padding: 2px 6px;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  white-space: nowrap;
  max-width: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.multiselect__more {
  color: #6c757d;
  font-size: 0.75rem;
  font-weight: 500;
}

.multiselect__select {
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

.multiselect--active .multiselect__select {
  transform: translateY(-50%) rotate(180deg);
}

.multiselect__content {
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

.multiselect__content-wrapper {
  max-height: 300px;
  overflow-y: auto;
}

.multiselect__search {
  padding: 8px 12px;
  border-bottom: 1px solid #dee2e6;
}

.multiselect__input {
  width: 100%;
  padding: 6px 12px;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
  font-size: 0.875rem;
}

.multiselect__actions {
  padding: 8px 12px;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  gap: 8px;
}

.multiselect__action {
  background: none;
  border: 1px solid #ced4da;
  padding: 4px 8px;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  cursor: pointer;
  color: #495057;
}

.multiselect__action:hover {
  background-color: #f8f9fa;
}

.multiselect__options {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 200px;
  overflow-y: auto;
}

.multiselect__option {
  padding: 8px 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
}

.multiselect__option:hover {
  background-color: #f8f9fa;
}

.multiselect__option--selected {
  background-color: #e3f2fd;
}

.multiselect__checkbox {
  margin: 0;
}

.multiselect__option-text {
  flex: 1;
  font-size: 0.875rem;
}

/* Focus styles */
.multiselect__tags:focus-within {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
