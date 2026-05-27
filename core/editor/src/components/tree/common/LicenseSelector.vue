<template>
  <div
    class="row mb-3"
    :class="{ 'field-disabled': disabled }"
  >
    <label
      :for="id"
      class="col-sm-2 col-form-label"
    >License</label>
    <div class="col-sm-10">
      <SingleSelect
        :id="id"
        :model-value="modelValue"
        :name="name"
        :options="availableLicenses"
        option-value="id"
        option-label="title"
        placeholder="Select License"
        search-placeholder="Search licenses..."
        :allow-clear="true"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <small class="text-muted">{{ helpText }}</small>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import SingleSelect from '../SingleSelect.vue';
import { logger } from '../../../utils/logger.js';

defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  id: {
    type: String,
    default: 'license-select'
  },
  name: {
    type: String,
    default: 'licence'
  },
  helpText: {
    type: String,
    default: 'License governing the use of this item.'
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

defineEmits(['update:modelValue']);

const availableLicenses = ref([]);
let loadPromise = null;

async function fetchLicenses() {
  if (loadPromise) return loadPromise;

  loadPromise = (async () => {
    try {
      const response = await fetch('/cfdef/licence/list?field_name=licence&page=1&page_limit=50', {
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
      const data = JSON.parse(text);

      if (data) {
        availableLicenses.value = data;
      } else {
        availableLicenses.value = [
          { id: 'cc0', title: 'CC0 (Public Domain)' },
          { id: 'cc-by', title: 'CC BY (Attribution)' },
          { id: 'cc-by-sa', title: 'CC BY-SA (Attribution-ShareAlike)' },
          { id: 'cc-by-nd', title: 'CC BY-ND (Attribution-NoDerivs)' },
          { id: 'cc-by-nc', title: 'CC BY-NC (Attribution-NonCommercial)' },
          { id: 'cc-by-nc-sa', title: 'CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)' },
          { id: 'cc-by-nc-nd', title: 'CC BY-NC-ND (Attribution-NonCommercial-NoDerivs)' }
        ];
      }
    } catch (err) {
      logger.error('Failed to fetch licenses:', err);
      availableLicenses.value = [
        { id: 'cc0', title: 'CC0 (Public Domain)' },
        { id: 'cc-by', title: 'CC BY (Attribution)' },
        { id: 'cc-by-sa', title: 'CC BY-SA (Attribution-ShareAlike)' },
        { id: 'cc-by-nd', title: 'CC BY-ND (Attribution-NoDerivs)' },
        { id: 'cc-by-nc', title: 'CC BY-NC (Attribution-NonCommercial)' },
        { id: 'cc-by-nc-sa', title: 'CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)' },
        { id: 'cc-by-nc-nd', title: 'CC BY-NC-ND (Attribution-NonCommercial-NoDerivs)' }
      ];
    }
  })();

  return loadPromise;
}

onMounted(() => fetchLicenses());

function ensureLoaded() {
  return fetchLicenses();
}

defineExpose({
  ensureLoaded,
  availableLicenses
});
</script>

<style scoped>
.field-disabled {
  opacity: 0.65;
  pointer-events: none;
}
</style>
