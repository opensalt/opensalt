<template>
  <div
    v-if="show"
    class="modal d-block"
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="findMatchModalLabel"
  >
    <div
      class="modal-dialog modal-lg modal-dialog-scrollable"
      role="document"
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="findMatchModalLabel"
            class="modal-title"
          >
            <i class="bi bi-search me-1" />
            Find Match
          </h5>
          <button
            type="button"
            class="btn-close"
            aria-label="Close"
            @click="$emit('close')"
          />
        </div>
        <div class="modal-body">
          <div
            v-if="itemTitle"
            class="mb-3 p-2 border rounded bg-light"
          >
            <div
              v-if="itemHumanCodingScheme"
              class="text-muted small"
            >
              {{ itemHumanCodingScheme }}
            </div>
            <div>{{ itemTitle }}</div>
          </div>

          <div
            v-if="loading"
            class="text-center py-4"
          >
            <div
              class="spinner-border text-primary"
              role="status"
            >
              <span class="visually-hidden">Searching...</span>
            </div>
            <p class="mt-2 mb-0 text-muted">
              Searching for candidate matches...
            </p>
          </div>

          <div
            v-else-if="error"
            class="alert alert-danger mb-0"
          >
            {{ error }}
          </div>

          <div
            v-else-if="candidates.length === 0"
            class="text-center py-4 text-muted"
          >
            <i class="bi bi-emoji-frown fs-2 mb-2" />
            <p class="mb-0">
              No candidate matches found.
            </p>
          </div>

          <ul
            v-else
            class="list-group"
          >
            <li
              v-for="candidate in candidates"
              :key="candidate.item_identifier"
              class="list-group-item d-flex justify-content-between align-items-start gap-3"
            >
              <div class="me-auto">
                <div
                  v-if="candidate.human_coding_scheme"
                  class="text-muted small"
                >
                  {{ candidate.human_coding_scheme }}
                </div>
                <div>{{ candidate.full_statement || candidate.abbreviated_statement || candidate.item_identifier }}</div>
              </div>
              <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span
                  class="badge"
                  :class="badgeClass(candidate.relevance)"
                >{{ Math.round(candidate.relevance * 100) }}%</span>
                <button
                  type="button"
                  class="btn btn-sm btn-primary"
                  @click="$emit('pick', candidate)"
                >
                  Use this match
                </button>
              </div>
            </li>
          </ul>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-outline-secondary"
            @click="$emit('close')"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  itemTitle: { type: String, default: '' },
  itemHumanCodingScheme: { type: String, default: '' },
  candidates: { type: Array, default: () => [] },
  error: { type: String, default: null },
});

defineEmits(['close', 'pick']);

function badgeClass(relevance) {
  const pct = Math.round(relevance * 100);
  if (pct >= 90) return 'bg-success';
  if (pct >= 75) return 'bg-warning text-dark';
  return 'bg-danger';
}
</script>
