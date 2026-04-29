<template>
  <div class="association-editor card">
    <div class="card-header">
      <i class="bi bi-link-45deg" /> Create Association
    </div>
    <div class="card-body">
      <form @submit.prevent="onSubmit">
        <div class="mb-2">
          <label class="form-label">Origin Item</label>
          <div class="form-control-plaintext">
            {{ origin?.title || origin?.identifier }}
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Destination Item</label>
          <div class="form-control-plaintext">
            {{ dest?.title || dest?.identifier }}
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Association Type</label>
          <Multiselect
            v-model="type"
            :options="typeOptions"
            placeholder="Select type"
          />
        </div>
        <div
          v-if="type === 'other'"
          class="mb-2"
        >
          <label class="form-label">Custom Association Type</label>
          <input
            v-model="customType"
            class="form-control"
            placeholder="ext:custom.type"
          >
          <div
            v-if="!isValidCustomType"
            class="text-danger"
          >
            Custom type must start with "ext:" and match the regex /^ext:[a-zA-Z0-9._-]+$/.
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Annotation</label>
          <input
            v-model="annotation"
            class="form-control"
          >
        </div>
        <div class="mb-2">
          <label class="form-label">Association Group</label>
          <Multiselect
            v-model="groupId"
            :options="groupOptions"
            label="title"
            track-by="id"
            placeholder="Select group"
          />
        </div>
        <button
          :disabled="!isValidCustomType"
          class="btn btn-primary"
          type="submit"
        >
          Create
        </button>
      </form>
    </div>
    <div class="card-footer">
      <span
        v-if="groupId"
        class="badge bg-dark bg-opacity-75"
      >{{ groupTitle }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import Multiselect from 'vue-multiselect';
const props = defineProps({
  origin: {
    type: Object,
    default: null
  },
  dest: {
    type: Object,
    default: null
  },
  groups: {
    type: Array,
    default: () => []
  }
});
const emit = defineEmits(['create']);
const typeOptions = [
  { value: 'isRelatedTo', label: 'Is Related To' },
  { value: 'exactMatchOf', label: 'Exact Match Of' },
  { value: 'isPartOf', label: 'Is Part Of' },
  { value: 'hasSkillLevel', label: 'Has Skill Level' },
  { value: 'isPeerOf', label: 'Is Peer Of' },
  { value: 'exemplar', label: 'Exemplar' },
  { value: 'isTranslationOf', label: 'Is Translation Of' },
  { value: 'isChildOf', label: 'Is Child Of' },
  { value: 'replacedBy', label: 'Replaced By' },
  { value: 'precedes', label: 'Precedes' },
  { value: 'other', label: 'Other' }
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

const customType = ref('');

const isValidCustomType = computed(() => {
  if (type.value !== 'other') return true;
  const value = customType.value.trim();
  return value.startsWith('ext:') && /^ext:[a-zA-Z0-9._-]+$/.test(value);
});
function onSubmit() {
  const finalType = type.value === 'other' ? customType.value.trim() : type.value;
  emit('create', {
    origin: props.origin,
    dest: props.dest,
    type: finalType,
    annotation: annotation.value,
    groupId: groupId.value || null
  });
}
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>
