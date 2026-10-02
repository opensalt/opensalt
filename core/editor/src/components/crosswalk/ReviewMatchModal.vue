<template>
  <BaseModal
    id="reviewMatchModal"
    :is-open="show"
    title=""
    size="xl"
    aria-labelledby="reviewMatchModalLabel"
    modal-dialog-classes="review-match-modal-dialog"
    @update:is-open="(val) => $emit('update:show', val)"
    @hidden="handleHidden"
  >
    <template #header>
      <div class="review-match-header d-flex flex-grow-1 align-items-center flex-wrap gap-2 me-2">
        <h5
          id="reviewMatchModalLabel"
          class="modal-title mb-0"
        >
          Review Match
        </h5>
        <div
          v-if="pair"
          class="d-flex flex-wrap align-items-center gap-2 ms-md-auto"
        >
          <span
            v-if="hasConfidence"
            class="badge"
            :class="confidenceBadgeClass"
            title="Suggested confidence"
          >{{ confidencePercentage }}% confidence</span>
          <span
            v-else-if="isCrosswalkSuggestion"
            class="badge bg-secondary"
            title="Suggested confidence"
          >No confidence score</span>
          <span
            class="badge"
            :class="statusBadgeClass"
            title="Review status"
          >{{ statusLabel }}</span>
        </div>
      </div>
    </template>

    <div
      v-if="pair"
      class="review-match-content"
    >
      <div
        v-if="displayError"
        class="alert alert-danger mb-3"
        role="alert"
      >
        {{ displayError }}
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <strong class="small text-muted text-uppercase">Origin</strong>
            <a
              v-if="originItemLink"
              :href="originItemLink"
              target="_blank"
              rel="noopener noreferrer"
              class="small"
            >
              Open in editor <i class="bi bi-box-arrow-up-right" />
            </a>
          </div>
          <div
            v-if="pair.originItem?.humanCodingScheme"
            class="badge bg-secondary mb-2"
          >
            {{ pair.originItem.humanCodingScheme }}
          </div>
          <div class="statement-panel border rounded p-3 bg-light">
            <div
              class="markdown-body statement-markdown"
              v-html="originHtml"
            />
          </div>
        </div>
        <div class="col-md-6">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <strong class="small text-muted text-uppercase">Destination</strong>
            <a
              v-if="destItemLink"
              :href="destItemLink"
              target="_blank"
              rel="noopener noreferrer"
              class="small"
            >
              Open in editor <i class="bi bi-box-arrow-up-right" />
            </a>
          </div>
          <div
            v-if="pair.destinationItem?.humanCodingScheme"
            class="badge bg-secondary mb-2"
          >
            {{ pair.destinationItem.humanCodingScheme }}
          </div>
          <div class="statement-panel border rounded p-3 bg-light">
            <div
              class="markdown-body statement-markdown"
              v-html="destHtml"
            />
          </div>
        </div>
      </div>

      <hr class="mb-3">

      <div class="form-horizontal">
        <AssociationTypeSelector
          v-model="formData.type"
          v-model:custom-type="customType"
          :is-disabled="isTypeDropdownDisabled"
          :is-valid-custom-type="isValidCustomType"
          :types="prioritizedTypes"
          @change="onTypeChange"
        />

        <div class="row mb-3">
          <label
            for="reviewMatchAnnotation"
            class="col-sm-3 col-form-label text-end"
          >
            Annotation
          </label>
          <div class="col-sm-9">
            <textarea
              id="reviewMatchAnnotation"
              v-model="formData.annotation"
              class="form-control"
              rows="3"
              placeholder="Optional annotation or description for this association"
            />
          </div>
        </div>

        <div
          v-if="showGroupSelector && filteredGroups.length > 0"
          class="row mb-3"
        >
          <label
            for="reviewMatchGroup"
            class="col-sm-3 col-form-label text-end"
          >
            Association Group
          </label>
          <div class="col-sm-9">
            <select
              id="reviewMatchGroup"
              v-model="formData.groupId"
              class="form-select"
            >
              <option value="default">
                None
              </option>
              <option
                v-for="group in filteredGroups"
                :key="group.id"
                :value="group.id"
              >
                {{ group.title }}
              </option>
            </select>
          </div>
        </div>

        <AdditionalFields
          v-model="additionalFields"
          :field-definitions="assocFieldDefinitions"
        />

        <div class="row mb-3">
          <div class="col-sm-3 col-form-label text-end">
            Extensions
          </div>
          <div class="col-sm-9">
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm"
              @click="extensionsEditor.open()"
            >
              Edit extensions
            </button>
            <small class="text-muted d-block">View or edit proprietary extension values for this association.</small>
          </div>
        </div>

        <ExtensionsEditor
          v-model="extensionsEditor.editable.value"
          v-model:show="extensionsEditor.show.value"
          entity-label="Association"
          :reserved-keys="extensionsEditor.reservedKeys"
        />
      </div>
    </div>

    <template #footer>
      <button
        type="button"
        class="btn btn-secondary"
        :disabled="acting"
        @click="closeModal"
      >
        Cancel
      </button>
      <template v-if="isCrosswalkSuggestion && !isApproved">
        <button
          type="button"
          class="btn btn-outline-danger"
          :disabled="acting || !isFormValid"
          @click="onReject"
        >
          Reject
        </button>
        <button
          type="button"
          class="btn btn-success"
          :disabled="acting || !isFormValid"
          @click="onApprove"
        >
          <span
            v-if="acting"
            class="spinner-border spinner-border-sm me-2"
            role="status"
          />
          Approve
        </button>
      </template>
      <template v-if="(isCrosswalkSuggestion && isApproved) || !isCrosswalkSuggestion">
        <button
          type="button"
          class="btn btn-outline-danger"
          :disabled="acting"
          @click="onDelete"
        >
          Delete
        </button>
      </template>
      <button
        v-if="!isCrosswalkSuggestion || isApproved"
        type="button"
        class="btn btn-primary"
        :disabled="acting || !isFormValid"
        @click="onSave"
      >
        <span
          v-if="acting"
          class="spinner-border spinner-border-sm me-2"
          role="status"
        />
        Save
      </button>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import BaseModal from '../shared/BaseModal.vue';
