<template>
  <div class="destination-fields">
    <div class="row mb-3">
      <label for="editAssociationFormDestinationUri" class="col-sm-3 col-form-label required text-end">
        {{ sideLabel }} URI *
      </label>
      <div class="col-sm-9">
        <input
          type="url"
          id="editAssociationFormDestinationUri"
          class="form-control"
          :value="uri"
          @input="$emit('update:uri', $event.target.value)"
          placeholder="https://example.com/item"
          required
        >
        <div class="form-text">
          Enter the full URI of the {{ sideLabelLower }} item
        </div>
      </div>
    </div>

    <div class="row mb-3">
      <label for="editAssociationFormDestinationIdentifier" class="col-sm-3 col-form-label text-end">
        {{ sideLabel }} Identifier
      </label>
      <div class="col-sm-9">
        <input
          type="text"
          id="editAssociationFormDestinationIdentifier"
          class="form-control"
          :value="identifier"
          @input="$emit('update:identifier', $event.target.value)"
          placeholder="e.g. 12345678-1234-1234-1234-123456789012"
        >
        <div class="form-text">
          Optional identifier (UUID) for the {{ sideLabelLower }} item
        </div>
      </div>
    </div>
    
    <div class="row mb-3">
      <label for="editAssociationFormDestinationTitle" class="col-sm-3 col-form-label text-end">
        {{ sideLabel }} Title
      </label>
      <div class="col-sm-9">
        <input
          type="text"
          id="editAssociationFormDestinationTitle"
          class="form-control"
          :value="title"
          @input="$emit('update:title', $event.target.value)"
          :placeholder="`Title of the ${sideLabelLower} item`"
        >
      </div>
    </div>

    <div class="row mb-3">
      <label for="editAssociationFormDestinationTargetType" class="col-sm-3 col-form-label text-end">
        Target Type
      </label>
      <div class="col-sm-9">
        <input
          type="text"
          id="editAssociationFormDestinationTargetType"
          class="form-control"
          :value="targetType"
          @input="$emit('update:targetType', $event.target.value)"
          placeholder="e.g. CASE"
        >
        <div class="form-text">
          Target Type is usually 'CASE' for CASE items. Leave blank for unknown/other target types.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

/**
 * DestinationFields Component
 *
 * Form fields for manually entering a destination (or origin) when one is not provided.
 */

const props = defineProps({
  uri: {
    type: String,
    default: ''
  },
  identifier: {
    type: String,
    default: ''
  },
  title: {
    type: String,
    default: ''
  },
  targetType: {
    type: String,
    default: ''
  },
  isOrigin: {
    type: Boolean,
    default: false
  }
});

const sideLabel = computed(() => props.isOrigin ? 'Origin' : 'Destination');
const sideLabelLower = computed(() => props.isOrigin ? 'origin' : 'destination');

defineEmits(['update:uri', 'update:identifier', 'update:title', 'update:targetType']);
</script>

<style scoped>
.form-control:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
