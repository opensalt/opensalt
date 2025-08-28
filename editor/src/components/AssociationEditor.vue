<template>
  <div class="association-editor card">
    <div class="card-header">
      <i class="bi bi-link-45deg"></i> Create Association
    </div>
    <div class="card-body">
      <form @submit.prevent="onSubmit">
        <div class="mb-2">
          <label class="form-label">Origin Item</label>
          <div class="form-control-plaintext">{{ origin?.title || origin?.identifier }}</div>
        </div>
        <div class="mb-2">
          <label class="form-label">Destination Item</label>
          <div class="form-control-plaintext">{{ dest?.title || dest?.identifier }}</div>
        </div>
        <div class="mb-2">
          <label class="form-label">Association Type</label>
          <Multiselect v-model="type" :options="typeOptions" placeholder="Select type" />
        </div>
        <div class="mb-2">
          <label class="form-label">Annotation</label>
          <input v-model="annotation" class="form-control" />
        </div>
        <div class="mb-2">
          <label class="form-label">Association Group</label>
          <Multiselect v-model="groupId" :options="groupOptions" label="title" track-by="id" placeholder="Select group" />
        </div>
        <button class="btn btn-primary" type="submit">Create</button>
      </form>
    </div>
    <div class="card-footer">
      <span v-if="groupId" class="badge bg-dark bg-opacity-75">{{ groupTitle }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import Multiselect from 'vue-multiselect';
const props = defineProps({
  origin: Object,
  dest: Object,
  groups: {
    type: Array,
    default: () => []
  }
});
const emit = defineEmits(['create']);
const typeOptions = [
  { value: 'isRelatedTo', label: 'isRelatedTo' },
  { value: 'precedes', label: 'precedes' },
  { value: 'exemplar', label: 'exemplar' }
];
const type = ref(typeOptions[0].value);
const annotation = ref('');
const groupOptions = computed(() => [
  { id: '', title: 'Default' },
  ...props.groups
]);
const groupId = ref('');
const groupTitle = computed(() => {
  if (!groupId.value) return 'Default';
  const g = props.groups.find(g => g.id === groupId.value);
  return g ? g.title : groupId.value;
});
function onSubmit() {
  emit('create', {
    origin: props.origin,
    dest: props.dest,
    type: type.value,
    annotation: annotation.value,
    groupId: groupId.value || null
  });
}
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style> 