import AssociationTypeSelector from '../association/AssociationTypeSelector.vue';
import AdditionalFields from '../tree/fields/AdditionalFields.vue';
import ExtensionsEditor from '../shared/ExtensionsEditor.vue';
import { useAssociationForm } from '../../composables/useAssociationForm';
import { useAdditionalFields } from '../../composables/useAdditionalFields.js';
import { useExtensionsEditor } from '../../composables/useExtensionsEditor.js';
import { getOrderedAssociationTypes } from '../../composables/useAssociationTypePriority';
import { renderMarkdown } from '@/utils/markdownRenderer.js';

const props = defineProps({
  show: { type: Boolean, default: false },
  pair: { type: Object, default: null },
  availableGroups: { type: Array, default: () => [] },
  originFrameworkId: { type: String, default: null },
  destinationFrameworkId: { type: String, default: null },
  acting: { type: Boolean, default: false },
  saveError: { type: String, default: '' },
});

const emit = defineEmits(['approve', 'reject', 'delete', 'save', 'hidden', 'update:show']);

const error = ref('');
const originHtml = ref('');
const destHtml = ref('');
const additionalFields = ref({});

const associationRef = computed(() => props.pair?.association ?? null);

const displayError = computed(() => props.saveError || error.value);

const { fieldDefinitions: assocFieldDefinitions, fetchFields: fetchAssocFields } = useAdditionalFields();

const extensionsEditor = useExtensionsEditor({
  scope: 'association',
  getRawExtensions: () => associationRef.value?.extensions,
});

const {
  formData,
  customType,
  exemplarUrlError,
  isTypeDropdownDisabled,
  isValidCustomType,
  showGroupSelector,
  isFormValid,
  loadAssociationData,
  onTypeChange,
  validateExemplarUrl,
  updateAssociationData,
  getFinalType,
} = useAssociationForm({
  mode: ref('edit'),
  initialType: ref(''),
  association: associationRef,
  currentItem: ref(null),
  destinationItem: computed(() => props.pair?.destinationItem ?? null),
  isReversed: ref(false),
});

const filteredGroups = computed(() => {
  if (!props.availableGroups) return [];
  return props.availableGroups.filter(g =>
    g.id !== 'default' &&
    g.id !== 'all' &&
    g.title !== 'Default' &&
    g.title !== 'All'
  );
});

