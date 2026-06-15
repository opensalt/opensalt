<template>
  <BaseModal
    id="rejectMatchModal"
    :is-open="show"
    title="Reject and Remove Match"
    aria-labelledby="rejectMatchModalLabel"
    @update:is-open="(val) => $emit('update:show', val)"
    @hidden="handleHidden"
  >
    <div
      v-if="error"
      class="alert alert-danger mb-3"
      role="alert"
    >
      {{ error }}
    </div>

    <div
      class="alert alert-warning"
      role="alert"
    >
      <strong>This will permanently remove the association from the framework.</strong>
      This action cannot be undone.
    </div>

    <p v-if="bulkCount > 0">
      Are you sure you want to reject and remove
      <strong>{{ bulkCount }}</strong> selected {{ bulkCount === 1 ? 'match' : 'matches' }}?
    </p>
    <p v-else>
      Are you sure you want to reject and remove this match?
    </p>

    <div
      v-if="pair"
      class="mt-3"
    >
      <div class="card">
        <div class="card-body">
          <div class="mb-3">
            <strong class="d-block small text-muted">Origin</strong>
            <span
              v-if="pair.originItem?.humanCodingScheme"
              class="badge bg-secondary me-2"
            >{{ pair.originItem.humanCodingScheme }}</span>
            <span>{{ originTitle }}</span>
          </div>
          <div class="mb-3">
            <strong class="d-block small text-muted">Destination</strong>
            <span
              v-if="pair.destinationItem?.humanCodingScheme"
              class="badge bg-secondary me-2"
            >{{ pair.destinationItem.humanCodingScheme }}</span>
            <span>{{ destinationTitle }}</span>
          </div>
          <div class="mb-0">
            <strong class="d-block small text-muted">Confidence</strong>
            <span class="badge bg-info">{{ Math.round((pair.confidence || 0) * 100) }}%</span>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <button
        type="button"
        class="btn btn-secondary"
        @click="closeModal"
      >
        Cancel
      </button>
      <button
        type="button"
        class="btn btn-danger"
        :disabled="deleting"
        @click="confirmReject"
      >
        <span
          v-if="deleting"
          class="spinner-border spinner-border-sm me-2"
          role="status"
          aria-hidden="true"
        />
        Reject &amp; Remove
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import BaseModal from '../shared/BaseModal.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  pair: { type: Object, default: null },
  bulkCount: { type: Number, default: 0 },
  deleting: { type: Boolean, default: false },
  error: { type: String, default: '' },
});

const emit = defineEmits(['confirm', 'cancel', 'hidden', 'update:show']);

const localDeleting = ref(false);

const originTitle = computed(() => {
  const t = props.pair?.originItem?.fullStatement || props.pair?.originItem?.title || 'Unknown item';
  return t.length > 100 ? t.substring(0, 100) + '...' : t;
});

const destinationTitle = computed(() => {
  const t = props.pair?.destinationItem?.fullStatement || props.pair?.destinationItem?.title || 'Unknown item';
  return t.length > 100 ? t.substring(0, 100) + '...' : t;
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    localDeleting.value = false;
  }
});

function closeModal() {
  emit('update:show', false);
  emit('cancel');
  emit('hidden');
}

function confirmReject() {
  emit('confirm', props.pair);
}

function handleHidden() {
  emit('update:show', false);
  emit('hidden');
}
</script>
