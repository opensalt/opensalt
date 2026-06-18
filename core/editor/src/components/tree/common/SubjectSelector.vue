<template>
  <div
    class="row mb-3"
    :class="{ 'field-disabled': disabled }"
  >
    <label
      :for="id"
      class="col-sm-2 col-form-label"
    >Subjects</label>
    <div class="col-sm-10">
      <MultiSelect
        :id="id"
        :model-value="modelValue"
        :name="name"
        :options="availableSubjects"
        option-value="id"
        option-label="title"
        :show-select-all="false"
        placeholder="Select subjects"
        search-placeholder="Search subjects..."
        :creatable="true"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <small class="text-muted">{{ helpText }}</small>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import MultiSelect from '../MultiSelect.vue';
import { logger } from '../../../utils/logger.js';

defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  id: {
    type: String,
    default: 'subject-select'
  },
  name: {
    type: String,
    default: 'subjects[]'
  },
  helpText: {
    type: String,
    default: 'Subject areas associated with this item.'
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

defineEmits(['update:modelValue']);

const availableSubjects = ref([]);
let loadPromise = null;

async function fetchSubjects() {
  if (loadPromise) return loadPromise;

  loadPromise = (async () => {
    try {
      logger.debug('Fetching subjects from API...');
      const response = await fetch('/cfdef/subject/list?field_name=subjects&page=1&page_limit=50', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const text = await response.text();
      // logger.debug('Raw subjects response:', text);

      const data = JSON.parse(text);
      logger.debug('Parsed subjects response:', data);

      if (data) {
        availableSubjects.value = data;
        logger.debug('Loaded subjects:', availableSubjects.value);
      } else {
        logger.warn('Unexpected response format for subjects:', data);
        throw new Error('Invalid response format');
      }
    } catch (err) {
      logger.error('Failed to fetch subjects:', err);
      availableSubjects.value = [
        { id: 'math', title: 'Mathematics' },
        { id: 'science', title: 'Science' },
        { id: 'english', title: 'English Language Arts' },
        { id: 'history', title: 'History' }
      ];
      logger.debug('Using fallback subjects:', availableSubjects.value);
    }
  })();

  return loadPromise;
}

onMounted(() => fetchSubjects());

function ensureLoaded() {
  return fetchSubjects();
}

defineExpose({
  ensureLoaded,
  availableSubjects
});
</script>

<style scoped>
.field-disabled {
  opacity: 0.65;
  pointer-events: none;
}
</style>