const prioritizedTypes = computed(() => {
  const source = props.pair?.originItem;
  const target = props.pair?.destinationItem;
  return getOrderedAssociationTypes(source, target, {
    isEditing: true,
    currentType: formData.type === 'other' ? customType.value : formData.type,
    isExemplarTarget: false,
    allowIsChildOf: false,
  }).filter((opt) => opt.value !== 'exemplar');
});

const isCrosswalkSuggestion = computed(() => props.pair?.isCrosswalkSuggestion === true);

const originStatement = computed(() => {
  const item = props.pair?.originItem;
  return item?.fullStatement || item?.title || item?.abbreviatedStatement || 'Unknown item';
});

const destStatement = computed(() => {
  const item = props.pair?.destinationItem;
  return item?.fullStatement || item?.title || item?.abbreviatedStatement || 'Unknown item';
});

function refreshStatementHtml() {
  originHtml.value = renderMarkdown(originStatement.value);
  destHtml.value = renderMarkdown(destStatement.value);
}

watch(
  () => [props.show, originStatement.value, destStatement.value],
  ([visible]) => {
    if (visible) {
      refreshStatementHtml();
    }
  }
);

watch(() => props.show, async (visible) => {
  if (visible && props.pair) {
    error.value = '';
    loadAssociationData();
    extensionsEditor.seed();
    additionalFields.value = props.pair.association?.additionalFields || {};
    await fetchAssocFields('association');
    refreshStatementHtml();
  }
}, { immediate: true });

watch(associationRef, (assoc) => {
  if (assoc && props.show) {
    loadAssociationData();
    extensionsEditor.seed();
    additionalFields.value = assoc.additionalFields || {};
  }
});

const hasConfidence = computed(() => {
  const c = props.pair?.confidence;
  return c !== null && c !== undefined;
});

const confidencePercentage = computed(() => Math.round((props.pair?.confidence || 0) * 100));

const confidenceBadgeClass = computed(() => {
  if (confidencePercentage.value >= 90) return 'bg-success';
  if (confidencePercentage.value >= 75) return 'bg-warning text-dark';
  return 'bg-danger';
});

const statusLabel = computed(() => props.pair?.status || 'pending');
const isApproved = computed(() => statusLabel.value === 'approved');

const statusBadgeClass = computed(() => {
  const map = { pending: 'bg-secondary', approved: 'bg-success', rejected: 'bg-danger', modified: 'bg-info', existing: 'bg-secondary' };
  return map[statusLabel.value] || 'bg-secondary';
});

const originItemLink = computed(() => {
  const id = props.pair?.originItem?.identifier;
  if (!id || !props.originFrameworkId) return null;
  return `/editor/${props.originFrameworkId}/${id}`;
});

const destItemLink = computed(() => {
  const id = props.pair?.destinationItem?.identifier;
  if (!id || !props.destinationFrameworkId) return null;
  return `/editor/${props.destinationFrameworkId}/${id}`;
});

function buildUpdatedAssociation() {
  if (!formData.type) {
    error.value = 'Please select an association type';
    return null;
  }
  if (!validateExemplarUrl()) {
    error.value = exemplarUrlError.value || 'Please fix validation errors before saving.';
    return null;
  }

  const finalType = getFinalType();
  const updated = updateAssociationData(finalType);
  updated.additionalFields = additionalFields.value;
  updated.extensions = extensionsEditor.buildExtensions();
  return updated;
}

function closeModal() {
  emit('update:show', false);
  emit('hidden');
}

function handleHidden() {
  emit('update:show', false);
  emit('hidden');
}

function onSave() {
  error.value = '';
  const updated = buildUpdatedAssociation();
  if (!updated) return;
  emit('save', updated);
}

function onApprove() {
  error.value = '';
  const updated = buildUpdatedAssociation();
  if (!updated) return;
  emit('approve', { id: props.pair?.id, updated });
}

function onReject() {
  emit('reject', props.pair?.id);
}

function onDelete() {
  emit('delete', props.pair?.association);
}
</script>

<style scoped>
.review-match-header {
  min-width: 0;
}

.statement-panel {
  max-height: min(28vh, 240px);
  overflow-y: auto;
  overflow-x: hidden;
}

.statement-markdown {
  font-size: 0.95rem;
}

.statement-markdown :deep(:last-child) {
  margin-bottom: 0;
}

:deep(.review-match-modal-dialog) {
  max-width: min(96vw, 1100px);
}
</style>
