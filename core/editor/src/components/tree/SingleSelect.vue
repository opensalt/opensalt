<template>
  <div
    ref="rootRef"
    class="singleselect"
    :class="{ 'singleselect--active': isOpen, 'singleselect--disabled': disabled }"
  >
    <div
      :id="id"
      class="singleselect__tags"
      role="combobox"
      tabindex="0"
      :aria-disabled="disabled"
      :aria-expanded="isOpen"
      aria-haspopup="listbox"
      :aria-controls="listboxId"
      :aria-activedescendant="activeDescendantId"
      :aria-labelledby="labelledbyId || undefined"
      :aria-label="labelledbyId ? undefined : placeholder"
      @click="toggleDropdown"
      @keydown="handleKeydown"
    >
      <div class="singleselect__tags-wrap">
        <span
          v-if="!selectedItem"
          class="singleselect__placeholder"
        >
          {{ placeholder }}
        </span>
        <span
          v-else
          class="singleselect__selected-value"
        >
          {{ selectedLabel }}
        </span>
      </div>
      <div
        v-if="isLoading"
        class="singleselect__spinner"
      />
      <div class="singleselect__select" />
    </div>

    <div
      v-show="isOpen"
      :id="listboxId"
      role="listbox"
      :aria-label="placeholder"
      class="singleselect__content"
    >
      <!-- Search stays pinned above the scrolling options -->
      <div
        v-if="searchable"
        class="singleselect__search"
      >
        <input
          v-model="searchQuery"
          type="text"
          class="singleselect__input"
          :placeholder="searchPlaceholder"
          :aria-label="searchPlaceholder"
          @keydown="handleSearchKeydown"
        >
      </div>

      <div
        aria-live="polite"
        class="visually-hidden"
      >
        {{ resultStatusMessage }}
      </div>

      <div class="singleselect__content-wrapper">
        <ul class="singleselect__options">
          <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/interactive-supports-focus, vuejs-accessibility/mouse-events-have-key-events -->
          <li
            v-if="allowClear"
            :id="clearOptionId"
            role="option"
            class="singleselect__option singleselect__option--clear"
            :class="{ 'singleselect__option--highlighted': highlightedIndex === -1 }"
            :aria-selected="modelValue === null"
            @click="clearSelection"
            @mouseenter="highlightedIndex = -1"
          >
            <span class="singleselect__option-text">{{ clearText }}</span>
          </li>
          <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/interactive-supports-focus, vuejs-accessibility/mouse-events-have-key-events -->
          <li
            v-if="createOption"
            :id="createOptionId"
            role="option"
            class="singleselect__option singleselect__option--create"
            :class="{ 'singleselect__option--highlighted': highlightedIndex === -2 }"
            :aria-selected="false"
            @click="selectCreateOption"
            @mouseenter="highlightedIndex = -2"
          >
            <span class="singleselect__option-text">{{ getOptionLabel(createOption) }}</span>
          </li>
          <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/interactive-supports-focus, vuejs-accessibility/mouse-events-have-key-events -->
          <template
            v-for="(group, groupIndex) in renderedGroups"
            :key="groupIndex"
          >
            <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/interactive-supports-focus, vuejs-accessibility/mouse-events-have-key-events -->
            <li
              v-if="group.label"
              role="presentation"
              class="singleselect__option-group"
            >
              {{ group.label }}
            </li>
            <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/interactive-supports-focus, vuejs-accessibility/mouse-events-have-key-events -->
            <li
              v-for="entry in group.entries"
              :id="getOptionId(entry.index)"
              :key="getOptionValue(entry.option)"
              role="option"
              class="singleselect__option"
              :class="{
                'singleselect__option--selected': isSelected(entry.option),
                'singleselect__option--highlighted': highlightedIndex === entry.index,
                'singleselect__option--grouped': Boolean(group.label)
              }"
              :aria-selected="isSelected(entry.option)"
              @click="selectOption(entry.option)"
              @mouseenter="highlightedIndex = entry.index"
            >
              <span class="singleselect__option-text">{{ getOptionLabel(entry.option) }}</span>
            </li>
          </template>
          <li
            v-if="selectableOptions.length === 0 && !createOption"
            class="singleselect__option singleselect__option--disabled"
            role="presentation"
          >
            <span class="singleselect__option-text">{{ noResultsText }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, useId } from 'vue'

