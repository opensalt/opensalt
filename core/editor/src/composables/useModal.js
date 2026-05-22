import { ref, onMounted, onUnmounted, nextTick } from 'vue';

/**
 * Composable for managing modal state and lifecycle.
 * Replaces the Bootstrap Modal JS pattern with a lightweight alternative.
 *
 * @param {Object} options - Configuration options
 * @param {string} options.id - Unique modal ID
 * @param {Function} options.onHidden - Callback when modal is hidden
 * @param {boolean} options.closeOnEscape - Whether to close on Escape key (default: true)
 * @param {boolean} options.closeOnBackdrop - Whether to close on backdrop click (default: true)
 * @returns {Object} Modal state and methods
 */
export function useModal(options = {}) {
  const {
    id = `modal-${Date.now()}`,
    onHidden = null,
    closeOnEscape = true,
    closeOnBackdrop = true
  } = options;

  const isOpen = ref(false);
  const modalElement = ref(null);

  function open() {
    isOpen.value = true;
    document.body.style.overflow = 'hidden';
  }

  function close() {
    isOpen.value = false;
    document.body.style.overflow = '';
    if (onHidden) {
      nextTick(() => onHidden());
    }
  }

  function toggle(state) {
    if (state) {
      open();
    } else {
      close();
    }
  }

  function handleKeydown(event) {
    if (event.key === 'Escape' && isOpen.value && closeOnEscape) {
      event.preventDefault();
      event.stopPropagation();
      close();
    }
  }

  function handleBackdropClick(event) {
    if (closeOnBackdrop && event.target === modalElement.value) {
      close();
    }
  }

  onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
  });

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    if (isOpen.value) {
      document.body.style.overflow = '';
    }
  });

  return {
    id,
    isOpen,
    modalElement,
    open,
    close,
    toggle,
    handleBackdropClick
  };
}
