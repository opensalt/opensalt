import { ref } from 'vue';

/**
 * Composable for managing modal state
 * Provides a clean way to handle multiple modals
 */
export function useModalManager() {
  // Modal states
  const modals = ref({});

  /**
   * Initialize a modal with a given name
   */
  function initModal(name, initialState = false) {
    if (!modals.value[name]) {
      modals.value[name] = ref(initialState);
    }
    return modals.value[name];
  }

  /**
   * Open a modal
   */
  function openModal(name) {
    if (modals.value[name]) {
      modals.value[name].value = true;
    }
  }

  /**
   * Close a modal
   */
  function closeModal(name) {
    if (modals.value[name]) {
      modals.value[name].value = false;
    }
  }

  /**
   * Toggle a modal
   */
  function toggleModal(name) {
    if (modals.value[name]) {
      modals.value[name].value = !modals.value[name].value;
    }
  }

  /**
   * Close all modals
   */
  function closeAllModals() {
    Object.keys(modals.value).forEach(name => {
      if (modals.value[name]) {
        modals.value[name].value = false;
      }
    });
  }

  /**
   * Check if a modal is open
   */
  function isModalOpen(name) {
    return modals.value[name]?.value || false;
  }

  return {
    modals,
    initModal,
    openModal,
    closeModal,
    toggleModal,
    closeAllModals,
    isModalOpen
  };
}

/**
 * Composable for managing form state
 */
export function useFormManager(initialData = {}) {
  const formData = ref({ ...initialData });
  const errors = ref({});
  const isDirty = ref(false);
  const isSubmitting = ref(false);

  /**
   * Reset form to initial state
   */
  function resetForm() {
    formData.value = { ...initialData };
    errors.value = {};
    isDirty.value = false;
  }

  /**
   * Update form data
   */
  function updateField(field, value) {
    formData.value[field] = value;
    isDirty.value = true;
    // Clear error for this field
    if (errors.value[field]) {
      delete errors.value[field];
    }
  }

  /**
   * Set multiple fields at once
   */
  function setFields(data) {
    formData.value = { ...formData.value, ...data };
    isDirty.value = true;
  }

  /**
   * Add an error for a field
   */
  function setError(field, message) {
    errors.value[field] = message;
  }

  /**
   * Set multiple errors
   */
  function setErrors(errorData) {
    errors.value = { ...errorData };
  }

  /**
   * Clear all errors
   */
  function clearErrors() {
    errors.value = {};
  }

  /**
   * Set submitting state
   */
  function setSubmitting(state) {
    isSubmitting.value = state;
  }

  return {
    formData,
    errors,
    isDirty,
    isSubmitting,
    resetForm,
    updateField,
    setFields,
    setError,
    setErrors,
    clearErrors,
    setSubmitting
  };
}