const props = defineProps({
  id: {
    type: String,
    default: ''
  },
  modelValue: {
    type: [String, Number, null],
    default: null
  },
  /**
   * Flat option list, or grouped entries shaped { label, options }.
   * Group entries are auto-detected by their options array; their labels
   * render as non-selectable headers (like optgroups).
   */
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
  },
  creatable: {
    type: Boolean,
    default: false
  },
  createOptionText: {
    type: String,
    default: "Create '{text}'"
  },
  /** id of an element to reference as the combobox's accessible name */
  labelledbyId: {
    type: String,
    default: ''
  },
  /** Disables the control: not focusable, will not open, visually muted */
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const isLoading = ref(false)
const searchQuery = ref('')
const highlightedIndex = ref(null)
const rootRef = ref(null)

// Unique IDs for ARIA relationships
const listboxId = useId()
const clearOptionId = `${listboxId}-clear`
const createOptionId = `${listboxId}-create`
const getOptionId = (index) => `${listboxId}-opt-${index}`

const activeDescendantId = computed(() => {
  if (highlightedIndex.value === null || highlightedIndex.value === undefined) {
    return undefined
  }
  if (highlightedIndex.value === -2) {
    return createOptionId
  }
  if (highlightedIndex.value === -1) {
    return clearOptionId
  }
  return getOptionId(highlightedIndex.value)
})

const isGroup = (option) => Boolean(option && Array.isArray(option.options))

const allFlatOptions = computed(() =>
  props.options.flatMap((option) => (isGroup(option) ? option.options : [option]))
)

const optionMatchesQuery = (option, query) => {
  const label = String(getOptionLabel(option) ?? '').toLowerCase()
  if (label.includes(query)) return true
  // Also match the option's other string fields (e.g. id, description)
  return Object.values(option).some(
    (value) => typeof value === 'string' && value.toLowerCase().includes(query)
  )
}

const filteredOptions = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return props.options

  if (!props.options.some(isGroup)) {
    return props.options.filter((option) => optionMatchesQuery(option, query))
  }

  const groups = []
  for (const option of props.options) {
    if (!isGroup(option)) {
      if (optionMatchesQuery(option, query)) groups.push(option)
      continue
    }
    // A query matching the group label keeps the whole group visible
    const groupLabel = String(option.label ?? '').toLowerCase()
    const options = groupLabel.includes(query)
      ? option.options
      : option.options.filter((child) => optionMatchesQuery(child, query))
    if (options.length > 0) groups.push({ label: option.label, options })
  }
  return groups
})

// Selectable entries only (group headers are skipped), in list order
const selectableOptions = computed(() =>
  filteredOptions.value.flatMap((option) => (isGroup(option) ? option.options : [option]))
)

// Render model pairing each selectable option with its flat index for ARIA ids
const renderedGroups = computed(() => {
  let index = 0
  return filteredOptions.value.map((option) => {
    if (isGroup(option)) {
      const entries = option.options.map((child) => ({ option: child, index: index++ }))
      return { label: option.label, entries }
    }
    return { label: null, entries: [{ option, index: index++ }] }
  })
})

const resultStatusMessage = computed(() => {
  if (!searchQuery.value.trim()) return ''
  const count = selectableOptions.value.length
  return count === 0 ? props.noResultsText : `${count} option${count === 1 ? '' : 's'} available`
})

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
    // Keep empty strings out of the fallback chain: resolve them to the
    // option value so options with blank titles stay identifiable.
    const label = option[props.optionLabel] ?? option.text ?? option.label ?? option.title
    if (label !== undefined && label !== null && label !== '') return label
    const value = getOptionValue(option)
    return (value !== null && value !== undefined) ? String(value) : ''
  }
  return option
}

