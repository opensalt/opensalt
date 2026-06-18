<template>
  <div
    v-if="show"
    data-testid="extensions-overlay"
    class="extensions-backdrop"
    @click.self="cancel"
    @keydown.esc="cancel"
  >
    <div
      class="extensions-dialog card"
      @click.stop
    >
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title mb-0">
          Edit extensions — {{ entityLabel }}
        </h5>
        <button
          type="button"
          class="btn-close"
          aria-label="Close"
          @click="cancel"
        />
      </div>
      <div class="card-body">
        <p class="form-text text-muted mb-3">
          Values are JSON: quote text (e.g. <code>"hello"</code>), use
          <code>true</code>/<code>false</code>, numbers, arrays, or objects.
        </p>

        <div
          v-for="(row, index) in rows"
          :key="index"
          data-testid="ext-row"
          class="row mb-2 align-items-start"
        >
          <div class="col-4">
            <input
              v-model="row.key"
              data-testid="ext-key"
              type="text"
              class="form-control form-control-sm"
              placeholder="extension key"
              :aria-label="`Extension key ${index + 1}`"
              @input="validateRow(row)"
            >
          </div>
          <div class="col-7">
            <textarea
              v-model="row.text"
              data-testid="ext-value"
              class="form-control form-control-sm"
              rows="1"
              placeholder="&quot;value&quot;"
              :aria-label="`Extension value ${index + 1}`"
              :aria-invalid="row.error ? 'true' : undefined"
              @input="validateRow(row)"
            />
            <div
              v-if="row.error"
              class="text-danger small"
              role="alert"
            >
              {{ row.error }}
            </div>
          </div>
          <div class="col-1">
            <button
              type="button"
              data-testid="ext-remove"
              class="btn btn-sm btn-outline-danger"
              :aria-label="`Remove extension ${index + 1}`"
              @click="removeRow(index)"
            >
              ×
            </button>
          </div>
        </div>

        <button
          type="button"
          data-testid="ext-add"
          class="btn btn-sm btn-outline-secondary"
          @click="addRow"
        >
          + Add extension
        </button>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <button
          type="button"
          data-testid="ext-cancel"
          class="btn btn-secondary"
          @click="cancel"
        >
          Cancel
        </button>
        <button
          type="button"
          data-testid="ext-apply"
          class="btn btn-primary"
          :disabled="!allValid"
          @click="apply"
        >
          Apply
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({})
  },
  show: {
    type: Boolean,
    default: false
  },
  entityLabel: {
    type: String,
    default: 'Item'
  },
  reservedKeys: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:modelValue', 'update:show']);

const rows = ref([]);

function syncRowsFromModel() {
  const src = props.modelValue && typeof props.modelValue === 'object' ? props.modelValue : {};
  rows.value = Object.entries(src).map(([key, value]) => ({
    key,
    text: JSON.stringify(value, null, 2),
    error: ''
  }));
}

watch(() => props.show, (val) => {
  if (val) syncRowsFromModel();
}, { immediate: true });

function validateRow(row) {
  const key = row.key.trim();
  if (key === '') {
    row.error = '';
    return;
  }
  if (props.reservedKeys.includes(key)) {
    row.error = 'Reserved key — managed elsewhere';
    return;
  }
  try {
    JSON.parse(row.text);
    row.error = '';
  } catch {
    row.error = 'Invalid JSON';
  }
}

const allValid = computed(() =>
  rows.value.every((r) => r.key.trim() === '' || r.error === '')
);

function addRow() {
  rows.value.push({ key: '', text: '', error: '' });
}

function removeRow(index) {
  rows.value.splice(index, 1);
}

function apply() {
  rows.value.forEach(validateRow);
  if (!allValid.value) return;
  const result = {};
  for (const r of rows.value) {
    const key = r.key.trim();
    if (!key || props.reservedKeys.includes(key)) continue;
    result[key] = JSON.parse(r.text);
  }
  emit('update:modelValue', result);
  emit('update:show', false);
}

function cancel() {
  emit('update:show', false);
}

function handleKeydown(event) {
  if (event.key === 'Escape' && props.show) cancel();
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
</script>

<style scoped>
.extensions-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1080;
}

.extensions-dialog {
  width: min(720px, 92vw);
  max-height: 85vh;
  overflow: auto;
}
</style>
