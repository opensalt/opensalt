<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="isOpen"
        :id="id"
        ref="modalRef"
        class="modal fade show"
        :class="{ 'd-block': isOpen }"
        tabindex="-1"
        role="dialog"
        :aria-labelledby="ariaLabelledby"
        :aria-modal="isOpen ? 'true' : undefined"
        aria-hidden="true"
        @click.self="onBackdropClick"
        @keydown.escape.prevent.stop="onEscape"
      >
        <div class="modal-dialog" :class="[sizeClass, modalDialogClasses]">
          <div class="modal-content">
            <div class="modal-header">
              <h5 v-if="title" class="modal-title" :id="ariaLabelledby">{{ title }}</h5>
              <slot name="header" :close="close"></slot>
              <button
                type="button"
                class="btn-close"
                :aria-label="closeLabel"
                @click="close"
              ></button>
            </div>
            <div class="modal-body">
              <slot></slot>
            </div>
            <div v-if="$slots.footer" class="modal-footer">
              <slot name="footer" :close="close"></slot>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';

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

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden';
    emit('shown');
  } else {
    document.body.style.overflow = '';
  }
});

onMounted(() => {
  if (props.isOpen) {
    document.body.style.overflow = 'hidden';
  }
});

onUnmounted(() => {
  document.body.style.overflow = '';
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