const createOption = computed(() => {
  if (!props.creatable || !searchQuery.value.trim()) return null

  const query = searchQuery.value.trim()
  const queryLower = query.toLowerCase()

  const exactMatch = props.options.some(opt =>
    getOptionLabel(opt).toLowerCase() === queryLower
  )
  if (exactMatch) return null

  return {
    [props.optionValue]: '__' + query,
    [props.optionLabel]: props.createOptionText.replace('{text}', query),
    _isCreateOption: true
  }
})

const hasCreateOption = computed(() => createOption.value !== null)

const selectedItem = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
    return null
  }
  const found = allFlatOptions.value.find((opt) => getOptionValue(opt) === props.modelValue)
  if (found) return found

  if (typeof props.modelValue === 'string' && props.modelValue.startsWith('__')) {
    const cleanValue = props.modelValue.substring(2)
    return {
      [props.optionValue]: props.modelValue,
      [props.optionLabel]: cleanValue,
      _isCreateOption: true
    }
  }

  return null
})

const selectedLabel = computed(() => {
  return selectedItem.value ? getOptionLabel(selectedItem.value) : ''
})

const isSelected = (option) => {
  return getOptionValue(option) === props.modelValue
}

const toggleDropdown = () => {
  if (props.disabled) return
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    // Reset search when opening so the full list is visible again
    searchQuery.value = ''
    highlightedIndex.value = null
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

const selectCreateOption = () => {
  if (createOption.value) {
    emit('update:modelValue', getOptionValue(createOption.value))
    isOpen.value = false
    searchQuery.value = ''
  }
}

// Keyboard navigation
const moveHighlight = (direction) => {
  if (selectableOptions.value.length === 0 && !hasCreateOption.value) return

  const hasClear = props.allowClear
  const hasCreate = hasCreateOption.value
  const totalFlatCount = selectableOptions.value.length + (hasClear ? 1 : 0) + (hasCreate ? 1 : 0)

  let flatIndex
  if (highlightedIndex.value === null || highlightedIndex.value === undefined) {
    flatIndex = direction > 0 ? 0 : totalFlatCount - 1
  } else {
    if (highlightedIndex.value === -2 && hasCreate) {
      flatIndex = 0
    } else if (highlightedIndex.value === -1 && hasClear) {
      flatIndex = hasCreate ? 1 : 0
    } else {
      const offset = (hasCreate ? 1 : 0) + (hasClear ? 1 : 0)
      flatIndex = highlightedIndex.value + offset
    }
    flatIndex = (flatIndex + direction + totalFlatCount) % totalFlatCount
  }

  const createCount = hasCreate ? 1 : 0
  const clearCount = hasClear ? 1 : 0
  const specialCount = createCount + clearCount

  if (flatIndex < specialCount) {
    if (hasCreate && flatIndex === 0) {
      highlightedIndex.value = -2
    } else {
      highlightedIndex.value = -1
    }
  } else {
    highlightedIndex.value = flatIndex - specialCount
  }
}

const highlightFirst = () => {
  if (selectableOptions.value.length === 0 && !hasCreateOption.value) return
  if (hasCreateOption.value) {
    highlightedIndex.value = -2
  } else {
    highlightedIndex.value = props.allowClear ? -1 : 0
  }
}

const highlightLast = () => {
  if (selectableOptions.value.length === 0 && !hasCreateOption.value) return
  if (selectableOptions.value.length === 0) {
    highlightedIndex.value = -2
  } else {
    highlightedIndex.value = selectableOptions.value.length - 1
  }
}

const handleSearchKeydown = (event) => {
  if (props.disabled) return
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      moveHighlight(1)
      break

    case 'ArrowUp':
      event.preventDefault()
      moveHighlight(-1)
      break

    case 'Enter':
      event.preventDefault()
      if (highlightedIndex.value === -2) {
        selectCreateOption()
      } else if (highlightedIndex.value === -1) {
        clearSelection()
      } else if (highlightedIndex.value !== null && highlightedIndex.value !== undefined) {
        selectOption(selectableOptions.value[highlightedIndex.value])
      } else if (selectableOptions.value.length > 0) {
        // Enter with no highlight picks the first matching option
        selectOption(selectableOptions.value[0])
      }
      break

    case 'Escape':
      event.preventDefault()
      isOpen.value = false
      break
  }
}

