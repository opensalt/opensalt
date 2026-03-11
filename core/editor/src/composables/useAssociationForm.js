import { ref, reactive, computed, toValue } from 'vue';
import { useFilterStore } from '../stores/filterStore';
import { getItemKind, MEANINGFUL_ASSOCIATIONS, MEANINGFUL_EXEMPLAR_ASSOCIATIONS } from './useAssociationTypePriority';

/**
 * Association types available for selection
 */
export const ASSOCIATION_TYPES = [
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

/**
 * Composable for managing association form state and validation
 *
 * This composable handles:
 * 1. Form data state management
 * 2. Form validation
 * 3. Custom type validation
 * 4. Exemplar-specific validation
 * 5. Association data creation/update
 *
 * @param {Object} options - Configuration options
 * @param {import('vue').Ref<string>|string} options.mode - 'add' or 'edit'
 * @param {import('vue').Ref<string>|string} options.initialType - Initial association type for add mode
 * @param {import('vue').Ref<Object>|Object} options.association - The association being edited
 * @param {import('vue').Ref<Object>|Object} options.currentItem - The current item (for add mode)
 * @param {import('vue').Ref<boolean>|boolean} options.isReversed - Whether direction is reversed
 * @returns {Object} - Composable return values
 */
export function useAssociationForm(options) {
  const {
    mode,
    initialType,
    association,
    currentItem,
    destinationItem,
    isReversed
  } = options;

  // Import and use filterStore to get current selected group
  const filterStore = useFilterStore();

  // Form state
  const formData = reactive({
    type: '',
    annotation: '',
    groupId: filterStore.selectedAssociationGroup === 'all' ? 'default' : filterStore.selectedAssociationGroup,
    // Exemplar-specific fields
    exemplarUrl: '',
    exemplarDescription: '',
    // Destination-specific fields (when creating manual external associations)
    destinationUri: '',
    destinationIdentifier: '',
    destinationTitle: '',
    destinationTargetType: 'CASE'
  });

  const customType = ref('');
  const exemplarUrlError = ref('');
  const destinationUriError = ref('');

  // Mode detection
  const isAddMode = computed(() => toValue(mode) === 'add');
  const isEditMode = computed(() => toValue(mode) === 'edit');

  // Disable type dropdown when adding an exemplar (no way to set destination for other types)
  const isTypeDropdownDisabled = computed(() => {
    return isAddMode.value && toValue(initialType) === 'exemplar';
  });

  // Custom type validation
  const isValidCustomType = computed(() => {
    if (formData.type !== 'other') return true;
    const value = customType.value.trim();
    return value.startsWith('ext:') && /^ext:[a-zA-Z0-9._-]+$/.test(value);
  });

  // Exemplar type detection
  const isExemplarType = computed(() => formData.type === 'exemplar');

  // Show group selector for all types if groups exist
  const showGroupSelector = computed(() => {
    return true;
  });

  // Modal title based on mode
  const modalTitle = computed(() => {
    if (isAddMode.value) {
      return isExemplarType.value ? 'Add Exemplar' : 'Add Association';
    }
    return 'Edit Association';
  });

  // Save button text based on mode
  const saveButtonText = computed(() => {
    if (isAddMode.value) {
      return isExemplarType.value ? 'Add Exemplar' : 'Create Association';
    }
    return 'Save Changes';
  });

  // Form validation
  const isFormValid = computed(() => {
    // Check custom type validity
    if (formData.type === 'other' && !isValidCustomType.value) {
      return false;
    }

    // Check exemplar URL validity
    if (isExemplarType.value) {
      if (!formData.exemplarUrl.trim()) {
        return false;
      }
      if (!validateUrl(formData.exemplarUrl)) {
        return false;
      }
      if (formData.exemplarUrl.length > 300) {
        return false;
      }
    }

    // Check custom destination validity if it's an add mode without a destination item
    if (isAddMode.value && !toValue(destinationItem) && !isExemplarType.value) {
      if (!formData.destinationUri.trim()) {
        return false;
      }
      if (!validateUrl(formData.destinationUri)) {
        return false;
      }
      if (formData.destinationUri.length > 300) {
        return false;
      }
    }

    return true;
  });

  /**
   * Validate a URL string
   * @param {string} url - The URL to validate
   * @returns {boolean} - Whether the URL is valid
   */
  function validateUrl(url) {
    try {
      new URL(url);
      return true;
    } catch {
      return false;
    }
  }

  /**
   * Load association data into the form (for edit mode)
   */
  function loadAssociationData() {
    // Reset form data
    formData.type = '';
    formData.annotation = '';
    formData.groupId = filterStore.selectedAssociationGroup === 'all' ? 'default' : filterStore.selectedAssociationGroup;
    formData.exemplarUrl = '';
    formData.exemplarDescription = '';
    customType.value = '';
    exemplarUrlError.value = '';

    if (isAddMode.value) {
      // Add mode: set initial type if provided
      if (toValue(initialType)) {
        formData.type = toValue(initialType);
      }
      return;
    }

    // Edit mode: load from existing association
    const assoc = toValue(association);
    if (!assoc) return;

    // Use associationType instead of type
    const assocType = assoc.associationType || assoc.type || '';

    // Determine if this is a known meaningful ext: type for the specific source/target pair
    const reversed = toValue(isReversed);
    const effectiveOrigin = reversed ? (assoc.destinationNodeURI || assoc.destination) : (assoc.originNodeURI || assoc.origin);
    const effectiveDest = reversed ? (assoc.originNodeURI || assoc.origin) : (assoc.destinationNodeURI || assoc.destination);

    // Calculate source and target kinds
    const sourceKind = getItemKind(effectiveOrigin);
    const targetKind = getItemKind(effectiveDest);

    const isExemplar = assocType === 'exemplar' || (effectiveDest && effectiveDest.targetType && effectiveDest.targetType !== 'CASE');

    const meaningfulTypes = isExemplar
      ? (MEANINGFUL_EXEMPLAR_ASSOCIATIONS[sourceKind] || [])
      : (MEANINGFUL_ASSOCIATIONS[`${sourceKind}→${targetKind}`] || []);

    const isKnownExtTypeForPair = meaningfulTypes.includes(assocType);

    if (assocType.startsWith('ext:') && !isKnownExtTypeForPair) {
      formData.type = 'other';
      customType.value = assocType;
    } else {
      formData.type = assocType;
    }

    // Use notes instead of annotation
    formData.annotation = assoc.notes || assoc.annotation || '';

    // Handle group ID from CFAssociationGroupingURI
    formData.groupId = assoc.CFAssociationGroupingURI?.identifier ||
      assoc.groupId ||
      'default';

    // Load exemplar-specific fields
    if (formData.type === 'exemplar') {
      // For exemplar, the destination is the URL
      formData.exemplarUrl = assoc.destinationNodeURI?.uri ||
        assoc.destination?.uri ||
        '';
      // Description is stored in notes for exemplars
      formData.exemplarDescription = assoc.notes || '';
    }
  }

  /**
   * Handle type change event
   */
  function onTypeChange() {
    // No longer auto-selecting default group for children/exemplars
    // We want to allow them to be grouped too.
  }

  /**
   * Validate exemplar URL and set error message
   * @returns {boolean} - Whether the URL is valid
   */
  function validateExemplarUrl() {
    if (!isExemplarType.value) {
      exemplarUrlError.value = '';
      return true;
    }

    if (!formData.exemplarUrl.trim()) {
      exemplarUrlError.value = 'URL is required';
      return false;
    }

    if (!validateUrl(formData.exemplarUrl)) {
      exemplarUrlError.value = 'Please enter a valid URL';
      return false;
    }

    if (formData.exemplarUrl.length > 300) {
      exemplarUrlError.value = 'URL must be 300 characters or less';
      return false;
    }
    exemplarUrlError.value = '';
    
    // Also validate destinationUri in generic case
    if (isAddMode.value && !toValue(destinationItem) && !isExemplarType.value) {
      if (!formData.destinationUri.trim()) {
        destinationUriError.value = 'Destination URI is required';
        return false;
      }
      if (!validateUrl(formData.destinationUri)) {
        destinationUriError.value = 'Please enter a valid URL';
        return false;
      }
      if (formData.destinationUri.length > 300) {
        destinationUriError.value = 'URL must be 300 characters or less';
        return false;
      }
      destinationUriError.value = '';
    }

    return true;
  }

  /**
   * Create association data for new associations (add mode)
   * @param {string} finalType - The final association type
   * @returns {Object} - The association data
   */
  function createAssociationData(finalType) {
    const item = toValue(currentItem);
    const destItem = toValue(destinationItem);
    const reversed = toValue(isReversed);

    const effectiveOrigin = reversed ? destItem : item;
    const effectiveDestination = reversed ? item : destItem;

    if (isExemplarType.value) {
      return {
        origin: { identifier: effectiveOrigin?.identifier },
        dest: { uri: formData.exemplarUrl },
        type: 'exemplar',
        annotation: formData.annotation,
        assocGroup: formData.groupId !== 'default' ? formData.groupId : null
      };
    }

    // Standard association
    const assocData = {
      origin: { identifier: effectiveOrigin?.identifier },
      type: finalType,
      annotation: formData.annotation,
      assocGroup: formData.groupId !== 'default' ? formData.groupId : null
    };

    if (effectiveDestination?.identifier) {
      assocData.dest = { 
        identifier: effectiveDestination.identifier,
        targetType: effectiveDestination.targetType || 'CASE'
      };
    } else if (isAddMode.value) {
      assocData.dest = { 
        uri: formData.destinationUri,
        targetType: formData.destinationTargetType || 'CASE'
      };
      if (formData.destinationIdentifier) {
        assocData.dest.identifier = formData.destinationIdentifier;
      }
    }

    // Origin targetType
    assocData.origin.targetType = effectiveOrigin?.targetType || 'CASE';

    return assocData;
  }

  /**
   * Update association data for existing associations (edit mode)
   * @param {string} finalType - The final association type
   * @returns {Object} - The updated association data
   */
  function updateAssociationData(finalType) {
    const assoc = toValue(association);

    // Keep the identifier from the original association
    const baseAssociation = {
      identifier: assoc.identifier,
      type: finalType,
      annotation: formData.annotation,
      assocGroup: formData.groupId !== 'default' ? formData.groupId : null
    };

    // Add exemplar-specific fields
    if (isExemplarType.value) {
      baseAssociation.annotation = formData.exemplarDescription;
    }

    return baseAssociation;
  }

  /**
   * Get the final type (handling custom types)
   * @returns {string} - The final association type
   */
  function getFinalType() {
    return formData.type === 'other' ? customType.value.trim() : formData.type;
  }

  /**
   * Reset the form to initial state
   */
  function resetForm() {
    formData.type = '';
    formData.annotation = '';
    formData.groupId = filterStore.selectedAssociationGroup === 'all' ? 'default' : filterStore.selectedAssociationGroup;
    formData.exemplarUrl = '';
    formData.exemplarDescription = '';
    customType.value = '';
    exemplarUrlError.value = '';
  }

  return {
    // Form state
    formData,
    customType,
    exemplarUrlError,
    destinationUriError,

    // Mode
    isAddMode,
    isEditMode,

    // Computed
    isTypeDropdownDisabled,
    isValidCustomType,
    isExemplarType,
    showGroupSelector,
    modalTitle,
    saveButtonText,
    isFormValid,

    // Association types
    associationTypes: ASSOCIATION_TYPES,

    // Methods
    validateUrl,
    loadAssociationData,
    onTypeChange,
    validateExemplarUrl,
    createAssociationData,
    updateAssociationData,
    getFinalType,
    resetForm
  };
}

export default useAssociationForm;
