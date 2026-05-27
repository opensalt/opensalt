<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-show="isOpen"
        :id="id"
        ref="modalRef"
        class="modal fade show"
        :class="{ 'd-block': isOpen }"
        tabindex="-1"
        role="dialog"
        :aria-labelledby="ariaLabelledby"
        :aria-modal="isOpen ? 'true' : undefined"
        :aria-hidden="!isOpen"
        @click.self="onBackdropClick"
        @keydown.escape.prevent.stop="onEscape"
        @keydown.tab="onTabTrap"
      >
        <div
          class="modal-dialog"
          :class="[sizeClass, modalDialogClasses]"
        >
          <div class="modal-content">
            <div class="modal-header">
              <h5
                v-if="title"
                :id="ariaLabelledby"
                class="modal-title"
              >
                {{ title }}
              </h5>
              <slot
                name="header"
                :close="close"
              />
              <button
                type="button"
                class="btn-close"
                :aria-label="closeLabel"
                @click="close"
              />
            </div>
            <div class="modal-body">
              <slot />
            </div>
            <div
              v-if="$slots.footer"
              class="modal-footer"
            >
              <slot
                name="footer"
                :close="close"
              />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch, nextTick, onMounted, onUnmounted } from 'vue';

const FOCUSABLE_SELECTOR = [
  'a[href]',
  'button:not([disabled])',
  'textarea:not([disabled])',
  'input:not([disabled])',
  'select:not([disabled])',
  '[tabindex]:not([tabindex="-1"])'
].join(', ');

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  id: {
    type: String,
    default: () => `modal-${Date.now()}`
  },
  title: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: ''
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  },
  closeOnEscape: {
    type: Boolean,
    default: true
  },
  closeLabel: {
    type: String,
    default: 'Close'
  },
  ariaLabelledby: {
    type: String,
    default: null
  },
  modalDialogClasses: {
    type: [String, Array, Object],
    default: ''
  }
});

const emit = defineEmits(['update:isOpen', 'hidden', 'shown']);

const modalRef = ref(null);
const previousFocusElement = ref(null);

const sizeClass = computed(() => {
  if (!props.size) return '';
  return `modal-${props.size}`;
});

function close() {
  emit('update:isOpen', false);
  emit('hidden');
  document.body.style.overflow = '';
}

function onBackdropClick(event) {
  if (props.closeOnBackdrop && event.target === modalRef.value) {
    close();
  }
}

function onEscape() {
  if (props.closeOnEscape) {
    close();
  }
}

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

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    previousFocusElement.value = document.activeElement;
    document.body.style.overflow = 'hidden';
    emit('shown');
    nextTick(() => focusFirstElement());
  } else {
    document.body.style.overflow = '';
    if (previousFocusElement.value) {
      previousFocusElement.value.focus();
      previousFocusElement.value = null;
    }
  }
});

onMounted(() => {
  if (props.isOpen) {
    previousFocusElement.value = document.activeElement;
    document.body.style.overflow = 'hidden';
    nextTick(() => {
      if (props.isOpen) {
        focusFirstElement();
      }
    });
  }
});

onUnmounted(() => {
  document.body.style.overflow = '';
  if (previousFocusElement.value) {
    previousFocusElement.value.focus();
    previousFocusElement.value = null;
  }
});
</script>

<style>
.modal {
  display: none;
}
.modal.d-block {
  display: block;
}
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.15s linear;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