const handleKeydown = (event) => {
  if (props.disabled) return
  switch (event.key) {
    case 'Enter':
    case ' ':
      event.preventDefault()
      if (!isOpen.value) {
        toggleDropdown()
      } else if (highlightedIndex.value !== null && highlightedIndex.value !== undefined) {
        if (highlightedIndex.value === -2) {
          selectCreateOption()
        } else if (highlightedIndex.value === -1) {
          clearSelection()
        } else {
          selectOption(selectableOptions.value[highlightedIndex.value])
        }
      }
      break

    case 'Escape':
      event.preventDefault()
      isOpen.value = false
      break

    case 'ArrowDown':
      event.preventDefault()
      if (!isOpen.value) {
        toggleDropdown()
      }
      moveHighlight(1)
      break

    case 'ArrowUp':
      event.preventDefault()
      if (!isOpen.value) {
        toggleDropdown()
      }
      moveHighlight(-1)
      break

    case 'Home':
      event.preventDefault()
      if (isOpen.value) {
        highlightFirst()
      }
      break

    case 'End':
      event.preventDefault()
      if (isOpen.value) {
        highlightLast()
      }
      break
  }
}

// Close only when the click lands outside this instance, so opening another
// select (or clicking anywhere else) closes this one.
const handleClickOutside = (event) => {
  if (rootRef.value && !rootRef.value.contains(event.target)) {
    isOpen.value = false
  }
}

// Also handle mousedown events to catch clicks that might be prevented
const handleMouseDownOutside = (event) => {
  if (rootRef.value && !rootRef.value.contains(event.target)) {
    isOpen.value = false
  }
}

// Reset highlight when dropdown closes
watch(isOpen, (newVal) => {
  if (!newVal) {
    highlightedIndex.value = null
  }
})

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
  display: flex;
  flex-direction: column;
  max-height: 300px;
  overflow: hidden;
}

/* Only the options scroll; the search box stays pinned above them */
.singleselect__content-wrapper {
  flex: 1 1 auto;
  min-height: 0;
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

.singleselect__option--highlighted {
  background-color: #e8e8ff;
  outline: 2px solid #0d6efd;
  outline-offset: -2px;
}

.singleselect__option--clear {
  color: #6c757d;
  font-style: italic;
  border-bottom: 1px solid #dee2e6;
}

.singleselect__option--clear:hover {
  background-color: #f8f9fa;
}

.singleselect__option--create {
  color: #0d6efd;
  font-weight: 500;
  border-bottom: 1px solid #dee2e6;
}

.singleselect__option--create:hover {
  background-color: #f0f7ff;
}

.singleselect__option--disabled {
  color: #6c757d;
  cursor: default;
}

.singleselect__option--disabled:hover {
  background-color: transparent;
}

.singleselect__option-group {
  padding: 6px 12px 4px;
  font-size: 0.875rem;
  font-weight: 700;
  color: #212529;
}

/* Options inside a group are indented under their bold header, like optgroups */
.singleselect__option--grouped {
  padding-left: 26px;
}

.singleselect__option-text {
  flex: 1;
  font-size: 0.875rem;
}

/* Focus styles */
.singleselect__tags:focus-visible {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
  outline: none;
}

.singleselect__tags:focus-within {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Disabled control */
.singleselect--disabled .singleselect__tags {
  background-color: #e9ecef;
  cursor: not-allowed;
}

.singleselect--disabled .singleselect__selected-value,
.singleselect--disabled .singleselect__placeholder {
  color: #6c757d;
}
</style>
