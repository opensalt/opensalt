<template>
  <!-- Actions - Only available for editable items -->
  <div
    v-if="canEditItem"
    id="itemOptions"
    class="card mt-0 border-0"
  >
    <div class="card-body py-0 ms-auto">
      <div class="d-flex gap-2">
        <div
          v-if="!isAdopted"
          class="btn-group"
        >
          <button
            type="button"
            class="btn btn-outline-primary"
            @click="$emit('add-child', 'general')"
          >
            <i
              class="bi bi-plus-circle"
              aria-hidden="true"
            /> Add Child Item
          </button>
          <button
            type="button"
            class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            <span class="visually-hidden">Toggle Dropdown</span>
          </button>
          <ul class="dropdown-menu">
            <li
              v-for="type in availableTypes"
              :key="type"
            >
              <a
                class="dropdown-item"
                href="#"
                :aria-label="`Add ${getTypeLabel(type)}`"
                @click.prevent="$emit('add-child', type)"
              >
                Add {{ getTypeLabel(type) }}
              </a>
            </li>
          </ul>
        </div>
        <button
          id="addExemplarBtn"
          type="button"
          class="btn btn-outline-secondary"
          @click="$emit('add-exemplar')"
        >
          <i
            class="bi bi-link-45deg"
            aria-hidden="true"
          /> Add Exemplar
        </button>
      </div>
    </div>
  </div>

  <!-- Actions Note for Viewed Framework Items -->
  <div
    v-else-if="isItemFromViewedFramework && !isReadOnly"
    class="card mt-3"
  >
    <div class="card-header">
      <h6 class="mb-0">
        Actions
      </h6>
    </div>
    <div class="card-body">
      <p class="text-muted mb-0">
        <i
          class="bi bi-info-circle me-2"
          aria-hidden="true"
        />
        Actions are not available for viewed framework items. Switch to the edited framework to add child items or exemplars.
      </p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  canEditItem: { type: Boolean, default: false },
  isAdopted: { type: Boolean, default: false },
  isItemFromViewedFramework: { type: Boolean, default: false },
  isReadOnly: { type: Boolean, default: false },
  availableTypes: { type: Array, default: () => [] },
});

defineEmits(['add-child', 'add-exemplar']);

function getTypeLabel(type) {
  const labels = {
    general: 'General Item',
    assessment: 'Assessment',
    course: 'Course',
    credential: 'Credential',
    job: 'Job',
    organization: 'Organization',
    public_key: 'Public Key',
    identifier: 'Identifier',
  };
  return labels[type] || type;
}
</script>
