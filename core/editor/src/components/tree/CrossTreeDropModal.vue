<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue';

const FOCUSABLE_SELECTOR = [
  'a[href]',
  'button:not([disabled])',
  'textarea:not([disabled])',
  'input:not([disabled])',
  'select:not([disabled])',
  '[tabindex]:not([tabindex="-1"])'
].join(', ');

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  sourceItem: {
    type: Object,
    default: null
  },
  targetItem: {
    type: Object,
    default: null
  },
});

const emit = defineEmits(['close', 'copy', 'associate']);

const modalRef = ref(null);
const previousFocusElement = ref(null);

function getFocusableElements() {
  if (!modalRef.value) return [];
  return [...modalRef.value.querySelectorAll(FOCUSABLE_SELECTOR)];
}

function focusFirstElement() {
  const focusable = getFocusableElements();
  if (focusable.length > 0) {
    focusable[0].focus();
  }
}

function onTabTrap(event) {
  if (!modalRef.value) return;
  const focusable = getFocusableElements();
  if (focusable.length === 0) return;

  const firstFocusable = focusable[0];
  const lastFocusable = focusable[focusable.length - 1];

  if (event.shiftKey) {
    if (document.activeElement === firstFocusable) {
      event.preventDefault();
      lastFocusable.focus();
    }
  } else {
    if (document.activeElement === lastFocusable) {
      event.preventDefault();
      firstFocusable.focus();
    }
  }
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    previousFocusElement.value = document.activeElement;
    nextTick(() => focusFirstElement());
  } else {
    if (previousFocusElement.value) {
      previousFocusElement.value.focus();
      previousFocusElement.value = null;
    }
  }
});

onMounted(() => {
  if (props.show) {
    previousFocusElement.value = document.activeElement;
    nextTick(() => {
      if (props.show) {
        focusFirstElement();
      }
    });
  }
});

onUnmounted(() => {
  if (previousFocusElement.value) {
    previousFocusElement.value.focus();
    previousFocusElement.value = null;
  }
});

function onCopy() {
    emit('copy');
}

function onAssociate() {
    emit('associate');
}

function onClose() {
    emit('close');
}
</script>

<template>
  <div
    v-if="show"
    ref="modalRef"
    class="modal fade"
    :class="{ show: show, 'd-block': show }"
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="crossTreeDropModalLabel"
    style="background-color: rgba(0,0,0,0.5);"
    @keydown.escape.prevent.stop="onClose"
    @keydown.tab="onTabTrap"
  >
    <div
      class="modal-dialog modal-dialog-centered"
      role="document"
    >
      <div class="modal-content">
        <div class="modal-header">
          <h5
            id="crossTreeDropModalLabel"
            class="modal-title"
          >
            Action Required
          </h5>
          <button
            type="button"
            class="btn-close"
            aria-label="Close"
            @click="onClose"
          />
        </div>
        <div class="modal-body">
          <p>
            You dragged local item <strong>{{ sourceItem?.title }}</strong> onto
            <strong>{{ targetItem?.title || 'Root' }}</strong>.
          </p>
          <p>What would you like to do?</p>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            @click="onClose"
          >
            Cancel
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="onAssociate"
          >
            Associate
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="onCopy"
          >
            Copy Item
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
