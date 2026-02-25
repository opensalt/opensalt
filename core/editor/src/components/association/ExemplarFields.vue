<template>
  <div class="exemplar-fields">
    <!-- Exemplar URL Field -->
    <div class="row mb-3">
      <label for="editAssociationFormExemplarUrl" class="col-sm-3 col-form-label required text-end">
        Exemplar URL *
      </label>
      <div class="col-sm-9">
        <input
          type="url"
          id="editAssociationFormExemplarUrl"
          class="form-control"
          :value="url"
          @input="$emit('update:url', $event.target.value)"
          placeholder="https://example.com/resource"
          required
        >
        <div v-if="urlError" class="text-danger small mt-1">
          {{ urlError }}
        </div>
        <div class="form-text">
          Enter the URL of the exemplar resource
        </div>
      </div>
    </div>

    <!-- Exemplar Description Field -->
    <div class="row mb-3">
      <label for="editAssociationFormExemplarDescription" class="col-sm-3 col-form-label text-end">
        Description
      </label>
      <div class="col-sm-9">
        <textarea
          id="editAssociationFormExemplarDescription"
          class="form-control"
          rows="3"
          :value="description"
          @input="$emit('update:description', $event.target.value)"
          placeholder="Optional description of the exemplar"
        ></textarea>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * ExemplarFields Component
 *
 * Form fields specific to exemplar associations.
 * Contains URL and description inputs with validation display.
 */

defineProps({
  /**
   * Exemplar URL value (v-model)
   */
  url: {
    type: String,
    default: ''
  },
  /**
   * Exemplar description value (v-model)
   */
  description: {
    type: String,
    default: ''
  },
  /**
   * Validation error message for URL
   */
  urlError: {
    type: String,
    default: ''
  }
});

defineEmits(['update:url', 'update:description']);
</script>

<style scoped>
.form-control:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
